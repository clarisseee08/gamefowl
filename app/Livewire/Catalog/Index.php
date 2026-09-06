<?php

declare(strict_types=1);

namespace App\Livewire\Catalog;

use App\Enums\BroodcockClass;
use App\Enums\Sex;
use App\Models\Broodcock;
use Illuminate\Contracts\View\View;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * The customer-facing catalogue.
 *
 * Deliberately a separate screen from the staff broodcock index rather than a
 * role-switched version of it. A customer browsing available stock and a
 * record keeper doing data entry want genuinely different things: the
 * catalogue is photo-led, hides farm-internal vocabulary (pens, statuses like
 * "resting"), and only ever shows birds that are actually on the farm.
 *
 * Authorization is still the Policy - this class narrows what is SHOWN, it is
 * not what enforces access.
 */
/*
 * The catalogue is the PUBLIC surface and gets its own shell: full-bleed like
 * the console, but without the console sidebar. A dense app rail is wrong for a
 * photo-led browse, and it would show a customer navigation for screens they
 * cannot open.
 */
#[Layout('layouts::catalog')]
final class Index extends Component
{
    use WithPagination;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(except: '')]
    public string $bloodline = '';

    #[Url(except: '')]
    public string $sex = '';

    #[Url(except: '')]
    public string $class = '';

    public function mount(): void
    {
        $this->authorize('viewAny', Broodcock::class);
    }

    public function updating(string $property): void
    {
        if ($property !== 'page') {
            $this->resetPage();
        }
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'bloodline', 'sex', 'class']);
        $this->resetPage();
    }

    public function hasActiveFilters(): bool
    {
        return $this->search !== '' || $this->bloodline !== ''
            || $this->sex !== '' || $this->class !== '';
    }

    /** @return LengthAwarePaginator<int, Broodcock> */
    #[Computed]
    public function birds(): LengthAwarePaginator
    {
        return Broodcock::query()
            // Photo per card, so it must be eager-loaded or the grid is one
            // extra query per bird against a database in Tokyo.
            ->with('primaryPhoto')
            // Sold and deceased birds are not part of the catalogue. This is
            // presentation, not security - the Policy still governs access.
            ->onFarm()
            ->search($this->search)
            ->bloodline($this->bloodline)
            ->sex($this->sex)
            ->classGrade($this->class)
            ->orderBy('name')
            ->paginate(12);
    }

    /** @return array<int, string> */
    #[Computed]
    public function bloodlineOptions(): array
    {
        return Broodcock::query()
            ->onFarm()
            ->whereNotNull('bloodline')
            ->distinct()
            ->orderBy('bloodline')
            ->pluck('bloodline')
            ->all();
    }

    /** @return array<int, Sex> */
    public function sexOptions(): array
    {
        return Sex::cases();
    }

    /** @return array<int, BroodcockClass> */
    public function classOptions(): array
    {
        return BroodcockClass::cases();
    }

    public function render(): View
    {
        return view('livewire.catalog.index');
    }
}
