<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\PosSessionRequest;
use App\Http\Resources\PosSessionResource;
use App\Models\PosSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class PosSessionController extends Controller
{
    /**
     * Listar Turnos de Caja
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $validated = $request->validate([
            'status' => 'nullable|string|in:opened,closed',
            'user_id' => 'nullable|integer|exists:users,id',
            'pos_config_id' => 'nullable|integer|exists:pos_configs,id',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $query = PosSession::query()
            ->with(['posConfig', 'user'])
            ->withCount('payments')
            ->orderBy('id', 'desc');

        if (! empty($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        if (! empty($validated['user_id'])) {
            $query->where('user_id', $validated['user_id']);
        }

        if (! empty($validated['pos_config_id'])) {
            $query->where('pos_config_id', $validated['pos_config_id']);
        }

        $perPage = (int) ($validated['per_page'] ?? 25);
        $paginator = $query->paginate($perPage);

        return PosSessionResource::collection($paginator);
    }

    /**
     * Abrir Turno de Caja
     */
    public function open(PosSessionRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $userId = auth()->id() ?? 1; // Fallback for tinker testing if needed
        $posConfigId = $validated['pos_config_id'];

        // 1. Check if this specific Terminal is already opened by someone else
        $isConfigInUse = PosSession::where('pos_config_id', $posConfigId)
            ->where('status', PosSession::STATUS_OPENED)
            ->exists();

        if ($isConfigInUse) {
            return response()->json([
                'message' => 'Esta Caja Registradora ya se encuentra abierta en otro turno. Debe ser cerrada primero.',
            ], 422);
        }

        // 2. Check if the current Cashier already has an active session ANYWHERE else
        $isUserBusy = PosSession::where('user_id', $userId)
            ->where('status', PosSession::STATUS_OPENED)
            ->exists();

        if ($isUserBusy) {
            return response()->json([
                'message' => 'No puedes abrir un nuevo turno porque ya tienes una sesión de caja activa. Ciérrala primero.',
            ], 422);
        }

        // Proceed to open
        $session = PosSession::create([
            'user_id' => $userId,
            'pos_config_id' => $posConfigId,
            'opening_balance' => $validated['opening_balance'],
            'opening_note' => $validated['opening_note'] ?? null,
            'opened_at' => now(),
            'status' => PosSession::STATUS_OPENED,
        ]);

        return response()->json([
            'message' => 'Turno de caja abierto exitosamente.',
            'data' => new PosSessionResource($session->load('posConfig')),
        ], 201);
    }

    /**
     * Ver Turno de Caja
     */
    public function show(PosSession $posSession): PosSessionResource
    {
        return new PosSessionResource($posSession->load(['posConfig', 'payments.paymentMethod']));
    }

    /**
     * Cerrar Turno de Caja
     */
    public function close(PosSessionRequest $request, PosSession $posSession): JsonResponse
    {
        if ($posSession->isClosed()) {
            return response()->json([
                'message' => 'Esta sesión ya se encuentra cerrada.',
            ], 422);
        }

        // In a strict ERP, only the cashiers or admins can close their own boxes
        if ($posSession->user_id !== (auth()->id() ?? 1) && ! auth()->user()?->hasRole('admin')) {
            return response()->json([
                'message' => 'Solo el titular o un administrador puede cerrar esta caja.',
            ], 403);
        }

        $validated = $request->validated();

        $posSession->update([
            'closing_balance' => $validated['closing_balance'],
            'closing_note' => $validated['closing_note'] ?? null,
            'closed_at' => now(),
            'status' => PosSession::STATUS_CLOSED,
        ]);

        return response()->json([
            'message' => 'Turno cerrado exitosamente.',
            'data' => new PosSessionResource($posSession->refresh()->load('posConfig')),
        ]);
    }
}
