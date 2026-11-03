<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Member;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class AttendanceService
{
    /**
     * Resolve a Member model from a Member instance, ID, or search code/phone/name.
     */
    public function resolveMember(Member|int|string $memberOrCode): ?Member
    {
        if ($memberOrCode instanceof Member) {
            return $memberOrCode->loadMissing(['activeSubscription.plan', 'activePtQuotas']);
        }

        if (is_int($memberOrCode) || (is_numeric($memberOrCode) && strlen((string) $memberOrCode) <= 8)) {
            $member = Member::with(['activeSubscription.plan', 'activePtQuotas'])->find((int) $memberOrCode);
            if ($member) {
                return $member;
            }
        }

        $code = trim((string) $memberOrCode);

        // 1. Try exact member code match (case-insensitive)
        $member = Member::with(['activeSubscription.plan', 'activePtQuotas'])
            ->where('member_code', $code)
            ->first();

        if ($member) {
            return $member;
        }

        // 2. Try exact phone match
        $member = Member::with(['activeSubscription.plan', 'activePtQuotas'])
            ->where('phone', $code)
            ->first();

        if ($member) {
            return $member;
        }

        // 3. Try name match
        return Member::with(['activeSubscription.plan', 'activePtQuotas'])
            ->where('name', 'like', $code)
            ->first();
    }

    /**
     * Check if the member has already checked in today.
     */
    public function hasCheckedInToday(Member|int $member): bool
    {
        $memberId = $member instanceof Member ? $member->id : $member;

        return Attendance::query()
            ->where('member_id', $memberId)
            ->whereDate('check_in_time', Carbon::today())
            ->exists();
    }

    /**
     * Check in a member and record attendance.
     */
    public function checkIn(
        Member|int|string $memberOrCode,
        User|int $staff,
        ?string $notes = null,
        bool $allowExpiredOverride = false
    ): Attendance {
        $member = $this->resolveMember($memberOrCode);

        if (! $member) {
            throw new InvalidArgumentException('Data member tidak ditemukan dalam sistem.');
        }

        if (! $member->is_active) {
            throw new InvalidArgumentException("Member {$member->name} berstatus non-aktif.");
        }

        $status = $member->membership_status;

        if ($status === 'expired' && ! $allowExpiredOverride) {
            throw new InvalidArgumentException("Paket membership {$member->name} sudah kedaluwarsa. Perlu perpanjangan langganan sebelum check-in.");
        }

        return DB::transaction(function () use ($member, $staff, $notes) {
            $staffId = is_int($staff) ? $staff : $staff->id;

            return Attendance::create([
                'member_id' => $member->id,
                'check_in_time' => Carbon::now(),
                'verified_by_user_id' => $staffId,
                'notes' => $notes,
            ])->load(['member.activeSubscription.plan', 'verifiedBy']);
        });
    }

    /**
     * Count total attendances recorded today.
     */
    public function getTodayAttendancesCount(): int
    {
        return Attendance::query()
            ->whereDate('check_in_time', Carbon::today())
            ->count();
    }
}
