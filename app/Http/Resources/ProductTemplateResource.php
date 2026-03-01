<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\ProductTemplate;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ProductTemplate
 */
final class ProductTemplateResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'price' => $this->price,
            'is_active' => $this->is_active,
            'is_pos_visible' => $this->is_pos_visible,
            'tracks_inventory' => $this->tracks_inventory,
            'is_service' => $this->is_service,

            // Appends
            'image' => $this->image,
            'sku' => $this->sku,
            'barcode' => $this->barcode,

            // Reaciones
            'category_id' => $this->category_id,
            'category' => new CategoryResource($this->whenLoaded('category')),
            // 'uom' => new UnitOfMeasureResource($this->whenLoaded('uom')), // (To implement later)
            'variants' => ProductProductResource::collection($this->whenLoaded('productProducts')),

            // MorphMany
            'images' => $this->whenLoaded('images', function () {
                return $this->images->map(function ($image) {
                    return [
                        'id' => $image->id,
                        'path' => \Illuminate\Support\Facades\Storage::url($image->path),
                    ];
                });
            }),

            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
