<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\CustomerRequest;
use App\Http\Resources\CustomerResource;
use App\Models\Company;
use App\Models\Partner;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\Activitylog\Models\Activity;

final class CustomerController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->input('per_page', 25);
        $search = $request->input('q') ?? $request->input('search');
        $status = $request->input('status');
        $companyId = $request->input('company_id');
        $statuses = $request->input('statuses'); // Array of statuses
        $portalFilters = $request->input('portal_filters');

        $query = Partner::query()
            ->with(['company', 'user'])
            ->customers()
            ->latest();

        if ($companyId) {
            $query->where('company_id', (int) $companyId);
        }

        if ($status) {
            $query->where('status', $status);
        }

        if (! empty($statuses) && is_array($statuses)) {
            $query->whereIn('status', $statuses);
        }

        if (! empty($portalFilters) && is_array($portalFilters)) {
            $withPortal = in_array('with_portal', $portalFilters, true);
            $withoutPortal = in_array('without_portal', $portalFilters, true);

            if ($withPortal && ! $withoutPortal) {
                $query->whereNotNull('user_id');
            } elseif ($withoutPortal && ! $withPortal) {
                $query->whereNull('user_id');
            }
        }

        if ($search) {
            $query->where(function ($qBuilder) use ($search) {
                $qBuilder->where('name', 'like', "%{$search}%")
                    ->orWhere('document_number', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('mobile', 'like', "%{$search}%");
            });
        }

        $wantsPagination = $request->has('per_page') || $request->has('page');

        if ($wantsPagination) {
            $paginator = $query->paginate((int) $perPage)->appends($request->query());
            $countsQuery = clone $query;

            return response()->json([
                'success' => true,
                'message' => 'Customers retrieved successfully.',
                'data' => CustomerResource::collection($paginator->items()),
                'links' => $paginator->linkCollection(),
                'meta' => [
                    'current_page' => $paginator->currentPage(),
                    'last_page' => $paginator->lastPage(),
                    'per_page' => $paginator->perPage(),
                    'total' => $paginator->total(),
                    'from' => $paginator->firstItem(),
                    'to' => $paginator->lastItem(),
                    'active_count' => (clone $countsQuery)->where('status', 'active')->count(),
                    'inactive_count' => (clone $countsQuery)->where('status', 'inactive')->count(),
                    'suspended_count' => (clone $countsQuery)->where('status', 'suspended')->count(),
                    'with_portal_count' => (clone $countsQuery)->whereNotNull('user_id')->count(),
                    'without_portal_count' => (clone $countsQuery)->whereNull('user_id')->count(),
                ],
            ]);
        }

        $limit = $request->input('limit');
        if ($limit) {
            $query->limit((int) $limit);
        }

        return $this->success(CustomerResource::collection($query->get()));
    }

    public function formOptions(): JsonResponse
    {
        $companies = Company::query()
            ->orderBy('business_name')
            ->get();

        return $this->success([
            'companies' => \App\Http\Resources\CompanyResource::collection($companies),
        ], 'Form options retrieved successfully');
    }

    public function show(Partner $customer): JsonResponse
    {
        if (! $customer->is_customer) {
            return $this->error('Este registro no es un cliente.', 404);
        }

        $customer->load(['company', 'user']);

        $activities = Activity::forSubject($customer)
            ->with('causer')
            ->latest()
            ->take(20)
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Resource retrieved successfully',
            'data' => new CustomerResource($customer),
            'meta' => [
                'activities' => $activities,
            ],
        ]);
    }

    public function store(CustomerRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $partner = Partner::query()
            ->where('document_type', $validated['document_type'])
            ->where('document_number', $validated['document_number'])
            ->first();

        if ($partner) {
            $partner->fill($validated);
            $partner->is_customer = true;
            $partner->save();

            return $this->success(new CustomerResource($partner->fresh()->load(['company', 'user'])));
        }

        $created = Partner::create(array_merge($validated, [
            'is_customer' => true,
            'is_member' => false,
            'status' => 'active',
        ]));

        return $this->created(new CustomerResource($created->load(['company', 'user'])));
    }

    public function update(CustomerRequest $request, Partner $customer): JsonResponse
    {
        if (! $customer->is_customer) {
            return $this->error('Este registro no es un cliente.', 404);
        }

        $validated = $request->validated();

        $customer->update($validated);
        $customer->is_customer = true;
        $customer->save();

        return $this->success(new CustomerResource($customer->fresh()->load(['company', 'user'])));
    }

    public function destroy(Partner $customer): JsonResponse
    {
        if (! $customer->is_customer) {
            return $this->error('Este registro no es un cliente.', 404);
        }

        $customer->delete();

        return $this->noContent();
    }

    /**
     * Toggle the active status of the resource.
     */
    public function toggleStatus(Partner $customer): JsonResponse
    {
        if (! $customer->is_customer) {
            return $this->error('Este registro no es un cliente.', 404);
        }

        $customer->update([
            'status' => $customer->status === 'active' ? 'inactive' : 'active',
        ]);

        return $this->success(new CustomerResource($customer->fresh()->load(['company', 'user'])));
    }
}
