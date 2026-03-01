<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class PosConfigResource extends JsonResource
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
            'company_id' => $this->company_id,
            'warehouse_id' => $this->warehouse_id,
            'name' => $this->name,

            // Boolean Configurations
            'apply_tax' => (bool) $this->apply_tax,
            'prices_include_tax' => (bool) $this->prices_include_tax,
            'is_active' => (bool) $this->is_active,

            // Timestamps
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            // Formatted Pivot Relations (For assigned Document Templates)
            'journals' => collect($this->whenLoaded('journals'))->map(function ($journal) {
                return [
                    'id' => $journal->id,
                    'name' => $journal->name,
                    'code' => $journal->code,
                    'document_type' => $journal->pivot->document_type,
                    'is_default' => (bool) $journal->pivot->is_default,
                ];
            }),

            // General Relations
            'warehouse' => new WarehouseResource($this->whenLoaded('warehouse')),
            'default_customer' => new CustomerResource($this->whenLoaded('defaultCustomer')),
            'tax' => new TaxResource($this->whenLoaded('tax')),
        ];
    }
}
