<?php

declare(strict_types=1);

namespace App\Reports;

use App\Enums\BroodcockClass;
use App\Enums\BroodcockStatus;
use App\Models\Broodcock;
use App\Models\MortalityRecord;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Mortality report - "how many birds are we losing, when, and to what?"
 *
 * Three views of the same filtered set: the death register itself, a breakdown
 * by cause, and a month-by-month trend. The aggregations are done in SQL
 * (groupBy + selectRaw) rather than by looping the hydrated models, because the
 * register is the one table on this farm that only ever grows.
 *
 * ON THE MORTALITY RATE
 * ---------------------
 * A death count is not a rate. A rate needs a denominator, and the honest
 * denominator is the AVERAGE FLOCK SIZE over the period - which this database
 * cannot produce. Birds enter the flock on a recorded date (date_hatched /
 * date_acquired), but they leave it on a recorded date only when they DIE:
 * `status = sold` and `status = retired` carry no dated event, so the headcount
 * on any past day cannot be reconstructed. Rather than invent a denominator,
 * this report publishes a clearly-labelled PROXY - population at risk, being
 * the birds still on the farm today plus every bird that died inside the
 * filtered range - and prints that denominator in words on the PDF.
 */
final class MortalityReport implements ReportDefinition
{
    /** Months of zero-fill on the by-period table before it degrades to data-only rows. */
    private const MAX_ZERO_FILLED_MONTHS = 60;

    /**
     * Container key under which the CONFIGURED report publishes itself.
     *
     * ReportController hands the PDF view only columns/rows/summary, and the
     * two extra aggregations this report renders are tables, not scalars, so
     * they cannot travel as summary tiles. Rather than edit the shared
     * controller - which every other report depends on - the configured
     * instance registers itself here and mortality.blade.php reads it back.
     * A private key, not self::class, so ReportRegistry::make() keeps handing
     * out fresh unfiltered instances exactly as before.
     */
    public const CURRENT = 'gfms.report.mortality.current';

    private ?string $from = null;

    private ?string $to = null;

    private ?string $cause = null;

    private ?string $bloodline = null;

    private ?string $class = null;

    public function key(): string
    {
        return 'mortality';
    }

    public function title(): string
    {
        return 'Mortality Report';
    }

    public function description(): string
    {
        return 'How many birds were lost, when they were lost, and what killed them.';
    }

    public function withFilters(array $filters): static
    {
        $this->from = $this->clean($filters['from'] ?? null);
        $this->to = $this->clean($filters['to'] ?? null);
        $this->cause = $this->clean($filters['cause'] ?? null);
        $this->bloodline = $this->clean($filters['bloodline'] ?? null);
        $this->class = $this->clean($filters['class'] ?? null);

        app()->instance(self::CURRENT, $this);

        return $this;
    }

    public function appliedFilters(): array
    {
        return array_filter([
            'from' => $this->from,
            'to' => $this->to,
            'cause' => $this->cause,
            'bloodline' => $this->bloodline,
            'class' => $this->class,
        ], static fn (?string $value): bool => $value !== null);
    }

    public function filterSummary(): string
    {
        $parts = [];

        if ($this->from !== null && $this->to !== null) {
            $parts[] = 'Died between '.$this->humanDate($this->from).' and '.$this->humanDate($this->to);
        } elseif ($this->from !== null) {
            $parts[] = 'Died on or after '.$this->humanDate($this->from);
        } elseif ($this->to !== null) {
            $parts[] = 'Died on or before '.$this->humanDate($this->to);
        } else {
            $parts[] = 'All dates';
        }

        if ($this->cause !== null) {
            $parts[] = 'Cause contains "'.$this->cause.'"';
        }

        if ($this->bloodline !== null) {
            $parts[] = 'Bloodline: '.$this->bloodline;
        }

        if ($this->class !== null) {
            $parts[] = 'Class: '.$this->classLabel($this->class);
        }

        return implode(' · ', $parts);
    }

    public function columns(): array
    {
        return [
            'band_number' => 'Band Number',
            'bird_name' => 'Bird Name',
            'sex' => 'Sex',
            'bloodline' => 'Bloodline',
            'class' => 'Class',
            'date_of_death' => 'Date of Death',
            'age_at_death' => 'Age at Death',
            'cause_of_death' => 'Cause of Death',
            'disposal_method' => 'Disposal Method',
            'recorded_by' => 'Recorded By',
        ];
    }

