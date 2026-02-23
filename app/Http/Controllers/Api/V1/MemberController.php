<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\MemberRequest;
use App\Http\Resources\MemberResource;
use App\Models\Company;
use App\Models\Partner;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Spatie\Activitylog\Models\Activity;

class MemberController extends Controller
{
    /**
     * Display a listing of members.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $validated = $request->validate([
            'search' => 'nullable|string|max:100',
            'status' => 'nullable|array',
            'status.*' => 'in:active,inactive,suspended,blacklisted',
            'portal' => 'nullable|array',
            'portal.*' => 'in:with_portal,without_portal',
            'per_page' => 'nullable|integer|min:5|max:200',
        ]);

        $query = Partner::query()
            ->members()
            ->with(['company', 'user'])
            ->latest('id');

        if (! empty($validated['search'])) {
            $search = trim($validated['search']);
            $query->where(function ($q) use ($search) {
                // Now using strictly DB tracked columns
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('document_number', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if (! empty($validated['status'])) {
            $query->whereIn('status', $validated['status']);
        }

        if (! empty($validated['portal'])) {
            $portal = $validated['portal'];
            $hasWith = in_array('with_portal', $portal, true);
            $hasWithout = in_array('without_portal', $portal, true);

            if ($hasWith && ! $hasWithout) {
                $query->whereNotNull('user_id');
            } elseif ($hasWithout && ! $hasWith) {
                $query->whereNull('user_id');
            }
        }

        // Meta Statistics extraction
        $statsQuery = clone $query;
        $activeTotal = (clone $statsQuery)->where('status', 'active')->count();
        $suspendedTotal = (clone $statsQuery)->where('status', 'suspended')->count();
        $portalTotal = (clone $statsQuery)->whereNotNull('user_id')->count();

        $perPage = (int) ($validated['per_page'] ?? 25);
        $paginator = $query->paginate($perPage);

        return MemberResource::collection($paginator)->additional([
            'meta' => [
                'active_count' => $activeTotal,
                'suspended_count' => $suspendedTotal,
                'with_portal_count' => $portalTotal,
            ],
        ]);
    }

    /**
     * Store and register a new member, applying upsert if Document exists for another Partner Type.
     */
    public function store(MemberRequest $request): JsonResponse
    {
        $validated = $request->validated();

        // Upsert Logic: Prevent duplicate strict creation if a Customer/Supplier already exists with this ID
        $partner = Partner::where('document_type', $validated['document_type'])
            ->where('document_number', $validated['document_number'])
            ->first();

        if ($partner) {
            $partner->fill($validated);
            $partner->is_member = true;
            $partner->save();
            $message = 'Miembro actualizado exitosamente (Partner existente)';
            $code = 200;
        } else {
            $validated['is_member'] = true;
            $validated['status'] = 'active';
            $partner = Partner::create($validated);
            $message = 'Miembro registrado exitosamente';
            $code = 201;
        }

        return response()->json([
            'message' => $message,
            'data' => new MemberResource($partner->load('company')),
        ], $code);
    }

    /**
     * Display the specified member.
     */
    public function show(Partner $member): JsonResponse
    {
        if (! $member->isMember()) {
            return response()->json(['message' => 'Profile is not a Member'], 404);
        }

        $member->load(['company', 'user', 'subscriptions.plan']);

        $activities = Activity::forSubject($member)
            ->with('causer')
            ->latest()
            ->take(20)
            ->get();

        return response()->json([
            'data' => new MemberResource($member),
            'meta' => [
                'activities' => $activities,
            ],
        ]);
    }

    /**
     * Update the specified member in storage.
     */
    public function update(MemberRequest $request, Partner $member): JsonResponse
    {
        if (! $member->isMember()) {
            return response()->json(['message' => 'Profile is not a Member'], 404);
        }

        $validated = $request->validated();
        $member->update($validated);

        return response()->json([
            'message' => 'Miembro actualizado exitosamente',
            'data' => new MemberResource($member->refresh()->load('company')),
        ]);
    }

    /**
     * Remove the specified member from storage.
     */
    public function destroy(Partner $member): JsonResponse
    {
        if (! $member->isMember()) {
            return response()->json(['message' => 'Profile is not a Member'], 404);
        }

        $member->delete();

        return response()->json(null, 204);
    }

    /**
     * Activate portal access for member enabling login functionalities.
     */
    public function activatePortal(Request $request, Partner $member): JsonResponse
    {
        if (! $member->isMember()) {
            return response()->json(['message' => 'Profile is not a Member'], 404);
        }

        if ($member->hasPortalAccess()) {
            throw ValidationException::withMessages([
                'error' => 'Este miembro ya tiene acceso al portal habilitado.',
            ]);
        }

        $validated = $request->validate([
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::create([
            'name' => $member->full_name,
            'email' => $member->email,
            'password' => Hash::make($validated['password']),
            'user_type' => 'customer',
            'company_id' => $member->company_id,
        ]);

        $member->update(['user_id' => $user->id]);

        return response()->json([
            'message' => 'Acceso al portal activado exitosamente.',
            'data' => new MemberResource($member->refresh()->load('user')),
        ]);
    }

    /**
     * Provide Form Options to render frontend creations.
     */
    public function formOptions(): JsonResponse
    {
        $companies = Company::orderBy('trade_name')
            ->select('id', 'trade_name')
            ->get();

        return response()->json([
            'data' => [
                'companies' => $companies,
            ],
        ]);
    }
}
