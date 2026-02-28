<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\TaxRequest;
use App\Http\Resources\TaxResource;
use App\Models\Tax;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

final class TaxController extends Controller
{
    /**
     * Provide active taxes directly for form selects.
     */
    public function formOptions(): JsonResponse
    {
        $taxes = Tax::active()->orderBy('name')->get();

        return response()->json([
            'taxes' => TaxResource::collection($taxes),
        ]);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $validated = $request->validate([
            'search' => 'nullable|string|max:255',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $query = Tax::orderBy('is_default', 'desc')
            ->orderBy('is_active', 'desc')
            ->orderBy('name');

        // Text search
        if (! empty($validated['search'])) {
            $search = $validated['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('tax_type', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Statistical meta counters
        $totalCount = (clone $query)->count();
        $activeCount = (clone $query)->where('is_active', true)->count();
        $igvCount = (clone $query)->where('tax_type', 'IGV')->count();

        $perPage = (int) ($validated['per_page'] ?? 25);
        $taxes = $query->paginate($perPage);

        return TaxResource::collection($taxes)->additional([
            'meta' => [
                'stats' => [
                    'total' => $totalCount,
                    'active' => $activeCount,
                    'igv' => $igvCount,
                ],
            ],
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(TaxRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $tax = DB::transaction(function () use ($validated) {
            // Un-check existing defaults of the identical tax_type locally if we are setting a new 'is_default'
            if ($validated['is_default'] ?? false) {
                Tax::where('tax_type', $validated['tax_type'])
                    ->where('is_default', true)
                    ->update(['is_default' => false]);
            }

            return Tax::create($validated);
        });

        return response()->json([
            'message' => 'Impuesto creado exitosamente.',
            'data' => new TaxResource($tax),
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Tax $tax): TaxResource
    {
        return new TaxResource($tax);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(TaxRequest $request, Tax $tax): JsonResponse
    {
        $validated = $request->validated();

        DB::transaction(function () use ($validated, $tax) {
            // Safely toggle flags among sibling taxes if patching the current one manually as `default`
            if (isset($validated['is_default']) && $validated['is_default'] === true) {
                $taxType = $validated['tax_type'] ?? $tax->tax_type;

                Tax::where('tax_type', $taxType)
                    ->where('is_default', true)
                    ->where('id', '!=', $tax->id)
                    ->update(['is_default' => false]);
            }

            $tax->update($validated);
        });

        return response()->json([
            'message' => 'Impuesto actualizado exitosamente.',
            'data' => new TaxResource($tax->refresh()),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Tax $tax): JsonResponse
    {
        // Intercept destruction for currently bound Taxes linked inside Purchases/Sales
        if ($tax->productables()->count() > 0) {
            return response()->json([
                'message' => 'No se puede eliminar el impuesto porque está siendo actualmente usado en productos documentados.',
            ], 422);
        }

        $tax->delete();

        return response()->json(null, 204);
    }

    /**
     * Toggle tax `is_active` status flag
     */
    public function toggleStatus(Tax $tax): JsonResponse
    {
        $tax->update([
            'is_active' => ! $tax->is_active,
        ]);

        return $this->success(new TaxResource($tax->fresh()));
    }
}
