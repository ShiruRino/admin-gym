<?php

namespace App\Models;

use App\Enums\Gender;
use App\Enums\SubscriptionStatus;
use Database\Factories\MemberFactory;
use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\HasApiTokens;

/**
 * @property int $id
 * @property string $member_code
 * @property string $name
 * @property string $phone
 * @property string|null $email
 * @property string|null $photo_path
 * @property string $password
 * @property Gender|null $gender
 * @property Carbon|null $birth_date
 * @property string|null $address
 * @property string|null $emergency_contact
 * @property string|null $notes
 * @property Carbon $join_date
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'member_code',
    'name',
    'phone',
    'email',
    'photo_path',
    'password',
    'gender',
    'birth_date',
    'address',
    'emergency_contact',
    'notes',
    'join_date',
    'is_active',
])]
#[Hidden(['password'])]
class Member extends Model implements AuthenticatableContract
{
    /** @use HasFactory<MemberFactory> */
    use Authenticatable, HasApiTokens, HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'gender' => Gender::class,
            'birth_date' => 'date',
            'join_date' => 'date',
            'is_active' => 'boolean',
            'password' => 'hashed',
        ];
    }

    /**
     * Subscriptions for this member.
     *
     * @return HasMany<MemberSubscription, $this>
     */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(MemberSubscription::class);
    }

    /**
     * Latest subscription.
     *
     * @return HasOne<MemberSubscription, $this>
     */
    public function latestSubscription(): HasOne
    {
        return $this->hasOne(MemberSubscription::class)->latestOfMany('end_date');
    }

    /**
     * Active subscription.
     *
     * @return HasOne<MemberSubscription, $this>
     */
    public function activeSubscription(): HasOne
    {
        return $this->hasOne(MemberSubscription::class)
            ->where('status', SubscriptionStatus::Active->value)
            ->where('end_date', '>=', Carbon::today())
            ->latestOfMany('end_date');
    }

    /**
     * PT Quotas for this member.
     *
     * @return HasMany<MemberPtQuota, $this>
     */
    public function ptQuotas(): HasMany
    {
        return $this->hasMany(MemberPtQuota::class);
    }

    /**
     * Active PT Quotas with remaining sessions ordered by oldest first (FIFO).
     *
     * @return HasMany<MemberPtQuota, $this>
     */
    public function activePtQuotas(): HasMany
    {
        return $this->hasMany(MemberPtQuota::class)
            ->where('is_active', true)
            ->where('remaining_sessions', '>', 0)
            ->orderBy('created_at', 'asc');
    }

    /**
     * Active PT Quota with remaining sessions (latest for backward compatibility).
     *
     * @return HasOne<MemberPtQuota, $this>
     */
    public function activePtQuota(): HasOne
    {
        return $this->hasOne(MemberPtQuota::class)
            ->where('is_active', true)
            ->where('remaining_sessions', '>', 0)
            ->latestOfMany();
    }

    /**
     * Total remaining PT sessions accumulated across all active batches.
     */
    public function getTotalRemainingPtSessionsAttribute(): int
    {
        if (array_key_exists('active_remaining_pt_sessions', $this->attributes)) {
            return (int) $this->attributes['active_remaining_pt_sessions'];
        }

        if (array_key_exists('pt_quotas_sum_remaining_sessions', $this->attributes)) {
            return (int) $this->attributes['pt_quotas_sum_remaining_sessions'];
        }

        return (int) $this->ptQuotas()->where('is_active', true)->where('remaining_sessions', '>', 0)->sum('remaining_sessions');
    }

    /**
     * Total PT sessions accumulated across all active batches.
     */
    public function getTotalPtSessionsAttribute(): int
    {
        if (array_key_exists('active_total_pt_sessions', $this->attributes)) {
            return (int) $this->attributes['active_total_pt_sessions'];
        }

        if (array_key_exists('pt_quotas_sum_total_sessions', $this->attributes)) {
            return (int) $this->attributes['pt_quotas_sum_total_sessions'];
        }

        return (int) $this->ptQuotas()->where('is_active', true)->where('remaining_sessions', '>', 0)->sum('total_sessions');
    }

    /**
     * PT Session logs.
     *
     * @return HasMany<PtSessionLog, $this>
     */
    public function ptSessionLogs(): HasMany
    {
        return $this->hasMany(PtSessionLog::class);
    }

    /**
     * Attendance history.
     *
     * @return HasMany<Attendance, $this>
     */
    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    /**
     * Invoices for this member.
     *
     * @return HasMany<Invoice, $this>
     */
    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    /**
     * Compute membership status indicator: 'active', 'expiring_soon', 'expired', or 'inactive'.
     */
    public function getMembershipStatusAttribute(): string
    {
        if (! $this->is_active) {
            return 'inactive';
        }

        $subscription = $this->activeSubscription;

        if (! $subscription) {
            return 'expired';
        }

        $today = Carbon::today();
        $daysRemaining = $today->diffInDays($subscription->end_date, false);

        if ($daysRemaining < 0) {
            return 'expired';
        }

        if ($daysRemaining <= 7) {
            return 'expiring_soon';
        }

        return 'active';
    }

    /**
     * Get accessible URL for profile picture.
     */
    public function getPhotoUrlAttribute(): ?string
    {
        return $this->photo_path ? asset('storage/'.$this->photo_path) : null;
    }

    /**
     * Generate sequential member code formatted as GYM-YYMM-XXXX.
     */
    public static function generateMemberCode(?Carbon $date = null): string
    {
        $date ??= Carbon::now();
        $prefix = 'GYM-'.$date->format('ym').'-';

        $lastMember = static::query()
            ->where('member_code', 'like', $prefix.'%')
            ->orderByDesc('member_code')
            ->first();

        if ($lastMember) {
            $lastNumber = (int) substr($lastMember->member_code, -4);
            $nextNumber = str_pad((string) ($lastNumber + 1), 4, '0', STR_PAD_LEFT);
        } else {
            $nextNumber = '0001';
        }

        return $prefix.$nextNumber;
    }
}
