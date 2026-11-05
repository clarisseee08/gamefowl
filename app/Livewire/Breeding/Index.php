<?php

declare(strict_types=1);

namespace App\Livewire\Breeding;

use App\Livewire\Concerns\ChoosesPageSize;
use App\Models\BreedingRecord;
use App\Models\Broodcock;
use Illuminate\Contracts\View\View;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

final class Index extends Component
{
    use ChoosesPageSize;
    use WithPagination;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(except: '')]
    public string $from = '';

    #[Url(except: '')]
    public string $to = '';

    #[Url(except: '')]
    public string $bloodline = '';

    #[Url(except: 'mating_date')]
    public string $sortBy = 'mating_date';

    #[Url(except: 'desc')]
    public string $sortDirection = 'desc';

    /** Columns a user is allowed to sort by - never interpolate raw input into SQL. */
    private const SORTABLE = ['mating_date', 'eggs_set', 'eggs_fertile', 'eggs_hatched'];

    /**
     * One press sets the column and the direction together.
     *
     * Pressing the column already sorted flips it; pressing another takes it
     * ascending, because "show me this column" almost always means "from the
     * top" on first press.
     */
    public function sort(string $column): void
    {
        if (! in_array($column, self::SORTABLE, true)) {
            return;
        }

        if ($this->sortBy === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDirection = 'asc';
        }

        $this->resetPage();
    }

    /**
     * The add / edit form, open in a side sheet over this list (the owner's
     * decision: create and edit never leave the list). The old create and
     * edit URLs still work - they render this list with the form open. The
     * form component itself is unchanged: it authorizes, validates and saves
     * exactly as it did as a page.
     */
    public bool $showForm = false;

    #[Locked]
    public ?int $formRecordId = null;

    /**
     * A new form is built ahead, so Add opens instantly. Closing the dialog
     * drops any edit and re-keys the new form, so the next Add is blank.
     */
    public int $formNonce = 0;

    public function updatedShowForm(bool $open): void
    {
        if ($open) {
            return;
        }

        $this->formRecordId = null;
        $this->formBroodcockId = null;
        $this->formNonce++;
    }

    /** Called by the dialog when a form that was typed in is closed: start the next one blank. */
    public function resetForm(): void
    {
        $this->showForm = false;
        $this->updatedShowForm(false);
    }

    #[Locked]
    public ?int $formBroodcockId = null;

    public function mount(?BreedingRecord $record = null): void
    {
        $this->authorize('viewAny', BreedingRecord::class);

        // The form is only built for someone allowed to create, so it can no
        // longer refuse an /edit or /create address itself: checked here.
        if ($record?->exists) {
            $this->authorize('update', $record);
            $this->openForm($record->id);
        } elseif (request()->routeIs('breeding.create')) {
            $this->authorize('create', BreedingRecord::class);
            $this->openForm(null, null);
        }
    }

    public function openForm(?int $recordId = null, ?int $broodcockId = null): void
    {
        $this->formRecordId = $recordId;
        $this->formBroodcockId = $broodcockId;
        $this->showForm = true;
    }

    public function updating(string $property): void
    {
        if ($property === 'showForm') {
            return;
        }

        if ($property !== 'page') {
            $this->resetPage();
        }
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'from', 'to', 'bloodline']);
        $this->resetPage();
    }

    public function hasActiveFilters(): bool
    {
        return $this->search !== '' || $this->from !== '' || $this->to !== '' || $this->bloodline !== '';
    }

    /** @return LengthAwarePaginator<int, BreedingRecord> */
    #[Computed]
    public function records(): LengthAwarePaginator
    {
        return BreedingRecord::query()
            // Every row renders both parents' names, so they must be loaded up
            // front - otherwise this is 2 extra queries per row against a
            // database in another country.
            //
            // withTrashed() because deleting a bird keeps its breeding history:
            // without it a deleted parent comes back null and the page fails.
            ->with([
                'sire' => fn ($query) => $query->withTrashed()->select(['id', 'name', 'band_number', 'bloodline', 'deleted_at'])->with('primaryPhoto'),
                'dam' => fn ($query) => $query->withTrashed()->select(['id', 'name', 'band_number', 'bloodline', 'deleted_at'])->with('primaryPhoto'),
            ])
            ->between($this->from ?: null, $this->to ?: null)
            ->when($this->search !== '', function ($query): void {
                $term = "%{$this->search}%";
                // Grouped: an ungrouped orWhereHas would let a mating whose dam
                // matches escape the date and bloodline filters around it.
                $query->where(function ($query) use ($term): void {
                    $query->whereHas('sire', fn ($q) => $q->whereLike('name', $term, caseSensitive: false)
                        ->orWhereLike('band_number', $term, caseSensitive: false))
                        ->orWhereHas('dam', fn ($q) => $q->whereLike('name', $term, caseSensitive: false)
                            ->orWhereLike('band_number', $term, caseSensitive: false));
                });
            })
            ->when($this->bloodline !== '', function ($query): void {
                $query->whereHas('sire', fn ($q) => $q->where('bloodline', $this->bloodline));
            })
            // Checked against the allow-list a second time: the URL can set
            // $sortBy directly without ever passing through sort().
            ->orderBy(
                in_array($this->sortBy, self::SORTABLE, true) ? $this->sortBy : 'mating_date',
                $this->sortDirection === 'asc' ? 'asc' : 'desc'
            )
            ->orderByDesc('id')
            ->paginate($this->pageSize());
    }

    /**
     * Farm-wide averages for the filtered set.
     *
     * Computed in SQL rather than by summing model accessors, so the figures
     * cover every matching record and not just the current page.
     *
     * @return array{matings: int, eggs_set: int, eggs_fertile: int, eggs_hatched: int, fertility: float|null, hatch: float|null}
     */
    #[Computed]
    public function summary(): array
    {
        $totals = BreedingRecord::query()
            ->between($this->from ?: null, $this->to ?: null)
            ->selectRaw('count(*) as matings')
            ->selectRaw('coalesce(sum(eggs_set), 0) as eggs_set')
            ->selectRaw('coalesce(sum(eggs_fertile), 0) as eggs_fertile')
            ->selectRaw('coalesce(sum(eggs_hatched), 0) as eggs_hatched')
            ->first();

        $set = (int) ($totals->eggs_set ?? 0);
        $fertile = (int) ($totals->eggs_fertile ?? 0);
        $hatched = (int) ($totals->eggs_hatched ?? 0);

        return [
            'matings' => (int) ($totals->matings ?? 0),
            'eggs_set' => $set,
            'eggs_fertile' => $fertile,
            'eggs_hatched' => $hatched,
            'fertility' => $set > 0 ? round(($fertile / $set) * 100, 1) : null,
            'hatch' => $fertile > 0 ? round(($hatched / $fertile) * 100, 1) : null,
        ];
    }

    /** @return array<int, string> */
    #[Computed]
    public function bloodlineOptions(): array
    {
        return Broodcock::query()
            ->whereNotNull('bloodline')
            ->distinct()
            ->orderBy('bloodline')
            ->pluck('bloodline')
            ->all();
    }

    public function render(): View
    {
        return view('livewire.breeding.index')
            ->title('Breeding Records');
    }
}
