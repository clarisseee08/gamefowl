<?php

declare(strict_types=1);

namespace App\Livewire\Broodcocks;

use App\Enums\BroodcockClass;
use App\Enums\BroodcockStatus;
use App\Enums\Sex;
use App\Models\Broodcock;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

final class Index extends Component
{
    use WithPagination;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(except: '')]
    public string $status = '';

    #[Url(except: '')]
    public string $class = '';

    #[Url(except: '')]
    public string $sex = '';

    #[Url(except: '')]
    public string $bloodline = '';

    /**
     * Whose birds to list: 'farm', 'outside' or 'all'.
     *
     * Defaults to the farm's OWN stock. Outside parents - borrowed and visiting
     * birds, created automatically by the breeding form so the pedigree keeps
     * the branch above them - are not livestock in this farm's care, and they
     * were previously mixed into this list with nothing to tell them apart.
     *
     * They stay reachable rather than hidden: a keeper still has to be able to
     * correct a borrowed hen's name or bloodline, and a bird you cannot find is
     * a bird you cannot fix.
     */
    #[Url(except: 'farm')]
    public string $ownership = 'farm';

    /** @var list<string> */
    private const OWNERSHIP = ['farm', 'outside', 'all'];

    #[Url(except: 'created_at')]
    public string $sortBy = 'created_at';

    #[Url(except: 'desc')]
    public string $sortDirection = 'desc';

    /** Columns a user is allowed to sort by - never interpolate raw input into SQL. */
    private const SORTABLE = ['band_number', 'name', 'bloodline', 'class', 'status', 'date_hatched', 'created_at'];

    public function mount(): void
    {
        $this->authorize('viewAny', Broodcock::class);
    }

    /** Any filter change must return to page 1, or the user lands on an empty page. */
    public function updating(string $property): void
    {
        if ($property !== 'page') {
            $this->resetPage();
        }
    }

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

    public function clearFilters(): void
    {
        // reset() restores declared defaults, so ownership returns to 'farm'
        // rather than to an empty string that would match nothing.
        $this->reset(['search', 'status', 'class', 'sex', 'bloodline', 'ownership']);
        $this->resetPage();
    }

    public function hasActiveFilters(): bool
    {
        return $this->search !== ''
            || $this->status !== ''
            || $this->class !== ''
            || $this->sex !== ''
            || $this->bloodline !== ''
            || $this->ownership !== 'farm';
    }

    /**
     * Clears one filter without touching the others, so a filter chip's × does
     * what it looks like it does.
     */
    public function clearFilter(string $filter): void
    {
        if (! in_array($filter, ['search', 'status', 'class', 'sex', 'bloodline', 'ownership'], true)) {
            return;
        }

        $this->reset($filter);
        $this->resetPage();
    }

    // -----------------------------------------------------------------
    // Bulk selection
    //
    // The selection lives in the component rather than in Alpine because it has
    // to survive pagination and a filter change - a selection that silently
    // empties when you turn the page is worse than no selection at all.
    // -----------------------------------------------------------------

    /** @var array<int, int> */
    public array $selected = [];

    public function toggleSelectPage(): void
    {
        $ids = $this->broodcocks->pluck('id')->all();
        $allOnPageSelected = ! array_diff($ids, $this->selected);

        $this->selected = $allOnPageSelected
            ? array_values(array_diff($this->selected, $ids))
            : array_values(array_unique([...$this->selected, ...$ids]));
    }

    public function clearSelection(): void
    {
        $this->selected = [];
    }

    /**
     * Whether the bulk Delete button should be offered at all.
     *
     * BroodcockPolicy::delete() takes a model, so a class-level @can in the view
     * throws "too few arguments" - a class-level check only works for methods
     * that take no instance, like viewAny and create. This asks the real
     * question instead: is there anything in the current selection this user is
     * actually allowed to delete? One bounded query, and only when something is
     * selected.
     */
    #[Computed]
    public function canDeleteSelection(): bool
    {
        if ($this->selected === []) {
            return false;
        }

        return Broodcock::query()
            ->whereIn('id', $this->selected)
            ->get()
            ->contains(fn (Broodcock $bird) => auth()->user()?->can('delete', $bird));
    }

    /**
     * Deletes the current selection.
     *
     * Deliberately a loop over the SAME authorize-then-delete path a single row
     * already uses, not a mass query. A `whereIn(...)->delete()` would bypass
     * the Policy and the model's own delete handling, which is exactly the kind
     * of shortcut that turns a bulk action into a data-loss incident. Rows the
     * user may not delete are skipped rather than failing the whole batch.
     */
    public function deleteSelected(): void
    {
        $deleted = 0;

        foreach (Broodcock::query()->whereIn('id', $this->selected)->get() as $bird) {
            if (auth()->user()?->can('delete', $bird)) {
                $bird->delete();
                $deleted++;
            }
        }

        $skipped = count($this->selected) - $deleted;
        $this->selected = [];
        $this->resetPage();

        session()->flash('success', $skipped > 0
            ? "{$deleted} ".str('bird')->plural($deleted)." removed. {$skipped} could not be removed with your role."
            : "{$deleted} ".str('bird')->plural($deleted).' removed from the active records.');
    }

    /** @return LengthAwarePaginator<int, Broodcock> */
    #[Computed]
    public function broodcocks(): LengthAwarePaginator
    {
        $sortBy = in_array($this->sortBy, self::SORTABLE, true) ? $this->sortBy : 'created_at';
        $direction = $this->sortDirection === 'asc' ? 'asc' : 'desc';

        return Broodcock::query()
            // Eager-loaded because the table renders the primary
            // photo per row. Without this the list issues 2 extra queries per
            // row - and each one is a round trip to Tokyo.
            ->with(['primaryPhoto'])
            ->tap(fn ($query) => $this->applyOwnership($query))
            ->search($this->search)
            ->status($this->status)
            ->classGrade($this->class)
            ->sex($this->sex)
            ->bloodline($this->bloodline)
            ->orderBy($sortBy, $direction)
            ->paginate(config('gfms.per_page'));
    }

    /**
     * Distinct bloodlines actually present in the data, so the filter never
     * offers an option that returns nothing.
     *
     * @return array<int, string>
     */
    #[Computed]
    public function bloodlineOptions(): array
    {
        return Broodcock::query()
            // Scoped the same way as the list, so the filter never offers a
            // bloodline that only exists on birds the current view excludes.
            ->tap(fn ($query) => $this->applyOwnership($query))
            ->whereNotNull('bloodline')
            ->distinct()
            ->orderBy('bloodline')
            ->pluck('bloodline')
            ->all();
    }

    /**
     * Narrows a query to the selected ownership.
     *
     * An unrecognised value falls through to farm stock rather than to "all" -
     * the URL is user input, and the safe failure for a customer-adjacent list
     * is to show less, not more.
     *
     * @param  Builder<Broodcock>  $query
     */
    private function applyOwnership(Builder $query): void
    {
        match ($this->ownership) {
            'all' => null,
            'outside' => $query->external(),
            default => $query->farmStock(),
        };
    }

    /** @return array<int, string> */
    public function ownershipOptions(): array
    {
        return self::OWNERSHIP;
    }

    /** @return array<int, BroodcockStatus> */
    public function statusOptions(): array
    {
        return BroodcockStatus::cases();
    }

    /** @return array<int, BroodcockClass> */
    public function classOptions(): array
    {
        return BroodcockClass::cases();
    }

    /** @return array<int, Sex> */
    public function sexOptions(): array
    {
        return Sex::cases();
    }

    public function render(): View
    {
        return view('livewire.broodcocks.index');
    }
}
