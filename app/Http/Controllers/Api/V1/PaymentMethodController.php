<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\PaymentMethodRequest;
use App\Http\Resources\PaymentMethodResource;
use App\Models\PaymentMethod;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class PaymentMethodController extends Controller
{
    /**
     * Listar Métodos de Pago
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $validated = $request->validate([
            'q' => 'nullable|string|max:255',
            'status' => 'nullable|string|in:active,inactive',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $query = PaymentMethod::query()->orderBy('name', 'asc');

        if (! empty($validated['q'])) {
            $query->where('name', 'like', "%{$validated['q']}%");
        }

        if (! empty($validated['status'])) {
            $query->where('is_active', $validated['status'] === 'active');
        }

        $perPage = (int) ($validated['per_page'] ?? 50);
        $paginator = $query->paginate($perPage);

        return PaymentMethodResource::collection($paginator);
    }

    /**
     * Crear Método de Pago
     */
    public function store(PaymentMethodRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $paymentMethod = PaymentMethod::create($validated);

        return response()->json([
            'message' => 'Método de pago creado exitosamente.',
            'data' => new PaymentMethodResource($paymentMethod),
        ], 201);
    }

    /**
     * Ver Método de Pago
     */
    public function show(PaymentMethod $paymentMethod): PaymentMethodResource
    {
        return new PaymentMethodResource($paymentMethod);
    }

    /**
     * Actualizar Método de Pago
     */
    public function update(PaymentMethodRequest $request, PaymentMethod $paymentMethod): JsonResponse
    {
        $validated = $request->validated();

        $paymentMethod->update($validated);

        return response()->json([
            'message' => 'Método de pago actualizado exitosamente.',
            'data' => new PaymentMethodResource($paymentMethod),
        ]);
    }

    /**
     * Eliminar Método de Pago
     */
    public function destroy(PaymentMethod $paymentMethod): JsonResponse
    {
        // Add basic protection against active deletion if linked (e.g., to pos_session_payments)
        // Since pos_session_payments isn't fully scoped yet, returning basic delete for now.
        $paymentMethod->delete();

        return response()->json([
            'message' => 'Método de pago eliminado exitosamente.',
        ]);
    }
    /**
     * Cambiar Estado
     */
    public function toggleStatus(PaymentMethod $paymentMethod): JsonResponse
    {
        $paymentMethod->update([
            'is_active' => ! $paymentMethod->is_active,
        ]);

        return $this->success(new PaymentMethodResource($paymentMethod->fresh()));
    }
}
