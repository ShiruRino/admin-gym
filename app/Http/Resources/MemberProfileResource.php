<?php

namespace App\Http\Resources;

use App\Models\Member;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Member
 */
class MemberProfileResource extends JsonResource
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
            'member_code' => $this->member_code,
            'name' => $this->name,
            'phone' => $this->phone,
            'email' => $this->email,
            'photo_url' => $this->photo_url,
            'gender' => $this->gender?->value,
            'gender_label' => $this->gender?->label(),
            'birth_date' => $this->birth_date?->format('Y-m-d'),
            'address' => $this->address,
            'emergency_contact' => $this->emergency_contact,
            'notes' => $this->notes,
            'join_date' => $this->join_date->format('Y-m-d'),
            'is_active' => $this->is_active,
            'membership_status' => $this->membership_status,
        ];
    }
}
