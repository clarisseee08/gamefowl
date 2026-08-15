<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\MortalityRecordFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * One row per bird, ever. Rows are created only through
 * App\Actions\Mortality\RecordMortality, which also flips the bird's status
 * to `deceased` inside the same transaction.
 */
class MortalityRecord extends Model
{
    /** @use HasFactory<MortalityRecordFactory> */
    use HasFactory;

    use LogsActivity;
    use SoftDeletes;

    /** @var list<string> */
    protected $fillable = [
        'broodcock_id',
        'date_of_death',
        'cause_of_death',
        'disposal_method',
        'remarks',
        'recorded_by',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'date_of_death' => 'date',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'broodcock_id', 'date_of_death', 'cause_of_death',
                'disposal_method', 'remarks',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('mortality');
    }

    /** Age at death, derived from the bird's hatch date. */
    public function ageAtDeathInMonths(): ?int
    {
        $hatched = $this->broodcock?->date_hatched;

        if ($hatched === null) {
            return null;
        }

        return (int) $hatched->diffInMonths($this->date_of_death);
    }

    // -----------------------------------------------------------------
    // Relationships
    // -----------------------------------------------------------------

    /** @return BelongsTo<Broodcock, $this> */
    public function broodcock(): BelongsTo
    {
        return $this->belongsTo(Broodcock::class);
    }

    /** @return BelongsTo<User, $this> */
    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    // -----------------------------------------------------------------
    // Scopes
    // -----------------------------------------------------------------

    /** @param Builder<MortalityRecord> $query */
    public function scopeBetween(Builder $query, ?string $from, ?string $to): void
    {
        if ($from !== null && $from !== '') {
            $query->whereDate('date_of_death', '>=', $from);
        }

        if ($to !== null && $to !== '') {
            $query->whereDate('date_of_death', '<=', $to);
        }
    }
}
