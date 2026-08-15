<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\BreedingRecordFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class BreedingRecord extends Model
{
    /** @use HasFactory<BreedingRecordFactory> */
    use HasFactory;

    use LogsActivity;
    use SoftDeletes;

    /** @var list<string> */
    protected $fillable = [
        'sire_id',
        'dam_id',
        'mating_date',
        'eggs_set',
        'eggs_fertile',
        'eggs_hatched',
        'offspring_count',
        'notes',
        'recorded_by',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'mating_date' => 'date',
            'eggs_set' => 'integer',
            'eggs_fertile' => 'integer',
            'eggs_hatched' => 'integer',
            'offspring_count' => 'integer',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'sire_id', 'dam_id', 'mating_date', 'eggs_set', 'eggs_fertile',
                'eggs_hatched', 'offspring_count', 'notes',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('breeding');
    }

    // -----------------------------------------------------------------
    // Computed rates
    //
    // These are ALWAYS derived and never stored or hand-entered. A stored
    // rate can silently contradict the egg counts it came from.
    // -----------------------------------------------------------------

    /** Percentage of eggs set that proved fertile. Null when nothing was set. */
    public function fertilityRate(): ?float
    {
        if ($this->eggs_set <= 0) {
            return null;
        }

        return round(($this->eggs_fertile / $this->eggs_set) * 100, 2);
    }

    /**
     * Percentage of FERTILE eggs that hatched.
     *
     * Deliberately measured against fertile eggs, not eggs set: hatchability
     * is a property of incubation, and dividing by eggs set would double-count
     * the infertility already reported by the fertility rate.
     */
    public function hatchRate(): ?float
    {
        if ($this->eggs_fertile <= 0) {
            return null;
        }

        return round(($this->eggs_hatched / $this->eggs_fertile) * 100, 2);
    }

    /** Eggs set through to hatched - the end-to-end yield of the mating. */
    public function overallHatchRate(): ?float
    {
        if ($this->eggs_set <= 0) {
            return null;
        }

        return round(($this->eggs_hatched / $this->eggs_set) * 100, 2);
    }

    /** How many hatched chicks have not yet been registered as broodcocks. */
    public function unregisteredOffspring(): int
    {
        return max(0, $this->eggs_hatched - $this->offspring_count);
    }

    public function hasUnregisteredOffspring(): bool
    {
        return $this->unregisteredOffspring() > 0;
    }

    // -----------------------------------------------------------------
    // Relationships
    // -----------------------------------------------------------------

    /** @return BelongsTo<Broodcock, $this> */
    public function sire(): BelongsTo
    {
        return $this->belongsTo(Broodcock::class, 'sire_id');
    }

    /** @return BelongsTo<Broodcock, $this> */
    public function dam(): BelongsTo
    {
        return $this->belongsTo(Broodcock::class, 'dam_id');
    }

    /** @return BelongsTo<User, $this> */
    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    // -----------------------------------------------------------------
    // Scopes
    // -----------------------------------------------------------------

    /** @param Builder<BreedingRecord> $query */
    public function scopeBetween(Builder $query, ?string $from, ?string $to): void
    {
        if ($from !== null && $from !== '') {
            $query->whereDate('mating_date', '>=', $from);
        }

        if ($to !== null && $to !== '') {
            $query->whereDate('mating_date', '<=', $to);
        }
    }

    /** @param Builder<BreedingRecord> $query */
    public function scopeForPair(Builder $query, int $sireId, int $damId): void
    {
        $query->where('sire_id', $sireId)->where('dam_id', $damId);
    }
}
