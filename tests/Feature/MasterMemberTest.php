<?php

use App\Enums\Gender;
use App\Livewire\Members\Index;
use App\Models\Member;
use App\Models\MemberPtQuota;
use App\Models\User;
use App\Notifications\MemberTemporaryPasswordNotification;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

test('guests are redirected from members page to login', function () {
    $response = $this->get(route('members.index'));
    $response->assertRedirect(route('login'));
});

test('authenticated staff can view members page', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('members.index'));
    $response->assertOk();
    $response->assertSee('Master Member');
});

test('can create a new member via Livewire form modal', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire::test(Index::class)
        ->call('openCreateModal')
        ->set('name', 'Budi Gunawan')
        ->set('phone', '081299887766')
        ->set('gender', Gender::Male->value)
        ->set('join_date', '2026-11-03')
        ->set('address', 'Jl. Merdeka No. 1')
        ->call('save')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('members', [
        'name' => 'Budi Gunawan',
        'phone' => '081299887766',
        'gender' => Gender::Male->value,
    ]);
});

test('validates duplicate phone number when creating member', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $existing = Member::factory()->create(['phone' => '081211112222']);

    Livewire::test(Index::class)
        ->call('openCreateModal')
        ->set('name', 'Member Baru')
        ->set('phone', '081211112222')
        ->set('join_date', '2026-11-03')
        ->call('save')
        ->assertHasErrors(['phone']);
});

test('can edit an existing member', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $member = Member::factory()->create([
        'name' => 'Nama Lama',
        'phone' => '081233445566',
    ]);

    Livewire::test(Index::class)
        ->call('openEditModal', $member->id)
        ->set('name', 'Nama Baru Terupdate')
        ->call('save')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('members', [
        'id' => $member->id,
        'name' => 'Nama Baru Terupdate',
    ]);
});

test('can toggle member active state', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $member = Member::factory()->create(['is_active' => true]);

    Livewire::test(Index::class)
        ->call('toggleActive', $member->id);

    expect($member->fresh()->is_active)->toBeFalse();

    Livewire::test(Index::class)
        ->call('toggleActive', $member->id);

    expect($member->fresh()->is_active)->toBeTrue();
});

test('can open member detail modal with relational data', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $member = Member::factory()->create();

    Livewire::test(Index::class)
        ->call('openDetailModal', $member->id)
        ->assertSet('showDetailModal', true)
        ->assertSet('detailMember.id', $member->id);
});

test('can sort members by column', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Member::factory()->create(['name' => 'Anton Wijaya', 'member_code' => 'GYM-2611-0001']);
    Member::factory()->create(['name' => 'Budi Santoso', 'member_code' => 'GYM-2611-0002']);

    Livewire::test(Index::class)
        ->call('sort', 'name')
        ->assertSet('sortBy', 'name')
        ->assertSet('sortDirection', 'asc')
        ->call('sort', 'name')
        ->assertSet('sortBy', 'name')
        ->assertSet('sortDirection', 'desc')
        ->call('sort', 'member_code')
        ->assertSet('sortBy', 'member_code')
        ->assertSet('sortDirection', 'asc');
});

test('displays accumulated PT quota across multiple active batches', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $member = Member::factory()->create(['name' => 'Kurniawan Pratama']);

    // Batch 1: 5 total, 3 remaining
    MemberPtQuota::factory()->create([
        'member_id' => $member->id,
        'total_sessions' => 5,
        'used_sessions' => 2,
        'remaining_sessions' => 3,
        'is_active' => true,
    ]);

    // Batch 2: 10 total, 10 remaining
    MemberPtQuota::factory()->create([
        'member_id' => $member->id,
        'total_sessions' => 10,
        'used_sessions' => 0,
        'remaining_sessions' => 10,
        'is_active' => true,
    ]);

    Livewire::test(Index::class)
        ->assertSee('13')
        ->assertSee('/ 15 sesi');
});

test('creating member with email generates temporary password and sends email notification', function () {
    Notification::fake();

    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire::test(Index::class)
        ->call('openCreateModal')
        ->set('name', 'Rina Anggraini')
        ->set('phone', '081288990011')
        ->set('email', 'rina@gmail.com')
        ->set('join_date', '2026-11-03')
        ->call('save')
        ->assertHasNoErrors();

    $member = Member::where('email', 'rina@gmail.com')->first();
    expect($member)->not->toBeNull();
    expect($member->phone)->toBe('081288990011');
    expect(strlen($member->password))->toBeGreaterThan(10); // hashed

    Notification::assertSentTo(
        $member,
        MemberTemporaryPasswordNotification::class,
        function (MemberTemporaryPasswordNotification $notification) {
            return $notification->isNewRegistration === true && ! empty($notification->temporaryPassword);
        }
    );
});

test('staff can reset and send temporary password to member email', function () {
    Notification::fake();

    $user = User::factory()->create();
    $this->actingAs($user);

    $member = Member::factory()->create([
        'email' => 'deni@gymhub.test',
        'password' => Hash::make('oldpassword'),
    ]);

    Livewire::test(Index::class)
        ->call('sendTemporaryPassword', $member->id);

    $freshMember = $member->fresh();
    expect(Hash::check('oldpassword', $freshMember->password))->toBeFalse();

    Notification::assertSentTo(
        $member,
        MemberTemporaryPasswordNotification::class,
        function (MemberTemporaryPasswordNotification $notification) {
            return $notification->isNewRegistration === false && ! empty($notification->temporaryPassword);
        }
    );
});

test('cannot create member with duplicate email', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Member::factory()->create(['email' => 'duplicate@gmail.com']);

    Livewire::test(Index::class)
        ->call('openCreateModal')
        ->set('name', 'Calon Member')
        ->set('phone', '081299998888')
        ->set('email', 'duplicate@gmail.com')
        ->set('join_date', '2026-11-03')
        ->call('save')
        ->assertHasErrors(['email']);
});
