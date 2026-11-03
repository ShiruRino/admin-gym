<?php

namespace App\Livewire\Auth;

use App\Enums\Gender;
use App\Models\Member;
use App\Notifications\MemberTemporaryPasswordNotification;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.auth')]
#[Title('Registrasi Member Baru')]
class MemberRegister extends Component
{
    public string $name = '';

    public string $email = '';

    public string $phone = '';

    public string $password = '';

    public string $password_confirmation = '';

    public ?string $gender = null;

    public ?string $birth_date = null;

    public ?string $address = null;

    public ?Member $registeredMember = null;

    /**
     * Submit member registration form.
     */
    public function register(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'min:2', 'max:150'],
            'email' => ['required', 'string', 'email', 'max:150', 'unique:members,email'],
            'phone' => ['required', 'string', 'min:8', 'max:20', 'unique:members,phone'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
            'password_confirmation' => ['required', 'string'],
            'gender' => ['nullable', Rule::enum(Gender::class)],
            'birth_date' => ['nullable', 'date', 'before:today'],
            'address' => ['nullable', 'string', 'max:500'],
        ]);

        $memberCode = Member::generateMemberCode();

        $member = Member::create([
            'member_code' => $memberCode,
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'password' => $validated['password'],
            'gender' => $validated['gender'],
            'birth_date' => $validated['birth_date'],
            'address' => $validated['address'],
            'join_date' => Carbon::today(),
            'is_active' => true,
        ]);

        $member->notify(new MemberTemporaryPasswordNotification($validated['password'], isNewRegistration: true));

        $this->registeredMember = $member;

        Flux::toast(variant: 'success', text: 'Pendaftaran member berhasil! Rincian akun telah dikirim ke email Anda.');
    }

    public function render(): View
    {
        return view('livewire.auth.member-register');
    }
}
