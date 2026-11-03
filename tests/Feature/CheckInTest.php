<?php

use App\Livewire\CheckIn\Index;
use App\Livewire\Members\Index as MembersIndex;
use App\Models\Attendance;
use App\Models\Member;
use App\Models\MemberSubscription;
use App\Models\Plan;
use App\Models\User;
use Livewire\Livewire;

test('guests are redirected from check-in page to login', function () {
    $response = $this->get(route('check-in.index'));
    $response->assertRedirect(route('login'));
});

test('authenticated staff can view check-in terminal page', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('check-in.index'));
    $response->assertOk();
    $response->assertSee('Terminal Presensi');
});

test('can search and select member by member code', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $member = Member::factory()->create([
        'name' => 'Bambang Sudarsono',
        'member_code' => 'GYM-2611-0088',
    ]);

    Livewire::test(Index::class)
        ->set('searchInput', 'GYM-2611-0088')
        ->assertSet('selectedMemberId', $member->id);
});

test('can execute check-in for active member via Livewire', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $member = Member::factory()->create(['name' => 'Rina Kartika']);
    $plan = Plan::factory()->membership()->create(['name' => 'Gold 1 Bulan']);

    MemberSubscription::factory()->create([
        'member_id' => $member->id,
        'plan_id' => $plan->id,
        'start_date' => now()->subDays(2),
        'end_date' => now()->addDays(28),
        'status' => 'active',
    ]);

    Livewire::test(Index::class)
        ->set('searchInput', $member->member_code)
        ->assertSet('selectedMemberId', $member->id)
        ->set('checkInNotes', 'Latihan rutin sore')
        ->call('executeCheckIn')
        ->assertDispatched('play-checkin-chime')
        ->assertSet('selectedMemberId', null);

    $this->assertDatabaseHas('attendances', [
        'member_id' => $member->id,
        'verified_by_user_id' => $user->id,
        'notes' => 'Latihan rutin sore',
    ]);
});

test('cannot check-in expired member without override', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $member = Member::factory()->create(['name' => 'Eko Prasetyo']);

    // Expired subscription
    MemberSubscription::factory()->create([
        'member_id' => $member->id,
        'start_date' => now()->subDays(60),
        'end_date' => now()->subDays(10),
        'status' => 'active',
    ]);

    Livewire::test(Index::class)
        ->set('searchInput', $member->member_code)
        ->assertSet('selectedMemberId', $member->id)
        ->call('executeCheckIn');

    $this->assertDatabaseMissing('attendances', [
        'member_id' => $member->id,
    ]);
});

test('can check-in expired member when override is checked', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $member = Member::factory()->create(['name' => 'Eko Prasetyo']);

    // Expired subscription
    MemberSubscription::factory()->create([
        'member_id' => $member->id,
        'start_date' => now()->subDays(60),
        'end_date' => now()->subDays(10),
        'status' => 'active',
    ]);

    Livewire::test(Index::class)
        ->set('searchInput', $member->member_code)
        ->assertSet('selectedMemberId', $member->id)
        ->set('allowExpiredOverride', true)
        ->set('checkInNotes', 'Bypass trial 1 hari')
        ->call('executeCheckIn');

    $this->assertDatabaseHas('attendances', [
        'member_id' => $member->id,
        'notes' => 'Bypass trial 1 hari',
    ]);
});

test('lists todays attendances in feed', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $member = Member::factory()->create(['name' => 'Fajar Nugraha']);
    Attendance::factory()->create([
        'member_id' => $member->id,
        'check_in_time' => now(),
    ]);

    Livewire::test(Index::class)
        ->assertSee('Fajar Nugraha')
        ->assertSee('1 Hadir');
});

test('can open member QR card modal in master member', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $member = Member::factory()->create(['name' => 'Galih Pratama', 'member_code' => 'GYM-2611-7777']);

    Livewire::test(MembersIndex::class)
        ->call('openCardModal', $member->id)
        ->assertSet('showCardModal', true)
        ->assertSet('cardMember.id', $member->id)
        ->assertSee('GYM-2611-7777');
});
