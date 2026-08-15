<?php

declare(strict_types=1);

namespace App\Reports;

use App\Enums\PerformanceEventType;
use App\Enums\PerformanceResult;
use App\Models\Broodcock;
use App\Models\PerformanceRecord;
use App\Support\PerformanceSummary;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Performance History - "how have our birds actually performed?"
 *
 * The whole point of this report is the win rate, and the whole risk in the win
 * rate is the denominator. Conditioning sessions and weigh-ins are performance
 * records too, but no one wins a weigh-in: they carry result = 'na' and
 * PerformanceEventType::hasContestResult() is false for them. Counting them
 * would quietly halve every bird's record, so every contest figure below is
 * taken through scopeContests().
 */
final class PerformanceHistoryReport implements ReportDefinition
{
    /** @var array<string, string> */
    private array $filters = [];

    /** Loaded once and shared by rows() and summary() - see records(). */
    private ?Collection $records = null;

    public function key(): string
    {
        return 'performance_history';
    }

    public function title(): string
    {
        return 'Performance History';
    }

    public function description(): string
    {
        return 'Every recorded sparring, derby, conditioning and weigh-in event, with each bird\'s contest record and win rate.';
    }

    /** @param array<string, mixed> $filters */
    public function withFilters(array $filters): static
    {
        // Normalised to trimmed strings and stripped of blanks, so the audit row
        // records the filters that were actually applied rather than a wall of
        // empty inputs the export form happened to submit.
        $this->filters = collect($filters)
            ->only(['from', 'to', 'event_type', 'result', 'bloodline', 'broodcock_id'])
            ->map(fn (mixed $value): string => trim((string) $value))
            ->filter(fn (string $value): bool => $value !== '')
            ->all();

        // Any change of filter invalidates the cached read.
        $this->records = null;

        // ReportController's view payload is fixed for all five reports, so it
        // has no way to hand the PDF this report's second aggregation. Binding
        // the configured instance for the current request lets the template
        // resolve the very object that produced the page - and the template
        // verifies it got that object by comparing filterSummary(), so an
        // unfiltered instance can never quietly supply a filtered table.
        app()->instance(self::class, $this);

        return $this;
    }

    /** @return array<string, string> */
    public function appliedFilters(): array
    {
        return $this->filters;
    }

    public function filterSummary(): string
    {
        $parts = [];

        $from = $this->filters['from'] ?? null;
        $to = $this->filters['to'] ?? null;

        if ($from !== null && $to !== null) {
            $parts[] = "Events from {$from} to {$to}";
        } elseif ($from !== null) {
            $parts[] = "Events from {$from} onwards";
        } elseif ($to !== null) {
            $parts[] = "Events up to {$to}";
        }

        if (isset($this->filters['event_type'])) {
            $parts[] = 'Event type: '.$this->eventTypeLabel($this->filters['event_type']);
        }

        if (isset($this->filters['result'])) {
            $parts[] = 'Result: '.$this->resultLabel($this->filters['result']);
        }

        if (isset($this->filters['bloodline'])) {
            $parts[] = 'Bloodline: '.$this->filters['bloodline'];
        }

        if (isset($this->filters['broodcock_id'])) {
            $parts[] = 'Bird: '.($this->filteredBird()?->displayName() ?? "#{$this->filters['broodcock_id']}");
        }

        return $parts === [] ? 'All performance records, no filters applied' : implode(' · ', $parts);
    }

    /** @return array<string, string> */
    public function columns(): array
    {
        return [
            'event_date' => 'Event Date',
            'band_number' => 'Band Number',
            'bird_name' => 'Bird Name',
            'bloodline' => 'Bloodline',
            'event_type' => 'Event Type',
            'result' => 'Result',
            'weight' => 'Weight (kg)',
            'duration' => 'Duration',
            'rating' => 'Rating (1-5)',
            'recorded_by' => 'Recorded By',
        ];
    }

    /** @return Collection<int, array<string, string|int|float|null>> */
    public function rows(): Collection
    {
        return $this->records()->map(fn (PerformanceRecord $record): array => [
            'event_date' => $record->event_date->format('d M Y'),
            'band_number' => $record->broodcock?->displayBand() ?? '-',
            'bird_name' => $record->broodcock?->name ?? '-',
            'bloodline' => $record->broodcock?->bloodline ?? '-',
            'event_type' => $record->event_type->label(),
            'result' => $record->event_type->hasContestResult()
                ? $record->result->label()
                // A weigh-in has no outcome. Printing "Not applicable" in a
                // Result column reads as a scoring decision; a dash does not.
                : '-',
            'weight' => $record->weight !== null ? number_format((float) $record->weight, 2) : '-',
            'duration' => $record->durationLabel() ?? '-',
            'rating' => $record->rating !== null ? (string) $record->rating : 'Not rated',
            'recorded_by' => $record->recordedBy?->full_name ?? 'Unknown',
        ]);
    }

    /** @return array<string, string|int|float|null> */
    public function summary(): array
    {
        // Built from the rows already in memory rather than a second aggregate
        // query, and by the same class the bird profile screen uses, so the
        // report and the screen can never disagree about a win rate.
        $overall = PerformanceSummary::fromRecords($this->records());

        return [
            'Events' => $overall->totalEvents,
            'Contests' => $overall->totalContests,
            'Record (W-L-D)' => $overall->recordLabel(),
            'Win Rate' => $overall->winRateLabel(),
            'Average Rating' => $overall->averageRatingLabel(),
            'Birds' => $this->records()->pluck('broodcock_id')->unique()->count(),
        ];
    }

