<?php

declare(strict_types=1);

namespace App\Livewire\Dashboard;

use App\Enums\BroodcockStatus;
use App\Models\BreedingRecord;
use App\Models\Broodcock;
use App\Models\HealthRecord;
use App\Models\MortalityRecord;
use App\Support\RecordCache;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * The farm dashboard.
 *
 * Every figure here is aggregated in SQL rather than by loading models and
 * counting in PHP. Against a database in Tokyo, the difference between one
 * grouped query and three hundred row reads is the difference between a page
 * that opens and a page nobody uses.
 *
 * And every figure is CACHED (see remember()): the screen ran sixteen queries,
 * at ~240ms a round trip from Render to Tokyo, so it took three to four
 * seconds to open every time - while its numbers change only when a record is
 * written, which is exactly when RecordCache throws them away.
 */
final class Overview extends Component
{
    /** Months of history in the breeding trend. */
    #[Url(except: 12)]
    public int $trendMonths = 12;

    /**
     * Add gamefowl opens its form in a dialog over the dashboard rather than
     * sending the owner to another page (the owner's decision). The form
     * component is unchanged.
     */
    public bool $showForm = false;

    /** Closing the dialog re-keys the form, so it reopens as the record stands. */
    public int $formNonce = 0;

    public function updatedShowForm(bool $open): void
    {
        if (! $open) {
            $this->formNonce++;
        }
    }

    /** Called by the dialog when a form that was typed in is closed: start the next one blank. */
    public function resetForm(): void
    {
        $this->showForm = false;
        $this->updatedShowForm(false);
    }

    public function mount(): void
    {
        $this->authorize('viewAny', BreedingRecord::class);
    }

    public function openForm(): void
    {
        $this->authorize('create', Broodcock::class);
        $this->showForm = true;
    }

    /**
     * A dashboard figure, read through RecordCache.
     *
     * Today's date is part of the key: "overdue", "due soon" and "this year"
     * move with the calendar, not only with writes, so a figure cached at
     * 23:59 must not be served at 00:01.
     *
     * @template TValue
     *
     * @param  Closure(): TValue  $callback
     * @return TValue
     */
    private function remember(string $figure, Closure $callback): mixed
    {
        return RecordCache::remember('dashboard:'.today()->toDateString().':'.$figure, $callback);
    }

    // -----------------------------------------------------------------
    // Headline counts
    // -----------------------------------------------------------------

    /** @return array{total: int, active: int, breeding: int, on_farm: int} */
    #[Computed]
    public function flock(): array
    {
        return $this->remember('flock', function (): mixed {
            // farmStock(): a borrowed hen recorded to complete a pedigree is not
            // this farm's livestock, and counting her here overstates the flock on
            // the screen the owner reads first.
            //
            // One query, not four: conditional aggregation instead of four counts.
            $row = Broodcock::query()
                ->farmStock()
                ->selectRaw('count(*) as total')
                ->selectRaw('sum(case when status = ? then 1 else 0 end) as active', [BroodcockStatus::Active->value])
                ->selectRaw('sum(case when status = ? then 1 else 0 end) as breeding', [BroodcockStatus::Breeding->value])
                // Placeholders built from the enum rather than a hand-written list:
                // the same four statuses were spelled out here and in scopeOnFarm,
                // and BroodcockStatus::isOnFarm() was the real answer all along.
                ->selectRaw(
                    'sum(case when status in ('.implode(', ', array_fill(0, count(BroodcockStatus::onFarmValues()), '?')).') then 1 else 0 end) as on_farm',
                    BroodcockStatus::onFarmValues()
                )
                ->first();

            return [
                'total' => (int) ($row->total ?? 0),
                'active' => (int) ($row->active ?? 0),
                'breeding' => (int) ($row->breeding ?? 0),
                'on_farm' => (int) ($row->on_farm ?? 0),
            ];
        });
    }

    /** @return Collection<int, object> */
    #[Computed]
    public function byStatus(): Collection
    {
        return $this->remember('by-status', function (): mixed {
            return Broodcock::query()
                ->farmStock()
                ->selectRaw('status, count(*) as total')
                ->groupBy('status')
                ->orderByDesc('total')
                ->get();
        });
    }

    /** @return Collection<int, object> */
    #[Computed]
    public function byClass(): Collection
    {
        return $this->remember('by-class', function (): mixed {
            return Broodcock::query()
                ->farmStock()
                ->selectRaw('class, count(*) as total')
                ->groupBy('class')
                ->orderByDesc('total')
                ->get();
        });
    }

    /** @return Collection<int, object> */
    #[Computed]
    public function byBloodline(): Collection
    {
        return $this->remember('by-bloodline', function (): mixed {
            return Broodcock::query()
                ->farmStock()
                ->selectRaw('bloodline, count(*) as total')
                ->whereNotNull('bloodline')
                ->groupBy('bloodline')
                ->orderByDesc('total')
                ->limit(8)
                ->get();
        });
    }

    // -----------------------------------------------------------------
    // Things that need attention
    // -----------------------------------------------------------------

    /** @return Collection<int, HealthRecord> */
    #[Computed]
    public function overdueVaccinations(): Collection
    {
        return $this->remember('overdue', function (): mixed {
            return HealthRecord::query()
                ->with('broodcock:id,name,band_number')
                ->forBirdsOnFarm()
                ->overdue()
                ->orderBy('next_due_date')
                ->limit(8)
                ->get();
        });
    }

    /** @return Collection<int, HealthRecord> */
    #[Computed]
    public function upcomingVaccinations(): Collection
    {
        return $this->remember('due-soon', function (): mixed {
            return HealthRecord::query()
                ->with('broodcock:id,name,band_number')
                ->forBirdsOnFarm()
                ->dueSoon((int) config('gfms.vaccination_warning_days'))
                ->orderBy('next_due_date')
                ->limit(8)
                ->get();
        });
    }

