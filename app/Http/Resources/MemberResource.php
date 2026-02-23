<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MemberResource extends JsonResource
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
            'user_id' => $this->user_id,
            
            // Documentos
            'document_type' => $this->document_type,
            'document_number' => $this->document_number,

            // Datos Personales
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'mobile' => $this->mobile,

            // Dirección
            'address' => $this->address,
            'ubigeo' => $this->ubigeo,

            // Demográficos BD Puros
            'birth_date' => $this->birth_date ? $this->birth_date->format('Y-m-d') : null,
            'gender' => $this->gender,

            // Estado y flags calculados
            'status' => $this->status,
            'is_customer' => (bool) $this->is_customer,
            'is_supplier' => (bool) $this->is_supplier,
            'is_member' => (bool) $this->is_member,
            'has_portal_access' => $this->user_id !== null,
            'notes' => $this->notes,

            // Tiempos
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            // Relaciones Condicionales
            'company' => new CompanyResource($this->whenLoaded('company')),
            'user' => $this->whenLoaded('user', function () {
                return ['id' => $this->user->id, 'name' => $this->user->name, 'email' => $this->user->email];
            }),
            'subscriptions' => MembershipSubscriptionResource::collection($this->whenLoaded('subscriptions')),
        ];
    }
}
