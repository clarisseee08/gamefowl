<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\BroodcockClass;
use App\Enums\BroodcockStatus;
use App\Enums\Sex;
use Database\Factories\BroodcockFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * The central entity. Every bird is a Broodcock, male or female - see the
 * `sex` enum. Modelling hens as a separate table would have split the
 * pedigree across two tables for no benefit.
 */
class Broodcock extends Model
{
    /** @use HasFactory<BroodcockFactory> */
    use HasFactory;

    use LogsActivity;
    use SoftDeletes;

    /**
     * Every ancestor relation needed to render a three-generation pedigree
     * tree in a fixed number of queries. Passing this to with() is what keeps
     * the pedigree page off the N+1 path.
     *
     * @var list<string>
     */
    public const PEDIGREE_RELATIONS = [
        'sire', 'dam',
        'sire.sire', 'sire.dam',
        'dam.sire', 'dam.dam',
        'sire.sire.sire', 'sire.sire.dam',
        'sire.dam.sire', 'sire.dam.dam',
        'dam.sire.sire', 'dam.sire.dam',
        'dam.dam.sire', 'dam.dam.dam',
    ];

    /** @var list<string> */
    protected $fillable = [
        'band_number',
        'name',
        'breed',
        'bloodline',
        'class',
        'sex',
        'date_hatched',
        'date_acquired',
        'weight',
        'color',
        'comb_type',
        'leg_color',
        'distinguishing_marks',
        'status',
        'sire_id',
        'dam_id',
        'pen_id',
        'notes',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'class' => BroodcockClass::class,
            'sex' => Sex::class,
            'status' => BroodcockStatus::class,
            'date_hatched' => 'date',
            'date_acquired' => 'date',
            'weight' => 'decimal:2',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'band_number', 'name', 'breed', 'bloodline', 'class', 'sex',
                'date_hatched', 'date_acquired', 'weight', 'color', 'comb_type',
                'leg_color', 'distinguishing_marks', 'status', 'sire_id',
                'dam_id', 'pen_id', 'notes',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('broodcock');
    }

    // -----------------------------------------------------------------
    // Derived age
    //
    // Age is NEVER stored. A stored age is wrong the day after it is
    // written; it is always computed from date_hatched.
    // -----------------------------------------------------------------

    public function ageInDays(): ?int
    {
        return $this->date_hatched?->diffInDays(now());
    }

    public function ageInMonths(): ?int
    {
        return $this->date_hatched?->diffInMonths(now());
    }

    /** Human-readable age, e.g. "2 yrs 3 mos". Null when hatch date is unknown. */
    public function ageLabel(): ?string
    {
        if ($this->date_hatched === null) {
            return null;
        }

        $months = (int) $this->date_hatched->diffInMonths(now());
        $years = intdiv($months, 12);
        $remainder = $months % 12;

        if ($years === 0) {
            return $months === 1 ? '1 mo' : "{$months} mos";
        }

        $label = $years === 1 ? '1 yr' : "{$years} yrs";

        return $remainder === 0 ? $label : "{$label} {$remainder} mos";
    }

    // -----------------------------------------------------------------
    // Relationships
    // -----------------------------------------------------------------

    /** @return BelongsTo<Broodcock, $this> */
    public function sire(): BelongsTo
    {
        return $this->belongsTo(self::class, 'sire_id');
    }

    /** @return BelongsTo<Broodcock, $this> */
    public function dam(): BelongsTo
    {
        return $this->belongsTo(self::class, 'dam_id');
    }

    /** @return BelongsTo<Pen, $this> */
    public function pen(): BelongsTo
    {
        return $this->belongsTo(Pen::class);
    }

    /** @return HasMany<Broodcock, $this> */
    public function offspringAsSire(): HasMany
    {
        return $this->hasMany(self::class, 'sire_id');
    }

    /** @return HasMany<Broodcock, $this> */
    public function offspringAsDam(): HasMany
    {
        return $this->hasMany(self::class, 'dam_id');
    }

    /** @return HasMany<BroodcockPhoto, $this> */
    public function photos(): HasMany
    {
        return $this->hasMany(BroodcockPhoto::class);
    }

    /** @return HasOne<BroodcockPhoto, $this> */
    public function primaryPhoto(): HasOne
    {
        return $this->hasOne(BroodcockPhoto::class)->where('is_primary', true);
    }

    /** @return HasMany<HealthRecord, $this> */
    public function healthRecords(): HasMany
    {
        return $this->hasMany(HealthRecord::class);
    }

    /** @return HasMany<PerformanceRecord, $this> */
    public function performanceRecords(): HasMany
    {
        return $this->hasMany(PerformanceRecord::class);
    }

