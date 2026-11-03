<?php

use App\Models\Attendance;
use App\Models\Member;
use App\Models\MemberSubscription;
use App\Models\Plan;
use App\Models\User;
use App\Services\AttendanceService;

test('can check in active member successfully', function () {
    $staff = User::factory()->create();
    $member = Member::factory()->create();
    $plan = Plan::factory()->membership()->create();

    MemberSubscription::factory()->create([
        'member_id' => $member->id,
        'plan_id' => $plan->id,
        'start_date' => now()->subDays(5),
        'end_date' => now()->addDays(25),
        'status' => 'active',
    ]);

    $service = app(AttendanceService::class);
    $attendance = $service->checkIn($member->member_code, $staff, 'Latihan sore');

    expect($attendance->member_id)->toBe($member->id)
        ->and($attendance->verified_by_user_id)->toBe($staff->id)
        ->and($attendance->notes)->toBe('Latihan sore');

    $this->assertDatabaseHas('attendances', [
        'id' => $attendance->id,
        'member_id' => $member->id,
        'verified_by_user_id' => $staff->id,
    ]);
});

test('fails check in for inactive member', function () {
    $staff = User::factory()->create();
    $member = Member::factory()->create(['is_active' => false]);

    $service = app(AttendanceService::class);

    expect(fn () => $service->checkIn($member, $staff))
        ->toThrow(InvalidArgumentException::class, "Member {$member->name} berstatus non-aktif.");
});

test('fails check in for expired member without override', function () {
    $staff = User::factory()->create();
    $member = Member::factory()->create(['is_active' => true]);

    // Member with expired subscription
    MemberSubscription::factory()->create([
        'member_id' => $member->id,
        'start_date' => now()->subDays(40),
        'end_date' => now()->subDays(10),
        'status' => 'active',
    ]);

    $service = app(AttendanceService::class);

    expect(fn () => $service->checkIn($member, $staff))
        ->toThrow(InvalidArgumentException::class, 'sudah kedaluwarsa');
});

test('allows check in for expired member when allowExpiredOverride is true', function () {
    $staff = User::factory()->create();
    $member = Member::factory()->create(['is_active' => true]);

    $service = app(AttendanceService::class);
    $attendance = $service->checkIn($member, $staff, 'Izin trial visit', allowExpiredOverride: true);

    expect($attendance->member_id)->toBe($member->id)
        ->and($attendance->notes)->toBe('Izin trial visit');
});

test('detects if member has already checked in today', function () {
    $member = Member::factory()->create();
    $service = app(AttendanceService::class);

    expect($service->hasCheckedInToday($member))->toBeFalse();

    Attendance::factory()->create([
        'member_id' => $member->id,
        'check_in_time' => now(),
    ]);

    expect($service->hasCheckedInToday($member))->toBeTrue();
});

test('resolves member by code or phone', function () {
    $member = Member::factory()->create([
        'member_code' => 'GYM-2611-9999',
        'phone' => '081299998888',
    ]);

    $service = app(AttendanceService::class);

    expect($service->resolveMember('GYM-2611-9999')->id)->toBe($member->id)
        ->and($service->resolveMember('081299998888')->id)->toBe($member->id);
});
