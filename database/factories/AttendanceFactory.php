<?php

namespace Database\Factories;

use App\Models\Attendance;
use App\Models\Member;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Attendance>
 */
class AttendanceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'member_id' => Member::factory(),
            'check_in_time' => now(),
            'verified_by_user_id' => User::factory(),
            'notes' => null,
        ];
    }
}
