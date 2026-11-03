<?php

use App\Livewire\PtSessions\Index;
use App\Models\Member;
use App\Models\MemberPtQuota;
use App\Models\Trainer;
use App\Models\User;
use Livewire\Livewire;

test('guests are redirected from pt-sessions page to login', function () {
    $response = $this->get(route('pt-sessions.index'));
    $response->assertRedirect(route('login'));
});

test('authenticated staff can view pt-sessions page', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('pt-sessions.index'));
    $response->assertOk();
    $response->assertSee('Sesi Personal Trainer');
});

test('can switch between quotas and logs tabs', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire::test(Index::class)
        ->assertSet('activeTab', 'quotas')
        ->call('setTab', 'logs')
        ->assertSet('activeTab', 'logs')
        ->call('setTab', 'quotas')
        ->assertSet('activeTab', 'quotas');
});

test('can deduct a session via Livewire modal', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $member = Member::factory()->create(['name' => 'Citra Lestari']);
    $trainer = Trainer::factory()->create(['name' => 'Coach Hendra']);
    $quota = MemberPtQuota::factory()->create([
        'member_id' => $member->id,
        'total_sessions' => 10,
        'used_sessions' => 0,
        'remaining_sessions' => 10,
        'is_active' => true,
    ]);

    Livewire::test(Index::class)
        ->call('openDeductModal', $quota->id)
        ->assertSet('showDeductModal', true)
        ->set('selectedTrainerId', $trainer->id)
        ->set('sessionNotes', 'Latihan Upper Body')
        ->call('deductSession')
        ->assertHasNoErrors()
        ->assertSet('showDeductModal', false);

    $quota->refresh();
    expect($quota->used_sessions)->toBe(1)
        ->and($quota->remaining_sessions)->toBe(9);

    $this->assertDatabaseHas('pt_session_logs', [
        'member_pt_quota_id' => $quota->id,
        'member_id' => $member->id,
        'trainer_id' => $trainer->id,
        'notes' => 'Latihan Upper Body',
    ]);
});

test('validates required fields when deducting session', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire::test(Index::class)
        ->call('openDeductModal')
        ->call('deductSession')
        ->assertHasErrors(['selectedMemberId', 'selectedTrainerId']);
});

test('Livewire component deducts session with FIFO across multiple batches when selecting member', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $member = Member::factory()->create(['name' => 'Doni Siregar']);
    $trainer = Trainer::factory()->create(['name' => 'Coach Hendra']);

    $batch1 = MemberPtQuota::factory()->create([
        'member_id' => $member->id,
        'total_sessions' => 5,
        'used_sessions' => 4,
        'remaining_sessions' => 1,
        'is_active' => true,
        'created_at' => now()->subDays(5),
    ]);

    $batch2 = MemberPtQuota::factory()->create([
        'member_id' => $member->id,
        'total_sessions' => 10,
        'used_sessions' => 0,
        'remaining_sessions' => 10,
        'is_active' => true,
        'created_at' => now()->subDay(),
    ]);

    Livewire::test(Index::class)
        ->call('openDeductModal', $member->id)
        ->assertSet('selectedMemberId', $member->id)
        ->set('selectedTrainerId', $trainer->id)
        ->set('sessionNotes', 'FIFO batch 1 drain')
        ->call('deductSession')
        ->assertHasNoErrors();

    $batch1->refresh();
    $batch2->refresh();

    expect($batch1->remaining_sessions)->toBe(0)
        ->and($batch1->is_active)->toBeFalse()
        ->and($batch2->remaining_sessions)->toBe(10);
});
