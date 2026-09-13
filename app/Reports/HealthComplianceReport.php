<?php

declare(strict_types=1);

namespace App\Reports;

use App\Enums\HealthRecordType;
use App\Models\HealthRecord;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use InvalidArgumentException;

/**
 * Health and Vaccination Compliance.
 *
 * Answers the one question the farm acts on: which birds are up to date on
 * their health programme, and which are overdue?
 *
 * Everything here is derived from `next_due_date` against today - nothing about
 * "overdue" is stored, so this report can never disagree with the records it is
 * built from. The classification rules are the model's (scopeOverdue,
 * scopeDueSoon, isOverdue, isDueSoon), reused rather than restated, so the
 * report and the on-screen schedule cannot drift apart.
 *
 * The boundary that matters: due YESTERDAY is overdue, due TODAY is not. A
 * follow-up scheduled for today has not been missed yet.
 *
 * REMARKS ARE INCLUDED. `health_records.remarks` is internal-only, and so is
 * this report - ReportPolicy::create() denies customers outright, and the
 * controller authorizes before the query runs. The remark is usually the reason
 * a follow-up matters ("reacted to the first dose", "re-dose after moult"), so
 * handing a keeper an overdue list without it would strip the one piece of
 * context that tells them how to act on the row.
 */
final class HealthComplianceReport implements ReportDefinition
{
    /** Values the `compliance` filter accepts. */
    private const COMPLIANCE_STATES = ['overdue', 'due_soon', 'compliant'];

    private ?string $from = null;

    private ?string $to = null;

    private ?HealthRecordType $recordType = null;

    private ?string $compliance = null;

    /**
     * Loaded once and shared by rows(), summary() and countsByType().
     *
     * The controller calls rows() and summary() separately; without this the
     * same result set would be fetched twice, i.e. twice the round trips to
     * Supabase for one PDF.
     *
     * @var EloquentCollection<int, HealthRecord>|null
     */
    private ?EloquentCollection $records = null;

    public function key(): string
    {
        return 'health_compliance';
    }

    public function title(): string
    {
        return 'Health and Vaccination Compliance';
    }

    public function description(): string
    {
        return 'Which birds are up to date on their health programme, and which are overdue.';
    }

    /** @param array<string, mixed> $filters */
    public function withFilters(array $filters): static
    {
        $this->from = $this->normaliseDate($filters['from'] ?? null);
        $this->to = $this->normaliseDate($filters['to'] ?? null);
        $this->recordType = $this->normaliseRecordType($filters['record_type'] ?? null);
        $this->compliance = $this->normaliseCompliance($filters['compliance'] ?? null);

        // Filters changed, so anything already fetched describes the old ones.
        $this->records = null;

        return $this;
    }

    /** @return array<string, mixed> */
    public function appliedFilters(): array
    {
        return array_filter([
            'from' => $this->from,
            'to' => $this->to,
            'record_type' => $this->recordType?->value,
            'compliance' => $this->compliance,

            // Persisted deliberately: the due-soon window is farm policy that
            // can be changed in config, and the same filters run a year later
            // under a different window would produce different numbers. A
            // report that cannot be reproduced cannot be defended.
            'warning_days' => $this->warningDays(),
        ], fn (mixed $value): bool => $value !== null);
    }

    public function filterSummary(): string
    {
        $parts = [];

        if ($this->from !== null && $this->to !== null) {
            $parts[] = 'Check-ups from '.$this->humanDate($this->from).' to '.$this->humanDate($this->to);
        } elseif ($this->from !== null) {
            $parts[] = 'Check-ups from '.$this->humanDate($this->from).' onwards';
        } elseif ($this->to !== null) {
            $parts[] = 'Check-ups up to '.$this->humanDate($this->to);
        } else {
            $parts[] = 'All check-up dates';
        }

        $parts[] = 'Record type: '.($this->recordType?->label() ?? 'All types');
        $parts[] = 'Compliance: '.($this->compliance === null ? 'All' : $this->complianceLabel($this->compliance));
        $parts[] = 'Due-soon window: '.$this->warningDays().' days';

        return implode(' · ', $parts);
    }

