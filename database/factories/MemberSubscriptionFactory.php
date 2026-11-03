<?php

namespace Database\Factories;

use App\Enums\SubscriptionStatus;
use App\Models\Invoice;
use App\Models\Member;
use App\Models\MemberSubscription;
use App\Models\Plan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MemberSubscription>
 */
class MemberSubscriptionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startDate = fake()->dateTimeBetween('-1 month', 'now');
        $endDate = (clone $startDate)->modify('+30 days');

        return [
            'member_id' => Member::factory(),
            'invoice_id' => Invoice::factory(),
            'plan_id' => Plan::factory(),
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
            'status' => SubscriptionStatus::Active,
        ];
    }

    /**
     * Expired state.
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'start_date' => now()->subDays(60)->format('Y-m-d'),
            'end_date' => now()->subDays(1)->format('Y-m-d'),
            'status' => SubscriptionStatus::Expired,
        ]);
    }
}
