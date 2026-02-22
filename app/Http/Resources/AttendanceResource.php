<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AttendanceResource extends JsonResource
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
            'partner_id' => $this->partner_id,
            'membership_subscription_id' => $this->membership_subscription_id,
            'company_id' => $this->company_id,
            
            // Core Timeline
            'check_in_time' => $this->check_in_time?->toDateTimeString(),
            'check_out_time' => $this->check_out_time?->toDateTimeString(),
            'duration_minutes' => $this->duration_minutes,
            
            // Computed Format
            'formatted_duration' => $this->getFormattedDuration(),
            'is_active' => $this->isActive(),
            
            // Result payload
            'status' => $this->status,
            'validation_message' => $this->validation_message,
            
            // Audit Overrides
            'is_manual_entry' => (bool) $this->is_manual_entry,
            'registered_by' => $this->registered_by,
            
            // Timestamps
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            
            // Relations
            'partner' => new CustomerResource($this->whenLoaded('partner')),
            'subscription' => new MembershipSubscriptionResource($this->whenLoaded('subscription')),
        ];
    }
}