    /** @return HasOne<MortalityRecord, $this> */
    public function mortalityRecord(): HasOne
    {
        return $this->hasOne(MortalityRecord::class);
    }

    /** @return HasMany<BreedingRecord, $this> */
    public function breedingRecordsAsSire(): HasMany
    {
        return $this->hasMany(BreedingRecord::class, 'sire_id');
    }

    /** @return HasMany<BreedingRecord, $this> */
    public function breedingRecordsAsDam(): HasMany
    {
        return $this->hasMany(BreedingRecord::class, 'dam_id');
    }

    /**
     * All offspring, regardless of which side of the pair this bird was on.
     *
     * @return Collection<int, Broodcock>
     */
    public function offspring(): Collection
    {
        return self::query()
            ->where('sire_id', $this->id)
            ->orWhere('dam_id', $this->id)
            ->orderBy('date_hatched')
            ->get();
    }

    // -----------------------------------------------------------------
    // Convenience
    // -----------------------------------------------------------------

    public function isDeceased(): bool
    {
        return $this->status === BroodcockStatus::Deceased;
    }

    /** Band number if banded, otherwise a clear placeholder - never a blank cell. */
    public function displayBand(): string
    {
        return $this->band_number !== null && $this->band_number !== ''
            ? $this->band_number
            : 'Not yet banded';
    }

    public function displayName(): string
    {
        return $this->band_number !== null && $this->band_number !== ''
            ? "{$this->name} ({$this->band_number})"
            : $this->name;
    }

    // -----------------------------------------------------------------
    // Scopes
    // -----------------------------------------------------------------

    /** @param Builder<Broodcock> $query */
    public function scopeSearch(Builder $query, ?string $term): void
    {
        $term = trim((string) $term);

        if ($term === '') {
            return;
        }

        $query->where(function (Builder $q) use ($term): void {
            $q->where('name', 'ilike', "%{$term}%")
                ->orWhere('band_number', 'ilike', "%{$term}%")
                ->orWhere('breed', 'ilike', "%{$term}%")
                ->orWhere('bloodline', 'ilike', "%{$term}%");
        });
    }

    /** @param Builder<Broodcock> $query */
    public function scopeStatus(Builder $query, BroodcockStatus|string|null $status): void
    {
        if ($status === null || $status === '') {
            return;
        }

        $query->where('status', $status instanceof BroodcockStatus ? $status->value : $status);
    }

    /** @param Builder<Broodcock> $query */
    public function scopeClassGrade(Builder $query, BroodcockClass|string|null $class): void
    {
        if ($class === null || $class === '') {
            return;
        }

        $query->where('class', $class instanceof BroodcockClass ? $class->value : $class);
    }

    /** @param Builder<Broodcock> $query */
    public function scopeSex(Builder $query, Sex|string|null $sex): void
    {
        if ($sex === null || $sex === '') {
            return;
        }

        $query->where('sex', $sex instanceof Sex ? $sex->value : $sex);
    }

    /** @param Builder<Broodcock> $query */
    public function scopeBloodline(Builder $query, ?string $bloodline): void
    {
        if ($bloodline === null || $bloodline === '') {
            return;
        }

        $query->where('bloodline', $bloodline);
    }

    /** @param Builder<Broodcock> $query */
    public function scopeBreed(Builder $query, ?string $breed): void
    {
        if ($breed === null || $breed === '') {
            return;
        }

        $query->where('breed', $breed);
    }

    /** @param Builder<Broodcock> $query */
    public function scopePen(Builder $query, int|string|null $penId): void
    {
        if ($penId === null || $penId === '') {
            return;
        }

        $query->where('pen_id', $penId);
    }

    /** Birds physically present on the farm (excludes sold and deceased). */
    /** @param Builder<Broodcock> $query */
    public function scopeOnFarm(Builder $query): void
    {
        $query->whereIn('status', [
            BroodcockStatus::Active->value,
            BroodcockStatus::Breeding->value,
            BroodcockStatus::Resting->value,
            BroodcockStatus::Retired->value,
        ]);
    }

    /** Birds that may be selected as a parent on a new breeding record. */
    /** @param Builder<Broodcock> $query */
    public function scopeBreedingEligible(Builder $query, ?Sex $sex = null): void
    {
        $query->whereIn('status', [
            BroodcockStatus::Active->value,
            BroodcockStatus::Breeding->value,
            BroodcockStatus::Resting->value,
        ]);

        if ($sex instanceof Sex) {
            $query->where('sex', $sex->value);
        }
    }
}
