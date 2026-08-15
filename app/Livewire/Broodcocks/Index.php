<?php

declare(strict_types=1);

namespace App\Livewire\Broodcocks;

use App\Enums\BroodcockClass;
use App\Enums\BroodcockStatus;
use App\Enums\Sex;
use App\Models\Broodcock;
use App\Models\Pen;
use Illuminate\Contracts\View\View;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
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

    #[Url(except: '')]
    public string $breed = '';

    #[Url(except: '')]
    public string $pen = '';

    #[Url(except: 'created_at')]
    public string $sortBy = 'created_at';

    #[Url(except: 'desc')]
    public string $sortDirection = 'desc';

    /** Columns a user is allowed to sort by - never interpolate raw input into SQL. */
    private const SORTABLE = ['band_number', 'name', 'breed', 'bloodline', 'class', 'status', 'date_hatched', 'created_at'];

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
        $this->reset(['search', 'status', 'class', 'sex', 'bloodline', 'breed', 'pen']);
        $this->resetPage();
    }

    public function hasActiveFilters(): bool
    {
        return $this->search !== ''
            || $this->status !== ''
            || $this->class !== ''
            || $this->sex !== ''
            || $this->bloodline !== ''
            || $this->breed !== ''
            || $this->pen !== '';
    }

    /** @return LengthAwarePaginator<int, Broodcock> */
    #[Computed]
    public function broodcocks(): LengthAwarePaginator
    {
        $sortBy = in_array($this->sortBy, self::SORTABLE, true) ? $this->sortBy : 'created_at';
        $direction = $this->sortDirection === 'asc' ? 'asc' : 'desc';

        return Broodcock::query()
            // Eager-loaded because the table renders pen name and primary
            // photo per row. Without this the list issues 2 extra queries per
            // row - and each one is a round trip to Tokyo.
            ->with(['pen:id,code,name', 'primaryPhoto'])
            ->search($this->search)
            ->status($this->status)
            ->classGrade($this->class)
            ->sex($this->sex)
            ->bloodline($this->bloodline)
            ->breed($this->breed)
            ->pen($this->pen)
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
            ->whereNotNull('bloodline')
            ->distinct()
            ->orderBy('bloodline')
            ->pluck('bloodline')
            ->all();
    }

    /** @return array<int, string> */
    #[Computed]
    public function breedOptions(): array
    {
        return Broodcock::query()
            ->whereNotNull('breed')
            ->distinct()
            ->orderBy('breed')
            ->pluck('breed')
            ->all();
    }

    /** @return Collection<int, Pen> */
    #[Computed]
    public function penOptions()
    {
        // Customers never see pens, so do not even build the list for them.
        if (! auth()->user()?->isInternal()) {
            return collect();
        }

        return Pen::query()->orderBy('code')->get(['id', 'code', 'name']);
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
