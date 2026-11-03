<?php

namespace App\Services;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\SubscriptionStatus;
use App\Models\Invoice;
use App\Models\Member;
use App\Models\MemberPtQuota;
use App\Models\MemberSubscription;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class TransactionService
{
    /**
     * Create an invoice and process subscription / PT quota activation.
     *
     * @param  array{
     *     member_id: int,
     *     plan_id: int,
     *     amount: float|numeric-string,
     *     payment_method: PaymentMethod|string,
     *     payment_status?: PaymentStatus|string,
     *     notes?: string|null,
     *     paid_at?: Carbon|string|null,
     * }  $data
     */
    public function createTransaction(array $data, ?User $staff = null): Invoice
    {
        return DB::transaction(function () use ($data, $staff) {
            $member = Member::findOrFail($data['member_id']);
            $plan = Plan::findOrFail($data['plan_id']);

            $paymentMethod = $data['payment_method'] instanceof PaymentMethod
                ? $data['payment_method']
                : PaymentMethod::from($data['payment_method']);

            $paymentStatus = isset($data['payment_status'])
                ? ($data['payment_status'] instanceof PaymentStatus
                    ? $data['payment_status']
                    : PaymentStatus::from($data['payment_status']))
                : PaymentStatus::Paid;

            $paidAt = $paymentStatus === PaymentStatus::Paid
                ? ($data['paid_at'] ?? Carbon::now())
                : null;

            $invoice = Invoice::create([
                'invoice_number' => Invoice::generateInvoiceNumber(),
                'member_id' => $member->id,
                'plan_id' => $plan->id,
                'amount' => $data['amount'],
                'payment_method' => $paymentMethod,
                'payment_status' => $paymentStatus,
                'paid_at' => $paidAt,
                'received_by_user_id' => $staff?->id ?? auth()->id(),
                'notes' => $data['notes'] ?? null,
            ]);

            // When paid, activate subscription and/or PT quota according to plan
            if ($paymentStatus === PaymentStatus::Paid) {
                $this->applyPlanBenefits($member, $plan, $invoice);
            }

            return $invoice->load(['member', 'plan', 'subscription', 'ptQuota', 'receivedBy']);
        });
    }

    /**
     * Apply plan benefits (accumulative subscription days and/or PT quota).
     */
    protected function applyPlanBenefits(Member $member, Plan $plan, Invoice $invoice): void
    {
        // 1. Membership duration extension (accumulative)
        if ($plan->duration_days && $plan->duration_days > 0) {
            $lastActiveSub = MemberSubscription::query()
                ->where('member_id', $member->id)
                ->where('status', SubscriptionStatus::Active)
                ->where('end_date', '>=', Carbon::today())
                ->orderByDesc('end_date')
                ->first();

            if ($lastActiveSub) {
                $startDate = $lastActiveSub->end_date->copy()->addDay();
            } else {
                $startDate = Carbon::today();
            }

            $endDate = $startDate->copy()->addDays($plan->duration_days)->subDay();

            MemberSubscription::create([
                'member_id' => $member->id,
                'invoice_id' => $invoice->id,
                'plan_id' => $plan->id,
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
                'status' => SubscriptionStatus::Active,
            ]);

            if (! $member->is_active) {
                $member->update(['is_active' => true]);
            }
        }

        // 2. Personal Trainer Quota allocation
        if ($plan->pt_sessions_count && $plan->pt_sessions_count > 0) {
            MemberPtQuota::create([
                'member_id' => $member->id,
                'invoice_id' => $invoice->id,
                'total_sessions' => $plan->pt_sessions_count,
                'used_sessions' => 0,
                'remaining_sessions' => $plan->pt_sessions_count,
                'is_active' => true,
            ]);
        }
    }
}
