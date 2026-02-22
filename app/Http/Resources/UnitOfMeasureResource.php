<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class UnitOfMeasureResource extends JsonResource
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
            'name' => $this->name,
            'symbol' => $this->symbol,
            'family' => $this->family,
            'base_unit_id' => $this->base_unit_id,
            'factor' => (float) $this->factor,
            'is_active' => (bool) $this->is_active,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            // Relations
            'base_unit' => new self($this->whenLoaded('baseUnit')),
            'derived_units' => self::collection($this->whenLoaded('derivedUnits')),
        ];
    }
}