    /** @return array<string, string> */
    public function columns(): array
    {
        return [
            'band_number' => 'Band Number',
            'bird_name' => 'Bird Name',
            'record_type' => 'Record Type',
            'product_name' => 'Product',
            'dosage' => 'Dosage',
            'checkup_date' => 'Check-up Date',
            'next_due_date' => 'Next Due Date',
            'schedule_state' => 'Schedule State',
            'days_until_due' => 'Days Until Due (minus = overdue)',
            'recorded_by' => 'Recorded By',
            'remarks' => 'Remarks',
        ];
    }

    /** @return Collection<int, array<string, string|int|float|null>> */
    public function rows(): Collection
    {
        return $this->records()
            ->map(fn (HealthRecord $record): array => [
                'band_number' => $record->broodcock?->displayBand() ?? 'Bird removed',
                'bird_name' => $record->broodcock?->name ?? '—',
                'record_type' => $record->record_type->label(),
                'product_name' => $record->product_name ?? '—',
                'dosage' => $record->dosage ?? '—',
                'checkup_date' => $record->checkup_date?->format('Y-m-d'),
                'next_due_date' => $record->next_due_date?->format('Y-m-d') ?? 'None scheduled',
                'schedule_state' => $record->scheduleState(),
                'days_until_due' => $record->daysUntilDue(),
                'recorded_by' => $record->recordedBy?->full_name ?? 'Not recorded',
                'remarks' => $record->remarks ?? '',
            ])
            ->values();
    }

    /** @return array<string, string|int|float|null> */
    public function summary(): array
    {
        $records = $this->records();

        return [
            'Records in range' => $records->count(),
            'Overdue' => $records->filter(fn (HealthRecord $r): bool => $r->isOverdue())->count(),
            'Due within '.$this->warningDays().' days' => $records
                ->filter(fn (HealthRecord $r): bool => $r->isDueSoon())
                ->count(),

            // Null, not 0. "No bird has a follow-up scheduled" and "every
            // scheduled follow-up was missed" are opposite findings, and a farm
            // acting on a 0% that actually meant "no data" would be chasing
            // birds that were never due.
            'Compliance rate' => $this->complianceRate(),
        ];
    }

    /**
     * Percentage of records WITH a follow-up date that are not overdue.
     *
     * Records with no follow-up date are excluded from both halves of the
     * fraction: a one-off check-up that never needed a second visit is not
     * evidence of compliance, and it is certainly not evidence of failure.
     */
    public function complianceRate(): ?float
    {
        $scheduled = $this->records()->filter(
            fn (HealthRecord $r): bool => $r->next_due_date !== null
        );

        if ($scheduled->isEmpty()) {
            return null;
        }

        $onTrack = $scheduled->reject(fn (HealthRecord $r): bool => $r->isOverdue())->count();

        return round($onTrack / $scheduled->count() * 100, 1);
    }

    /**
     * Row counts per record type, rendered under the main table.
     *
     * Static and derived from the rows rather than from the query, because
     * ReportController hands the PDF template `$rows` but not the report
     * instance - and one implementation the view and the tests both call is
     * worth more than a prettier signature. It also costs no extra query.
     *
     * Every type is listed, including the ones with no records: a farm that has
     * recorded zero dewormings this quarter needs to see that zero, and an
     * absent row reads as "not applicable" rather than "none done".
     *
     * @param  Collection<int, array<string, string|int|float|null>>  $rows
     * @return Collection<int, array{label: string, count: int}>
     */
    public static function countsByType(Collection $rows): Collection
    {
        $counts = $rows->countBy('record_type');

        return collect(HealthRecordType::cases())
            ->map(fn (HealthRecordType $type): array => [
                'label' => $type->label(),
                'count' => (int) $counts->get($type->label(), 0),
            ])
            ->values();
    }

