<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\ProductProduct;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ProductProduct
 */
final class ProductProductResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product_template_id' => $this->product_template_id,
            'sku' => $this->sku,
            'barcode' => $this->barcode,
            'price' => $this->price,
            'cost_price' => $this->cost_price,
            'is_principal' => $this->is_principal,
            'stock' => $this->stock,
            'attributes' => AttributeValueResource::collection($this->whenLoaded('attributeValues')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
