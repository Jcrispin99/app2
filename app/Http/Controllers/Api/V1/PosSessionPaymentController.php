<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\PosSessionPaymentResource;
use App\Models\PosSessionPayment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PosSessionPaymentController extends Controller
{
    /**
     * Display a listing of individual payments collected during shifts.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $validated = $request->validate([
            'pos_session_id' => 'nullable|integer|exists:pos_sessions,id',
            'payment_method_id' => 'nullable|integer|exists:payment_methods,id',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $query = PosSessionPayment::query()
            ->with(['paymentMethod', 'sale'])
            ->orderBy('id', 'desc');

        if (! empty($validated['pos_session_id'])) {
            $query->where('pos_session_id', $validated['pos_session_id']);
        }

        if (! empty($validated['payment_method_id'])) {
            $query->where('payment_method_id', $validated['payment_method_id']);
        }

        $perPage = (int) ($validated['per_page'] ?? 50);
        $paginator = $query->paginate($perPage);

        return PosSessionPaymentResource::collection($paginator);
    }
}
