<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Broodcock;

/**
 * The two figures the public pages share.
 *
 * WHY THIS EXISTS. Splitting the front page into separate pages left the stock
 * count and the bloodline list needed in four places - the front page, About,
 * Visit and the catalogue's own heading. Each one reaching for its own query
 * is how two pages come to disagree about how many birds the farm keeps, which
 * is exactly the kind of thing a visitor notices and a developer does not.
 *
 * BOTH READ THROUGH farmStock()->onFarm(), the same pair the catalogue filters
 * on. That is the rule: no public page may advertise a bird the catalogue
 * would not list. An outside hen borrowed for one mating is a node in a family
 * tree, not stock, and a bird that died is not either.
 *
 * Cached, because every uncached query in production is a ~240ms round trip to
 * Tokyo and these sit on the most-visited pages in the application. The keys
 * are shared with App\Livewire\Landing\Index deliberately - one cache entry,
 * invalidated once.
 */
final class PublicFarm
{
    /**
     * The bloodlines the farm currently keeps, alphabetically.
     *
     * @return list<string>
     */
    public static function bloodlines(): array
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

    /** How many birds are on the farm right now. */
    public static function stockCount(): int
    {
        return RecordCache::remember('landing:stock-count', fn (): int => Broodcock::query()
            ->farmStock()
            ->onFarm()
            ->count());
    }
}