    /**
     * Per-bird contest record, aggregated in SQL.
     *
     * One grouped query, not a loop over birds: the report must not get slower
     * as the flock grows, and each round trip to Supabase is a network hop.
     *
     * Win rate is null - never 0 - for a bird with no contests. "Never
     * competed" and "lost every fight" are different claims about a bird, and a
     * farm buying breeding stock pays very different money for each.
     *
     * @return Collection<int, array<string, string|int|float|null>>
     */
    public function perBirdSummary(): Collection
    {
        $rows = $this->baseQuery()
            ->join('broodcocks', 'broodcocks.id', '=', 'performance_records.broodcock_id')
            ->groupBy('broodcocks.id', 'broodcocks.name', 'broodcocks.band_number', 'broodcocks.bloodline')
            ->select('broodcocks.id as broodcock_id', 'broodcocks.name', 'broodcocks.band_number', 'broodcocks.bloodline')
            ->selectRaw('count(*) as total_events')
            // Contests are counted by summing the three real outcomes rather
            // than count(*), so a non-contest row can never reach the divisor.
            ->selectRaw('sum(case when performance_records.result = ? then 1 else 0 end) as wins', [PerformanceResult::Win->value])
            ->selectRaw('sum(case when performance_records.result = ? then 1 else 0 end) as losses', [PerformanceResult::Loss->value])
            ->selectRaw('sum(case when performance_records.result = ? then 1 else 0 end) as draws', [PerformanceResult::Draw->value])
            // count(rating) skips NULLs, so an unrated event lowers neither the
            // numerator nor the denominator - it is absent, not a zero.
            ->selectRaw('count(performance_records.rating) as rated_count')
            ->selectRaw('sum(performance_records.rating) as rating_sum')
            ->orderByDesc('total_events')
            ->orderBy('broodcocks.name')
            ->get();

        return $rows->map(function (object $row): array {
            $wins = (int) $row->wins;
            $losses = (int) $row->losses;
            $draws = (int) $row->draws;
            $contests = $wins + $losses + $draws;
            $ratedCount = (int) $row->rated_count;

            return [
                'bird' => $this->birdLabel((string) $row->name, $row->band_number),
                'bloodline' => $row->bloodline ?? '-',
                'events' => (int) $row->total_events,
                'contests' => $contests,
                'wins' => $wins,
                'losses' => $losses,
                'draws' => $draws,
                // The guard that makes this report honest.
                'win_rate' => $contests === 0 ? null : round($wins / $contests * 100, 1),
                'average_rating' => $ratedCount === 0 ? null : round(((int) $row->rating_sum) / $ratedCount, 2),
            ];
        });
    }

    public function pdfView(): string
    {
        return 'reports.pdf.performance-history';
    }

    // -----------------------------------------------------------------
    // Internals
    // -----------------------------------------------------------------

    /**
     * The filtered records, read once per instance.
     *
     * rows() and summary() are both called by the controller for one export;
     * without this the same rows would be fetched twice.
     *
     * @return Collection<int, PerformanceRecord>
     */
    private function records(): Collection
    {
        return $this->records ??= $this->baseQuery()
            // Every row prints the bird and the recorder. Model::shouldBeStrict()
            // turns a missed eager load into an exception rather than a slow page,
            // which is the correct trade against a network database.
            ->with([
                'broodcock:id,name,band_number,bloodline',
                'recordedBy:id,full_name',
            ])
            ->orderBy('event_date', 'desc')
            // Stable tiebreaker: two events on the same date must not swap
            // places between the CSV and the PDF of the same report.
            ->orderBy('id', 'desc')
            ->get();
    }

    /** @return Builder<PerformanceRecord> */
    private function baseQuery(): Builder
    {
        return PerformanceRecord::query()
            ->between($this->filters['from'] ?? null, $this->filters['to'] ?? null)
            ->ofType($this->filters['event_type'] ?? null)
            ->withResult($this->filters['result'] ?? null)
            ->when(
                isset($this->filters['broodcock_id']),
                fn (Builder $query) => $query->where('performance_records.broodcock_id', $this->filters['broodcock_id']),
            )
            ->when(
                isset($this->filters['bloodline']),
                fn (Builder $query) => $query->whereHas(
                    'broodcock',
                    fn (Builder $birds) => $birds->bloodline($this->filters['bloodline']),
                ),
            );
    }

    private function filteredBird(): ?Broodcock
    {
        if (! isset($this->filters['broodcock_id'])) {
            return null;
        }

        return Broodcock::query()->find($this->filters['broodcock_id']);
    }

    private function birdLabel(string $name, ?string $bandNumber): string
    {
        return $bandNumber !== null && $bandNumber !== ''
            ? "{$name} ({$bandNumber})"
            : "{$name} (Not yet banded)";
    }

    private function eventTypeLabel(string $value): string
    {
        return PerformanceEventType::tryFrom($value)?->label() ?? $value;
    }

    private function resultLabel(string $value): string
    {
        return PerformanceResult::tryFrom($value)?->label() ?? $value;
    }
}
