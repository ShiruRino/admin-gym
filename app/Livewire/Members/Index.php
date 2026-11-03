<?php

namespace App\Livewire\Members;

use App\Enums\Gender;
use App\Models\Member;
use App\Notifications\MemberTemporaryPasswordNotification;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Master Member')]
class Index extends Component
{
    use WithPagination;

    public string $search = '';

    public string $statusFilter = 'all';

    public string $sortBy = 'created_at';

    public string $sortDirection = 'desc';

    public bool $showFormModal = false;

    public bool $showDetailModal = false;

    public bool $showCardModal = false;

    public ?int $selectedMemberId = null;

    public ?Member $detailMember = null;

    public ?Member $cardMember = null;

    public string $member_code = '';

    public string $name = '';

    public string $phone = '';

    public ?string $email = null;

    public ?string $gender = null;

    public ?string $birth_date = null;

    public ?string $address = null;

    public ?string $emergency_contact = null;

    public ?string $notes = null;

    public string $join_date = '';

    public bool $is_active = true;

    /**
     * Reset pagination when search or filter updates.
     */
    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    /**
     * Sort records by the given column.
     */
    public function sort(string $column): void
    {
        if (! in_array($column, ['member_code', 'name', 'phone', 'email', 'is_active', 'join_date', 'created_at'], true)) {
            return;
        }

        if ($this->sortBy === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDirection = 'asc';
        }

        $this->resetPage();
    }

    /**
     * Open form modal for creating a new member.
     */
    public function openCreateModal(): void
    {
        $this->resetForm();
        $this->member_code = Member::generateMemberCode();
        $this->join_date = now()->format('Y-m-d');
        $this->showFormModal = true;
    }

    /**
     * Open form modal for editing an existing member.
     */
    public function openEditModal(int $id): void
    {
        $member = Member::findOrFail($id);

        $this->selectedMemberId = $member->id;
        $this->member_code = $member->member_code;
        $this->name = $member->name;
        $this->phone = $member->phone;
        $this->email = $member->email;
        $this->gender = $member->gender?->value;
        $this->birth_date = $member->birth_date?->format('Y-m-d');
        $this->address = $member->address;
        $this->emergency_contact = $member->emergency_contact;
        $this->notes = $member->notes;
        $this->join_date = $member->join_date->format('Y-m-d');
        $this->is_active = $member->is_active;

        $this->showFormModal = true;
    }

    /**
     * Open detail modal for a member.
     */
    public function openDetailModal(int $id): void
    {
        $this->detailMember = Member::with([
            'subscriptions.plan',
            'ptQuotas.invoice.plan',
            'ptSessionLogs.trainer',
            'attendances' => fn ($q) => $q->latest()->limit(5),
            'invoices' => fn ($q) => $q->latest()->limit(5),
        ])->findOrFail($id);

        $this->showDetailModal = true;
    }

    /**
     * Open member card modal displaying digital QR card.
     */
    public function openCardModal(int $id): void
    {
        $this->cardMember = Member::with(['activeSubscription.plan', 'activePtQuotas'])->findOrFail($id);
        $this->showCardModal = true;
    }

    /**
     * Save or update member data.
     */
    public function save(): void
    {
        $validated = $this->validate([
            'member_code' => [
                'required',
                'string',
                'max:30',
                Rule::unique('members', 'member_code')->ignore($this->selectedMemberId),
            ],
            'name' => ['required', 'string', 'min:2', 'max:150'],
            'phone' => [
                'required',
                'string',
                'min:8',
                'max:20',
                Rule::unique('members', 'phone')->ignore($this->selectedMemberId),
            ],
            'email' => [
                'nullable',
                'email',
                'max:150',
                Rule::unique('members', 'email')->ignore($this->selectedMemberId),
            ],
            'gender' => ['nullable', Rule::enum(Gender::class)],
            'birth_date' => ['nullable', 'date'],
            'address' => ['nullable', 'string'],
            'emergency_contact' => ['nullable', 'string', 'max:20'],
            'notes' => ['nullable', 'string'],
            'join_date' => ['required', 'date'],
            'is_active' => ['boolean'],
        ]);

        if ($this->selectedMemberId) {
            $member = Member::findOrFail($this->selectedMemberId);
            $member->update($validated);
            Flux::toast(variant: 'success', text: 'Data member berhasil diperbarui.');
        } else {
            $tempPassword = Str::password(8, letters: true, numbers: true, symbols: false);
            $member = Member::create(array_merge($validated, [
                'password' => $tempPassword,
            ]));

            if ($member->email) {
                $member->notify(new MemberTemporaryPasswordNotification($tempPassword, isNewRegistration: true));
                Flux::toast(variant: 'success', text: "Member baru berhasil didaftarkan. Password sementara telah dikirim ke {$member->email}.");
            } else {
                Flux::toast(variant: 'success', text: 'Member baru berhasil didaftarkan.');
            }
        }

        $this->showFormModal = false;
        $this->resetForm();
    }

