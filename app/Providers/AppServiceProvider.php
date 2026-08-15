<?php

declare(strict_types=1);

namespace App\Providers;

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
        // Refuse migrate:fresh / migrate:refresh / db:wipe outside local dev.
        // The database is a shared remote Supabase project - an accidental
        // wipe is not a local inconvenience, it is everyone's data.
        DB::prohibitDestructiveCommands($this->app->isProduction());

        // Fail loudly in development instead of silently returning null:
        //  - accessing an un-eager-loaded relation throws (catches N+1 early,
        //    which matters because every query is a network round trip to
        //    Tokyo), and
        //  - assigning to a non-existent attribute throws.
        Model::shouldBeStrict(! $this->app->isProduction());
    }
}
