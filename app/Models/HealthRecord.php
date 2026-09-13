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

    /**
     * DEFAULT days ahead that counts as "coming up soon".
     *
     * The live value is config('gfms.vaccination_warning_days') - this is only
     * what that key falls back to. Read it through warningDays() rather than
     * directly, or the badge on a row will disagree with the screen counting
     * the rows.
     */
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

    /**
     * The configured look-ahead window, in days.
     *
     * Farm policy, so it lives in config rather than in this class. Everything
     * that asks "is this due soon?" - the badge on a row, the schedule screen,
     * the compliance report - must go through here or they contradict each
     * other on the same record, which is precisely what happened when this
     * method read the constant directly.
     */
    public static function warningDays(): int
    {
        return (int) config('gfms.vaccination_warning_days', self::UPCOMING_WINDOW_DAYS);
    }

    public function isDueSoon(): bool
    {
        if ($this->next_due_date === null || $this->isOverdue()) {
            return false;
        }

        return $this->next_due_date->lessThanOrEqualTo(today()->addDays(self::warningDays()));
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
    public function scopeDueSoon(Builder $query, ?int $days = null): void
    {
        $days ??= self::warningDays();

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
