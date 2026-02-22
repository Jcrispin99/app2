<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class MembershipPlanResource extends JsonResource
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
            'product_product_id' => $this->product_product_id,
            'name' => $this->name,
            'description' => $this->description,
            'duration_days' => $this->duration_days,
            'duration_months' => $this->getDurationInMonths(),
            'price' => (float) $this->price,
            'max_entries_per_month' => $this->max_entries_per_month,
            'max_entries_per_day' => $this->max_entries_per_day,
            'time_restricted' => (bool) $this->time_restricted,
            'allowed_time_start' => $this->allowed_time_start ? mb_substr($this->allowed_time_start, 0, 5) : null,
            'allowed_time_end' => $this->allowed_time_end ? mb_substr($this->allowed_time_end, 0, 5) : null,
            'allowed_days' => $this->allowed_days ?? [],
            'allows_freezing' => (bool) $this->allows_freezing,
            'max_freeze_days' => $this->max_freeze_days,
            'is_active' => (bool) $this->is_active,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            // Computed booleans for ease of UI consumption
            'is_unlimited_entries' => $this->isUnlimitedEntries(),
            'has_time_restriction' => $this->hasTimeRestriction(),
            'has_day_restriction' => $this->hasDayRestriction(),
            'allows_freeze' => $this->allowsFreeze(),

            // Helpful formatted representation
            'formatted_price' => $this->getFormattedPrice(),

            // Expose relationships conditionally
            'company' => new CompanyResource($this->whenLoaded('company')),
        ];
    }
}
