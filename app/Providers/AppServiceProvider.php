<?php

declare(strict_types=1);

namespace App\Providers;

use App\Support\FarmProfile;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        /*
         * Refuse migrate:fresh / migrate:refresh / migrate:reset / migrate:rollback
         * / db:wipe.
         *
         * This used to be gated on isProduction(), which protected the one
         * place the danger does not live. There is no separate production
         * database: render.yaml points at the SAME Supabase project that every
         * .env on every developer machine points at. So the account that wipes
         * the farm's records is not the deploy - it is a developer typing
         * `php artisan migrate:fresh` locally, where isProduction() is false
         * and the guard therefore did nothing at all.
         *
         * Prohibited everywhere instead, with two carve-outs:
         *
         *  - runningUnitTests(), because RefreshDatabase runs migrate:fresh
         *    against the in-memory SQLite database on every suite run. Without
         *    this the guard takes the whole feature suite down with it, and a
         *    guard that forces itself to be removed is not a guard.
         *
         *  - an explicit GFMS_ALLOW_DESTRUCTIVE_DB=true, for the genuine case
         *    of rebuilding a LOCAL database. It is deliberately a variable and
         *    not a --force flag: setting it is a separate, deliberate act that
         *    survives in the shell history of whoever chose it, rather than
         *    something reachable by reflex at the end of a long command.
         */
        DB::prohibitDestructiveCommands(
            self::shouldProhibitDestructiveCommands(
                runningUnitTests: $this->app->runningUnitTests(),
                explicitlyAllowed: (bool) config('gfms.allow_destructive_db'),
            )
        );

        /*
         * Replace config('gfms.farm.*') with what the owner saved at /settings.
         *
         * Before every reader of it runs, and cheap: a file-cache hit, not a
         * query, on all but the first request after a change. It cannot throw -
         * see FarmProfile - because this same boot() runs ahead of `artisan
         * migrate`, and a query against the table that migration has not
         * created yet would take the migration down with it.
         */
        FarmProfile::apply();

        // Fail loudly in development instead of silently returning null:
        //  - accessing an un-eager-loaded relation throws (catches N+1 early,
        //    which matters because every query is a network round trip to
        //    Tokyo), and
        //  - assigning to a non-existent attribute throws.
        Model::shouldBeStrict(! $this->app->isProduction());
    }

    /**
     * The rule above, as a pure function so it can be asserted directly.
     *
     * Testing it through boot() is not possible in any honest way: during the
     * suite runningUnitTests() is true by definition, so the interesting branch
     * - a developer on their own machine - is the one branch a test can never
     * be standing in.
     */
    public static function shouldProhibitDestructiveCommands(
        bool $runningUnitTests,
        bool $explicitlyAllowed,
    ): bool {
        if ($runningUnitTests) {
            return false;
        }

        return ! $explicitlyAllowed;
    }
}
