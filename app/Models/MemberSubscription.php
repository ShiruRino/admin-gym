<?php

namespace App\Models;

use App\Enums\SubscriptionStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $member_id
 * @property int|null $invoice_id
 * @property int|null $plan_id
 * @property Carbon $start_date
 * @property Carbon $end_date
 * @property SubscriptionStatus $status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'member_id',
    'invoice_id',
    'plan_id',
    'start_date',
    'end_date',
    'status',
])]
class MemberSubscription extends Model
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
            'start_date' => 'date',
            'end_date' => 'date',
            'status' => SubscriptionStatus::class,
        ];
    }

    /**
     * Member associated with the subscription.
     *
     * @return BelongsTo<Member, $this>
     */
    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    /**
     * Invoice for this subscription.
     *
     * @return BelongsTo<Invoice, $this>
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /**
     * Plan for this subscription.
     *
     * @return BelongsTo<Plan, $this>
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    /**
     * Scope for currently active subscriptions.
     *
     * @param  Builder<MemberSubscription>  $query
     * @return Builder<MemberSubscription>
     */
    #[Scope]
    protected function active(Builder $query): Builder
    {
        return $query->where('status', SubscriptionStatus::Active)
            ->where('end_date', '>=', Carbon::today());
    }

    /**
     * Scope for subscriptions expiring in next N days.
     *
     * @param  Builder<MemberSubscription>  $query
     * @return Builder<MemberSubscription>
     */
    #[Scope]
    protected function expiringSoon(Builder $query, int $days = 7): Builder
    {
        $today = Carbon::today();
        $limit = Carbon::today()->addDays($days);

        return $query->where('status', SubscriptionStatus::Active)
            ->whereBetween('end_date', [$today, $limit]);
    }
}
