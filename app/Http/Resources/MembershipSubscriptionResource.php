<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class MembershipSubscriptionResource extends JsonResource
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
            'membership_plan_id' => $this->membership_plan_id,
            'company_id' => $this->company_id,

            // Core Dates
            'start_date' => $this->start_date?->toDateString(),
            'end_date' => $this->end_date?->toDateString(),
            'original_end_date' => $this->original_end_date?->toDateString(),

            // Financial & Sales context
            'amount_paid' => (float) $this->amount_paid,
            'payment_method' => $this->payment_method,
            'payment_reference' => $this->payment_reference,
            'sold_by' => $this->sold_by,

            // Counters & limits
            'entries_used' => $this->entries_used,
            'last_entry_date' => $this->last_entry_date?->toDateString(),
            'entries_this_month' => $this->entries_this_month,
            'current_month_start' => $this->current_month_start?->toDateString(),

            // Freezes Context
            'total_days_frozen' => $this->total_days_frozen,
            'remaining_freeze_days' => $this->remaining_freeze_days,

            // System
            'status' => $this->status,
            'notes' => $this->notes,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            // Computed Business Variables for UI Ease
            'is_active' => $this->isActive(),
            'is_expired' => $this->isExpired(),
            'is_frozen' => $this->isFrozen(),
            'can_entry' => $this->canEntry(),
            'progress' => $this->getProgress(),
            'days_remaining' => $this->getDaysRemaining(),

            // Relationships Exposed if Requested
            'plan' => new MembershipPlanResource($this->whenLoaded('plan')),
            'partner' => new CustomerResource($this->whenLoaded('partner')),
            'freezes' => MembershipFreezeResource::collection($this->whenLoaded('freezes')),
        ];
    }
}
