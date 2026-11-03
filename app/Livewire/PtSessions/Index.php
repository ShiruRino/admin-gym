<?php

namespace App\Livewire\PtSessions;

use App\Models\Member;
use App\Models\MemberPtQuota;
use App\Models\PtSessionLog;
use App\Models\Trainer;
use App\Services\PtQuotaService;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Sesi Personal Trainer')]
class Index extends Component
{
    use WithPagination;

    public string $activeTab = 'quotas'; // 'quotas' or 'logs'

    // Quotas tab state
    public string $quotaSearch = '';

    public string $quotaStatusFilter = 'available'; // 'all', 'available', 'depleted'

    public string $quotaSortBy = 'created_at';

    public string $quotaSortDirection = 'desc';

    // Logs tab state
    public string $logSearch = '';

    public ?int $logTrainerFilter = null;

    public string $logSortBy = 'used_at';

    public string $logSortDirection = 'desc';

    // Modal Deduct Session
    public bool $showDeductModal = false;

    public ?int $selectedMemberId = null;

    public ?int $selectedQuotaId = null;

    public ?int $selectedTrainerId = null;

    public ?string $sessionNotes = null;

    public function updatingQuotaSearch(): void
    {
        $this->resetPage('quotasPage');
    }

    public function updatingQuotaStatusFilter(): void
    {
        $this->resetPage('quotasPage');
    }

    public function updatingLogSearch(): void
    {
        $this->resetPage('logsPage');
    }

    public function updatingLogTrainerFilter(): void
    {
        $this->resetPage('logsPage');
    }

