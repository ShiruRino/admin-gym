<?php

use App\Enums\PaymentMethod;
use App\Livewire\Invoices\Index;
use App\Models\Invoice;
use App\Models\Member;
use App\Models\Plan;
use App\Models\User;
use Livewire\Livewire;

test('guests are redirected from invoices page to login', function () {
    $response = $this->get(route('invoices.index'));
    $response->assertRedirect(route('login'));
});

test('authenticated staff can view invoices page', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('invoices.index'));
    $response->assertOk();
    $response->assertSee('Transaksi & Tagihan');
});

test('can create a new transaction via Livewire modal and automatically activate plan', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $member = Member::factory()->create(['name' => 'Budi Sanjaya']);
    $plan = Plan::factory()->membership(30, 350000)->create();

    Livewire::test(Index::class)
        ->call('openCreateModal')
        ->set('member_id', $member->id)
        ->set('plan_id', $plan->id)
        ->set('amount', '350000')
        ->set('payment_method', PaymentMethod::Cash->value)
        ->set('notes', 'Pembayaran tunai di meja depan')
        ->call('save')
        ->assertHasNoErrors()
        ->assertSet('showCreateModal', false)
        ->assertSet('showDetailModal', true);

    $this->assertDatabaseHas('invoices', [
        'member_id' => $member->id,
        'plan_id' => $plan->id,
        'amount' => 350000.00,
        'payment_method' => PaymentMethod::Cash->value,
    ]);

    $this->assertDatabaseHas('member_subscriptions', [
        'member_id' => $member->id,
        'plan_id' => $plan->id,
    ]);
});

test('can view invoice detail and receipt modal', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $invoice = Invoice::factory()->create();

    Livewire::test(Index::class)
        ->call('openDetailModal', $invoice->id)
        ->assertSet('showDetailModal', true)
        ->assertSet('detailInvoice.id', $invoice->id);
});

test('can sort invoices table columns', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Invoice::factory()->count(2)->create();

    Livewire::test(Index::class)
        ->call('sort', 'amount')
        ->assertSet('sortBy', 'amount')
        ->assertSet('sortDirection', 'asc')
        ->call('sort', 'amount')
        ->assertSet('sortBy', 'amount')
        ->assertSet('sortDirection', 'desc');
});
