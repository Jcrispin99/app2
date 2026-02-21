<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Purchase;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Purchase
 */
final class PurchaseResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'serie' => $this->serie,
            'correlative' => $this->correlative,
            'sequence_code' => "{$this->serie}-{$this->correlative}",
            'date' => $this->date?->format('Y-m-d'),
            'vendor_bill_number' => $this->vendor_bill_number,
            'vendor_bill_date' => $this->vendor_bill_date?->format('Y-m-d'),
            'status' => $this->status,
            'payment_status' => $this->payment_status,
            'total' => (float) $this->total,
            'observation' => $this->observation,

            'company_id' => $this->company_id,
            'partner_id' => $this->partner_id,
            'warehouse_id' => $this->warehouse_id,
            'journal_id' => $this->journal_id,

            // Relaciones
            'partner' => new SupplierResource($this->whenLoaded('partner')),
            'warehouse' => new WarehouseResource($this->whenLoaded('warehouse')),
            'company' => new CompanyResource($this->whenLoaded('company')),
            'lines' => ProductableResource::collection($this->whenLoaded('productables')),

            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
