<?php

declare(strict_types=1);

namespace App\Reports;

use App\Models\BreedingRecord;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Which pairs, and which bloodlines, actually produce.
 *
 * Two groupings are reported from the same filtered set of matings: one row per
 * sire x dam pair (what rows() returns and what CSV exports), and one row per
 * bloodline (bloodlineRows(), rendered as a second table in the PDF). A pair
 * table alone cannot answer "is Sweater worth keeping?" and a bloodline table
 * alone cannot answer "which hen should this cock go back to?".
 *
 * ---------------------------------------------------------------------------
 * THE ARITHMETIC RULE - the only thing in this file that is easy to get wrong.
 *
 * Every aggregate rate here is SUM(numerator) / SUM(denominator). It is NEVER
 * the average of the per-record percentages that BreedingRecord::fertilityRate()
 * returns. AVG(rate) weighs a 2-egg mating exactly as heavily as a 200-egg one:
 * a 2/2 clutch (100%) averaged with a 50/100 clutch (50%) reports 75%, when the
 * farm actually set 102 eggs and got 52 fertile - 51.0%. The naive figure is
 * off by 24 percentage points and always flatters small clutches, which is
 * precisely the mistake a breeder would act on.
 *
 * The SUMs are done by the database (groupBy + selectRaw) so the query cost is
 * constant no matter how many matings exist. Only the final division happens in
 * PHP - deliberately, because SQLite (the test database) does integer division
 * and would truncate 52/102 to 0 where Postgres would not. Dividing in PHP
 * makes production and the test suite agree.
 * ---------------------------------------------------------------------------
 */
final class BreedingPerformanceReport implements ReportDefinition
{
    private ?string $from = null;

    private ?string $to = null;

    private ?string $bloodline = null;

    private ?int $sireId = null;

    private ?int $damId = null;

    /** @var Collection<int, array<string, string|int|float|null>>|null */
    private ?Collection $pairRows = null;

    /** @var Collection<int, array<string, string|int|float|null>>|null */
    private ?Collection $bloodlineRows = null;

    /** @var array{matings: int, eggs_set: int, eggs_fertile: int, eggs_hatched: int}|null */
    private ?array $totals = null;

    public function key(): string
    {
        return 'breeding_performance';
    }

    public function title(): string
    {
        return 'Breeding Performance';
    }

    public function description(): string
    {
        return 'Which sire and dam pairs, and which bloodlines, actually produce - fertility and hatch rates weighted by the number of eggs actually set.';
    }

    /** @param  array<string, mixed>  $filters */
    public function withFilters(array $filters): static
    {
        $this->from = $this->cleanString($filters['from'] ?? null);
        $this->to = $this->cleanString($filters['to'] ?? null);
        $this->bloodline = $this->cleanString($filters['bloodline'] ?? null);
        $this->sireId = $this->cleanId($filters['sire_id'] ?? null);
        $this->damId = $this->cleanId($filters['dam_id'] ?? null);

        // Filters changed, so anything already computed is stale.
        $this->pairRows = null;
        $this->bloodlineRows = null;
        $this->totals = null;

        return $this;
    }

    /** @return array<string, mixed> */
    public function appliedFilters(): array
    {
        return array_filter([
            'from' => $this->from,
            'to' => $this->to,
            'bloodline' => $this->bloodline,
            'sire_id' => $this->sireId,
            'dam_id' => $this->damId,
        ], static fn (mixed $value): bool => $value !== null);
    }

    public function filterSummary(): string
    {
        $parts = [];

        if ($this->from !== null && $this->to !== null) {
            $parts[] = "Mated between {$this->from} and {$this->to}";
        } elseif ($this->from !== null) {
            $parts[] = "Mated on or after {$this->from}";
        } elseif ($this->to !== null) {
            $parts[] = "Mated on or before {$this->to}";
        }

        if ($this->bloodline !== null) {
            $parts[] = "Sire bloodline: {$this->bloodline}";
        }

        if ($this->sireId !== null) {
            $parts[] = "Sire #{$this->sireId}";
        }

        if ($this->damId !== null) {
            $parts[] = "Dam #{$this->damId}";
        }

        return $parts === [] ? 'All matings on record' : implode(' · ', $parts);
    }

