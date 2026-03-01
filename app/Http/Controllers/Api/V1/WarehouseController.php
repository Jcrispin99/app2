<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\WarehouseRequest;
use App\Http\Resources\WarehouseResource;
use App\Models\Warehouse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class WarehouseController extends Controller
{
    /**
     * Listar Almacenes
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->input('per_page', 25);
        $search = $request->input('search');

        $query = Warehouse::with(['company']);

        if ($search) {
            $query->where('name', 'like', "%{$search}%")
                ->orWhere('location', 'like', "%{$search}%");
        }

        $warehouses = $query->latest()->paginate((int) $perPage)->appends($request->query());

        return $this->success(
            WarehouseResource::collection($warehouses)->response()->getData(true)
        );
    }

    /**
     * Opciones de Formulario
     */
    public function formOptions(): JsonResponse
    {
        $companies = \App\Models\Company::query()->orderBy('business_name')->get();

        return $this->success([
            'companies' => \App\Http\Resources\CompanyResource::collection($companies),
        ], 'Form options retrieved successfully');
    }

    /**
     * Crear Almacén
     */
    public function store(WarehouseRequest $request): JsonResponse
    {
        $warehouse = Warehouse::create($request->validated());
        $warehouse->load('company');

        return $this->created(new WarehouseResource($warehouse));
    }

    /**
     * Ver Almacén
     */
    public function show(Warehouse $warehouse): JsonResponse
    {
        $warehouse->load('company');

        return $this->success(new WarehouseResource($warehouse));
    }

    /**
     * Actualizar Almacén
     */
    public function update(WarehouseRequest $request, Warehouse $warehouse): JsonResponse
    {
        $warehouse->update($request->validated());
        $warehouse->load('company');

        return $this->success(new WarehouseResource($warehouse));
    }

    /**
     * Eliminar Almacén
     */
    public function destroy(Warehouse $warehouse): JsonResponse
    {
        $warehouse->delete();

        return $this->noContent();
    }
    /**
     * Cambiar Estado
     */
    public function toggleStatus(Warehouse $warehouse): JsonResponse
    {
        $warehouse->update([
            'is_active' => ! $warehouse->is_active,
        ]);

        return $this->success(new WarehouseResource($warehouse->fresh()->load('company')));
    }
}
