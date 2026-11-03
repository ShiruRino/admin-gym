<?php

namespace App\Services;

use App\Models\Member;
use App\Models\MemberPtQuota;
use App\Models\PtSessionLog;
use App\Models\Trainer;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class PtQuotaService
{
    /**
     * Deduct one PT session for the given member (FIFO across active quota batches)
     * or from a specific quota, and record the usage log.
     */
    public function deductSession(
        Member|MemberPtQuota|int $memberOrQuota,
        Trainer|int $trainer,
        User|int $staff,
        ?string $notes = null,
        ?Carbon $usedAt = null
    ): PtSessionLog {
        return DB::transaction(function () use ($memberOrQuota, $trainer, $staff, $notes, $usedAt) {
            $memberId = match (true) {
                $memberOrQuota instanceof Member => $memberOrQuota->id,
                $memberOrQuota instanceof MemberPtQuota => $memberOrQuota->member_id,
                default => (int) $memberOrQuota,
            };

            // Deduct using FIFO: oldest active quota batch with remaining sessions first
            /** @var MemberPtQuota|null $quota */
            $quota = MemberPtQuota::query()
                ->where('member_id', $memberId)
                ->where('is_active', true)
                ->where('remaining_sessions', '>', 0)
                ->orderBy('created_at', 'asc')
                ->orderBy('id', 'asc')
                ->lockForUpdate()
                ->first();

            if (! $quota) {
                throw new InvalidArgumentException('Member ini tidak memiliki kuota sesi Personal Trainer yang aktif atau tersisa.');
            }

            $trainerId = is_int($trainer) ? $trainer : $trainer->id;
            $staffId = is_int($staff) ? $staff : $staff->id;

            $log = PtSessionLog::create([
                'member_pt_quota_id' => $quota->id,
                'member_id' => $quota->member_id,
                'trainer_id' => $trainerId,
                'recorded_by_user_id' => $staffId,
                'used_at' => $usedAt ?? Carbon::now(),
                'notes' => $notes,
            ]);

            $quota->increment('used_sessions');
            $quota->decrement('remaining_sessions');

            if ($quota->fresh()->remaining_sessions <= 0) {
                $quota->update(['is_active' => false]);
            }

            return $log->load(['member', 'trainer', 'recordedBy', 'quota']);
        });
    }
}
