<?php

declare(strict_types=1);

namespace App\Livewire\Catalog;

use App\Enums\BroodcockClass;
use App\Enums\Sex;
use App\Models\Broodcock;
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
 * THE SHELL DEPENDS ON WHO IS LOOKING - see layoutForViewer().
 *
 * For a CUSTOMER this is the whole application, and it gets the bare catalogue
 * shell: full-bleed, no console sidebar. A dense app rail is wrong for a
 * photo-led browse, and it would show a customer navigation for screens they
 * cannot open.
 *
 * For STAFF it is one screen among many, and stripping the sidebar stranded
 * them: every other console screen has the rail, this one replaced it with a
 * single "Console" button, so the way back to Broodcocks was two clicks and a
 * guess. The original reasoning was right about customers and wrong about the
 * people who use this system all day.
 */
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
        return Broodcock::query()
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
            ->paginate(12);
    }

    /** @return array<int, string> */
    #[Computed]
    public function bloodlineOptions(): array
    {
        return Broodcock::query()
            ->farmStock()
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
        return view('livewire.catalog.index')
            ->layout($this->layoutForViewer())
            ->title('Catalogue');
    }

    /**
     * Which shell this screen renders inside.
     *
     * Set HERE rather than with a #[Layout] attribute, and that is forced
     * rather than stylistic: Livewire applies the attribute inside its render
     * hook, AFTER render() has returned, and it overwrites whatever the view
     * asked for. An attribute plus a ->layout() call is not a conflict the
     * framework resolves in your favour - the attribute simply wins, silently.
     *
     * Staff get the console shell so the catalogue keeps the sidebar every
     * other internal screen has. Customers get the bare shell, which is the
     * only navigation they could use anyway.
     */
    private function layoutForViewer(): string
    {
        return auth()->user()?->isInternal()
            ? 'layouts::app'
            : 'layouts::catalog';
    }
}
