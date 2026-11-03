<?php

namespace App\Livewire\Plans;

use App\Enums\PlanType;
use App\Models\Plan;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Master Paket Layanan')]
class Index extends Component
{
    use WithPagination;

    public string $search = '';

    public string $typeFilter = 'all';

    public string $statusFilter = 'all';

    public string $sortBy = 'created_at';

    public string $sortDirection = 'desc';

    public bool $showFormModal = false;

    public bool $showDetailModal = false;

    public ?int $selectedPlanId = null;

    public ?Plan $detailPlan = null;

    public string $name = '';

    public string $type = 'membership';

    public ?int $duration_days = null;

    public ?int $pt_sessions_count = null;

    public string $price = '';

    public ?string $description = null;

    public bool $is_active = true;

    /**
     * Reset pagination when search or filters update.
     */
    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingTypeFilter(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    /**
     * Sort records by the given column.
     */
    public function sort(string $column): void
    {
        if (! in_array($column, ['name', 'type', 'duration_days', 'price', 'subscriptions_count', 'is_active', 'created_at'], true)) {
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
     * Open form modal for creating a new plan.
     */
    public function openCreateModal(): void
    {
        $this->resetForm();
        $this->type = 'membership';
        $this->duration_days = 30;
        $this->is_active = true;
        $this->showFormModal = true;
    }

    /**
     * Open form modal for editing a plan.
     */
    public function openEditModal(int $id): void
    {
        $plan = Plan::findOrFail($id);

        $this->selectedPlanId = $plan->id;
        $this->name = $plan->name;
        $this->type = $plan->type->value;
        $this->duration_days = $plan->duration_days;
        $this->pt_sessions_count = $plan->pt_sessions_count;
        $this->price = (string) (float) $plan->price;
        $this->description = $plan->description;
        $this->is_active = $plan->is_active;

        $this->showFormModal = true;
    }

    /**
     * Open detail modal for a plan.
     */
    public function openDetailModal(int $id): void
    {
        $this->detailPlan = Plan::withCount(['invoices', 'subscriptions'])->findOrFail($id);
        $this->showDetailModal = true;
    }

    /**
     * Save or update plan.
     */
    public function save(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'min:2', 'max:100'],
            'type' => ['required', Rule::enum(PlanType::class)],
            'duration_days' => ['nullable', 'integer', 'min:1'],
            'pt_sessions_count' => ['nullable', 'integer', 'min:1'],
            'price' => ['required', 'numeric', 'min:0'],
            'description' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ]);

        if ($this->selectedPlanId) {
            $plan = Plan::findOrFail($this->selectedPlanId);
            $plan->update($validated);
            Flux::toast(variant: 'success', text: 'Data paket berhasil diperbarui.');
        } else {
            Plan::create($validated);
            Flux::toast(variant: 'success', text: 'Paket baru berhasil ditambahkan.');
        }

        $this->showFormModal = false;
        $this->resetForm();
    }

    /**
     * Toggle active state for a plan.
     */
    public function toggleActive(int $id): void
    {
        $plan = Plan::findOrFail($id);
        $plan->is_active = ! $plan->is_active;
        $plan->save();

        $statusText = $plan->is_active ? 'diaktifkan' : 'dinonaktifkan';
        Flux::toast(variant: 'success', text: "Paket {$plan->name} berhasil {$statusText}.");
    }

    /**
     * Reset form fields.
     */
    protected function resetForm(): void
    {
        $this->reset([
            'selectedPlanId',
            'name',
            'type',
            'duration_days',
            'pt_sessions_count',
            'price',
            'description',
            'is_active',
        ]);
        $this->resetValidation();
    }

    /**
     * Get paginated plans list.
     *
     * @return LengthAwarePaginator<Plan>
     */
    #[Computed]
    public function plans(): LengthAwarePaginator
    {
        return Plan::query()
            ->withCount('subscriptions')
            ->when($this->search !== '', fn (Builder $q) => $q->where('name', 'like', '%'.$this->search.'%'))
            ->when($this->typeFilter !== 'all', fn (Builder $q) => $q->where('type', $this->typeFilter))
            ->when($this->statusFilter === 'active', fn (Builder $q) => $q->where('is_active', true))
            ->when($this->statusFilter === 'inactive', fn (Builder $q) => $q->where('is_active', false))
            ->when($this->sortBy, function (Builder $query) {
                $query->orderBy($this->sortBy, $this->sortDirection);
            }, function (Builder $query) {
                $query->latest();
            })
            ->paginate(10);
    }

    public function render(): View
    {
        return view('livewire.plans.index');
    }
}
