<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Partner;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Partner
 */
final class SupplierResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'is_supplier' => (bool) $this->is_supplier,
            'is_member' => (bool) $this->is_member,
            'document_type' => $this->document_type,
            'document_number' => $this->document_number,
            'name' => $this->name,
            'business_name' => $this->business_name,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'email' => $this->email,
            'phone' => $this->phone,
            'mobile' => $this->mobile,
            'address' => $this->address,
            'district' => $this->district,
            'province' => $this->province,
            'department' => $this->department,
            'payment_terms' => $this->payment_terms,
            'provider_category' => $this->provider_category,
            'status' => $this->status,
            'notes' => $this->notes,
            'company_id' => $this->company_id,
            'company' => new CompanyResource($this->whenLoaded('company')),

            // Appends
            'full_name' => $this->full_name,
            'display_name' => $this->display_name,

            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
