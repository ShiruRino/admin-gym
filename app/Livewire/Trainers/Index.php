<?php

namespace App\Livewire\Trainers;

use App\Models\Trainer;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

#[Title('Master Trainer')]
class Index extends Component
{
    use WithFileUploads, WithPagination;

    public string $search = '';

    public string $statusFilter = 'all';

    public string $sortBy = 'created_at';

    public string $sortDirection = 'desc';

    public bool $showFormModal = false;

    public bool $showDetailModal = false;

    public ?int $selectedTrainerId = null;

    public ?Trainer $detailTrainer = null;

    public string $name = '';

    public string $phone = '';

    public string $specialty = '';

    public bool $is_active = true;

    /** @var mixed */
    public $photo = null;

    public ?string $existingPhotoUrl = null;

    /**
     * Reset pagination when search or filter updates.
     */
    public function updatingSearch(): void
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
        if (! in_array($column, ['name', 'specialty', 'phone', 'pt_session_logs_count', 'is_active', 'created_at'], true)) {
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
     * Open form modal for creating a new trainer.
     */
    public function openCreateModal(): void
    {
        $this->resetForm();
        $this->is_active = true;
        $this->showFormModal = true;
    }

    /**
     * Open form modal for editing a trainer.
     */
    public function openEditModal(int $id): void
    {
        $trainer = Trainer::findOrFail($id);

        $this->selectedTrainerId = $trainer->id;
        $this->name = $trainer->name;
        $this->phone = $trainer->phone;
        $this->specialty = $trainer->specialty;
        $this->is_active = $trainer->is_active;
        $this->existingPhotoUrl = $trainer->photo_url;

        $this->showFormModal = true;
    }

    /**
     * Remove photo from trainer.
     */
    public function removePhoto(): void
    {
        if ($this->selectedTrainerId) {
            $trainer = Trainer::findOrFail($this->selectedTrainerId);
            if ($trainer->photo_path && Storage::disk('public')->exists($trainer->photo_path)) {
                Storage::disk('public')->delete($trainer->photo_path);
            }
            $trainer->update(['photo_path' => null]);
        }

        $this->photo = null;
        $this->existingPhotoUrl = null;
        Flux::toast(variant: 'success', text: 'Foto trainer berhasil dihapus.');
    }

    /**
     * Open detail modal for a trainer.
     */
    public function openDetailModal(int $id): void
    {
        $this->detailTrainer = Trainer::with([
            'ptSessionLogs' => fn ($q) => $q->with('member')->latest()->limit(10),
        ])->findOrFail($id);

        $this->showDetailModal = true;
    }

    /**
     * Save or update trainer.
     */
    public function save(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'min:2', 'max:150'],
            'phone' => [
                'required',
                'string',
                'min:8',
                'max:20',
                Rule::unique('trainers', 'phone')->ignore($this->selectedTrainerId),
            ],
            'specialty' => ['required', 'string', 'max:100'],
            'is_active' => ['boolean'],
            'photo' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:2048'],
        ]);

        if ($this->photo) {
            $photoPath = $this->photo->store('trainers/photos', 'public');
            $validated['photo_path'] = $photoPath;
        }

        unset($validated['photo']);

        if ($this->selectedTrainerId) {
            $trainer = Trainer::findOrFail($this->selectedTrainerId);
            if (isset($validated['photo_path']) && $trainer->photo_path && Storage::disk('public')->exists($trainer->photo_path)) {
                Storage::disk('public')->delete($trainer->photo_path);
            }
            $trainer->update($validated);
            Flux::toast(variant: 'success', text: 'Data trainer berhasil diperbarui.');
        } else {
            Trainer::create($validated);
            Flux::toast(variant: 'success', text: 'Trainer baru berhasil ditambahkan.');
        }

        $this->showFormModal = false;
        $this->resetForm();
    }

    /**
     * Toggle active state for a trainer.
     */
    public function toggleActive(int $id): void
    {
        $trainer = Trainer::findOrFail($id);
        $trainer->is_active = ! $trainer->is_active;
        $trainer->save();

        $statusText = $trainer->is_active ? 'diaktifkan' : 'dinonaktifkan';
        Flux::toast(variant: 'success', text: "Trainer {$trainer->name} berhasil {$statusText}.");
    }

    /**
     * Reset form fields.
     */
    protected function resetForm(): void
    {
        $this->reset([
            'selectedTrainerId',
            'name',
            'phone',
            'specialty',
            'is_active',
            'photo',
            'existingPhotoUrl',
        ]);
        $this->resetValidation();
    }

    /**
     * Get paginated trainers list.
     *
     * @return LengthAwarePaginator<Trainer>
     */
    #[Computed]
    public function trainers(): LengthAwarePaginator
    {
        return Trainer::query()
            ->withCount('ptSessionLogs')
            ->when($this->search !== '', function (Builder $query) {
                $term = '%'.$this->search.'%';
                $query->where(function (Builder $q) use ($term) {
                    $q->where('name', 'like', $term)
                        ->orWhere('phone', 'like', $term)
                        ->orWhere('specialty', 'like', $term);
                });
            })
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
        return view('livewire.trainers.index');
    }
}
