<?php

namespace App\Livewire\Dashboard;

use App\Enums\PaymentStatus;
use App\Models\Attendance;
use App\Models\Invoice;
use App\Models\Member;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Dashboard')]
class Index extends Component
{
    /**
     * Total active members with valid subscription.
     */
    #[Computed]
    public function totalActiveMembers(): int
    {
        return Member::query()
            ->where('is_active', true)
            ->whereHas('subscriptions', function (Builder $q) {
                $q->where('status', 'active')
                    ->where('end_date', '>=', now()->toDateString());
            })
            ->count();
    }

    /**
     * Count of attendances checked in today.
     */
    #[Computed]
    public function todayCheckIns(): int
    {
        return Attendance::query()
            ->whereDate('check_in_time', now()->toDateString())
            ->count();
    }

    /**
     * Count of members expiring within 7 days.
     */
    #[Computed]
    public function expiringSoonCount(): int
    {
        return Member::query()
            ->where('is_active', true)
            ->whereHas('subscriptions', function (Builder $q) {
                $q->where('status', 'active')
                    ->whereBetween('end_date', [now()->toDateString(), now()->addDays(7)->toDateString()]);
            })
            ->count();
    }

    /**
     * Total paid revenue this month.
     */
    #[Computed]
    public function monthlyRevenue(): int
    {
        return (int) Invoice::query()
            ->where('payment_status', PaymentStatus::Paid->value)
            ->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])
            ->sum('amount');
    }

    /**
     * List of members expiring soon (within next 7 days).
     *
     * @return Collection<int, Member>
     */
    #[Computed]
    public function expiringMembers(): Collection
    {
        return Member::query()
            ->with(['activeSubscription.plan'])
            ->where('is_active', true)
            ->whereHas('subscriptions', function (Builder $q) {
                $q->where('status', 'active')
                    ->whereBetween('end_date', [now()->toDateString(), now()->addDays(7)->toDateString()]);
            })
            ->limit(10)
            ->get();
    }

    /**
     * Generate WhatsApp reminder link for member.
     */
    public function getWhatsAppReminderUrl(Member $member): string
    {
        $phone = preg_replace('/[^0-9]/', '', $member->phone);
        if (str_starts_with($phone, '0')) {
            $phone = '62'.substr($phone, 1);
        }

        $endDate = $member->activeSubscription?->end_date?->format('d M Y') ?? 'segera';
        $message = "Halo kak {$member->name}, kami dari gym menginformasikan bahwa masa aktif membership Anda akan berakhir pada {$endDate}. Yuk segera lakukan perpanjangan di meja kasir/resepsionis. Terima kasih!";

        return 'https://wa.me/'.$phone.'?text='.urlencode($message);
    }

    public function render(): View
    {
        return view('livewire.dashboard.index');
    }
}
