<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\MembershipSubscriptionRequest;
use App\Http\Resources\MembershipSubscriptionResource;
use App\Models\MembershipPlan;
use App\Models\MembershipSubscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Auth;

final class MembershipSubscriptionController extends Controller
{
    /**
     * Listar Suscripciones
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $validated = $request->validate([
            'q' => 'nullable|string|max:255',
            'status' => 'nullable|string|in:active,frozen,expired',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $query = MembershipSubscription::query()
            ->with(['plan', 'partner', 'company'])
            ->orderBy('id', 'desc');

        if (! empty($validated['q'])) {
            $q = $validated['q'];
            $query->whereHas('partner', function ($builder) use ($q) {
                $builder->where('name', 'like', "%{$q}%")
                    ->orWhere('document_number', 'like', "%{$q}%");
            });
        }

        if (! empty($validated['status'])) {
            $status = $validated['status'];
            if ($status === 'active') {
                $query->active();
            } elseif ($status === 'frozen') {
                $query->frozen();
            } elseif ($status === 'expired') {
                $query->expired();
            }
        }

        // Stats Map
        $statsObj = clone $query;
        $totalCount = (clone $statsObj)->count();
        $activeCount = (clone $statsObj)->active()->count();
        $frozenCount = (clone $statsObj)->frozen()->count();

        $perPage = (int) ($validated['per_page'] ?? 25);
        $paginator = $query->paginate($perPage);

        return MembershipSubscriptionResource::collection($paginator)->additional([
            'meta' => [
                'stats' => [
                    'total' => $totalCount,
                    'active' => $activeCount,
                    'frozen' => $frozenCount,
                ],
            ],
        ]);
    }

    /**
     * Crear Suscripción
     */
    public function store(MembershipSubscriptionRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $plan = MembershipPlan::findOrFail($validated['membership_plan_id']);

        // Compute temporal dates automatically
        $startDate = \Carbon\Carbon::parse($validated['start_date']);
        $originalEndDate = $startDate->copy()->addDays($plan->duration_days);

        $subscriptionData = array_merge($validated, [
            'original_end_date' => $originalEndDate->toDateString(),
            'end_date' => $originalEndDate->toDateString(),
            'remaining_freeze_days' => $plan->allows_freezing ? $plan->max_freeze_days : 0,
            'status' => 'active',
            'entries_used' => 0,
            'entries_this_month' => 0,
            'total_days_frozen' => 0,
            'sold_by' => Auth::id(),
        ]);

        $subscription = MembershipSubscription::create($subscriptionData);
        $subscription->load(['plan', 'partner', 'company']);

        return response()->json([
            'message' => 'Suscripción adquirida y registrada exitosamente.',
            'data' => new MembershipSubscriptionResource($subscription),
        ], 201);
    }

    /**
     * Ver Suscripción
     */
    public function show(MembershipSubscription $membershipSubscription): MembershipSubscriptionResource
    {
        // Check for freezes logic on the fly when retrieving Single Item
        $membershipSubscription->applyScheduledFreezeIfNeeded();
        $membershipSubscription->load(['plan', 'partner', 'company', 'freezes']);

        return new MembershipSubscriptionResource($membershipSubscription);
    }

    /**
     * Actualizar Suscripción
     */
    public function update(MembershipSubscriptionRequest $request, MembershipSubscription $membershipSubscription): JsonResponse
    {
        $validated = $request->validated();

        $plan = MembershipPlan::findOrFail($validated['membership_plan_id']);

        // Compute temporal dates automatically based on new plan or start date
        $startDate = \Carbon\Carbon::parse($validated['start_date']);
        $originalEndDate = $startDate->copy()->addDays($plan->duration_days);

        $subscriptionData = array_merge($validated, [
            'original_end_date' => $originalEndDate->toDateString(),
            // Keeping end_date logical sync. Freezes logic would normally require complex recalcs.
            'end_date' => $originalEndDate->clone()->addDays($membershipSubscription->total_days_frozen ?? 0)->toDateString(),
            'remaining_freeze_days' => $plan->allows_freezing ? $plan->max_freeze_days : 0,
        ]);

        $membershipSubscription->update($subscriptionData);
        $membershipSubscription->load(['plan', 'partner', 'company']);

        return response()->json([
            'message' => 'Suscripción actualizada exitosamente.',
            'data' => new MembershipSubscriptionResource($membershipSubscription),
        ]);
    }
}
