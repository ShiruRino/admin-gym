<?php

use App\Models\Member;
use App\Models\MemberPtQuota;
use App\Models\Trainer;
use App\Models\User;
use App\Services\PtQuotaService;

test('can deduct one PT session and record usage log', function () {
    $staff = User::factory()->create();
    $trainer = Trainer::factory()->create();
    $quota = MemberPtQuota::factory()->create([
        'total_sessions' => 10,
        'used_sessions' => 2,
        'remaining_sessions' => 8,
        'is_active' => true,
    ]);

    $service = app(PtQuotaService::class);
    $log = $service->deductSession($quota, $trainer, $staff, 'Chest workout');

    expect($log->member_id)->toBe($quota->member_id)
        ->and($log->trainer_id)->toBe($trainer->id)
        ->and($log->recorded_by_user_id)->toBe($staff->id)
        ->and($log->notes)->toBe('Chest workout');

    $quota->refresh();
    expect($quota->used_sessions)->toBe(3)
        ->and($quota->remaining_sessions)->toBe(7)
        ->and($quota->is_active)->toBeTrue();
});

test('deactivates quota when last session is used', function () {
    $staff = User::factory()->create();
    $trainer = Trainer::factory()->create();
    $quota = MemberPtQuota::factory()->create([
        'total_sessions' => 5,
        'used_sessions' => 4,
        'remaining_sessions' => 1,
        'is_active' => true,
    ]);

    $service = app(PtQuotaService::class);
    $service->deductSession($quota, $trainer, $staff);

    $quota->refresh();
    expect($quota->used_sessions)->toBe(5)
        ->and($quota->remaining_sessions)->toBe(0)
        ->and($quota->is_active)->toBeFalse();
});

test('throws exception when deducting from depleted quota', function () {
    $staff = User::factory()->create();
    $trainer = Trainer::factory()->create();
    $quota = MemberPtQuota::factory()->depleted()->create();

    $service = app(PtQuotaService::class);

    expect(fn () => $service->deductSession($quota, $trainer, $staff))
        ->toThrow(InvalidArgumentException::class);
});

test('deducts PT session using FIFO when member has multiple active quota batches', function () {
    $staff = User::factory()->create();
    $trainer = Trainer::factory()->create();
    $member = Member::factory()->create();

    // Batch 1 (older purchase, 1 session remaining)
    $batch1 = MemberPtQuota::factory()->create([
        'member_id' => $member->id,
        'total_sessions' => 5,
        'used_sessions' => 4,
        'remaining_sessions' => 1,
        'is_active' => true,
        'created_at' => now()->subDays(10),
    ]);

    // Batch 2 (newer purchase, 10 sessions remaining)
    $batch2 = MemberPtQuota::factory()->create([
        'member_id' => $member->id,
        'total_sessions' => 10,
        'used_sessions' => 0,
        'remaining_sessions' => 10,
        'is_active' => true,
        'created_at' => now()->subDays(2),
    ]);

    $service = app(PtQuotaService::class);

    // First deduction: should deduct from Batch 1 (FIFO)
    $log1 = $service->deductSession($member, $trainer, $staff, 'First FIFO session');

    $batch1->refresh();
    $batch2->refresh();

    expect($log1->member_pt_quota_id)->toBe($batch1->id)
        ->and($batch1->remaining_sessions)->toBe(0)
        ->and($batch1->is_active)->toBeFalse()
        ->and($batch2->remaining_sessions)->toBe(10)
        ->and($batch2->is_active)->toBeTrue();

    // Second deduction: Batch 1 is now exhausted, so it automatically deducts from Batch 2!
    $log2 = $service->deductSession($member, $trainer, $staff, 'Second FIFO session');

    $batch1->refresh();
    $batch2->refresh();

    expect($log2->member_pt_quota_id)->toBe($batch2->id)
        ->and($batch2->remaining_sessions)->toBe(9)
        ->and($batch2->used_sessions)->toBe(1)
        ->and($batch2->is_active)->toBeTrue();
});
