<?php

declare(strict_types=1);

namespace App\Livewire\Landing;

use App\Models\Broodcock;
use App\Support\RecordCache;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * The farm's front door.
 *
 * `/` used to be a redirect with a comment explaining that the front door
 * depends on who is knocking. Half of that is still true - staff go to the
 * dashboard, because their working day starts there and an advertisement is
 * no use to them - but the other half sent the public to /catalog, which
 * meant the farm's website opened on a grid of birds with nothing saying
 * whose farm it was or how to reach it.
 *
 * So this page exists, and the catalogue is ON it rather than being it.
 *
 * THE WHOLE CATALOGUE, with its filters, not a curated strip. That was a
 * deliberate choice and it carries a known cost: a page holding both a
 * filterable grid and a form is heavier than either alone, and the grid
 * resizing moves what sits below it. Both are mitigated rather than ignored -
 * the grid is paginated to twelve, which bounds the movement to a single row,
 * and the hero anchors straight to the form so nobody has to scroll past the
 * stock to reach it.
 */
final class Index extends Component
{
    public function mount(): void
    {
        /*
         * Staff never see this page.
         *
         * Not an authorization rule - there is nothing here a member of staff
         * may not look at. It is that the dashboard is where their work is,
         * and landing them on the shop window every morning would be a step to
         * click past.
         */
        if (auth()->user()?->isInternal()) {
            $this->redirectRoute('dashboard', navigate: false);
        }
    }

    /**
     * The bloodlines this farm actually keeps.
     *
     * THE ONLY COLOUR THE MASTHEAD IS ALLOWED. Colour in this system means
     * bloodline and nothing else, so a hero that wants to be more than ink on
     * paper has exactly one honest way to get there: show the bloodlines.
     *
     * Rendered as x-bloodline-chip rather than x-band-tag - a band tag
     * describes one BIRD, and given no band number it correctly renders "Not
     * yet banded", which is meaningless about a bloodline. The chip shares the
     * same App\Support\BandTag resolver, so the colours a visitor meets in the
     * masthead are the ones they then see on the birds below.
     *
     * Same farmStock()->onFarm() filter the catalogue uses, so the masthead
     * cannot advertise a bloodline the list below does not contain.
     *
     * @return list<string>
     */
    #[Computed]
    public function bloodlines(): array
    {
        return RecordCache::remember('landing:bloodlines', fn (): array => Broodcock::query()
            ->farmStock()
            ->onFarm()
            ->whereNotNull('bloodline')
            ->where('bloodline', '!=', '')
            ->distinct()
            ->orderBy('bloodline')
            ->pluck('bloodline')
            ->all());
    }

    /**
     * How many birds are on the farm right now.
     *
     * Cached with everything else: this is the most-hit page in the
     * application and every uncached query against the production database
     * costs roughly 240ms of round trip.
     */
    #[Computed]
    public function stockCount(): int
    {
        return RecordCache::remember('landing:stock-count', fn (): int => Broodcock::query()
            ->farmStock()
            ->onFarm()
            ->count());
    }

    /**
     * The bird whose photograph leads the page.
     *
     * Chosen by band number from config('gfms.home_hero_band') - an editorial
     * choice, explained there - and falling back to the first bird with a
     * photograph, so a stale setting can never leave the hero empty. Null when
     * no bird on the farm has a photograph at all.
     */
    #[Computed]
    public function heroBird(): ?Broodcock
    {
        $band = (string) config('gfms.home_hero_band');

        return RecordCache::remember('landing:hero:'.$band, function () use ($band): ?Broodcock {
            $withPhoto = Broodcock::query()
                ->with('primaryPhoto')
                ->farmStock()
                ->onFarm()
                ->whereHas('primaryPhoto');

            return ($band !== '' ? (clone $withPhoto)->where('band_number', $band)->first() : null)
                ?? $withPhoto->orderByDesc('date_hatched')->first();
        });
    }

