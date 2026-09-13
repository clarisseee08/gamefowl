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
     * How many ancestor generations the pedigree view renders. Three is what
     * the thesis specifies; raising it grows the eager-load set exponentially.
     */
    'pedigree_generations' => 3,
    /*
     * What the farm tells the public.
     *
     * These feed the footer and the "Visit us" section, and every one of them
     * defaults to an empty string on purpose: the footer omits a row it has no
     * value for rather than rendering a label with nothing after it. A farm
     * that has not supplied a phone number shows no phone number, not "Phone -".
     *
     * They live in the environment rather than as committed defaults because
     * they are a real person's contact details.
     */
    'farm' => [
        'name' => env('GFMS_FARM_NAME', 'SSGuad Game Farm'),
        'address' => env('GFMS_FARM_ADDRESS', ''),
        'phone' => env('GFMS_FARM_PHONE', ''),
        'email' => env('GFMS_FARM_EMAIL', ''),
        'hours' => env('GFMS_FARM_HOURS', ''),
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
