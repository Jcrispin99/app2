<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\UnitOfMeasureRequest;
use App\Http\Resources\UnitOfMeasureResource;
use App\Models\UnitOfMeasure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class UnitOfMeasureController extends Controller
{
    /**
     * Opciones de Formulario
     */
    public function formOptions(): JsonResponse
    {
        // Provide available grouped families and independent active units
        $families = UnitOfMeasure::active()
            ->whereNotNull('family')
            ->distinct()
            ->pluck('family');

        $activeUnits = UnitOfMeasure::active()->orderBy('family')->orderBy('name')->get();

        return response()->json([
            'families' => $families,
            'unit_of_measures' => UnitOfMeasureResource::collection($activeUnits),
        ]);
    }

    /**
     * Listar Unidades de Medida
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $validated = $request->validate([
            'q' => 'nullable|string|max:255',
            'family' => 'nullable|string|max:255',
            'base_unit_id' => 'nullable|integer|exists:unit_of_measures,id',
            'only_active' => 'nullable|boolean',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $onlyActive = filter_var($validated['only_active'] ?? false, FILTER_VALIDATE_BOOLEAN);

        $query = UnitOfMeasure::query()
            ->with('baseUnit')
            ->orderBy('family')
            ->orderBy('name');

        // Búsqueda Textual
        if (! empty($validated['q'])) {
            $q = $validated['q'];
            $query->where(function ($builder) use ($q) {
                $builder->where('name', 'like', '%'.$q.'%')
                    ->orWhere('symbol', 'like', '%'.$q.'%');
            });
        }

        if (! empty($validated['family'])) {
            $query->where('family', $validated['family']);
        }

        if (array_key_exists('base_unit_id', $validated)) {
            $query->where('base_unit_id', $validated['base_unit_id']);
        }

        if ($onlyActive) {
            $query->active();
        }

        // Gather statistical counts pre-pagination
        $countsQuery = clone $query;
        $activeCount = (clone $countsQuery)->active()->count();
        $baseCount = (clone $countsQuery)->whereNull('base_unit_id')->count();
        $familyCount = (clone $countsQuery)->whereNotNull('family')->distinct('family')->count('family');

        $perPage = (int) ($validated['per_page'] ?? 25);
        $paginator = $query->paginate($perPage);

        return UnitOfMeasureResource::collection($paginator)->additional([
            'meta' => [
                'stats' => [
                    'active_count' => $activeCount,
                    'base_count' => $baseCount,
                    'family_count' => $familyCount,
                ],
            ],
        ]);
    }

    /**
     * Crear Unidad de Medida
     */
    public function store(UnitOfMeasureRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $isBase = empty($validated['base_unit_id']);
        $factor = $isBase ? 1 : (float) ($validated['factor'] ?? 1);

        $unitOfMeasure = UnitOfMeasure::create([
            'name' => $validated['name'],
            'symbol' => $validated['symbol'] ?? null,
            'family' => $validated['family'],
            'base_unit_id' => $isBase ? null : (int) $validated['base_unit_id'],
            'factor' => $factor,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        $unitOfMeasure->load('baseUnit');

        return response()->json([
            'message' => 'Unidad de medida creada exitosamente.',
            'data' => new UnitOfMeasureResource($unitOfMeasure),
        ], 201);
    }

    /**
     * Ver Unidad de Medida
     */
    public function show(UnitOfMeasure $unitOfMeasure): UnitOfMeasureResource
    {
        $unitOfMeasure->load(['baseUnit', 'derivedUnits']);

        return new UnitOfMeasureResource($unitOfMeasure);
    }

    /**
     * Actualizar Unidad de Medida
     */
    public function update(UnitOfMeasureRequest $request, UnitOfMeasure $unitOfMeasure): JsonResponse
    {
        $validated = $request->validated();

        // Ensure values strictly apply based on payload presence / HTTP Verb (PATCH support)
        if (array_key_exists('base_unit_id', $validated)) {
            $isBase = empty($validated['base_unit_id']);
            $validated['base_unit_id'] = $isBase ? null : (int) $validated['base_unit_id'];

            // Factor assignment follows Base dynamic conditionally if patched
            if (array_key_exists('factor', $validated)) {
                $validated['factor'] = $isBase ? 1 : (float) ($validated['factor'] ?? $unitOfMeasure->factor);
            } elseif ($isBase) {
                $validated['factor'] = 1;
            }
        } elseif (array_key_exists('factor', $validated)) {
            // We are merely altering the standalone factor without altering the parent tree
            $isBase = empty($unitOfMeasure->base_unit_id);
            $validated['factor'] = $isBase ? 1 : (float) ($validated['factor'] ?? $unitOfMeasure->factor);
        }

        $unitOfMeasure->update($validated);
        $unitOfMeasure->refresh()->load(['baseUnit']);

        return response()->json([
            'message' => 'Unidad de medida actualizada exitosamente.',
            'data' => new UnitOfMeasureResource($unitOfMeasure),
        ]);
    }

    /**
     * Eliminar Unidad de Medida
     */
    public function destroy(UnitOfMeasure $unitOfMeasure): JsonResponse
    {
        if ($unitOfMeasure->derivedUnits()->exists()) {
            return response()->json([
                'message' => 'No se puede eliminar una unidad que se utiliza actualmente como unidad base para sub-unidades derivadas.',
            ], 422);
        }

        if ($unitOfMeasure->productables()->exists()) {
            return response()->json([
                'message' => 'No se puede eliminar una unidad atada al historial de ventas o compras.',
            ], 422);
        }

        $unitOfMeasure->delete();

        return response()->json(null, 204);
    }

    /**
     * Cambiar Estado
     */
    public function toggleStatus(UnitOfMeasure $unitOfMeasure): JsonResponse
    {
        $unitOfMeasure->update([
            'is_active' => ! $unitOfMeasure->is_active,
        ]);

        return $this->success(new UnitOfMeasureResource($unitOfMeasure->fresh()->load('baseUnit')));
    }
}