    /** @return array<string, string> */
    public function columns(): array
    {
        return [
            'sire' => 'Sire',
            'dam' => 'Dam',
            'bloodline' => 'Bloodline',
            'matings' => 'Matings',
            'eggs_set' => 'Eggs Set',
            'eggs_fertile' => 'Fertile',
            'eggs_hatched' => 'Hatched',
            'fertility_rate' => 'Fertility Rate % (fertile / set)',
            'hatch_rate' => 'Hatch Rate % (hatched / fertile)',
        ];
    }

    /** @return array<string, string> */
    public function bloodlineColumns(): array
    {
        return [
            'bloodline' => 'Bloodline',
            'pairs' => 'Pairs',
            'matings' => 'Matings',
            'eggs_set' => 'Eggs Set',
            'eggs_fertile' => 'Fertile',
            'eggs_hatched' => 'Hatched',
            'fertility_rate' => 'Fertility Rate % (fertile / set)',
            'hatch_rate' => 'Hatch Rate % (hatched / fertile)',
        ];
    }

    /**
     * One row per sire x dam pair. This is what the CSV export contains.
     *
     * Single query: the pair's identity (name, band, bloodline) is fetched by
     * joining broodcocks twice rather than by loading BreedingRecord models and
     * touching ->sire / ->dam, which would be one lazy load per row and would
     * throw under Model::shouldBeStrict() anyway.
     *
     * @return Collection<int, array<string, string|int|float|null>>
     */
    public function rows(): Collection
    {
        if ($this->pairRows instanceof Collection) {
            return $this->pairRows;
        }

        $aggregates = $this->baseQuery()
            ->select([
                'breeding_records.sire_id',
                'breeding_records.dam_id',
                'sires.name as sire_name',
                'sires.band_number as sire_band',
                'sires.bloodline as sire_bloodline',
                'dams.name as dam_name',
                'dams.band_number as dam_band',
            ])
            ->selectRaw('count(*) as matings')
            ->selectRaw('sum(breeding_records.eggs_set) as eggs_set')
            ->selectRaw('sum(breeding_records.eggs_fertile) as eggs_fertile')
            ->selectRaw('sum(breeding_records.eggs_hatched) as eggs_hatched')
            ->groupBy(
                'breeding_records.sire_id',
                'breeding_records.dam_id',
                'sires.name',
                'sires.band_number',
                'sires.bloodline',
                'dams.name',
                'dams.band_number',
            )
            // Biggest producers first; a report read top-down should open on the
            // pairs the farm has actually invested eggs in.
            ->orderByRaw('sum(breeding_records.eggs_set) desc')
            ->orderBy('sires.name')
            ->orderBy('dams.name')
            // toBase() applies the model's global scopes and then returns plain
            // stdClass rows. Hydrating BreedingRecord models from an aggregate
            // SELECT would be misleading - these rows are not matings.
            ->toBase()
            ->get();

        return $this->pairRows = $aggregates->map(function (object $row): array {
            $eggsSet = (int) $row->eggs_set;
            $fertile = (int) $row->eggs_fertile;
            $hatched = (int) $row->eggs_hatched;

            return [
                'sire' => $this->displayBird($row->sire_name, $row->sire_band),
                'dam' => $this->displayBird($row->dam_name, $row->dam_band),
                'bloodline' => $row->sire_bloodline ?? 'Unrecorded',
                'matings' => (int) $row->matings,
                'eggs_set' => $eggsSet,
                'eggs_fertile' => $fertile,
                'eggs_hatched' => $hatched,
                'fertility_rate' => $this->rate($fertile, $eggsSet),
                'hatch_rate' => $this->rate($hatched, $fertile),
            ];
        })->values();
    }

