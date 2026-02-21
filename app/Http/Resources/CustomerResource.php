<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Partner;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Partner
 */
final class CustomerResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'is_customer' => (bool) $this->is_customer,
            'is_member' => (bool) $this->is_member,
            'document_type' => $this->document_type,
            'document_number' => $this->document_number,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'mobile' => $this->mobile,
            'address' => $this->address,
            'ubigeo' => $this->ubigeo,
            'birth_date' => $this->birth_date?->format('Y-m-d'),
            'gender' => $this->gender,
            'status' => $this->status,
            'notes' => $this->notes,

            'company_id' => $this->company_id,
            'user_id' => $this->user_id,

            // Relaciones
            'company' => new CompanyResource($this->whenLoaded('company')),

            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
