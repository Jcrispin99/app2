<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class SaleResource extends JsonResource
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
            'document_number' => $this->document_number,
            'serie' => $this->serie,
            'correlative' => $this->correlative,
            'date' => $this->date ? $this->date->format('Y-m-d H:i:s') : null,
            'notes' => $this->notes,
            'status' => $this->status,
            'payment_status' => $this->payment_status,

            // Subtotals & Tax
            'subtotal' => (float) $this->subtotal,
            'tax_amount' => (float) $this->tax_amount,
            'total' => (float) $this->total,

            // Simple Relations
            'partner_id' => $this->partner_id,
            'warehouse_id' => $this->warehouse_id,
            'journal_id' => $this->journal_id,
            'company_id' => $this->company_id,
            'user_id' => $this->user_id,

            // Timestamps
            'created_at' => $this->created_at ? $this->created_at->format('Y-m-d H:i:s') : null,
            'updated_at' => $this->updated_at ? $this->updated_at->format('Y-m-d H:i:s') : null,

            // Eager Loaded Relations
            'partner' => new CustomerResource($this->whenLoaded('partner')),
            'warehouse' => new WarehouseResource($this->whenLoaded('warehouse')),
            'company' => new CompanyResource($this->whenLoaded('company')),

            // Using the polymorphic ProductableResource
            'products' => ProductableResource::collection($this->whenLoaded('products')),

            // Nested relations via array mapped directly
            'journal' => $this->whenLoaded('journal', function () {
                return [
                    'id' => $this->journal->id,
                    'name' => $this->journal->name,
                    'code' => $this->journal->code,
                    'document_type_code' => $this->journal->document_type_code,
                ];
            }),

            'user' => $this->whenLoaded('user', function () {
                return [
                    'id' => $this->user->id,
                    'name' => $this->user->name,
                ];
            }),

            'original_sale' => $this->whenLoaded('originalSale', function () {
                return [
                    'id' => $this->originalSale->id,
                    'document' => $this->originalSale->document_number,
                    'status' => $this->originalSale->status,
                    'journal_code' => $this->originalSale->journal ? $this->originalSale->journal->code : null,
                    'doc_type' => $this->originalSale->journal ? (string) $this->originalSale->journal->document_type_code : '',
                ];
            }),

            'credit_notes' => $this->whenLoaded('creditNotes', function () {
                return $this->creditNotes->map(function ($note) {
                    return [
                        'id' => $note->id,
                        'document' => $note->document_number,
                        'status' => $note->status,
                        'journal_code' => $note->journal ? $note->journal->code : null,
                        'doc_type' => $note->journal ? (string) $note->journal->document_type_code : '',
                    ];
                });
            }),
        ];
    }
}
