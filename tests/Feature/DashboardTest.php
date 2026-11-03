<?php

use App\Livewire\Dashboard\Index;
use App\Models\Attendance;
use App\Models\Invoice;
use App\Models\Member;
use App\Models\MemberSubscription;
use App\Models\User;
use Livewire\Livewire;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertOk();
    $response->assertSee('Dashboard Operasional Gym');
});

test('dashboard computes metrics and lists expiring members correctly', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $member = Member::factory()->create(['name' => 'Wahyudi Santoso']);
    MemberSubscription::factory()->create([
        'member_id' => $member->id,
        'invoice_id' => null,
        'start_date' => now()->subDays(20),
        'end_date' => now()->addDays(3),
        'status' => 'active',
    ]);

    Attendance::factory()->create([
        'member_id' => $member->id,
        'check_in_time' => now(),
    ]);

    Invoice::factory()->create([
        'member_id' => $member->id,
        'amount' => 350000,
        'payment_status' => 'paid',
        'created_at' => now(),
    ]);

    Livewire::test(Index::class)
        ->assertSee('Wahyudi Santoso')
        ->assertSee('Rp 350.000')
        ->assertSee('kunjungan')
        ->assertSee('3 hari lagi');
});
