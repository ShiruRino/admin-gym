<?php

namespace App\Models;

use App\Enums\PlanType;
use Database\Factories\PlanFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property PlanType $type
 * @property int|null $duration_days
 * @property int|null $pt_sessions_count
 * @property string $price
 * @property string|null $description
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'name',
    'type',
    'duration_days',
    'pt_sessions_count',
    'price',
    'description',
    'is_active',
])]
class Plan extends Model
{
    /** @use HasFactory<PlanFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => PlanType::class,
            'duration_days' => 'integer',
            'pt_sessions_count' => 'integer',
            'price' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Scope for active plans.
     *
     * @param  Builder<Plan>  $query
     * @return Builder<Plan>
     */
    #[Scope]
    protected function active(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for membership plans.
     *
     * @param  Builder<Plan>  $query
     * @return Builder<Plan>
     */
    #[Scope]
    protected function membership(Builder $query): Builder
    {
        return $query->where('type', PlanType::Membership);
    }

    /**
     * Scope for PT session plans.
     *
     * @param  Builder<Plan>  $query
     * @return Builder<Plan>
     */
    #[Scope]
    protected function ptSession(Builder $query): Builder
    {
        return $query->where('type', PlanType::PtSession);
    }

    /**
     * Scope for bundle plans.
     *
     * @param  Builder<Plan>  $query
     * @return Builder<Plan>
     */
    #[Scope]
    protected function bundle(Builder $query): Builder
    {
        return $query->where('type', PlanType::Bundle);
    }

    /**
     * Invoices associated with this plan.
     *
     * @return HasMany<Invoice, $this>
     */
    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    /**
     * Subscriptions activated with this plan.
     *
     * @return HasMany<MemberSubscription, $this>
     */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(MemberSubscription::class);
    }
}
