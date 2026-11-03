<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $member_pt_quota_id
 * @property int $member_id
 * @property int|null $trainer_id
 * @property int|null $recorded_by_user_id
 * @property Carbon $used_at
 * @property string|null $notes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'member_pt_quota_id',
    'member_id',
    'trainer_id',
    'recorded_by_user_id',
    'used_at',
    'notes',
])]
class PtSessionLog extends Model
{
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'used_at' => 'datetime',
        ];
    }

    /**
     * Quota associated with this session.
     *
     * @return BelongsTo<MemberPtQuota, $this>
     */
    public function quota(): BelongsTo
    {
        return $this->belongsTo(MemberPtQuota::class, 'member_pt_quota_id');
    }

    /**
     * Member for this session.
     *
     * @return BelongsTo<Member, $this>
     */
    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    /**
     * Trainer who led the session.
     *
     * @return BelongsTo<Trainer, $this>
     */
    public function trainer(): BelongsTo
    {
        return $this->belongsTo(Trainer::class);
    }

    /**
     * Staff who recorded this session.
     *
     * @return BelongsTo<User, $this>
     */
    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by_user_id');
    }
}
