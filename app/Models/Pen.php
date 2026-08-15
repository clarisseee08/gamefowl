<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\PenFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Pen extends Model
{
    /** @use HasFactory<PenFactory> */
    use HasFactory;

    use LogsActivity;
    use SoftDeletes;

    /** @var list<string> */
    protected $fillable = [
        'code',
        'name',
        'location',
        'capacity',
        'notes',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'capacity' => 'integer',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['code', 'name', 'location', 'capacity', 'notes'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('pen');
    }

    /** @return HasMany<Broodcock, $this> */
    public function broodcocks(): HasMany
    {
        return $this->hasMany(Broodcock::class);
    }

    /**
     * How many birds are currently housed here.
     * Prefer the eager-loaded `broodcocks_count` when it is available so this
     * does not fire a query per row on the pen index.
     */
    public function occupancy(): int
    {
        return (int) ($this->broodcocks_count ?? $this->broodcocks()->count());
    }

    public function remainingCapacity(): ?int
    {
        if ($this->capacity <= 0) {
            return null;
        }

        return max(0, $this->capacity - $this->occupancy());
    }

    public function isFull(): bool
    {
        return $this->capacity > 0 && $this->occupancy() >= $this->capacity;
    }

    /** @param Builder<Pen> $query */
    public function scopeSearch(Builder $query, ?string $term): void
    {
        $term = trim((string) $term);

        if ($term === '') {
            return;
        }

        $query->where(function (Builder $q) use ($term): void {
            $q->whereLike('code', "%{$term}%", caseSensitive: false)
                ->orWhereLike('name', "%{$term}%", caseSensitive: false)
                ->orWhereLike('location', "%{$term}%", caseSensitive: false);
        });
    }
}
