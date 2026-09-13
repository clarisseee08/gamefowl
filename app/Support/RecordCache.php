<?php

declare(strict_types=1);

namespace App\Support;

use Closure;
use Illuminate\Support\Facades\Cache;

/**
 * Cached reads of farm records, invalidated as a whole on any write.
 *
 * WHY THIS EXISTS AT ALL. The application database is a Supabase project in
 * ap-northeast-1 and the web service runs in Render's Singapore region, so
 * every query costs a round trip that measured ~240ms no matter how small it
 * was - a count(*) over four rows took 230ms - on top of ~700ms to open the
 * connection, which php-fpm cannot reuse between requests. The catalogue ran
 * four queries and therefore spent ~2.2 seconds returning four birds. Nothing
 * about that is fixable by writing better SQL; the only useful move is to stop
 * making the round trips.
 *
 * WHY A VERSION COUNTER RATHER THAN CACHE TAGS. Tags are the obvious tool and
 * they are not available: Laravel only supports them on stores that can index
 * by tag, and production runs CACHE_STORE=file. Bumping an integer that is
 * baked into every key achieves the same thing on any store - the old keys are
 * not deleted, they simply become unreachable and age out on their own TTL.
 *
 * WHY INVALIDATION IS ALL-OR-NOTHING. A farm of this size writes a record a
 * handful of times a day and serves far more reads than that, so the cost of
 * throwing away every cached read on any write is negligible, and the
 * alternative - working out which cached views a particular bird appears in -
 * is where staleness bugs come from. A catalogue that is fast and wrong
 * advertises birds the farm has already sold.
 */
final class RecordCache
{
    /**
     * Bumped on every write; part of every key, so a bump orphans all of them.
     */
    private const VERSION_KEY = 'farm-records:version';

    /**
     * A backstop, not the invalidation mechanism - that is the version counter.
     *
     * An hour rather than a few minutes because this is a low-traffic public
     * catalogue: at a five-minute TTL a visitor who arrives twice in a morning
     * pays the full 2.2 seconds both times, which defeats the point. An hour
     * is short enough that anything that somehow escapes invalidation - a row
     * changed directly in the database rather than through Eloquent - corrects
     * itself the same morning.
     */
    private const TTL_SECONDS = 3600;

    /**
     * @template TValue
     *
     * @param  Closure(): TValue  $callback
     * @return TValue
     */
    public static function remember(string $key, Closure $callback): mixed
    {
        return Cache::remember(
            'farm-records:'.self::version().':'.$key,
            self::TTL_SECONDS,
            $callback,
        );
    }

    /**
     * Orphan every cached read. Called from model events on any farm record.
     */
    public static function invalidate(): void
    {
        Cache::forever(self::VERSION_KEY, self::version() + 1);
    }

    private static function version(): int
    {
        return (int) Cache::rememberForever(self::VERSION_KEY, fn (): int => 1);
    }
}