    #[Computed]
    public function overdueCount(): int
    {
        return $this->remember('overdue-count', function (): mixed {
            return HealthRecord::query()->forBirdsOnFarm()->overdue()->count();
        });
    }

    /** @return Collection<int, MortalityRecord> */
    #[Computed]
    public function recentMortality(): Collection
    {
        return $this->remember('recent-deaths', function (): mixed {
            return MortalityRecord::query()
                ->with('broodcock:id,name,band_number')
                ->orderByDesc('date_of_death')
                ->limit(6)
                ->get();
        });
    }

    #[Computed]
    public function deathsThisYear(): int
    {
        return $this->remember('deaths-this-year', function (): mixed {
            return MortalityRecord::query()
                ->whereYear('date_of_death', now()->year)
                ->count();
        });
    }

    // -----------------------------------------------------------------
    // Breeding success trend
    // -----------------------------------------------------------------

    /** First day of the trend window: shared by the chart and the figures above it. */
    private function trendStart(): Carbon
    {
        return now()->startOfMonth()->subMonths($this->trendMonths - 1);
    }

    /**
     * Fertility and hatch rate per month.
     *
     * Rates are computed from SUMMED totals per month, never as an average of
     * per-record percentages - averaging percentages weights a 2-egg mating
     * the same as a 200-egg one, which is simply the wrong number.
     *
     * @return array<int, array{label: string, matings: int, eggs_set: int, fertility: float|null, hatch: float|null}>
     */
    #[Computed]
    public function breedingTrend(): array
    {
        return $this->remember('trend:'.$this->trendMonths, function (): mixed {
            $since = $this->trendStart();

            $records = BreedingRecord::query()
                ->where('mating_date', '>=', $since->toDateString())
                ->get(['mating_date', 'eggs_set', 'eggs_fertile', 'eggs_hatched']);

            // Bucketing in PHP rather than SQL keeps this portable: Postgres uses
            // to_char() and SQLite uses strftime(), and this set is bounded by the
            // date filter above, so there is no scale concern.
            $buckets = [];

            for ($i = 0; $i < $this->trendMonths; $i++) {
                $month = $since->copy()->addMonths($i);
                $buckets[$month->format('Y-m')] = [
                    'label' => $month->format('M Y'),
                    'matings' => 0,
                    'eggs_set' => 0,
                    'eggs_fertile' => 0,
                    'eggs_hatched' => 0,
                ];
            }

            foreach ($records as $record) {
                $key = $record->mating_date->format('Y-m');

                if (! isset($buckets[$key])) {
                    continue;
                }

                $buckets[$key]['matings']++;
                $buckets[$key]['eggs_set'] += $record->eggs_set;
                $buckets[$key]['eggs_fertile'] += $record->eggs_fertile;
                $buckets[$key]['eggs_hatched'] += $record->eggs_hatched;
            }

            return array_values(array_map(static function (array $bucket): array {
                return [
                    'label' => $bucket['label'],
                    'matings' => $bucket['matings'],
                    'eggs_set' => $bucket['eggs_set'],
                    // Null, not 0 - "no eggs were set" is not "0% fertility".
                    'fertility' => $bucket['eggs_set'] > 0
                        ? round(($bucket['eggs_fertile'] / $bucket['eggs_set']) * 100, 1)
                        : null,
                    'hatch' => $bucket['eggs_fertile'] > 0
                        ? round(($bucket['eggs_hatched'] / $bucket['eggs_fertile']) * 100, 1)
                        : null,
                ];
            }, $buckets));
        });
    }

    /** @return array{fertility: float|null, hatch: float|null, matings: int} */
    #[Computed]
    public function breedingOverall(): array
    {
        return $this->remember('breeding:'.$this->trendMonths, function (): mixed {
            // The same window as the chart beneath the period selector. Counting all
            // time here put an all-time 81.3% next to a 75% month on screen.
            $row = BreedingRecord::query()
                ->where('mating_date', '>=', $this->trendStart()->toDateString())
                ->selectRaw('count(*) as matings')
                ->selectRaw('coalesce(sum(eggs_set), 0) as eggs_set')
                ->selectRaw('coalesce(sum(eggs_fertile), 0) as eggs_fertile')
                ->selectRaw('coalesce(sum(eggs_hatched), 0) as eggs_hatched')
                ->first();

            $set = (int) ($row->eggs_set ?? 0);
            $fertile = (int) ($row->eggs_fertile ?? 0);
            $hatched = (int) ($row->eggs_hatched ?? 0);

            return [
                'matings' => (int) ($row->matings ?? 0),
                'fertility' => $set > 0 ? round(($fertile / $set) * 100, 1) : null,
                'hatch' => $fertile > 0 ? round(($hatched / $fertile) * 100, 1) : null,
            ];
        });
    }

    /**
     * Birds with no parents recorded - the pedigree gaps worth filling.
     *
     * farmStock() is not a detail here: an outside bird has no parents BY
     * DEFINITION - she exists only as a node so the tree keeps the branch above
     * her - so counting outside birds made this tile report a backlog of
     * pedigree work that nobody can ever do, and which grows every time a
     * borrowed hen is recorded.
     */
    #[Computed]
    public function birdsWithoutPedigree(): int
    {
        return $this->remember('without-pedigree', function (): mixed {
            return Broodcock::query()
                ->farmStock()
                ->whereNull('sire_id')
                ->whereNull('dam_id')
                ->count();
        });
    }

    public function render(): View
    {
        return view('livewire.dashboard.overview')
            ->title('Dashboard');
    }
}
