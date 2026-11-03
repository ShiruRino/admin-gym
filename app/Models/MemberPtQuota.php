<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $member_id
 * @property int|null $invoice_id
 * @property int $total_sessions
 * @property int $used_sessions
 * @property int $remaining_sessions
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'member_id',
    'invoice_id',
    'total_sessions',
    'used_sessions',
    'remaining_sessions',
    'is_active',
])]
class MemberPtQuota extends Model
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
            'total_sessions' => 'integer',
            'used_sessions' => 'integer',
            'remaining_sessions' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Member associated with the PT quota.
     *
     * @return BelongsTo<Member, $this>
     */
    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    /**
     * Invoice for this PT quota.
     *
     * @return BelongsTo<Invoice, $this>
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /**
     * Session usage logs.
     *
     * @return HasMany<PtSessionLog, $this>
     */
    public function sessionLogs(): HasMany
    {
        return $this->hasMany(PtSessionLog::class);
    }

    /**
     * Scope for active quotas with remaining sessions.
     *
     * @param  Builder<MemberPtQuota>  $query
     * @return Builder<MemberPtQuota>
     */
    #[Scope]
    protected function available(Builder $query): Builder
    {
        return $query->where('is_active', true)
            ->where('remaining_sessions', '>', 0);
    }
}
