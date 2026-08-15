<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\ReportFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * An audit row written every time a report is generated, capturing who ran
 * it, when, and with exactly which filters - so a report can be explained and
 * reproduced after the fact.
 */
class Report extends Model
{
    /** @use HasFactory<ReportFactory> */
    use HasFactory;

    use LogsActivity;

    /** @var list<string> */
    protected $fillable = [
        'report_type',
        'parameters',
        'format',
        'row_count',
        'generated_by',
        'generated_at',
        'file_path',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'parameters' => 'array',
            'generated_at' => 'datetime',
            'row_count' => 'integer',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['report_type', 'parameters', 'format', 'row_count'])
            ->dontSubmitEmptyLogs()
            ->useLogName('report');
    }

    /** @return BelongsTo<User, $this> */
    public function generatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    /** @param Builder<Report> $query */
    public function scopeOfType(Builder $query, ?string $type): void
    {
        if ($type === null || $type === '') {
            return;
        }

        $query->where('report_type', $type);
    }
}
