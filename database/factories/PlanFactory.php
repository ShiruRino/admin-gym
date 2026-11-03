<?php

namespace Database\Factories;

use App\Enums\PlanType;
use App\Models\Plan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Plan>
 */
class PlanFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => 'Gym Regular 1 Bulan',
            'type' => PlanType::Membership,
            'duration_days' => 30,
            'pt_sessions_count' => null,
            'price' => 350000.00,
            'description' => 'Akses gym reguler bebas setiap hari selama 30 hari.',
            'is_active' => true,
        ];
    }

    /**
     * Membership plan state.
     */
    public function membership(int $days = 30, float $price = 350000.00): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => PlanType::Membership,
            'duration_days' => $days,
            'pt_sessions_count' => null,
            'price' => $price,
        ]);
    }

    /**
     * PT Session plan state.
     */
    public function ptSession(int $sessions = 10, float $price = 1400000.00): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => PlanType::PtSession,
            'duration_days' => null,
            'pt_sessions_count' => $sessions,
            'price' => $price,
        ]);
    }

    /**
     * Bundle plan state.
     */
    public function bundle(int $days = 90, int $sessions = 5, float $price = 1550000.00): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => PlanType::Bundle,
            'duration_days' => $days,
            'pt_sessions_count' => $sessions,
            'price' => $price,
        ]);
    }
}
