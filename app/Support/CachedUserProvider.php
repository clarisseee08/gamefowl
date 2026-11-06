<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\User;
use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Cache;
use Throwable;

/**
 * The signed-in user, read from the cache instead of the database.
 *
 * Laravel looks the user up by id on EVERY request - every page and every
 * Livewire click inside one - and against the Supabase database in Tokyo that
 * lookup is a ~240ms round trip before any of the screen's own work starts.
 * The row it reads changes a few times a year.
 *
 * CORRECTNESS RESTS ON forget(). User::booted() calls it on every save and
 * delete, so deactivating an account, changing a role or a password takes
 * effect on that user's very next request, exactly as before. The TTL is only
 * a backstop for a row edited outside the application.
 *
 * Only retrieveById() is cached. Sign-in by credentials and "remember me"
 * tokens still go to the database every time, as they should.
 */
final class CachedUserProvider extends EloquentUserProvider
{
    private const TTL_SECONDS = 600;

    /** True while a cache failure is being reported; see retrieveById(). */
    private static bool $reportingFailure = false;

    public function retrieveById($identifier): ?Authenticatable
    {
        if ($identifier === null || $identifier === '') {
            return null;
        }

        // Re-entry: report() below adds the signed-in user's id to the log
        // entry, which asks this provider for the user again - and, with the
        // cache still broken, would fail and report again until PHP ran out
        // of memory. While a failure is being reported, go straight to the
        // database.
        if (self::$reportingFailure) {
            return parent::retrieveById($identifier);
        }

        try {
            $user = Cache::remember(self::key($identifier), self::TTL_SECONDS, function () use ($identifier): User|false {
                // false, not null: Cache::remember() treats a cached null as a
                // miss, so an unknown id would hit the database every time.
                return parent::retrieveById($identifier) ?? false;
            });
        } catch (Throwable $exception) {
            // A cache that cannot be read or written must not sign everybody
            // out or fail every page: read the user from the database as
            // Laravel would have, and log why the cache was skipped.
            self::$reportingFailure = true;

            try {
                report($exception);
            } finally {
                self::$reportingFailure = false;
            }

            return parent::retrieveById($identifier);
        }

        return $user instanceof User ? $user : null;
    }

    public static function forget(int|string $identifier): void
    {
        Cache::forget(self::key($identifier));
    }

    private static function key(int|string $identifier): string
    {
        return 'auth-user:'.$identifier;
    }
}
