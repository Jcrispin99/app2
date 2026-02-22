<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PosSessionPaymentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'pos_session_id' => $this->pos_session_id,
            'sale_id' => $this->sale_id,
            'payment_method_id' => $this->payment_method_id,
            
            'amount' => (float) $this->amount,
            'reference_sale_id' => $this->reference_sale_id,
            
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            
            // Relations
            'payment_method' => new PaymentMethodResource($this->whenLoaded('paymentMethod')),
        ];
    }
}