    /**
     * One row per bloodline (the SIRE's bloodline), rendered as the second
     * table in the PDF.
     *
     * This is aggregated straight from breeding_records rather than by summing
     * the pair rows, so the rates stay egg-weighted end to end. Rolling up
     * already-computed pair percentages would reintroduce exactly the unweighted
     * average this report exists to avoid.
     *
     * @return Collection<int, array<string, string|int|float|null>>
     */
    public function rowsByBloodline(): Collection
    {
        if ($this->bloodlineRows instanceof Collection) {
            return $this->bloodlineRows;
        }

        $aggregates = $this->baseQuery()
            ->select(['sires.bloodline as bloodline'])
            ->selectRaw('count(*) as matings')
            // Explicit casts, because count(distinct (a, b)) is Postgres-only
            // and SQLite (the test database) accepts only a single expression.
            ->selectRaw("count(distinct (cast(breeding_records.sire_id as text) || '-' || cast(breeding_records.dam_id as text))) as pairs")
            ->selectRaw('sum(breeding_records.eggs_set) as eggs_set')
            ->selectRaw('sum(breeding_records.eggs_fertile) as eggs_fertile')
            ->selectRaw('sum(breeding_records.eggs_hatched) as eggs_hatched')
            ->groupBy('sires.bloodline')
            ->orderByRaw('sum(breeding_records.eggs_set) desc')
            ->orderBy('sires.bloodline')
            ->toBase()
            ->get();

        return $this->bloodlineRows = $aggregates->map(function (object $row): array {
            $eggsSet = (int) $row->eggs_set;
            $fertile = (int) $row->eggs_fertile;
            $hatched = (int) $row->eggs_hatched;

            return [
                'bloodline' => $row->bloodline ?? 'Unrecorded',
                'pairs' => (int) $row->pairs,
                'matings' => (int) $row->matings,
                'eggs_set' => $eggsSet,
                'eggs_fertile' => $fertile,
                'eggs_hatched' => $hatched,
                'fertility_rate' => $this->rate($fertile, $eggsSet),
                'hatch_rate' => $this->rate($hatched, $fertile),
            ];
        })->values();
    }

    /**
     * The same by-bloodline grouping, folded from already-aggregated pair rows.
     *
     * ReportController hands the PDF view only columns/rows/summary and is
     * shared by every report, so the template cannot call rowsByBloodline()
     * (it never receives the report instance). This static fold lets the PDF
     * build the second table from the $rows it does receive, without a second
     * round trip to Supabase.
     *
     * It is still SUM-then-divide: it adds up the pair TOTALS - eggs set,
     * fertile, hatched - and divides once at the end. It never averages the
     * pair percentages, which would undo the weighting the pair query applied.
     * BreedingPerformanceReportTest asserts this returns exactly what the SQL
     * rowsByBloodline() returns, so the two can never drift apart.
     *
     * @param  Collection<int, array<string, string|int|float|null>>  $pairRows
     * @return Collection<int, array<string, string|int|float|null>>
     */
    public static function foldByBloodline(Collection $pairRows): Collection
    {
        $report = new self;

        return $pairRows
            ->groupBy(static fn (array $row): string => (string) $row['bloodline'])
            ->map(static function (Collection $group, string $bloodline) use ($report): array {
                $eggsSet = (int) $group->sum('eggs_set');
                $fertile = (int) $group->sum('eggs_fertile');
                $hatched = (int) $group->sum('eggs_hatched');

                return [
                    'bloodline' => $bloodline,
                    'pairs' => $group->count(),
                    'matings' => (int) $group->sum('matings'),
                    'eggs_set' => $eggsSet,
                    'eggs_fertile' => $fertile,
                    'eggs_hatched' => $hatched,
                    'fertility_rate' => $report->rate($fertile, $eggsSet),
                    'hatch_rate' => $report->rate($hatched, $fertile),
                ];
            })
            ->sortBy([
                static fn (array $a, array $b): int => $b['eggs_set'] <=> $a['eggs_set'],
                static fn (array $a, array $b): int => $a['bloodline'] <=> $b['bloodline'],
            ])
            ->values();
    }

