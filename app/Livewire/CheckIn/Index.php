<?php

namespace App\Livewire\CheckIn;

use App\Models\Attendance;
use App\Models\Member;
use App\Services\AttendanceService;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Presensi & Check-in')]
class Index extends Component
{
    use WithPagination;

    // Search and Input State
    public string $searchInput = '';

    public ?int $selectedMemberId = null;

    public ?string $checkInNotes = null;

    public bool $allowExpiredOverride = false;

    // Filter today's log
    public string $todaySearch = '';

    // Camera scanner modal
    public bool $showCameraModal = false;

    // Last checked in member for success card
    public ?array $lastCheckedInMember = null;

    public function updatingTodaySearch(): void
    {
        $this->resetPage('attendancesPage');
    }

    /**
     * React when search input changes.
     */
    public function updatedSearchInput(string $value, AttendanceService $attendanceService): void
    {
        $term = trim($value);

        if ($term === '') {
            $this->selectedMemberId = null;

            return;
        }

        // If an exact member code or exact phone is matched, select immediately!
        $exactMember = Member::where('member_code', $term)
            ->orWhere('phone', $term)
            ->first();

        if ($exactMember) {
            $this->selectedMemberId = $exactMember->id;
        }
    }

    /**
     * Handle scan / enter event from physical scanner or keyboard.
     */
    public function handleScanOrEnter(AttendanceService $attendanceService): void
    {
        $term = trim($this->searchInput);

        if (! $this->selectedMemberId && $term !== '') {
            $member = $attendanceService->resolveMember($term);
            if ($member) {
                $this->selectedMemberId = $member->id;
            } else {
                Flux::toast(variant: 'danger', text: "Member dengan kode/nama '{$term}' tidak ditemukan.");

                return;
            }
        }

        if ($this->selectedMemberId) {
            $this->executeCheckIn($attendanceService);
        }
    }

    /**
     * Handle scan result from device camera.
     */
    public function handleCameraScan(string $scannedCode, AttendanceService $attendanceService): void
    {
        $this->showCameraModal = false;
        $this->searchInput = trim($scannedCode);

        $member = $attendanceService->resolveMember($this->searchInput);
        if ($member) {
            $this->selectedMemberId = $member->id;
            Flux::toast(variant: 'success', text: "Member {$member->name} berhasil discan.");
        } else {
            Flux::toast(variant: 'danger', text: "Kode QR '{$this->searchInput}' tidak terdaftar pada sistem.");
        }
    }

    /**
     * Select member from search dropdown or list.
     */
    public function selectMember(int $id): void
    {
        $this->selectedMemberId = $id;
        $this->searchInput = '';
    }

    /**
     * Clear selected member and input.
     */
    public function clearSelection(): void
    {
        $this->selectedMemberId = null;
        $this->searchInput = '';
        $this->checkInNotes = null;
        $this->allowExpiredOverride = false;
    }

    /**
     * Execute check-in for the selected member.
     */
    public function executeCheckIn(AttendanceService $attendanceService): void
    {
        if (! $this->selectedMemberId) {
            Flux::toast(variant: 'warning', text: 'Pilih atau scan member terlebih dahulu.');

            return;
        }

        $member = Member::find($this->selectedMemberId);
        if (! $member) {
            Flux::toast(variant: 'danger', text: 'Data member tidak ditemukan.');
            $this->clearSelection();

            return;
        }

        try {
            $attendance = $attendanceService->checkIn(
                $member,
                auth()->user(),
                $this->checkInNotes,
                $this->allowExpiredOverride
            );

            $this->lastCheckedInMember = [
                'name' => $member->name,
                'member_code' => $member->member_code,
                'time' => $attendance->check_in_time->format('H:i'),
                'status' => $member->membership_status,
                'plan_name' => $member->activeSubscription?->plan?->name ?? 'Langganan',
            ];

            Flux::toast(
                variant: 'success',
                text: "Check-in berhasil! Selamat datang, {$member->name}."
            );

            $this->dispatch('play-checkin-chime');
            $this->clearSelection();
            $this->resetPage('attendancesPage');
        } catch (\InvalidArgumentException $e) {
            Flux::toast(variant: 'danger', text: $e->getMessage());
        } catch (\Exception $e) {
            Flux::toast(variant: 'danger', text: 'Terjadi kesalahan sistem saat check-in.');
        }
    }

    /**
     * Currently selected member details.
     */
    #[Computed]
    public function selectedMember(): ?Member
    {
        if (! $this->selectedMemberId) {
            return null;
        }

        return Member::with([
            'activeSubscription.plan',
            'activePtQuotas',
            'attendances' => fn ($q) => $q->latest()->limit(3),
        ])->find($this->selectedMemberId);
    }

    /**
     * Dropdown search suggestions when typing.
     *
     * @return Collection<int, Member>
     */
    #[Computed]
    public function searchSuggestions(): Collection
    {
        $term = trim($this->searchInput);

        if ($term === '' || $this->selectedMemberId) {
            return new Collection;
        }

        return Member::query()
            ->with(['activeSubscription.plan'])
            ->where(function (Builder $q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                    ->orWhere('member_code', 'like', "%{$term}%")
                    ->orWhere('phone', 'like', "%{$term}%");
            })
            ->limit(6)
            ->get();
    }

    /**
     * Today's attendance list.
     *
     * @return LengthAwarePaginator<Attendance>
     */
    #[Computed]
    public function todayAttendances(): LengthAwarePaginator
    {
        return Attendance::query()
            ->with(['member.activeSubscription.plan', 'verifiedBy'])
            ->whereDate('check_in_time', now()->toDateString())
            ->when($this->todaySearch !== '', function (Builder $query) {
                $term = '%'.$this->todaySearch.'%';
                $query->whereHas('member', function (Builder $mQ) use ($term) {
                    $mQ->where('name', 'like', $term)
                        ->orWhere('member_code', 'like', $term);
                });
            })
            ->latest('check_in_time')
            ->paginate(15, ['*'], 'attendancesPage');
    }

    /**
     * Count of total check-ins today.
     */
    #[Computed]
    public function todayCount(): int
    {
        return Attendance::query()
            ->whereDate('check_in_time', now()->toDateString())
            ->count();
    }

    public function render(): View
    {
        return view('livewire.check-in.index');
    }
}
