<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\PosConfigRequest;
use App\Http\Resources\PosConfigResource;
use App\Models\PosConfig;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

final class PosConfigController extends Controller
{
    /**
     * Listar Terminales POS
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $validated = $request->validate([
            'q' => 'nullable|string|max:255',
            'status' => 'nullable|string|in:active,inactive',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $query = PosConfig::query()
            ->with(['warehouse', 'tax']) // Shallow relations for grid
            ->withExists(['sessions as has_active_session' => function ($q) {
                $q->whereIn('status', ['opened', 'opening_control', 'closing_control']);
            }])
            ->orderBy('id', 'desc');

        if (! empty($validated['q'])) {
            $query->where('name', 'like', "%{$validated['q']}%");
        }

        if (! empty($validated['status'])) {
            $query->where('is_active', $validated['status'] === 'active');
        }

        $perPage = (int) ($validated['per_page'] ?? 25);
        $paginator = $query->paginate($perPage);

        return PosConfigResource::collection($paginator);
    }

    /**
     * Crear Terminal POS
     */
    public function store(PosConfigRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $journalsData = $validated['journals'] ?? [];

        // Remove journals from payload before creating parent
        unset($validated['journals']);

        // Default to current tenant's company if none specified directly
        if (! isset($validated['company_id'])) {
            $validated['company_id'] = auth()->user()?->company_id ?? 1;
        }

        // Translate the simple array input array into what Eloquent sync() needs
        $syncData = [];
        foreach ($journalsData as $journalEntry) {
            $journalId = $journalEntry['journal_id'];
            $syncData[$journalId] = [
                'document_type' => $journalEntry['document_type'],
                'is_default' => $journalEntry['is_default'] ?? false,
            ];
        }

        $posConfig = DB::transaction(function () use ($validated, $syncData) {
            $config = PosConfig::create($validated);

            // Sync Journals logic
            $config->journals()->sync($syncData);

            return $config->loadMissing(['warehouse', 'defaultCustomer', 'tax', 'journals']);
        });

        return response()->json([
            'message' => 'Terminal POS creada exitosamente.',
            'data' => new PosConfigResource($posConfig),
        ], 201);
    }

    /**
     * Ver Terminal POS
     */
    public function show(PosConfig $posConfig): PosConfigResource
    {
        return new PosConfigResource($posConfig->loadMissing(['warehouse', 'defaultCustomer', 'tax', 'journals']));
    }

    /**
     * Actualizar Terminal POS
     */
    public function update(PosConfigRequest $request, PosConfig $posConfig): JsonResponse
    {
        $validated = $request->validated();
        $journalsData = $validated['journals'] ?? null;

        if (isset($validated['journals'])) {
            unset($validated['journals']);
        }

        $posConfig = DB::transaction(function () use ($posConfig, $validated, $journalsData) {
            $posConfig->update($validated);

            if ($journalsData !== null) {
                // Translate the simple array input array into what Eloquent sync() needs
                $syncData = [];
                foreach ($journalsData as $journalEntry) {
                    $journalId = $journalEntry['journal_id'];
                    $syncData[$journalId] = [
                        'document_type' => $journalEntry['document_type'],
                        'is_default' => $journalEntry['is_default'] ?? false,
                    ];
                }

                // Wipe and recreate relationships with fresh pivot properties
                $posConfig->journals()->sync($syncData);
            }

            return $posConfig->refresh()->loadMissing(['warehouse', 'defaultCustomer', 'tax', 'journals']);
        });

        return response()->json([
            'message' => 'Configuración actualizada exitosamente.',
            'data' => new PosConfigResource($posConfig),
        ]);
    }

    /**
     * Eliminar Terminal POS
     */
    public function destroy(PosConfig $posConfig): JsonResponse
    {
        // Need to detach any pivot relations
        DB::transaction(function () use ($posConfig) {
            $posConfig->journals()->detach();
            $posConfig->delete();
        });

        return response()->json([
            'message' => 'Terminal POS eliminada exitosamente.',
        ]);
    }

    /**
     * Cambiar Estado
     */
    public function toggleStatus(PosConfig $posConfig): JsonResponse
    {
        $posConfig->update([
            'is_active' => ! $posConfig->is_active,
        ]);

        return $this->success(new PosConfigResource($posConfig->fresh()->loadMissing(['warehouse', 'tax'])));
    }
}
