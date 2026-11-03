<?php

use App\Enums\Gender;
use App\Livewire\Auth\MemberRegister;
use App\Models\Member;
use App\Notifications\MemberTemporaryPasswordNotification;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

test('prospective member can view public self registration page', function () {
    $response = $this->get(route('member.register'));

    $response->assertOk()
        ->assertSee('Pendaftaran Member Gym')
        ->assertSee('Daftar Sebagai Member');
});

test('prospective member can register themselves via web form', function () {
    Notification::fake();

    Livewire::test(MemberRegister::class)
        ->set('name', 'Siti Rahmawati')
        ->set('email', 'siti.rahma@gmail.com')
        ->set('phone', '081255443322')
        ->set('gender', Gender::Female->value)
        ->set('birth_date', '2000-03-15')
        ->set('address', 'Jl. Flamboyan No. 8')
        ->set('password', 'password123')
        ->set('password_confirmation', 'password123')
        ->call('register')
        ->assertHasNoErrors()
        ->assertSee('Pendaftaran Berhasil!')
        ->assertSee('GymHub Digital Card');

    $member = Member::where('email', 'siti.rahma@gmail.com')->first();
    expect($member)->not->toBeNull();
    expect($member->name)->toBe('Siti Rahmawati');
    expect($member->phone)->toBe('081255443322');
    expect(str_starts_with($member->member_code, 'GYM-'))->toBeTrue();
    expect(Hash::check('password123', $member->password))->toBeTrue();

    Notification::assertSentTo(
        $member,
        MemberTemporaryPasswordNotification::class
    );
});

test('web registration validates unique email and phone', function () {
    Member::factory()->create([
        'email' => 'terdaftar@gymhub.test',
        'phone' => '081199887766',
    ]);

    Livewire::test(MemberRegister::class)
        ->set('name', 'Member Coba')
        ->set('email', 'terdaftar@gymhub.test')
        ->set('phone', '081199887766')
        ->set('password', 'secret123')
        ->set('password_confirmation', 'secret123')
        ->call('register')
        ->assertHasErrors(['email', 'phone']);
});

test('web registration requires matching password confirmation', function () {
    Livewire::test(MemberRegister::class)
        ->set('name', 'Member Coba')
        ->set('email', 'coba@gymhub.test')
        ->set('phone', '081199887755')
        ->set('password', 'secret123')
        ->set('password_confirmation', 'different123')
        ->call('register')
        ->assertHasErrors(['password']);
});
