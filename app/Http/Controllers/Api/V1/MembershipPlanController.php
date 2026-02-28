<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\MembershipPlanRequest;
use App\Http\Resources\MembershipPlanResource;
use App\Models\MembershipPlan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class MembershipPlanController extends Controller
{
    /**
     * Provide minimal options list (Select comboboxes).
     */
    public function formOptions(): JsonResponse
    {
        $plans = MembershipPlan::active()->select('id', 'name', 'price')->orderBy('name')->get();

        return response()->json([
            'membership_plans' => $plans,
        ]);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $validated = $request->validate([
            'q' => 'nullable|string|max:255',
            'status' => 'nullable|string|in:active,inactive',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $query = MembershipPlan::query()
            ->with(['company'])
            ->orderBy('name');

        if (! empty($validated['q'])) {
            $q = $validated['q'];
            $query->where(function ($builder) use ($q) {
                $builder->where('name', 'like', "%{$q}%")
                    ->orWhere('description', 'like', "%{$q}%");
            });
        }

        if (! empty($validated['status'])) {
            $query->where('is_active', $validated['status'] === 'active');
        }

        // Statistical meta counters
        $countsQuery = clone $query;
        $totalCount = (clone $countsQuery)->count();
        $activeCount = (clone $countsQuery)->active()->count();

        $perPage = (int) ($validated['per_page'] ?? 25);
        $paginator = $query->paginate($perPage);

        return MembershipPlanResource::collection($paginator)->additional([
            'meta' => [
                'stats' => [
                    'total_count' => $totalCount,
                    'active_count' => $activeCount,
                ],
            ],
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(MembershipPlanRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $plan = MembershipPlan::create($validated);
        $plan->load('company');

        return response()->json([
            'message' => 'Plan de suscripción creado exitosamente.',
            'data' => new MembershipPlanResource($plan),
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(MembershipPlan $membershipPlan): MembershipPlanResource
    {
        $membershipPlan->load('company');

        return new MembershipPlanResource($membershipPlan);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(MembershipPlanRequest $request, MembershipPlan $membershipPlan): JsonResponse
    {
        $validated = $request->validated();
        $membershipPlan->update($validated);

        return response()->json([
            'message' => 'Plan actualizado exitosamente.',
            'data' => new MembershipPlanResource($membershipPlan->refresh()->load('company')),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     * Note: Deletion is locked if bound to active historic Subscriptions.
     */
    public function destroy(MembershipPlan $membershipPlan): JsonResponse
    {
        if ($membershipPlan->subscriptions()->exists()) {
            return response()->json([
                'message' => 'No se puede eliminar un Plan de Suscripción que tiene miembros y operaciones facturadas históricas asignadas.',
            ], 422);
        }

        $membershipPlan->delete();

        return response()->json(null, 204);
    }

    /**
     * Utility method: Toggle `is_active` flag from data tables.
     */
    public function toggleStatus(MembershipPlan $membershipPlan): JsonResponse
    {
        $membershipPlan->update([
            'is_active' => ! $membershipPlan->is_active,
        ]);

        return $this->success(new MembershipPlanResource($membershipPlan->fresh()->load('company')));
    }
}
