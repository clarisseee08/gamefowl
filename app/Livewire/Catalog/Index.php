<?php

declare(strict_types=1);

namespace App\Livewire\Catalog;

use App\Enums\BroodcockClass;
use App\Enums\Sex;
use App\Livewire\Concerns\ChoosesShellByViewer;
use App\Models\Broodcock;
use App\Support\RecordCache;
use Illuminate\Contracts\View\View;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
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
 * PUBLIC. No account is needed to reach this screen - it is the farm's
 * advertisement, and requiring a login to look at stock defeats the purpose.
 * BroodcockPolicy::viewAny() accepts a null user; the query below is what
 * decides which birds a visitor is shown.
 *
 * THE SHELL DEPENDS ON WHO IS LOOKING - see ChoosesShellByViewer.
 */
final class Index extends Component
{
    use ChoosesShellByViewer;
    use WithPagination;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(except: '')]
    public string $bloodline = '';

    #[Url(except: '')]
    public string $sex = '';

    #[Url(except: '')]
    public string $class = '';

    /**
     * Show only birds the farm is actually offering.
     *
     * OFF by default, and that is a judgement rather than an oversight. The
     * farm marks almost nothing for sale at any given time, so defaulting this
     * on would present an empty catalogue to a customer who came to look at the
     * stock. The catalogue's job is to show what the farm HAS; this narrows it
     * to what the farm will part with.
     */
    #[Url(except: false)]
    public bool $forSaleOnly = false;

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
        $this->reset(['search', 'bloodline', 'sex', 'class', 'forSaleOnly']);
        $this->resetPage();
    }

    public function hasActiveFilters(): bool
    {
        return $this->search !== '' || $this->bloodline !== ''
            || $this->sex !== '' || $this->class !== '' || $this->forSaleOnly;
    }

    /** @return LengthAwarePaginator<int, Broodcock> */
    #[Computed]
    public function birds(): LengthAwarePaginator
    {
        // Cached because each of the queries below costs a ~240ms round trip in
        // production regardless of how little it asks for - see
        // App\Support\RecordCache for the measurements. The key carries every
        // filter and the page number, so a customer who has narrowed the list
        // is never handed somebody else's view of it.
        return RecordCache::remember($this->birdsCacheKey(), fn (): LengthAwarePaginator => Broodcock::query()
            // Photo per card, so it must be eager-loaded or the grid is one
            // extra query per bird against a database in Tokyo.
            ->with('primaryPhoto')
            // farmStock(): a borrowed or visiting hen is recorded so the
            // pedigree stays whole, but she belongs to somebody else. Offering
            // her in the customer catalogue advertises stock this farm cannot
            // sell, which is a conversation the farm has to walk back.
            ->farmStock()
            // Sold and deceased birds are not part of the catalogue. This is
            // presentation, not security - the Policy still governs access.
            ->onFarm()
            ->when($this->forSaleOnly, fn ($query) => $query->where('for_sale', true))
            ->search($this->search)
            ->bloodline($this->bloodline)
            ->sex($this->sex)
            ->classGrade($this->class)
            ->orderBy('name')
            ->paginate(12));
    }

    /**
     * One cache key per distinct view of the catalogue.
     *
     * Hashed rather than concatenated because `search` is free text typed by a
     * customer. Pasted straight into a key it brings slashes, colons and
     * whatever length it likes into a filename on the cache disk.
     */
    private function birdsCacheKey(): string
    {
        return 'catalog:grid:'.md5(serialize([
            $this->search,
            $this->bloodline,
            $this->sex,
            $this->class,
            $this->forSaleOnly,
            $this->getPage(),
        ]));
    }

    /** @return array<int, string> */
    #[Computed]
    public function bloodlineOptions(): array
    {
        // The filter dropdown only changes when a bird carrying a bloodline
        // nobody has used before is registered, which is rare - but unCached it
        // cost a full round trip on every single page view.
        return RecordCache::remember('catalog:bloodlines', fn (): array => Broodcock::query()
            ->farmStock()
            ->onFarm()
            ->whereNotNull('bloodline')
            ->distinct()
            ->orderBy('bloodline')
            ->pluck('bloodline')
            ->all());
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
        return view('livewire.catalog.index')
            ->layout($this->viewerShell())
            ->title('Catalogue');
    }
}
