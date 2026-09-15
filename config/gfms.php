<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| GFMS application settings
|--------------------------------------------------------------------------
|
| Farm-specific tunables. Kept here rather than hard-coded so the researchers
| can change farm policy without editing application code.
|
*/

return [

    /*
     * Where broodcock photos are stored.
     *
     * "public"   - storage/app/public, served via `php artisan storage:link`.
     *              Works with zero cloud configuration.
     * "supabase" - the private Supabase Storage bucket over its S3-compatible
     *              gateway. Requires the SUPABASE_S3_* credentials.
     */
    'photo_disk' => env('GFMS_PHOTO_DISK', 'public'),

    /* Upload limits for broodcock photos. */
    'photos' => [
        'max_kilobytes' => (int) env('GFMS_PHOTO_MAX_KB', 4096),
        'accepted_mimes' => ['jpeg', 'jpg', 'png', 'webp'],
        'max_per_broodcock' => (int) env('GFMS_PHOTO_MAX_PER_BIRD', 10),
    ],

    /*
     * How many days ahead a vaccination or deworming counts as "due soon" on
     * the health schedule and compliance report.
     */
    'vaccination_warning_days' => (int) env('GFMS_VACCINATION_WARNING_DAYS', 30),

    /* Rows per page on list screens. */
    'per_page' => (int) env('GFMS_PER_PAGE', 15),

    /*
     * Whether migrate:fresh, migrate:refresh, migrate:reset, migrate:rollback
     * and db:wipe are allowed to run.
     *
     * OFF EVERYWHERE by default, including local development, because there is
     * no separate development database - every .env and render.yaml point at
     * the same Supabase project. A local `migrate:fresh` therefore destroys the
     * farm's real records, not a disposable copy.
     *
     * Turn it on only to rebuild a database you are certain is your own, and
     * turn it straight back off. See AppServiceProvider::boot().
     */
    'allow_destructive_db' => filter_var(
        env('GFMS_ALLOW_DESTRUCTIVE_DB', false),
        FILTER_VALIDATE_BOOLEAN
    ),

    /*
     * How many ancestor generations the pedigree view renders.
     *
     * ONE - the sire and dam, and nothing above them. It was three, and the
     * farm asked for it to be cut back: a bird's parents are what they
     * actually record and can vouch for, and two further columns of "Not
     * recorded" made a screen whose job is to prove traceability look like a
     * screen that had failed to load.
     *
     * Raising it costs one extra query per generation and grows the column
     * count exponentially - 2, then 4, then 8. The view labels each column
     * from an array that currently names two; add to it before raising this.
     */
    'pedigree_generations' => 1,
    /*
     * What the farm tells the public.
     *
     * THESE ARE THE FALLBACK, NOT THE SOURCE. The live values are one row in
     * `farm_settings`, edited by the owner at /settings, and
     * App\Support\FarmProfile overwrites this whole array with them in
     * AppServiceProvider::boot(). What is left here is what the application
     * shows when there is no such row to read - a fresh clone before its first
     * migration, or a database that cannot be reached at boot.
     *
     * So the entries below stay environment-driven and stay empty. Every one
     * of them defaults to an empty string on purpose: the footer omits a row it
     * has no value for rather than rendering a label with nothing after it. A
     * farm that has not supplied a phone number shows no phone number, not
     * "Phone -". That behaviour is what makes an unmigrated database degrade
     * into a quiet page rather than a broken one.
     *
     * Read them with config('gfms.farm.*') as before. Nothing that displays
     * these values needs to know the database is involved, which is the point -
     * it includes reports/pdf/_layout.blade.php, and dompdf can only be handed
     * plain scalars.
     */
    'farm' => [
        'name' => env('GFMS_FARM_NAME', 'SSGuad Game Farm'),
        'address' => env('GFMS_FARM_ADDRESS', ''),
        'phone' => env('GFMS_FARM_PHONE', ''),
        'email' => env('GFMS_FARM_EMAIL', ''),
        'hours' => env('GFMS_FARM_HOURS', ''),
        /* A free paragraph for the public Visit section. Owner-written; there
         * is deliberately no environment default worth shipping. */
        'note' => env('GFMS_FARM_NOTE', ''),
    ],

    /*
     * The SYSTEM's own name, which is not the farm's name.
     *
     * Kept here as the single source so a rename cannot land on the login screen
     * and miss a PDF footer - the kind of drift a panel opens with.
     * tests/Unit/SystemNameTest.php fails on any hardcoded occurrence of the old
     * name in a view, template or mail file.
     *
     * NOTE: the internals are deliberately NOT renamed. config/gfms-brand.php,
     * the GFMS_* env keys, route names, table names and CSS prefixes all stay.
     * Renaming them buys nothing visible and risks the whole test suite against
     * a deadline.
     */
    'system' => [
        'name' => env('APP_NAME', 'Digital Broodcock Farm Record Management System'),
        'short' => env('GFMS_SHORT_NAME', 'SSGuad Game Farm'),
        'tagline' => 'Broodcock farm records',
    ],

];
