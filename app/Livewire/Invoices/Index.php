<?php

namespace App\Livewire\Invoices;

use App\Enums\PaymentStatus;
use App\Models\Invoice;
use App\Models\Member;
use App\Models\Plan;
use App\Services\TransactionService;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Transaksi & Tagihan')]
class Index extends Component
{
    use WithPagination;

    public string $search = '';

    public string $statusFilter = 'all';

    public string $methodFilter = 'all';

    public string $sortBy = 'created_at';

    public string $sortDirection = 'desc';

    public bool $showCreateModal = false;

    public bool $showDetailModal = false;

    public ?Invoice $detailInvoice = null;

    // Form fields for new transaction
    public ?int $member_id = null;

    public ?int $plan_id = null;

    public string $amount = '';

    public string $payment_method = 'cash';

    public ?string $notes = null;

    /**
     * Reset pagination when search or filters update.
     */
    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatingMethodFilter(): void
    {
        $this->resetPage();
    }

    /**
     * Sort records by the given column.
     */
    public function sort(string $column): void
    {
        if (! in_array($column, ['invoice_number', 'amount', 'payment_method', 'payment_status', 'paid_at', 'created_at'], true)) {
            return;
        }

        if ($this->sortBy === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDirection = 'asc';
        }

        $this->resetPage();
    }

    /**
     * Open create transaction modal.
     */
    public function openCreateModal(?int $memberId = null): void
    {
        $this->resetForm();
        if ($memberId) {
            $this->member_id = $memberId;
        }
        $this->showCreateModal = true;
    }

    /**
     * Auto-fill amount when plan is selected.
     */
    public function updatedPlanId(?int $planId): void
    {
        if ($planId) {
            $plan = Plan::find($planId);
            if ($plan) {
                $this->amount = (string) (int) $plan->price;
            }
        }
    }

    /**
     * Open invoice detail and receipt modal.
     */
    public function openDetailModal(int $id): void
    {
        $this->detailInvoice = Invoice::with(['member', 'plan', 'subscription', 'ptQuota', 'receivedBy'])->findOrFail($id);
        $this->showDetailModal = true;
    }

    /**
     * Save new transaction.
     */
    public function save(TransactionService $transactionService): void
    {
        $validated = $this->validate([
            'member_id' => ['required', 'exists:members,id'],
            'plan_id' => ['required', 'exists:plans,id'],
            'amount' => ['required', 'numeric', 'min:0'],
            'payment_method' => ['required', 'in:cash,transfer,qris'],
            'notes' => ['nullable', 'string', 'max:500'],
        ], [
            'member_id.required' => 'Silakan pilih member terlebih dahulu.',
            'plan_id.required' => 'Silakan pilih paket layanan.',
            'amount.required' => 'Nominal pembayaran wajib diisi.',
            'amount.numeric' => 'Nominal harus berupa angka yang valid.',
            'payment_method.required' => 'Pilih metode pembayaran.',
        ]);

        $invoice = $transactionService->createTransaction([
            'member_id' => $validated['member_id'],
            'plan_id' => $validated['plan_id'],
            'amount' => (float) $validated['amount'],
            'payment_method' => $validated['payment_method'],
            'payment_status' => PaymentStatus::Paid,
            'notes' => $validated['notes'] ?? null,
        ], auth()->user());

        $this->showCreateModal = false;
        $this->resetForm();

        Flux::toast(variant: 'success', text: "Transaksi {$invoice->invoice_number} berhasil dicatat & paket diaktifkan.");

        // Automatically show the receipt modal
        $this->openDetailModal($invoice->id);
    }

    /**
     * Reset form fields.
     */
    protected function resetForm(): void
    {
        $this->reset([
            'member_id',
            'plan_id',
            'amount',
            'payment_method',
            'notes',
        ]);
        $this->payment_method = 'cash';
        $this->resetValidation();
    }

    /**
     * Available members list for selection.
     *
     * @return Collection<int, Member>
     */
    #[Computed]
    public function availableMembers()
    {
        return Member::query()
            ->orderBy('name')
            ->get(['id', 'name', 'member_code', 'phone']);
    }

    /**
     * Available plans list for selection.
     *
     * @return Collection<int, Plan>
     */
    #[Computed]
    public function availablePlans()
    {
        return Plan::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    /**
     * Currently selected plan model.
     */
    #[Computed]
    public function selectedPlan(): ?Plan
    {
        return $this->plan_id ? Plan::find($this->plan_id) : null;
    }

    /**
     * Get paginated invoices list.
     *
     * @return LengthAwarePaginator<Invoice>
     */
    #[Computed]
    public function invoices(): LengthAwarePaginator
    {
        return Invoice::query()
            ->with(['member', 'plan', 'receivedBy', 'subscription', 'ptQuota'])
            ->when($this->search !== '', function (Builder $query) {
                $term = '%'.$this->search.'%';
                $query->where(function (Builder $q) use ($term) {
                    $q->where('invoice_number', 'like', $term)
                        ->orWhereHas('member', function (Builder $mQuery) use ($term) {
                            $mQuery->where('name', 'like', $term)
                                ->orWhere('member_code', 'like', $term)
                                ->orWhere('phone', 'like', $term);
                        });
                });
            })
            ->when($this->statusFilter !== 'all', function (Builder $query) {
                $query->where('payment_status', $this->statusFilter);
            })
            ->when($this->methodFilter !== 'all', function (Builder $query) {
                $query->where('payment_method', $this->methodFilter);
            })
            ->when($this->sortBy, function (Builder $query) {
                $query->orderBy($this->sortBy, $this->sortDirection);
            }, function (Builder $query) {
                $query->latest();
            })
            ->paginate(10);
    }

    public function render(): View
    {
        return view('livewire.invoices.index');
    }
}
