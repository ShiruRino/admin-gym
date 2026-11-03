<?php

namespace Database\Factories;

use App\Models\Invoice;
use App\Models\Member;
use App\Models\MemberPtQuota;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MemberPtQuota>
 */
class MemberPtQuotaFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $total = fake()->randomElement([5, 10, 15, 20]);
        $used = fake()->numberBetween(0, $total - 1);

        return [
            'member_id' => Member::factory(),
            'invoice_id' => Invoice::factory(),
            'total_sessions' => $total,
            'used_sessions' => $used,
            'remaining_sessions' => $total - $used,
            'is_active' => true,
        ];
    }

    /**
     * Depleted/exhausted quota state.
     */
    public function depleted(): static
    {
        return $this->state(fn (array $attributes) => [
            'total_sessions' => 10,
            'used_sessions' => 10,
            'remaining_sessions' => 0,
            'is_active' => false,
        ]);
    }
}
