<?php

namespace App\Models;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $invoice_number
 * @property int $member_id
 * @property int|null $plan_id
 * @property string $amount
 * @property PaymentMethod $payment_method
 * @property PaymentStatus $payment_status
 * @property Carbon|null $paid_at
 * @property int|null $received_by_user_id
 * @property string|null $notes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'invoice_number',
    'member_id',
    'plan_id',
    'amount',
    'payment_method',
    'payment_status',
    'paid_at',
    'received_by_user_id',
    'notes',
])]
class Invoice extends Model
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
            'amount' => 'decimal:2',
            'payment_method' => PaymentMethod::class,
            'payment_status' => PaymentStatus::class,
            'paid_at' => 'datetime',
        ];
    }

    /**
     * Member associated with the invoice.
     *
     * @return BelongsTo<Member, $this>
     */
    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    /**
     * Plan purchased in this invoice.
     *
     * @return BelongsTo<Plan, $this>
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    /**
     * Staff member who received the payment.
     *
     * @return BelongsTo<User, $this>
     */
    public function receivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by_user_id');
    }

    /**
     * Subscription record created from this invoice.
     *
     * @return HasOne<MemberSubscription, $this>
     */
    public function subscription(): HasOne
    {
        return $this->hasOne(MemberSubscription::class);
    }

    /**
     * PT Quota created from this invoice.
     *
     * @return HasOne<MemberPtQuota, $this>
     */
    public function ptQuota(): HasOne
    {
        return $this->hasOne(MemberPtQuota::class);
    }

    /**
     * Generate invoice number formatted as INV-YYYYMMDD-XXXX.
     */
    public static function generateInvoiceNumber(?Carbon $date = null): string
    {
        $date ??= Carbon::now();
        $prefix = 'INV-'.$date->format('Ymd').'-';

        $lastInvoice = static::query()
            ->where('invoice_number', 'like', $prefix.'%')
            ->orderByDesc('invoice_number')
            ->first();

        if ($lastInvoice) {
            $lastNumber = (int) substr($lastInvoice->invoice_number, -4);
            $nextNumber = str_pad((string) ($lastNumber + 1), 4, '0', STR_PAD_LEFT);
        } else {
            $nextNumber = '0001';
        }

        return $prefix.$nextNumber;
    }
}
