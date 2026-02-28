<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\SupplierRequest;
use App\Http\Resources\SupplierResource;
use App\Models\Partner;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\Activitylog\Models\Activity;

final class SupplierController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->input('per_page', 25);
        $search = $request->input('q') ?? $request->input('search');
        $status = $request->input('status');
        $companyId = $request->input('company_id');

        $query = Partner::query()
            ->with('company')
            ->suppliers()
            ->latest();

        if ($companyId) {
            $query->where('company_id', (int) $companyId);
        }

        if ($status) {
            $query->where('status', $status);
        }

        if ($search) {
            $query->where(function ($qBuilder) use ($search) {
                $qBuilder->where('name', 'like', "%{$search}%")
                    ->orWhere('document_number', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $paginator = $query->paginate((int) $perPage)->appends($request->query());
        $countsQuery = clone $query;

        return response()->json([
            'success' => true,
            'message' => 'Suppliers retrieved successfully.',
            'data' => SupplierResource::collection($paginator->items()),
            'links' => $paginator->linkCollection(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
                // Custom supplier counts based on status
                'active_count' => clone $countsQuery->where('status', 'active')->count(),
                'inactive_count' => clone $countsQuery->where('status', 'inactive')->count(),
                'suspended_count' => clone $countsQuery->where('status', 'suspended')->count(),
                'blacklisted_count' => clone $countsQuery->where('status', 'blacklisted')->count(),
            ],
        ]);
    }

    public function formOptions(): JsonResponse
    {
        $companies = \App\Models\Company::query()->orderBy('business_name')->get();

        return response()->json([
            'success' => true,
            'message' => 'Form options retrieved successfully',
            'data' => [
                'companies' => \App\Http\Resources\CompanyResource::collection($companies),
            ],
        ]);
    }

    public function show(Partner $supplier): JsonResponse
    {
        if (! $supplier->is_supplier) {
            return $this->error('Este registro no es un proveedor.', 404);
        }

        $supplier->load('company');

        $activities = Activity::forSubject($supplier)
            ->with('causer')
            ->latest()
            ->take(20)
            ->get();

        // We explicitly wrap in JsonResponse to maintain the 'meta' appending logic
        return response()->json([
            'success' => true,
            'message' => 'Resource retrieved successfully',
            'data' => new SupplierResource($supplier),
            'meta' => [
                'activities' => $activities,
            ],
        ]);
    }

    public function store(SupplierRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $providerCategory = $validated['provider_category'] ?? $validated['supplier_category'] ?? null;
        unset($validated['supplier_category'], $validated['provider_category']);

        // Check if partner already exists based on document info
        $partner = Partner::query()
            ->where('document_type', $validated['document_type'])
            ->where('document_number', $validated['document_number'])
            ->first();

        if ($partner) {
            $partner->fill($validated);
            $partner->is_supplier = true;
            $partner->provider_category = $providerCategory;
            $partner->save();

            return $this->success(new SupplierResource($partner->fresh()->load('company')));
        }

        $created = Partner::create(array_merge($validated, [
            'is_supplier' => true,
            'is_member' => false,
            'status' => 'active',
            'provider_category' => $providerCategory,
        ]));

        return $this->created(new SupplierResource($created->load('company')));
    }

    public function update(SupplierRequest $request, Partner $supplier): JsonResponse
    {
        if (! $supplier->is_supplier) {
            return $this->error('Este registro no es un proveedor.', 404);
        }

        $validated = $request->validated();

        $providerCategory = $validated['provider_category'] ?? $validated['supplier_category'] ?? null;
        unset($validated['supplier_category'], $validated['provider_category']);

        $supplier->update(array_merge($validated, [
            'provider_category' => $providerCategory,
        ]));

        return $this->success(new SupplierResource($supplier->fresh()->load('company')));
    }

    public function destroy(Partner $supplier): JsonResponse
    {
        if (! $supplier->is_supplier) {
            return $this->error('Este registro no es un proveedor.', 404);
        }

        $supplier->delete();

        return $this->noContent();
    }

    public function toggleStatus(Partner $supplier): JsonResponse
    {
        if (! $supplier->is_supplier) {
            return $this->error('Este registro no es un proveedor.', 404);
        }

        $supplier->update([
            'status' => $supplier->status === 'active' ? 'inactive' : 'active',
        ]);

        return $this->success(new SupplierResource($supplier->fresh()->load('company')));
    }
}
