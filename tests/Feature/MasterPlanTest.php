<?php

use App\Enums\PlanType;
use App\Livewire\Plans\Index;
use App\Models\Plan;
use App\Models\User;
use Livewire\Livewire;

test('guests are redirected from plans page to login', function () {
    $response = $this->get(route('plans.index'));
    $response->assertRedirect(route('login'));
});

test('authenticated staff can view plans page', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('plans.index'));
    $response->assertOk();
    $response->assertSee('Master Paket Layanan');
});

test('can create a membership plan via form modal', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire::test(Index::class)
        ->call('openCreateModal')
        ->set('name', 'Paket Fitness Pelajar 1 Bulan')
        ->set('type', PlanType::Membership->value)
        ->set('duration_days', 30)
        ->set('price', '250000')
        ->set('description', 'Diskon khusus member pelajar.')
        ->call('save')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('plans', [
        'name' => 'Paket Fitness Pelajar 1 Bulan',
        'type' => PlanType::Membership->value,
        'duration_days' => 30,
        'price' => 250000.00,
    ]);
});

test('can create a PT session plan via form modal', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire::test(Index::class)
        ->call('openCreateModal')
        ->set('name', 'Paket PT Intensif 15 Sesi')
        ->set('type', PlanType::PtSession->value)
        ->set('pt_sessions_count', 15)
        ->set('price', '1800000')
        ->call('save')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('plans', [
        'name' => 'Paket PT Intensif 15 Sesi',
        'type' => PlanType::PtSession->value,
        'pt_sessions_count' => 15,
        'price' => 1800000.00,
    ]);
});

test('can edit an existing plan', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $plan = Plan::factory()->create([
        'name' => 'Paket Lama',
        'price' => 100000.00,
    ]);

    Livewire::test(Index::class)
        ->call('openEditModal', $plan->id)
        ->set('name', 'Paket Baru Diskon')
        ->set('price', '120000')
        ->call('save')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('plans', [
        'id' => $plan->id,
        'name' => 'Paket Baru Diskon',
        'price' => 120000.00,
    ]);
});

test('can toggle plan active status', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $plan = Plan::factory()->create(['is_active' => true]);

    Livewire::test(Index::class)
        ->call('toggleActive', $plan->id);

    expect($plan->fresh()->is_active)->toBeFalse();
});

test('can sort plans by column', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Plan::factory()->create(['name' => 'Paket A', 'price' => 100000]);
    Plan::factory()->create(['name' => 'Paket B', 'price' => 200000]);

    Livewire::test(Index::class)
        ->call('sort', 'name')
        ->assertSet('sortBy', 'name')
        ->assertSet('sortDirection', 'asc')
        ->call('sort', 'name')
        ->assertSet('sortBy', 'name')
        ->assertSet('sortDirection', 'desc')
        ->call('sort', 'price')
        ->assertSet('sortBy', 'price')
        ->assertSet('sortDirection', 'asc');
});
