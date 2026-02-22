<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class MembershipFreezeResource extends JsonResource
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
            'membership_subscription_id' => $this->membership_subscription_id,
            'freeze_start_date' => $this->freeze_start_date?->toDateString(),
            'freeze_end_date' => $this->freeze_end_date?->toDateString(),
            'days_frozen' => $this->days_frozen,
            'planned_days' => $this->planned_days,
            'status' => $this->status,
            'reason' => $this->reason,
            'requested_by' => $this->requested_by,
            'approved_by' => $this->approved_by,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
