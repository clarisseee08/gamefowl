<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Support\RecordCache;

/**
 * Any write to a farm record orphans every cached read (see RecordCache).
 *
 * A trait rather than a booted() per model because the cache now covers the
 * console - the dashboard counts health, breeding and mortality records - so
 * every model those screens read has to take part, and a model that forgets
 * to is a dashboard that keeps showing yesterday's overdue vaccinations.
 *
 * `saved` covers both creation and update; `deleted` covers a soft delete as
 * well as a hard one, and `restored` the undo of one. Laravel calls
 * boot<TraitName>() for every model that uses the trait.
 */
trait InvalidatesRecordCache
{
    protected static function bootInvalidatesRecordCache(): void
    {
        $forget = static function (): void {
            RecordCache::invalidate();
        };

        static::saved($forget);
        static::deleted($forget);

        if (method_exists(static::class, 'restored')) {
            static::restored($forget);
        }
    }
}