    public function rows(): Collection
    {
        // Both relations the mapper touches are eager-loaded here. Two extra
        // queries total, no matter how many deaths come back -
        // Model::shouldBeStrict() turns any miss into an exception, not a
        // silent extra round trip to Supabase.
        return $this->baseQuery()
            ->with(['broodcock', 'recordedBy'])
            ->orderByDesc('date_of_death')
            ->orderByDesc('id')
            ->get()
            ->map(function (MortalityRecord $record): array {
                $bird = $record->broodcock;
                $months = $record->ageAtDeathInMonths();

                return [
                    'band_number' => $bird?->displayBand() ?? '—',
                    'bird_name' => $bird?->name ?? '—',
                    'sex' => $bird?->sex?->label() ?? '—',
                    'bloodline' => $bird?->bloodline ?? '—',
                    'class' => $bird?->class?->label() ?? '—',
                    'date_of_death' => $record->date_of_death?->format('j M Y') ?? '—',
                    // A bird with no hatch date has no knowable age. "Unknown"
                    // is the truth; "0 months" would be a fabricated newborn.
                    'age_at_death' => $months === null ? 'Unknown' : $this->ageLabel($months),
                    'cause_of_death' => $record->cause_of_death,
                    'disposal_method' => $record->disposal_method ?? 'Not recorded',
                    'recorded_by' => $record->recordedBy?->full_name ?? 'Not recorded',
                ];
            });
    }

    public function summary(): array
    {
        $byCause = $this->byCause();
        $rate = $this->mortalityRate();

        return [
            'Total Deaths in Range' => $this->totalDeaths(),
            'Deaths This Month' => $this->deathsThisMonth(),
            'Most Common Cause' => $byCause->first()['cause'] ?? 'None recorded',
            $rate['label'] => $rate['display'],
        ];
    }

    public function pdfView(): string
    {
        return 'reports.pdf.mortality';
    }

    // -----------------------------------------------------------------
    // Aggregations - SQL, not loops
    // -----------------------------------------------------------------

    /**
     * Deaths grouped by cause, with each cause's share of the FILTERED total.
     *
     * The denominator is the sum of these groups, so the percentages describe
     * the range the reader is looking at. Dividing by an unfiltered total would
     * make every filtered report understate every cause.
     *
     * @return Collection<int, array{cause: string, deaths: int, percentage: float}>
     */
    public function byCause(): Collection
    {
        /** @var Collection<int, object{cause_of_death: string, deaths: int}> $groups */
        $groups = $this->baseQuery()
            ->toBase()
            ->selectRaw('cause_of_death, count(*) as deaths')
            ->groupBy('cause_of_death')
            ->orderByDesc('deaths')
            ->orderBy('cause_of_death')
            ->get();

        $total = (int) $groups->sum(static fn (object $row): int => (int) $row->deaths);

        if ($total === 0) {
            return collect();
        }

        return $groups->map(static fn (object $row): array => [
            'cause' => (string) $row->cause_of_death,
            'deaths' => (int) $row->deaths,
            'percentage' => round((int) $row->deaths / $total * 100, 1),
        ])->values();
    }

    /**
     * Deaths per calendar month across the filtered range.
     *
     * Months with no deaths are filled in so a reader can see a quiet month
     * rather than having to notice a missing row - a gap in a trend table is
     * indistinguishable from a gap in the data.
     *
     * @return Collection<int, array{period: string, label: string, deaths: int}>
     */
    public function byPeriod(): Collection
    {
        /** @var Collection<int, object{period: string, deaths: int}> $groups */
        $groups = $this->baseQuery()
            ->toBase()
            ->selectRaw($this->monthExpression().' as period, count(*) as deaths')
            ->groupBy(DB::raw($this->monthExpression()))
            ->orderBy('period')
            ->get();

        /** @var array<string, int> $counts */
        $counts = $groups
            ->mapWithKeys(static fn (object $row): array => [(string) $row->period => (int) $row->deaths])
            ->all();

        foreach ($this->periodKeys(array_keys($counts)) as $key) {
            $counts[$key] ??= 0;
        }

        ksort($counts);

        return collect($counts)
            ->map(static fn (int $deaths, string $period): array => [
                'period' => $period,
                'label' => CarbonImmutable::createFromFormat('Y-m-d', $period.'-01')->format('F Y'),
                'deaths' => $deaths,
            ])
            ->values();
    }

