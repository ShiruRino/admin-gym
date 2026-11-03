<?php

namespace App\Http\Resources;

use App\Models\Member;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Member
 */
class MemberCardResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $sub = $this->activeSubscription;
        $daysRemaining = $sub ? max(0, (int) ceil(now()->diffInDays($sub->end_date, false))) : 0;

        return [
            'member_code' => $this->member_code,
            'name' => $this->name,
            'photo_url' => $this->photo_url,
            'qr_payload' => $this->member_code,
            'membership_status' => $this->membership_status,
            'plan_name' => $sub?->plan?->name,
            'start_date' => $sub?->start_date?->format('Y-m-d'),
            'end_date' => $sub?->end_date?->format('Y-m-d'),
            'days_remaining' => $daysRemaining,
        ];
    }
}
