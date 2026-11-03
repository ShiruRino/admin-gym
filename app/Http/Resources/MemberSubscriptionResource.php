<?php

namespace App\Http\Resources;

use App\Models\Member;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Member
 */
class MemberSubscriptionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $sub = $this->activeSubscription;
        $latest = $this->latestSubscription;

        return [
            'has_active_subscription' => $sub !== null,
            'membership_status' => $this->membership_status,
            'active_subscription' => $sub ? [
                'id' => $sub->id,
                'plan_id' => $sub->plan_id,
                'plan_name' => $sub->plan?->name,
                'start_date' => $sub->start_date->format('Y-m-d'),
                'end_date' => $sub->end_date->format('Y-m-d'),
                'days_remaining' => max(0, (int) ceil(now()->diffInDays($sub->end_date, false))),
                'status' => $sub->status?->value ?? (string) $sub->status,
            ] : null,
            'latest_subscription' => (! $sub && $latest) ? [
                'id' => $latest->id,
                'plan_id' => $latest->plan_id,
                'plan_name' => $latest->plan?->name,
                'start_date' => $latest->start_date->format('Y-m-d'),
                'end_date' => $latest->end_date->format('Y-m-d'),
                'status' => $latest->status?->value ?? (string) $latest->status,
            ] : null,
        ];
    }
}