    /** @return array<string, string|int|float|null> */
    public function summary(): array
    {
        $totals = $this->totals();

        return [
            'Total Matings' => $totals['matings'],
            'Total Eggs Set' => $totals['eggs_set'],
            'Overall Fertility Rate' => $this->rate($totals['eggs_fertile'], $totals['eggs_set']),
            'Overall Hatch Rate' => $this->rate($totals['eggs_hatched'], $totals['eggs_fertile']),
        ];
    }

    /**
     * The farm-wide totals behind the summary tiles.
     *
     * One query, and again SUM-then-divide: the headline fertility rate must be
     * the same number a person would get by dividing the two totals printed
     * beside it, or the tiles contradict each other.
     *
     * @return array{matings: int, eggs_set: int, eggs_fertile: int, eggs_hatched: int}
     */
    public function totals(): array
    {
        if ($this->totals !== null) {
            return $this->totals;
        }

        $row = $this->baseQuery()
            ->selectRaw('count(*) as matings')
            ->selectRaw('coalesce(sum(breeding_records.eggs_set), 0) as eggs_set')
            ->selectRaw('coalesce(sum(breeding_records.eggs_fertile), 0) as eggs_fertile')
            ->selectRaw('coalesce(sum(breeding_records.eggs_hatched), 0) as eggs_hatched')
            ->toBase()
            ->first();

        return $this->totals = [
            'matings' => (int) ($row->matings ?? 0),
            'eggs_set' => (int) ($row->eggs_set ?? 0),
            'eggs_fertile' => (int) ($row->eggs_fertile ?? 0),
            'eggs_hatched' => (int) ($row->eggs_hatched ?? 0),
        ];
    }

    public function pdfView(): string
    {
        return 'reports.pdf.breeding-performance';
    }

    // -----------------------------------------------------------------
    // Internals
    // -----------------------------------------------------------------

    /**
     * The filtered mating set, joined to both parents.
     *
     * Every grouping starts here so the pair table, the bloodline table and the
     * summary tiles can never be computed over different populations.
     *
     * The joins deliberately do NOT exclude soft-deleted broodcocks: a bird can
     * be retired from the flock long after its matings happened, and dropping
     * its history would quietly change last year's fertility rate. Soft-deleted
     * breeding_records ARE excluded, by the model's SoftDeletes global scope.
     *
     * @return Builder<BreedingRecord>
     */
    private function baseQuery(): Builder
    {
        return BreedingRecord::query()
            ->join('broodcocks as sires', 'sires.id', '=', 'breeding_records.sire_id')
            ->join('broodcocks as dams', 'dams.id', '=', 'breeding_records.dam_id')
            ->between($this->from, $this->to)
            ->when($this->bloodline !== null, fn (Builder $query) => $query->where('sires.bloodline', $this->bloodline))
            ->when($this->sireId !== null, fn (Builder $query) => $query->where('breeding_records.sire_id', $this->sireId))
            ->when($this->damId !== null, fn (Builder $query) => $query->where('breeding_records.dam_id', $this->damId));
    }

    /**
     * Percentage, or null when the denominator is zero.
     *
     * "No eggs were set" is not "0% fertility" - one is an absent measurement,
     * the other is a catastrophic result, and a breeder must not see the second
     * when the truth is the first.
     */
    private function rate(int $numerator, int $denominator): ?float
    {
        if ($denominator <= 0) {
            return null;
        }

        return round(($numerator / $denominator) * 100, 2);
    }

    /** "Sultan (AB-1234)", never a bare band or a blank cell. */
    private function displayBird(?string $name, ?string $band): string
    {
        $name = $name !== null && $name !== '' ? $name : 'Unnamed';

        return $band !== null && $band !== ''
            ? "{$name} ({$band})"
            : "{$name} (Not yet banded)";
    }

    private function cleanString(mixed $value): ?string
    {
        if (! is_string($value) && ! is_int($value)) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function cleanId(mixed $value): ?int
    {
        $value = $this->cleanString($value);

        return $value !== null && ctype_digit($value) ? (int) $value : null;
    }
}
