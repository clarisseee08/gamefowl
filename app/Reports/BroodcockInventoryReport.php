<?php

declare(strict_types=1);

namespace App\Reports;

use App\Enums\BroodcockClass;
use App\Enums\BroodcockStatus;
use App\Enums\Sex;
use App\Models\Broodcock;
use App\Models\Pen;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * "What birds does the farm have, and how are they distributed?"
 *
 * The flock census. Every other report in the system reads a slice of activity
 * (health events, matings, deaths); this one reads the stock itself, which is
 * why it is the report the farm runs most and the one an auditor asks for
 * first.
 */
final class BroodcockInventoryReport implements ReportDefinition
{
    /**
     * Normalised filters. Populated by withFilters() and persisted verbatim
     * into reports.parameters, so a stored report can be re-run exactly.
     *
     * @var array<string, string>
     */
    private array $filters = [];

    public function key(): string
    {
        return 'broodcock_inventory';
    }

    public function title(): string
    {
        return 'Broodcock Inventory';
    }

    public function description(): string
    {
        return 'A complete census of the flock, showing how the birds are distributed by status, class, bloodline and pen.';
    }

    public function withFilters(array $filters): static
    {
        $this->filters = [];

        // Each filter is validated against its own enum/shape here rather than
        // trusted from the request: appliedFilters() is written into the audit
        // row, so a filter that was silently ignored must never be recorded as
        // though it had been applied.
        foreach (['status' => BroodcockStatus::class, 'class' => BroodcockClass::class, 'sex' => Sex::class] as $field => $enum) {
            $value = $this->scalar($filters[$field] ?? null);

            if ($value !== null && $enum::tryFrom($value) !== null) {
                $this->filters[$field] = $value;
            }
        }

        foreach (['bloodline', 'breed'] as $field) {
            $value = $this->scalar($filters[$field] ?? null);

            if ($value !== null) {
                $this->filters[$field] = $value;
            }
        }

        $penId = $this->scalar($filters['pen_id'] ?? null);

        if ($penId !== null && ctype_digit($penId)) {
            $this->filters['pen_id'] = $penId;
        }

        foreach (['from', 'to'] as $field) {
            $date = $this->date($filters[$field] ?? null);

            if ($date instanceof Carbon) {
                $this->filters[$field] = $date->toDateString();
            }
        }

        // ReportController passes only columns/rows/summary to the PDF view -
        // that is the shared contract and it is deliberately not widened for
        // one report. The two breakdowns below the table are specific to this
        // report, so the configured instance is shared into the container and
        // the template resolves it back. Rendering without withFilters() still
        // works: the container simply builds an unfiltered instance.
        app()->instance(self::class, $this);

        return $this;
    }

    public function appliedFilters(): array
    {
        return $this->filters;
    }

    public function filterSummary(): string
    {
        $parts = [];

        if (isset($this->filters['status'])) {
            $parts[] = 'Status: '.BroodcockStatus::from($this->filters['status'])->label();
        }

        if (isset($this->filters['class'])) {
            $parts[] = 'Class: '.BroodcockClass::from($this->filters['class'])->label();
        }

        if (isset($this->filters['sex'])) {
            $parts[] = 'Sex: '.Sex::from($this->filters['sex'])->label();
        }

        if (isset($this->filters['bloodline'])) {
            $parts[] = 'Bloodline: '.$this->filters['bloodline'];
        }

        if (isset($this->filters['breed'])) {
            $parts[] = 'Breed: '.$this->filters['breed'];
        }

        if (isset($this->filters['pen_id'])) {
            $parts[] = 'Pen: '.($this->penLabel() ?? '#'.$this->filters['pen_id']);
        }

        $from = $this->filters['from'] ?? null;
        $to = $this->filters['to'] ?? null;

        if ($from !== null && $to !== null) {
            $parts[] = 'Acquired '.$this->humanDate($from).' to '.$this->humanDate($to);
        } elseif ($from !== null) {
            $parts[] = 'Acquired on or after '.$this->humanDate($from);
        } elseif ($to !== null) {
            $parts[] = 'Acquired on or before '.$this->humanDate($to);
        }

        // Never blank: a report that states no filters is claiming to cover
        // the whole flock, and it should say so out loud.
        return $parts === [] ? 'All birds (no filters applied)' : implode(' · ', $parts);
    }

    public function columns(): array
    {
        return [
            'band_number' => 'Band Number',
            'name' => 'Name',
            'sex' => 'Sex',
            'breed' => 'Breed',
            'bloodline' => 'Bloodline',
            'class' => 'Class',
            'status' => 'Status',
            'age' => 'Age',
            'date_hatched' => 'Date Hatched',
            'date_acquired' => 'Date Acquired',
            'weight' => 'Weight (kg)',
            'pen' => 'Pen',
            'sire' => 'Sire',
            'dam' => 'Dam',
        ];
    }

    public function rows(): Collection
    {
        return $this->query()
            // Model::shouldBeStrict() turns a lazy load into an exception, and
            // this loop reads all three relations on every row - so all three
            // are loaded up front, in three queries regardless of flock size.
            ->with(['pen', 'sire', 'dam'])
            ->orderBy('band_number')
            ->orderBy('name')
            ->get()
            ->map(fn (Broodcock $bird): array => [
                'band_number' => $bird->displayBand(),
                'name' => $bird->name,
                'sex' => $bird->sex->label(),
                'breed' => $bird->breed ?? '—',
                'bloodline' => $bird->bloodline ?? '—',
                'class' => $bird->class->label(),
                'status' => $bird->status->label(),
                // ageLabel() is null when the hatch date is unknown. "Unknown"
                // is the honest answer; a 0 or a negative number would be a
                // fabricated fact about a real bird.
                'age' => $bird->ageLabel() ?? 'Unknown',
                'date_hatched' => $bird->date_hatched?->format('j M Y') ?? 'Unknown',
                'date_acquired' => $bird->date_acquired?->format('j M Y') ?? '—',
                'weight' => $bird->weight !== null ? number_format((float) $bird->weight, 2) : '—',
                'pen' => $bird->pen?->code ?? 'Unassigned',
                'sire' => $bird->sire?->name ?? 'Unknown',
                'dam' => $bird->dam?->name ?? 'Unknown',
            ])
            ->values();
    }