    /**
     * The birds in the grid - eight, never including the hero.
     *
     * EACH PHOTOGRAPH IS USED ONCE on this page, so the bird in the hero is
     * left out here; it is still one click away under "See all". Banded birds
     * lead, then any offered for sale, then the youngest - the same order the
     * register has always used, because an unbanded bird reads "Not yet
     * banded" and is the least informative card to open on.
     *
     * primaryPhoto is eager-loaded: one row per bird, and an uncached query
     * against production costs ~240ms.
     *
     * @return Collection<int, Broodcock>
     */
    #[Computed]
    public function gridBirds(): Collection
    {
        $heroId = $this->heroBird?->id;

        return RecordCache::remember('landing:grid:'.($heroId ?? 'none'), fn () => Broodcock::query()
            ->with('primaryPhoto')
            ->farmStock()
            ->onFarm()
            ->when($heroId, fn ($q) => $q->whereKeyNot($heroId))
            ->orderByRaw('CASE WHEN band_number IS NULL OR band_number = ? THEN 1 ELSE 0 END', [''])
            ->orderByDesc('for_sale')
            ->orderByDesc('date_hatched')
            ->limit(8)
            ->get());
    }

    /**
     * The bloodlines to offer as filters: the ones among the birds in the grid.
     *
     * Not every bloodline on the farm - a pill for a bloodline whose only bird
     * is the hero would empty the grid when pressed, which is the same trap
     * the catalogue's "Available birds only" box used to set.
     *
     * @return list<string>
     */
    #[Computed]
    public function gridBloodlines(): array
    {
        return $this->gridBirds
            ->pluck('bloodline')
            ->filter(fn ($line) => filled($line))
            ->unique()
            ->sort(SORT_NATURAL | SORT_FLAG_CASE)
            ->values()
            ->all();
    }

    /**
     * One real bird, with its sire and dam, for the pedigree block.
     *
     * THE RULE: a bird on the farm whose sire AND dam are both recorded, and
     * whose parents' bloodlines are lines the farm itself keeps - compared
     * without case, because the records hold "5k Sweater" and "5K Sweater".
     * That last condition is what keeps a slip of data entry (a dam whose
     * bloodline reads "Broodcock") off the front page. Lowest id wins, so the
     * choice is stable from one visit to the next.
     *
     * Null when no bird qualifies, and the block shows the farm's bloodline
     * counts instead. Nothing is invented to fill it.
     */
    #[Computed]
    public function pedigreeBird(): ?Broodcock
    {
        $kept = array_map('mb_strtolower', $this->bloodlines);

        return RecordCache::remember('landing:pedigree', fn () => Broodcock::query()
            ->with(['sire', 'dam'])
            ->farmStock()
            ->onFarm()
            ->whereNotNull('sire_id')
            ->whereNotNull('dam_id')
            ->orderBy('id')
            ->get()
            ->first(fn (Broodcock $bird): bool => $bird->sire && $bird->dam
                && in_array(mb_strtolower((string) $bird->sire->bloodline), $kept, true)
                && in_array(mb_strtolower((string) $bird->dam->bloodline), $kept, true)));
    }

    /**
     * How many birds on the farm have at least one parent on record.
     *
     * For the proof line under the hero. It is the honest version of a claim
     * the design brief suggested - "sire and dam recorded for each" - which
     * the data does not support: most birds have one parent or both recorded,
     * not all. The page says how many, and the number moves as the farm
     * records more.
     */
    #[Computed]
    public function parentageCount(): int
    {
        return RecordCache::remember('landing:parentage-count', fn (): int => Broodcock::query()
            ->farmStock()
            ->onFarm()
            ->where(fn ($q) => $q->whereNotNull('sire_id')->orWhereNotNull('dam_id'))
            ->count());
    }

    /**
     * How many birds carry each bloodline - the pedigree block's fallback.
     *
     * @return array<string, int>
     */
    #[Computed]
    public function bloodlineCounts(): array
    {
        return RecordCache::remember('landing:bloodline-counts', fn (): array => Broodcock::query()
            ->farmStock()
            ->onFarm()
            ->whereNotNull('bloodline')
            ->where('bloodline', '!=', '')
            ->selectRaw('bloodline, count(*) as birds')
            ->groupBy('bloodline')
            ->orderByDesc('birds')
            ->orderBy('bloodline')
            ->pluck('birds', 'bloodline')
            ->map(fn ($n) => (int) $n)
            ->all());
    }

    public function render(): View
    {
        /*
         * Not the farm's name. head-meta already appends
         * config('gfms.system.short'), which on this installation IS the farm's
         * name - passing it here produced "SSGuad Game Farm · SSGuad Game Farm"
         * in the browser tab.
         */
        return view('livewire.landing.index')
            ->layout('layouts::catalog')
            ->title('Broodcocks and breeding stock');
    }
}
