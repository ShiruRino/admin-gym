<?php

namespace Database\Factories;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Invoice;
use App\Models\Member;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Invoice>
 */
class InvoiceFactory extends Factory
{
    protected static int $sequence = 1;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $invoiceNumber = 'INV-'.now()->format('Ymd').'-'.str_pad((string) (static::$sequence++), 4, '0', STR_PAD_LEFT);

        return [
            'invoice_number' => $invoiceNumber,
            'member_id' => Member::factory(),
            'plan_id' => Plan::factory(),
            'amount' => 350000.00,
            'payment_method' => fake()->randomElement([PaymentMethod::Cash, PaymentMethod::Transfer, PaymentMethod::Qris]),
            'payment_status' => PaymentStatus::Paid,
            'paid_at' => now(),
            'received_by_user_id' => User::factory(),
            'notes' => fake()->optional()->sentence(),
        ];
    }

    /**
     * Unpaid state.
     */
    public function unpaid(): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_status' => PaymentStatus::Unpaid,
            'paid_at' => null,
        ]);
    }
}