    public function summary(): array
    {
        // One aggregate query rather than counting a fetched collection, so the
        // tiles cost the same whether the farm holds 40 birds or 4,000.
        $totals = $this->query()
            ->selectRaw('count(*) as total_birds')
            ->selectRaw('sum(case when status = ? then 1 else 0 end) as active_birds', [BroodcockStatus::Active->value])
            ->selectRaw("sum(case when band_number is null or band_number = '' then 1 else 0 end) as unbanded")
            ->toBase()
            ->first();

        $total = (int) ($totals->total_birds ?? 0);
        $unbanded = (int) ($totals->unbanded ?? 0);

        return [
            'Total Birds' => $total,
            'Active Birds' => (int) ($totals->active_birds ?? 0),
            'Bloodlines' => $this->query()->distinct()->count('bloodline'),
            'Banded / Unbanded' => ($total - $unbanded).' / '.$unbanded,
        ];
    }

    /**
     * Counts by status, computed in the database.
     *
     * Grouped in SQL rather than by walking rows() in PHP: the breakdown is a
     * different question from the listing, and tying it to a fetched
     * collection would make it silently wrong the day the listing is paginated.
     *
     * @return Collection<int, array{label: string, count: int, share: float}>
     */
    public function statusBreakdown(): Collection
    {
        $counts = $this->query()
            ->selectRaw('status, count(*) as aggregate')
            ->groupBy('status')
            ->orderByDesc('aggregate')
            ->toBase()
            ->get();

        return $this->withShare($counts, 'status', fn (?string $value): string => BroodcockStatus::tryFrom((string) $value)?->label() ?? (string) $value);
    }

    /**
     * Counts by bloodline, computed in the database. Birds with no recorded
     * bloodline are grouped under an explicit label rather than dropped - an
     * inventory that quietly omits birds is not an inventory.
     *
     * @return Collection<int, array{label: string, count: int, share: float}>
     */
    public function bloodlineBreakdown(): Collection
    {
        $counts = $this->query()
            ->selectRaw('bloodline, count(*) as aggregate')
            ->groupBy('bloodline')
            ->orderByDesc('aggregate')
            ->toBase()
            ->get();

        return $this->withShare(
            $counts,
            'bloodline',
            fn (?string $value): string => ($value === null || $value === '') ? 'Not recorded' : $value
        );
    }

    public function pdfView(): string
    {
        return 'reports.pdf.broodcock-inventory';
    }

    /**
     * The filtered base query. Rebuilt per call so rows(), summary() and both
     * breakdowns can never share - and mutate - one another's builder.
     *
     * @return Builder<Broodcock>
     */
    private function query(): Builder
    {
        return Broodcock::query()
            // An INVENTORY is what the farm owns. Outside parents are pedigree
            // nodes recorded to keep a family tree whole - not livestock in the
            // farm's care - so counting them here inflates "Total Birds" on the
            // report a panel is most likely to open, and it inflates it by a
            // number that grows every time a borrowed hen is entered.
            ->farmStock()
            ->status($this->filters['status'] ?? null)
            ->classGrade($this->filters['class'] ?? null)
            ->sex($this->filters['sex'] ?? null)
            ->bloodline($this->filters['bloodline'] ?? null)
            ->breed($this->filters['breed'] ?? null)
            ->pen($this->filters['pen_id'] ?? null)
            ->when(
                isset($this->filters['from']),
                fn (Builder $q) => $q->whereDate('date_acquired', '>=', $this->filters['from'])
            )
            ->when(
                isset($this->filters['to']),
                fn (Builder $q) => $q->whereDate('date_acquired', '<=', $this->filters['to'])
            );
    }

    /**
     * Turns raw group-by counts into labelled rows carrying their percentage
     * share, so the PDF renders a distribution rather than a bare tally.
     *
     * @param  Collection<int, object>  $counts
     * @param  callable(?string): string  $label
     * @return Collection<int, array{label: string, count: int, share: float}>
     */
    private function withShare(Collection $counts, string $column, callable $label): Collection
    {
        $total = (int) $counts->sum('aggregate');

        return $counts
            ->map(fn (object $row): array => [
                'label' => $label($row->{$column}),
                'count' => (int) $row->aggregate,
                'share' => $total > 0 ? round((int) $row->aggregate / $total * 100, 1) : 0.0,
            ])
            ->values();
    }

    /** The pen's code, for the filter line. Null when the pen no longer exists. */
    private function penLabel(): ?string
    {
        $code = Pen::query()->whereKey($this->filters['pen_id'])->value('code');

        return $code === null ? null : (string) $code;
    }

    private function humanDate(string $date): string
    {
        return Carbon::parse($date)->format('j M Y');
    }

    /** Casts a request value to a trimmed non-empty string, or null. */
    private function scalar(mixed $value): ?string
    {
        if (! is_string($value) && ! is_int($value)) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function date(mixed $value): ?Carbon
    {
        $value = $this->scalar($value);

        if ($value === null) {
            return null;
        }

        // An unparseable date is dropped rather than thrown: a malformed query
        // string should widen the report, never 500 in a farm office.
        try {
            return Carbon::parse($value)->startOfDay();
        } catch (\Throwable) {
            return null;
        }
    }
}
