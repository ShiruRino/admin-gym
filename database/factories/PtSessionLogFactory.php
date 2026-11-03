<?php

namespace Database\Factories;

use App\Models\Member;
use App\Models\MemberPtQuota;
use App\Models\PtSessionLog;
use App\Models\Trainer;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PtSessionLog>
 */
class PtSessionLogFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'member_pt_quota_id' => MemberPtQuota::factory(),
            'member_id' => Member::factory(),
            'trainer_id' => Trainer::factory(),
            'recorded_by_user_id' => User::factory(),
            'used_at' => now(),
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