    /**
     * Reset and send a temporary password to member's email.
     */
    public function sendTemporaryPassword(int $id): void
    {
        $member = Member::findOrFail($id);

        if (blank($member->email)) {
            Flux::toast(variant: 'danger', text: 'Member ini belum memiliki alamat email terdaftar.');

            return;
        }

        $tempPassword = Str::password(8, letters: true, numbers: true, symbols: false);
        $member->password = $tempPassword;
        $member->save();

        $member->notify(new MemberTemporaryPasswordNotification($tempPassword, isNewRegistration: false));

        Flux::toast(variant: 'success', text: "Password sementara baru berhasil dikirim ke {$member->email}.");
    }

    /**
     * Toggle active state for a member.
     */
    public function toggleActive(int $id): void
    {
        $member = Member::findOrFail($id);
        $member->is_active = ! $member->is_active;
        $member->save();

        $statusText = $member->is_active ? 'diaktifkan' : 'dinonaktifkan';
        Flux::toast(variant: 'success', text: "Member {$member->name} berhasil {$statusText}.");
    }

    /**
     * Reset form fields.
     */
    protected function resetForm(): void
    {
        $this->reset([
            'selectedMemberId',
            'member_code',
            'name',
            'phone',
            'email',
            'gender',
            'birth_date',
            'address',
            'emergency_contact',
            'notes',
            'join_date',
            'is_active',
        ]);
        $this->resetValidation();
    }

    /**
     * Get paginated members list.
     *
     * @return LengthAwarePaginator<Member>
     */
    #[Computed]
    public function members(): LengthAwarePaginator
    {
        return Member::query()
            ->with(['activeSubscription.plan'])
            ->withSum(['ptQuotas as active_remaining_pt_sessions' => function (Builder $q) {
                $q->where('is_active', true)->where('remaining_sessions', '>', 0);
            }], 'remaining_sessions')
            ->withSum(['ptQuotas as active_total_pt_sessions' => function (Builder $q) {
                $q->where('is_active', true)->where('remaining_sessions', '>', 0);
            }], 'total_sessions')
            ->when($this->search !== '', function (Builder $query) {
                $term = '%'.$this->search.'%';
                $query->where(function (Builder $q) use ($term) {
                    $q->where('name', 'like', $term)
                        ->orWhere('phone', 'like', $term)
                        ->orWhere('email', 'like', $term)
                        ->orWhere('member_code', 'like', $term);
                });
            })
            ->when($this->statusFilter === 'inactive', function (Builder $query) {
                $query->where('is_active', false);
            })
            ->when($this->statusFilter === 'active', function (Builder $query) {
                $query->where('is_active', true)
                    ->whereHas('subscriptions', function (Builder $q) {
                        $q->where('status', 'active')
                            ->where('end_date', '>=', now()->toDateString());
                    });
            })
            ->when($this->statusFilter === 'expiring_soon', function (Builder $query) {
                $query->where('is_active', true)
                    ->whereHas('subscriptions', function (Builder $q) {
                        $q->where('status', 'active')
                            ->whereBetween('end_date', [now()->toDateString(), now()->addDays(7)->toDateString()]);
                    });
            })
            ->when($this->statusFilter === 'expired', function (Builder $query) {
                $query->where('is_active', true)
                    ->whereDoesntHave('subscriptions', function (Builder $q) {
                        $q->where('status', 'active')
                            ->where('end_date', '>=', now()->toDateString());
                    });
            })
            ->when($this->sortBy, function (Builder $query) {
                $query->orderBy($this->sortBy, $this->sortDirection);
            }, function (Builder $query) {
                $query->latest();
            })
            ->paginate(10);
    }

    public function render(): View
    {
        return view('livewire.members.index');
    }
}
