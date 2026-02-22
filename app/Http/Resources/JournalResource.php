<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class JournalResource extends JsonResource
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
            'code' => $this->code,
            'type' => $this->type,
            'is_fiscal' => $this->is_fiscal,
            'document_type_code' => $this->document_type_code,
            'company_id' => $this->company_id,
            'sequence_id' => $this->sequence_id,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            // Relaciones opcionales inyectables
            'company' => new CompanyResource($this->whenLoaded('company')),
            'sequence' => $this->whenLoaded('sequence', function () {
                return [
                    'id' => $this->sequence->id,
                    'sequence_size' => $this->sequence->sequence_size,
                    'step' => $this->sequence->step,
                    'next_number' => $this->sequence->next_number,
                ];
            }),
        ];
    }
}