    /**
     * Switch tab.
     */
    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
    }

    /**
     * Sort quotas table.
     */
    public function sortQuotas(string $column): void
    {
        if (! in_array($column, ['remaining_sessions', 'total_sessions', 'created_at'], true)) {
            return;
        }

        if ($this->quotaSortBy === $column) {
            $this->quotaSortDirection = $this->quotaSortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->quotaSortBy = $column;
            $this->quotaSortDirection = 'asc';
        }

        $this->resetPage('quotasPage');
    }

    /**
     * Sort session logs table.
     */
    public function sortLogs(string $column): void
    {
        if (! in_array($column, ['used_at', 'created_at'], true)) {
            return;
        }

        if ($this->logSortBy === $column) {
            $this->logSortDirection = $this->logSortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->logSortBy = $column;
            $this->logSortDirection = 'asc';
        }

        $this->resetPage('logsPage');
    }

    /**
     * Open deduct modal for a member or specific quota.
     */
    public function openDeductModal(?int $id = null): void
    {
        $this->resetDeductForm();
        if ($id) {
            $quota = MemberPtQuota::find($id);
            if ($quota) {
                $this->selectedMemberId = $quota->member_id;
                $this->selectedQuotaId = $quota->id;
            } else {
                $this->selectedMemberId = $id;
            }
        }
        $this->showDeductModal = true;
    }

    /**
     * Currently selected member.
     */
    #[Computed]
    public function selectedMember(): ?Member
    {
        return $this->selectedMemberId
            ? Member::with(['activePtQuotas.invoice.plan'])->find($this->selectedMemberId)
            : null;
    }

    /**
     * Currently selected quota (first active batch or explicitly set).
     */
    #[Computed]
    public function selectedQuota(): ?MemberPtQuota
    {
        if ($this->selectedQuotaId) {
            return MemberPtQuota::with(['member', 'invoice.plan'])->find($this->selectedQuotaId);
        }

        return $this->selectedMember?->activePtQuotas?->first();
    }

    /**
     * Available members with active PT quota for selection in deduct modal.
     *
     * @return Collection<int, Member>
     */
    #[Computed]
    public function availableMembers(): Collection
    {
        return Member::query()
            ->whereHas('ptQuotas', function (Builder $q) {
                $q->where('is_active', true)->where('remaining_sessions', '>', 0);
            })
            ->withSum(['ptQuotas as active_remaining_pt_sessions' => function (Builder $q) {
                $q->where('is_active', true)->where('remaining_sessions', '>', 0);
            }], 'remaining_sessions')
            ->withCount(['ptQuotas as active_batches_count' => function (Builder $q) {
                $q->where('is_active', true)->where('remaining_sessions', '>', 0);
            }])
            ->orderBy('name')
            ->get();
    }

    /**
     * Available quotas for selection in deduct modal (legacy compatibility).
     *
     * @return Collection<int, MemberPtQuota>
     */
    #[Computed]
    public function availableQuotas()
    {
        return MemberPtQuota::query()
            ->with(['member'])
            ->where('is_active', true)
            ->where('remaining_sessions', '>', 0)
            ->get();
    }

    /**
     * Active trainers list.
     *
     * @return Collection<int, Trainer>
     */
    #[Computed]
    public function activeTrainers()
    {
        return Trainer::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    /**
     * Execute deduct session using FIFO across active member batches.
     */
    public function deductSession(PtQuotaService $ptQuotaService): void
    {
        if (! $this->selectedMemberId && $this->selectedQuotaId) {
            $this->selectedMemberId = MemberPtQuota::find($this->selectedQuotaId)?->member_id;
        }

        $this->validate([
            'selectedMemberId' => ['required', 'exists:members,id'],
            'selectedTrainerId' => ['required', 'exists:trainers,id'],
            'sessionNotes' => ['nullable', 'string', 'max:255'],
        ], [
            'selectedMemberId.required' => 'Pilih member yang akan dipotong kuota PT-nya.',
            'selectedTrainerId.required' => 'Pilih trainer pendamping sesi latihan.',
        ]);

        $member = Member::findOrFail($this->selectedMemberId);
        $trainer = Trainer::findOrFail($this->selectedTrainerId);

        try {
            $log = $ptQuotaService->deductSession(
                $member,
                $trainer,
                auth()->user(),
                $this->sessionNotes
            );

            $remainingTotal = $member->ptQuotas()->where('is_active', true)->sum('remaining_sessions');

            $this->showDeductModal = false;
            $this->resetDeductForm();

            Flux::toast(
                variant: 'success',
                text: "Sesi latihan member {$member->name} bersama {$trainer->name} berhasil dicatat. Sisa total kuota member: {$remainingTotal} sesi."
            );
        } catch (\Exception $e) {
            Flux::toast(variant: 'danger', text: $e->getMessage());
        }
    }

    /**
     * Reset deduct form.
     */
    protected function resetDeductForm(): void
    {
        $this->reset([
            'selectedMemberId',
            'selectedQuotaId',
            'selectedTrainerId',
            'sessionNotes',
        ]);
        $this->resetValidation();
    }

    /**
     * Paginated PT Quotas.
     *
     * @return LengthAwarePaginator<MemberPtQuota>
     */
    #[Computed]
    public function quotas(): LengthAwarePaginator
    {
        return MemberPtQuota::query()
            ->with(['member', 'invoice.plan'])
            ->when($this->quotaSearch !== '', function (Builder $query) {
                $term = '%'.$this->quotaSearch.'%';
                $query->whereHas('member', function (Builder $mQuery) use ($term) {
                    $mQuery->where('name', 'like', $term)
                        ->orWhere('member_code', 'like', $term)
                        ->orWhere('phone', 'like', $term);
                });
            })
            ->when($this->quotaStatusFilter === 'available', function (Builder $query) {
                $query->where('is_active', true)->where('remaining_sessions', '>', 0);
            })
            ->when($this->quotaStatusFilter === 'depleted', function (Builder $query) {
                $query->where('remaining_sessions', '<=', 0);
            })
            ->when($this->quotaSortBy, function (Builder $query) {
                $query->orderBy($this->quotaSortBy, $this->quotaSortDirection);
            }, function (Builder $query) {
                $query->latest();
            })
            ->paginate(10, ['*'], 'quotasPage');
    }

    /**
     * Paginated Session Logs.
     *
     * @return LengthAwarePaginator<PtSessionLog>
     */
    #[Computed]
    public function sessionLogs(): LengthAwarePaginator
    {
        return PtSessionLog::query()
            ->with(['member', 'trainer', 'recordedBy', 'quota'])
            ->when($this->logSearch !== '', function (Builder $query) {
                $term = '%'.$this->logSearch.'%';
                $query->where(function (Builder $q) use ($term) {
                    $q->whereHas('member', function (Builder $mQ) use ($term) {
                        $mQ->where('name', 'like', $term)
                            ->orWhere('member_code', 'like', $term);
                    })->orWhereHas('trainer', function (Builder $tQ) use ($term) {
                        $tQ->where('name', 'like', $term);
                    })->orWhere('notes', 'like', $term);
                });
            })
            ->when($this->logTrainerFilter, function (Builder $query) {
                $query->where('trainer_id', $this->logTrainerFilter);
            })
            ->when($this->logSortBy, function (Builder $query) {
                $query->orderBy($this->logSortBy, $this->logSortDirection);
            }, function (Builder $query) {
                $query->latest('used_at');
            })
            ->paginate(10, ['*'], 'logsPage');
    }

    public function render(): View
    {
        return view('livewire.pt-sessions.index');
    }
}
