<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PerformanceEventType;
use App\Enums\PerformanceResult;
use Database\Factories\PerformanceRecordFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class PerformanceRecord extends Model
{
    /** @use HasFactory<PerformanceRecordFactory> */
    use HasFactory;

    use LogsActivity;
    use SoftDeletes;

    /** @var list<string> */
    protected $fillable = [
        'broodcock_id',
        'event_date',
        'event_type',
        'weight',
        'result',
        'duration_seconds',
        'rating',
        'remarks',
        'recorded_by',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'event_type' => PerformanceEventType::class,
            'result' => PerformanceResult::class,
            'event_date' => 'date',
            'weight' => 'decimal:2',
            'duration_seconds' => 'integer',
            'rating' => 'integer',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'broodcock_id', 'event_date', 'event_type', 'weight', 'result',
                'duration_seconds', 'rating', 'remarks',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('performance');
    }

    /** Duration formatted as m:ss for display. */
    public function durationLabel(): ?string
    {
        if ($this->duration_seconds === null) {
            return null;
        }

        return sprintf('%d:%02d', intdiv($this->duration_seconds, 60), $this->duration_seconds % 60);
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

    /** Only events where a win/loss/draw is meaningful. */
    /** @param Builder<PerformanceRecord> $query */
    public function scopeContests(Builder $query): void
    {
        $query->where('result', '!=', PerformanceResult::NotApplicable->value);
    }

    /** @param Builder<PerformanceRecord> $query */
    public function scopeOfType(Builder $query, PerformanceEventType|string|null $type): void
    {
        if ($type === null || $type === '') {
            return;
        }

        $query->where('event_type', $type instanceof PerformanceEventType ? $type->value : $type);
    }

    /** @param Builder<PerformanceRecord> $query */
    public function scopeWithResult(Builder $query, PerformanceResult|string|null $result): void
    {
        if ($result === null || $result === '') {
            return;
        }

        $query->where('result', $result instanceof PerformanceResult ? $result->value : $result);
    }

    /** @param Builder<PerformanceRecord> $query */
    public function scopeBetween(Builder $query, ?string $from, ?string $to): void
    {
        if ($from !== null && $from !== '') {
            $query->whereDate('event_date', '>=', $from);
        }

        if ($to !== null && $to !== '') {
            $query->whereDate('event_date', '<=', $to);
        }
    }
}