    /**
     * The mortality rate, and the words that justify it.
     *
     * @return array{
     *     label: string, display: string, deaths: int, denominator: int,
     *     percentage: ?float, is_proxy: bool, denominator_sentence: string, caveat: string
     * }
     */
    public function mortalityRate(): array
    {
        $deaths = $this->totalDeaths();

        // Population at risk = birds still on the farm + birds that died inside
        // the range. The cause filter is deliberately NOT applied here: a
        // cause-specific numerator over a whole-population denominator is a
        // cause-specific mortality rate, which is what a reader wants. Applying
        // it to the denominator too would shrink the flock to only the birds
        // that died of that cause, and every rate would collapse to 100%.
        $survivors = (int) Broodcock::query()
            ->onFarm()
            ->tap(fn (Builder $query) => $this->applyBirdFilters($query))
            ->toBase()
            ->count();

        $diedInRange = (int) MortalityRecord::query()
            ->between($this->from, $this->to)
            ->whereHas('broodcock', fn (Builder $query) => $this->applyBirdFilters($query))
            ->toBase()
            ->count();

        // whereHas() above is unconditional so the bird filters can be applied
        // uniformly; with no bird filters it is a plain "has a bird" test,
        // which is also what keeps orphaned rows out of the denominator.
        $denominator = $survivors + $diedInRange;

        $label = 'Mortality Rate (proxy)';

        $caveat = 'This is a PROXY, not a true mortality rate. A true rate divides deaths by the '
            .'AVERAGE FLOCK SIZE over the period. The system records a dated event when a bird '
            .'enters the flock and when it dies, but no dated event when a bird is sold, '
            .'transferred or otherwise leaves alive - so the headcount on any past day cannot be '
            .'reconstructed and an honest average flock size cannot be computed from this data. '
            .'The denominator below is used instead, and is stated in full so it can be checked.';

        if ($denominator === 0) {
            return [
                'label' => $label,
                'display' => 'Not computable',
                'deaths' => $deaths,
                'denominator' => 0,
                'percentage' => null,
                'is_proxy' => true,
                'denominator_sentence' => 'No birds on the farm and no deaths in range, so there is nothing to divide by.',
                'caveat' => $caveat,
            ];
        }

        $percentage = round($deaths / $denominator * 100, 1);

        return [
            'label' => $label,
            'display' => $percentage.'%',
            'deaths' => $deaths,
            'denominator' => $denominator,
            'percentage' => $percentage,
            'is_proxy' => true,
            // Spelled out so a panel member can check the arithmetic without
            // reading any code: numerator, denominator, and what each is.
            'denominator_sentence' => sprintf(
                '%s %s in range ÷ %s birds at risk = %s%%. The population at risk is the %s '
                .'%s still on the farm today plus the %s that died within this date range (%s).',
                number_format($deaths),
                $deaths === 1 ? 'death' : 'deaths',
                number_format($denominator),
                $percentage,
                number_format($survivors),
                $survivors === 1 ? 'bird' : 'birds',
                number_format($diedInRange),
                $this->birdFilterPhrase(),
            ),
            'caveat' => $caveat,
        ];
    }

    public function totalDeaths(): int
    {
        return (int) $this->baseQuery()->toBase()->count();
    }

    /**
     * Deaths in the current calendar month.
     *
     * Intersected with the report's own range, so the tile can never claim more
     * deaths than the table beneath it lists.
     */
    public function deathsThisMonth(): int
    {
        $startOfMonth = CarbonImmutable::now()->startOfMonth()->toDateString();
        $endOfMonth = CarbonImmutable::now()->endOfMonth()->toDateString();

        return (int) $this->baseQuery()
            ->between($startOfMonth, $endOfMonth)
            ->toBase()
            ->count();
    }

    // -----------------------------------------------------------------
    // Query construction
    // -----------------------------------------------------------------

    /** @return Builder<MortalityRecord> */
    private function baseQuery(): Builder
    {
        return MortalityRecord::query()
            ->between($this->from, $this->to)
            ->when(
                $this->cause !== null,
                // whereLike(caseSensitive: false) compiles to ILIKE on Postgres
                // and LIKE on SQLite, so "resp" finds "Respiratory infection" in
                // production and in the in-memory test database alike.
                fn (Builder $query) => $query->whereLike('cause_of_death', '%'.$this->cause.'%', caseSensitive: false)
            )
            ->whereHas('broodcock', fn (Builder $query) => $this->applyBirdFilters($query));
    }

