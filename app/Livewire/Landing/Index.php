<?php

declare(strict_types=1);

namespace App\Livewire\Landing;

use App\Models\Broodcock;
use App\Support\RecordCache;
use Illuminate\Contracts\View\View;
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
     * paper has exactly one honest way to get there: show the bloodlines. Band
     * tags here are the same tags the catalogue below uses, resolved by the
     * same App\Support\BandTag, so the colours a visitor meets in the masthead
     * are the ones they then see on the birds.
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
