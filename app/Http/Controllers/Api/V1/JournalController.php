<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\JournalRequest;
use App\Http\Resources\JournalResource;
use App\Models\Company;
use App\Models\Journal;
use App\Models\Sequence;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

final class JournalController extends Controller
{
    /**
     * Opciones de Formulario
     */
    public function formOptions(): JsonResponse
    {
        $companies = Company::select('id', 'name')->get();

        return response()->json([
            'companies' => $companies,
        ]);
    }

    /**
     * Listar Diarios
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $validated = $request->validate([
            'search' => 'nullable|string|max:255',
            'type' => 'nullable|string|in:sale,purchase,purchase-order,quote,cash',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $query = Journal::with(['sequence', 'company'])->orderBy('created_at', 'desc');

        // Filter by type if provided
        if (! empty($validated['type'])) {
            $query->where('type', $validated['type']);
        }

        // Apply string search
        if (! empty($validated['search'])) {
            $search = $validated['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        // Gather statistical counts before paginating
        $totalCount = (clone $query)->count();
        $fiscalCount = (clone $query)->where('is_fiscal', true)->count();
        $salesCount = (clone $query)->where('type', 'sale')->count();

        $perPage = (int) ($validated['per_page'] ?? 25);
        $journals = $query->paginate($perPage);

        return JournalResource::collection($journals)->additional([
            'meta' => [
                'stats' => [
                    'total' => $totalCount,
                    'fiscal' => $fiscalCount,
                    'sales' => $salesCount,
                ],
            ],
        ]);
    }

    /**
     * Crear Diario
     */
    public function store(JournalRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $journal = DB::transaction(function () use ($validated) {
            // 1. Transactionally enforce a new underlying Sequence entity
            $sequence = Sequence::create([
                'sequence_size' => $validated['sequence_size'] ?? 8,
                'step' => $validated['step'] ?? 1,
                'next_number' => $validated['next_number'] ?? 1,
            ]);

            // 2. Attach Journal onto newborn Sequence row ID
            return Journal::create([
                'name' => $validated['name'],
                'code' => $validated['code'],
                'type' => $validated['type'],
                'is_fiscal' => $validated['is_fiscal'] ?? false,
                'document_type_code' => $validated['document_type_code'] ?? null,
                'company_id' => $validated['company_id'],
                'sequence_id' => $sequence->id,
            ]);
        });

        $journal->load(['sequence', 'company']);

        return response()->json([
            'message' => 'Diario creado exitosamente.',
            'data' => new JournalResource($journal),
        ], 201);
    }

    /**
     * Ver Diario
     */
    public function show(Journal $journal): JournalResource
    {
        $journal->load(['sequence', 'company']);

        return new JournalResource($journal);
    }

    /**
     * Actualizar Diario
     */
    public function update(JournalRequest $request, Journal $journal): JsonResponse
    {
        $validated = $request->validated();

        DB::transaction(function () use ($validated, $journal) {
            // Base journal modifications
            $journal->update(array_merge(
                array_intersect_key($validated, array_flip([
                    'name', 'code', 'type', 'is_fiscal', 'document_type_code', 'company_id',
                ]))
            ));

            // Optional targeted logic to overwrite the active sequence counter manually
            if (isset($validated['next_number'])) {
                $journal->sequence->update([
                    'next_number' => $validated['next_number'],
                ]);
            }
        });

        $journal->refresh()->load(['sequence', 'company']);

        return response()->json([
            'message' => 'Diario actualizado exitosamente.',
            'data' => new JournalResource($journal),
        ]);
    }

    /**
     * Eliminar Diario
     */
    public function destroy(Journal $journal): JsonResponse
    {
        // Intercept destruction for journals with existing bound records in system
        if ($journal->purchases()->count() > 0) {
            return response()->json([
                'message' => 'No se puede eliminar el diario porque tiene documentos asociados (Compras).',
            ], 422);
        }

        DB::transaction(function () use ($journal) {
            $sequence = $journal->sequence;

            // Delete actual journal registry
            $journal->delete();

            // Perform Cascade garbage collection of targeted Sequences lacking Journal ties
            if ($sequence && $sequence->journals()->count() === 0) {
                $sequence->delete();
            }
        });

        return response()->json(null, 204);
    }

    /**
     * Reiniciar Secuencia
     */
    public function resetSequence(Journal $journal): JsonResponse
    {
        if ($journal->sequence) {
            $journal->sequence->update([
                'next_number' => 1,
            ]);

            return response()->json([
                'message' => 'Secuencia reiniciada a 1 exitosamente.',
            ]);
        }

        return response()->json([
            'message' => 'El diario actual no posee una secuencia configurable.',
        ], 422);
    }
}
