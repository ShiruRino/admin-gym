<?php

namespace Database\Factories;

use App\Models\Trainer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Trainer>
 */
class TrainerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $specialties = [
            'Hypertrophy & Bodybuilding',
            'Fat Loss & Conditioning',
            'Functional Training & Calisthenics',
            'Powerlifting & Strength',
            'Post-Rehab & Mobility',
        ];

        return [
            'name' => fake()->name(),
            'phone' => fake()->numerify('081#########'),
            'specialty' => fake()->randomElement($specialties),
            'is_active' => true,
        ];
    }

    /**
     * Inactive trainer state.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
