<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\FarmSetting;
use Illuminate\Support\Facades\Cache;
use Throwable;

/**
 * Puts the farm_settings row where every existing reader already looks.
 *
 * WHY THIS EXISTS RATHER THAN NINE EDITED CALL SITES. The farm's name and
 * contact details are read by the catalog footer and header, the console
 * sidebar, the error pages, head-meta, the dashboard greeting and
 * reports/pdf/_layout.blade.php - all of them through config('gfms.farm.*').
 * Making the values owner-editable by rewriting each of those would have meant
 * teaching the PDF layout to reach a model, and that layout is rendered by
 * dompdf, a CSS 2.1 engine handed a pre-rendered string. Overwriting the
 * config array once, early, leaves all nine readers untouched and correct.
 *
 * IT MUST NEVER THROW, and that is not defensive habit. This runs in
 * AppServiceProvider::boot(), which runs before `php artisan migrate` does. An
 * unguarded query here on a database that has not got the table yet takes down
 * the very command that would create it - a bootstrap deadlock with no way out
 * except editing code. Every failure is swallowed and leaves the environment
 * defaults from config/gfms.php standing.
 *
 * A FAILURE IS NEVER CACHED. Caching the miss would mean the first request
 * after a deploy - the one most likely to race the migration - pinned the
 * blank fallback in place until something explicitly forgot it.
 */
final class FarmProfile
{
    private const CACHE_KEY = 'farm-profile';

    /**
     * Map of config key under `gfms.farm` => farm_settings column.
     */
    private const FIELDS = [
        'name' => 'farm_name',
        'address' => 'address',
        'phone' => 'phone',
        'email' => 'email',
        'hours' => 'hours',
        'note' => 'visitor_note',
    ];

    public static function apply(): void
    {
        $stored = self::stored();

        if ($stored === null) {
            return;
        }

        config()->set('gfms.farm', [...config('gfms.farm'), ...$stored]);
    }

    /**
     * Drop the cached copy and read it again, in that order.
     *
     * NOT JUST A CACHE CLEAR, and the difference is visible. apply() runs once
     * per process, in boot(); by the time the owner presses Save on /settings
     * it has already run, so merely forgetting the key would leave the request
     * that did the saving - and every later request in that same process -
     * rendering the values the page had just replaced. The sidebar would still
     * be showing the old farm name above the form that changed it.
     *
     * It also matters under test, where the ordering is worse: the application
     * boots before RefreshDatabase has migrated anything, so the first apply()
     * finds no table and correctly falls back to the environment. Re-applying
     * on write is what lets a test save a value and then see it on a page.
     */
    public static function refresh(): void
    {
        Cache::forget(self::CACHE_KEY);

        self::apply();
    }

    /**
     * The stored values, or null if there are none to be had.
     *
     * Deliberately not RecordCache: that is versioned and thrown away whenever
     * any bird, hatch or health record is written, and a farm's phone number
     * has no reason to be re-read because somebody weighed a cock. This is its
     * own key, forgotten only when the row itself changes.
     *
     * @return array<string, string>|null
     */
    private static function stored(): ?array
    {
        $cached = Cache::get(self::CACHE_KEY);

        if (is_array($cached)) {
            return $cached;
        }

        try {
            $row = FarmSetting::query()->oldest('id')->first();
        } catch (Throwable) {
            // No table, no connection, or a migration in flight. The caller
            // keeps the config/gfms.php defaults.
            return null;
        }

        if ($row === null) {
            return null;
        }

        $values = [];

        foreach (self::FIELDS as $configKey => $column) {
            // Null becomes an empty string rather than staying null, because
            // the views test these with a plain truthiness check to decide
            // whether to render a row at all - see the footer in
            // layouts/catalog.blade.php. '' and null read the same there, and
            // '' is what the environment defaults were.
            $values[$configKey] = (string) ($row->{$column} ?? '');
        }

        Cache::forever(self::CACHE_KEY, $values);

        return $values;
    }
}
