<?php

namespace Database\Factories;

use App\Enums\Gender;
use App\Models\Member;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;

/**
 * @extends Factory<Member>
 */
class MemberFactory extends Factory
{
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $joinDate = fake()->dateTimeBetween('-1 year', 'now');

        return [
            'member_code' => Member::generateMemberCode(Carbon::parse($joinDate)),
            'name' => fake()->name(),
            'phone' => fake()->unique()->numerify('081#########'),
            'email' => fake()->unique()->safeEmail(),
            'password' => static::$password ??= Hash::make('123456'),
            'gender' => fake()->randomElement([Gender::Male, Gender::Female]),
            'birth_date' => fake()->dateTimeBetween('-50 years', '-18 years')->format('Y-m-d'),
            'address' => fake()->address(),
            'emergency_contact' => fake()->numerify('081#########'),
            'notes' => fake()->optional()->sentence(),
            'join_date' => $joinDate->format('Y-m-d'),
            'is_active' => true,
        ];
    }

    /**
     * Inactive member state.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
