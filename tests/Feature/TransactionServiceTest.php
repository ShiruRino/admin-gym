<?php

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\PlanType;
use App\Enums\SubscriptionStatus;
use App\Models\Member;
use App\Models\MemberSubscription;
use App\Models\Plan;
use App\Models\User;
use App\Services\TransactionService;
use Illuminate\Support\Carbon;

test('creates invoice and activates new membership subscription starting from today', function () {
    $staff = User::factory()->create();
    $member = Member::factory()->create(['is_active' => false]);
    $plan = Plan::factory()->membership(30, 350000)->create();

    $service = app(TransactionService::class);
    $invoice = $service->createTransaction([
        'member_id' => $member->id,
        'plan_id' => $plan->id,
        'amount' => 350000,
        'payment_method' => PaymentMethod::Cash,
    ], $staff);

    expect($invoice->payment_status)->toBe(PaymentStatus::Paid)
        ->and($invoice->member_id)->toBe($member->id)
        ->and($invoice->plan_id)->toBe($plan->id)
        ->and($invoice->received_by_user_id)->toBe($staff->id)
        ->and($invoice->subscription)->not->toBeNull()
        ->and($invoice->subscription->status)->toBe(SubscriptionStatus::Active)
        ->and($invoice->subscription->start_date->format('Y-m-d'))->toBe(Carbon::today()->format('Y-m-d'))
        ->and($invoice->subscription->end_date->format('Y-m-d'))->toBe(Carbon::today()->addDays(29)->format('Y-m-d'));

    expect($member->fresh()->is_active)->toBeTrue();
});

test('accumulatively extends subscription if member has active subscription', function () {
    $staff = User::factory()->create();
    $member = Member::factory()->create(['is_active' => true]);
    $plan = Plan::factory()->membership(30, 350000)->create();

    // Existing subscription ending in 10 days
    $existingEndDate = Carbon::today()->addDays(10);
    MemberSubscription::factory()->create([
        'member_id' => $member->id,
        'start_date' => Carbon::today()->subDays(20),
        'end_date' => $existingEndDate,
        'status' => SubscriptionStatus::Active,
    ]);

    $service = app(TransactionService::class);
    $invoice = $service->createTransaction([
        'member_id' => $member->id,
        'plan_id' => $plan->id,
        'amount' => 350000,
        'payment_method' => PaymentMethod::Transfer,
    ], $staff);

    $newSub = $invoice->subscription;
    expect($newSub)->not->toBeNull()
        ->and($newSub->start_date->format('Y-m-d'))->toBe($existingEndDate->copy()->addDay()->format('Y-m-d'))
        ->and($newSub->end_date->format('Y-m-d'))->toBe($existingEndDate->copy()->addDays(30)->format('Y-m-d'));
});

test('allocates PT quota when purchasing PT session plan', function () {
    $staff = User::factory()->create();
    $member = Member::factory()->create();
    $plan = Plan::factory()->ptSession(10, 1500000)->create();

    $service = app(TransactionService::class);
    $invoice = $service->createTransaction([
        'member_id' => $member->id,
        'plan_id' => $plan->id,
        'amount' => 1500000,
        'payment_method' => PaymentMethod::Qris,
    ], $staff);

    expect($invoice->subscription)->toBeNull()
        ->and($invoice->ptQuota)->not->toBeNull()
        ->and($invoice->ptQuota->total_sessions)->toBe(10)
        ->and($invoice->ptQuota->used_sessions)->toBe(0)
        ->and($invoice->ptQuota->remaining_sessions)->toBe(10)
        ->and($invoice->ptQuota->is_active)->toBeTrue();
});

test('activates both subscription and PT quota for bundle plan', function () {
    $staff = User::factory()->create();
    $member = Member::factory()->create();
    $plan = Plan::factory()->create([
        'name' => 'Bundling 1 Bulan + 5 PT',
        'type' => PlanType::Bundle,
        'duration_days' => 30,
        'pt_sessions_count' => 5,
        'price' => 900000,
    ]);

    $service = app(TransactionService::class);
    $invoice = $service->createTransaction([
        'member_id' => $member->id,
        'plan_id' => $plan->id,
        'amount' => 900000,
        'payment_method' => PaymentMethod::Cash,
    ], $staff);

    expect($invoice->subscription)->not->toBeNull()
        ->and($invoice->ptQuota)->not->toBeNull()
        ->and($invoice->ptQuota->total_sessions)->toBe(5)
        ->and($invoice->subscription->status)->toBe(SubscriptionStatus::Active);
});