    public function pdfView(): string
    {
        return 'reports.pdf.health-compliance';
    }

    /**
     * The due-soon look-ahead, in days.
     *
     * Delegates rather than re-reading the config key, so the report, the
     * schedule screen and the badge on an individual row cannot disagree about
     * what "due soon" means - they did, before HealthRecord::isDueSoon() was
     * taught to read the config at all.
     */
    public function warningDays(): int
    {
        return HealthRecord::warningDays();
    }

    // -----------------------------------------------------------------
    // Query
    // -----------------------------------------------------------------

    /**
     * The filtered records, fetched at most once.
     *
     * @return EloquentCollection<int, HealthRecord>
     */
    private function records(): EloquentCollection
    {
        return $this->records ??= HealthRecord::query()
            // Both relations are read by every row. Model::shouldBeStrict() is
            // on, so a missed eager load throws rather than quietly issuing one
            // query per bird against a database a network hop away.
            ->with(['broodcock', 'recordedBy'])
            ->between($this->from, $this->to)
            ->ofType($this->recordType)
            ->tap(fn (Builder $query) => $this->applyCompliance($query))
            // Nulls last on both Postgres and SQLite: ASC puts NULLs first on
            // SQLite and last on Postgres, so the ordering is made explicit
            // rather than left to the driver. Then oldest due date first, which
            // is the most overdue bird - the one at most risk - at the top.
            ->orderByRaw('case when next_due_date is null then 1 else 0 end')
            ->orderBy('next_due_date')
            ->orderBy('id')
            ->get();
    }

    /**
     * The three compliance states partition the result set exactly.
     *
     * overdue: due date already passed.
     * due_soon: due today through today + warning window.
     * compliant: everything else - scheduled beyond the window, or no
     *            follow-up needed at all.
     *
     * Every record matches exactly one, so the three filters sum to the
     * unfiltered total and nothing is counted twice.
     *
     * @param  Builder<HealthRecord>  $query
     */
    private function applyCompliance(Builder $query): void
    {
        if ($this->compliance === null) {
            return;
        }

        $windowEnd = today()->addDays($this->warningDays());

        match ($this->compliance) {
            'overdue' => $query->overdue(),
            'due_soon' => $query->dueSoon($this->warningDays()),
            'compliant' => $query->where(function (Builder $inner) use ($windowEnd): void {
                $inner->whereNull('next_due_date')
                    ->orWhereDate('next_due_date', '>', $windowEnd);
            }),
            default => throw new InvalidArgumentException("Unknown compliance state [{$this->compliance}]."),
        };
    }

    // -----------------------------------------------------------------
    // Filter normalisation
    //
    // Filters arrive straight off the query string, so anything unparseable is
    // dropped rather than trusted. A report that silently ignores a nonsense
    // filter is safer than one that errors at the farm's only workstation.
    // -----------------------------------------------------------------

    private function normaliseDate(mixed $value): ?string
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        try {
            return CarbonImmutable::parse(trim($value))->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }

    private function normaliseRecordType(mixed $value): ?HealthRecordType
    {
        if ($value instanceof HealthRecordType) {
            return $value;
        }

        return is_string($value) ? HealthRecordType::tryFrom(trim($value)) : null;
    }

    private function normaliseCompliance(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        return in_array($value, self::COMPLIANCE_STATES, true) ? $value : null;
    }

    private function complianceLabel(string $state): string
    {
        return match ($state) {
            'overdue' => 'Overdue only',
            'due_soon' => 'Due soon only',
            'compliant' => 'Compliant only',
            default => $state,
        };
    }

    private function humanDate(string $date): string
    {
        return CarbonImmutable::parse($date)->format('j F Y');
    }
}
