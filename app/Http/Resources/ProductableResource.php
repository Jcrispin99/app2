<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Productable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Productable
 */
final class ProductableResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'quantity' => (float) $this->quantity,
            'price' => (float) $this->price,
            'subtotal' => (float) $this->subtotal,
            'tax_rate' => (float) $this->tax_rate,
            'tax_amount' => (float) $this->tax_amount,
            'total' => (float) $this->total,

            // UOM Handling
            'quantity_uom' => $this->quantity_uom ? (float) $this->quantity_uom : null,
            'price_uom' => $this->price_uom ? (float) $this->price_uom : null,
            'uom_factor' => $this->uom_factor ? (float) $this->uom_factor : null,

            // Relaciones
            'product' => new ProductProductResource($this->whenLoaded('productProduct')),
            // 'tax' => new TaxResource($this->whenLoaded('tax')), // (Pendings: TaxResource if it existed)
            // 'uom' => new UnitOfMeasureResource($this->whenLoaded('uom')), // (Pendings: UOM resource if it existed)
        ];
    }
}
