<?php

declare(strict_types=1);

namespace App\Livewire\Catalog;

use App\Enums\BroodcockClass;
use App\Enums\Sex;
use App\Models\Broodcock;
use App\Support\RecordCache;
use Illuminate\Contracts\View\View;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Browsing the farm's stock: the grid, the filters and the pagination.
 *
 * NESTED, NEVER ROUTED. Two pages show this - /catalog, where it is the whole
 * screen, and the landing page, where it sits between the farm's story and the
 * form for arranging a visit. It lives in its own component because two
 * surfaces rendering the same stock from two implementations is exactly how
 * they drift apart: a filter fixed in one place and not the other, a bird
 * visible on one page and not the other.
 *
 * Whoever embeds it owns the page chrome. This class sets no layout and no
 * title - see render() at the bottom.
 *
 * What it shows is deliberately not what the staff broodcock index shows. A
 * customer browsing available stock and a record keeper doing data entry want
 * genuinely different things: this is photo-led, hides farm-internal
 * vocabulary (pens, statuses like "resting"), and only ever lists birds that
 * are actually on the farm.
 *
 * PUBLIC. No account is needed - it is the farm's advertisement, and requiring
 * a login to look at stock defeats the purpose. BroodcockPolicy::viewAny()
 * accepts a null user; the query below is what decides which birds a visitor
 * is shown. Authorization is still the Policy: this narrows what is SHOWN, it
 * is not what enforces access.
 */
final class Browse extends Component
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

    /**
     * The heading level this component's own title renders at.
     *
     * THE REASON THIS IS A PROP. Browse is routed at /catalog, where "Our
     * Gamefowl" is genuinely the page's heading - and it is ALSO embedded in
     * the front page below the farm's nameplate, where it is not. Hard-coding
     * <h1> gave / two of them, which docs/redesign/gaps.md recorded during the
     * migration and left standing.
     *
     * Two h1s is not a tidiness complaint. A screen-reader user listing a
     * page's headings to find their way around gets two competing answers to
     * "what is this page", and the second one is a section of the first.
     *
     * Only the tag changes. The size, weight and brand marker are set by the
     * classes on the element, so both renderings look identical.
     */
    public string $headingLevel = 'h1';

    public function mount(string $headingLevel = 'h1'): void
    {
        $this->authorize('viewAny', Broodcock::class);

        // Allow-listed rather than echoed: this value reaches the template as a
        // raw tag name, and the one thing a tag name must never be is whatever
        // a caller happened to pass.
        $this->headingLevel = in_array($headingLevel, ['h1', 'h2'], true) ? $headingLevel : 'h1';
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

    /*
     * No ->layout() and no ->title() here, deliberately.
     *
     * This component is NESTED - by Catalog\Index on /catalog and by
     * Landing\Index on the front page - and a child that tries to choose a
     * layout is either ignored or renders a second <html>. Whoever embeds it
     * owns the page chrome.
     */
    public function render(): View
    {
        return view('livewire.catalog.browse');
    }
}
