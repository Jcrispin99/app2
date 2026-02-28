<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\CompanyRequest;
use App\Http\Resources\CompanyResource;
use App\Models\Company;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class CompanyController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->input('per_page', 15);
        $search = $request->input('search');

        $query = Company::query()->orderBy('id');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('business_name', 'like', "%{$search}%")
                    ->orWhere('trade_name', 'like', "%{$search}%")
                    ->orWhere('ruc', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($perPage === '-1' || $perPage === 'total') {
            $companies = $query->get();

            return $this->success(CompanyResource::collection($companies));
        }

        $companies = $query->paginate((int) $perPage);

        return $this->success(
            CompanyResource::collection($companies)->response()->getData(true)
        );
    }

    public function formOptions(): JsonResponse
    {
        $companies = Company::query()
            ->mainOffices()
            ->orderBy('business_name')
            ->get();

        return $this->success([
            'parent_companies' => CompanyResource::collection($companies),
        ], 'Form options retrieved successfully');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function store(CompanyRequest $request): JsonResponse
    {
        $company = Company::create($request->validated());

        return $this->created(new CompanyResource($company));
    }

    /**
     * Display the specified resource.
     */
    public function show(Company $company): JsonResponse
    {
        return $this->success(new CompanyResource($company));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function update(CompanyRequest $request, Company $company): JsonResponse
    {
        $company->update($request->validated());

        return $this->success(new CompanyResource($company));
    }

    public function destroy(Company $company): JsonResponse
    {
        $company->delete();

        return $this->noContent();
    }

    /**
     * Toggle the active status of the resource.
     */
    public function toggleStatus(Company $company): JsonResponse
    {
        $company->update([
            'active' => ! $company->active,
        ]);

        return $this->success(new CompanyResource($company));
    }
}
