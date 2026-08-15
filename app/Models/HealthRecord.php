<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\HealthRecordType;
use Database\Factories\HealthRecordFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class HealthRecord extends Model
{
    /** @use HasFactory<HealthRecordFactory> */
    use HasFactory;

    use LogsActivity;
    use SoftDeletes;

    /** Days ahead that counts as "coming up soon" on the vaccination schedule. */
    public const UPCOMING_WINDOW_DAYS = 30;

    /** @var list<string> */
    protected $fillable = [
        'broodcock_id',
        'record_type',
        'product_name',
        'dosage',
        'checkup_date',
        'next_due_date',
        'condition',
        'remarks',
        'recorded_by',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'record_type' => HealthRecordType::class,
            'checkup_date' => 'date',
            'next_due_date' => 'date',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'broodcock_id', 'record_type', 'product_name', 'dosage',
                'checkup_date', 'next_due_date', 'condition', 'remarks',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('health');
    }

    // -----------------------------------------------------------------
    // Schedule state
    // -----------------------------------------------------------------

    public function isOverdue(): bool
    {
        return $this->next_due_date !== null
            && $this->next_due_date->isBefore(today());
    }

    public function isDueSoon(): bool
    {
        if ($this->next_due_date === null || $this->isOverdue()) {
            return false;
        }

        return $this->next_due_date->lessThanOrEqualTo(today()->addDays(self::UPCOMING_WINDOW_DAYS));
    }

    /** Plain-language schedule state for the UI. */
    public function scheduleState(): string
    {
        return match (true) {
            $this->next_due_date === null => 'No follow-up needed',
            $this->isOverdue() => 'Overdue',
            $this->isDueSoon() => 'Due soon',
            default => 'Scheduled',
        };
    }

    public function daysUntilDue(): ?int
    {
        return $this->next_due_date === null
            ? null
            : (int) today()->diffInDays($this->next_due_date, false);
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

    /** @param Builder<HealthRecord> $query */
    public function scopeOverdue(Builder $query): void
    {
        $query->whereNotNull('next_due_date')
            ->whereDate('next_due_date', '<', today());
    }

    /** @param Builder<HealthRecord> $query */
    public function scopeDueSoon(Builder $query, int $days = self::UPCOMING_WINDOW_DAYS): void
    {
        $query->whereNotNull('next_due_date')
            ->whereDate('next_due_date', '>=', today())
            ->whereDate('next_due_date', '<=', today()->addDays($days));
    }

    /** @param Builder<HealthRecord> $query */
    public function scopeOfType(Builder $query, HealthRecordType|string|null $type): void
    {
        if ($type === null || $type === '') {
            return;
        }

        $query->where('record_type', $type instanceof HealthRecordType ? $type->value : $type);
    }

    /** @param Builder<HealthRecord> $query */
    public function scopeBetween(Builder $query, ?string $from, ?string $to): void
    {
        if ($from !== null && $from !== '') {
            $query->whereDate('checkup_date', '>=', $from);
        }

        if ($to !== null && $to !== '') {
            $query->whereDate('checkup_date', '<=', $to);
        }
    }
}
