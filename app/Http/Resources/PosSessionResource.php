<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PosSessionResource extends JsonResource
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
            'pos_config_id' => $this->pos_config_id,
            'user_id' => $this->user_id,

            // Financial Status
            'status' => $this->status,
            'opening_balance' => (float) $this->opening_balance,
            'closing_balance' => $this->closing_balance !== null ? (float) $this->closing_balance : null,
            'opening_note' => $this->opening_note,
            'closing_note' => $this->closing_note,

            // Time Tracking
            'opened_at' => $this->opened_at,
            'closed_at' => $this->closed_at,

            // Auto Calculated Totals
            'total_payments' => $this->whenCounted('payments'),

            // Standard Dates
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            // Relations
            'config' => new PosConfigResource($this->whenLoaded('posConfig')),
            'payments' => PosSessionPaymentResource::collection($this->whenLoaded('payments')),
            // 'user' could be added here if there was a base UserResource
        ];
    }
}