    /**
     * Bloodline and class live on the bird, not the death - applied identically
     * to the register, the aggregations and the rate denominator so all four
     * describe the same population.
     *
     * @param  Builder<Broodcock>  $query
     */
    private function applyBirdFilters(Builder $query): void
    {
        $query->bloodline($this->bloodline)
            ->classGrade($this->class);
    }

    private function birdFilterPhrase(): string
    {
        $parts = [];

        if ($this->bloodline !== null) {
            $parts[] = $this->bloodline.' bloodline';
        }

        if ($this->class !== null) {
            $parts[] = $this->classLabel($this->class);
        }

        return $parts === [] ? 'whole flock' : implode(', ', $parts);
    }

    /**
     * Portable "YYYY-MM" bucket.
     *
     * DELIBERATE DRIVER BRANCH. Postgres has to_char(), SQLite has strftime(),
     * and neither knows the other. Formatting in PHP instead would mean pulling
     * every row back to group it, which is exactly what this report must not do.
     * Tests run on SQLite and production runs on Postgres, so both arms matter.
     */
    private function monthExpression(): string
    {
        return match (DB::connection()->getDriverName()) {
            'pgsql' => "to_char(date_of_death, 'YYYY-MM')",
            'mysql', 'mariadb' => "date_format(date_of_death, '%Y-%m')",
            'sqlsrv' => "format(date_of_death, 'yyyy-MM')",
            default => "strftime('%Y-%m', date_of_death)",
        };
    }

    /**
     * Every "YYYY-MM" bucket the by-period table should show, zero-fill included.
     *
     * The span is taken from the from/to filters when given, otherwise from the
     * months that actually contain deaths. December-to-January is handled by
     * walking real month boundaries rather than by incrementing a number.
     *
     * @param  array<int, string>  $observed
     * @return array<int, string>
     */
    private function periodKeys(array $observed): array
    {
        $start = $this->from !== null
            ? CarbonImmutable::parse($this->from)->startOfMonth()
            : ($observed === [] ? null : CarbonImmutable::parse(min($observed).'-01'));

        $end = $this->to !== null
            ? CarbonImmutable::parse($this->to)->startOfMonth()
            : ($observed === [] ? null : CarbonImmutable::parse(max($observed).'-01'));

        if ($start === null || $end === null || $start->greaterThan($end)) {
            return [];
        }

        // An open-ended or absurd range would otherwise emit thousands of empty
        // rows; past the cap the table shows only the months that have data.
        if ($start->diffInMonths($end) > self::MAX_ZERO_FILLED_MONTHS) {
            return [];
        }

        $keys = [];

        for ($cursor = $start; $cursor->lessThanOrEqualTo($end); $cursor = $cursor->addMonth()) {
            $keys[] = $cursor->format('Y-m');
        }

        return $keys;
    }

    // -----------------------------------------------------------------
    // Formatting
    // -----------------------------------------------------------------

    private function ageLabel(int $months): string
    {
        $years = intdiv($months, 12);
        $remainder = $months % 12;

        if ($years === 0) {
            return $months === 1 ? '1 mo' : "{$months} mos";
        }

        $label = $years === 1 ? '1 yr' : "{$years} yrs";

        return $remainder === 0 ? $label : "{$label} {$remainder} mos";
    }

    private function classLabel(string $class): string
    {
        return BroodcockClass::tryFrom($class)?->label() ?? $class;
    }

    private function humanDate(string $date): string
    {
        return CarbonImmutable::parse($date)->format('j M Y');
    }

    /** @param  mixed  $value */
    private function clean($value): ?string
    {
        if (! is_string($value) && ! is_int($value)) {
            return null;
        }

        $trimmed = trim((string) $value);

        return $trimmed === '' ? null : $trimmed;
    }

    /**
     * Referenced so the on-farm definition this report divides by stays visibly
     * tied to the enum that defines it.
     *
     * @return array<int, string>
     */
    public function onFarmStatuses(): array
    {
        return array_values(array_map(
            static fn (BroodcockStatus $status): string => $status->label(),
            array_filter(BroodcockStatus::cases(), static fn (BroodcockStatus $status): bool => $status->isOnFarm())
        ));
    }
}
