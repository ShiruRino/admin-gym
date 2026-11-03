<?php

namespace App\Models;

use Database\Factories\TrainerFactory;
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
 * @property string $phone
 * @property string $specialty
 * @property string|null $photo_path
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'name',
    'phone',
    'specialty',
    'photo_path',
    'is_active',
])]
class Trainer extends Model
{
    /** @use HasFactory<TrainerFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /**
     * Scope for active trainers.
     *
     * @param  Builder<Trainer>  $query
     * @return Builder<Trainer>
     */
    #[Scope]
    protected function active(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * PT Session logs led by this trainer.
     *
     * @return HasMany<PtSessionLog, $this>
     */
    public function ptSessionLogs(): HasMany
    {
        return $this->hasMany(PtSessionLog::class);
    }

    /**
     * Get accessible URL for profile picture.
     */
    public function getPhotoUrlAttribute(): ?string
    {
        return $this->photo_path ? asset('storage/'.$this->photo_path) : null;
    }
}
