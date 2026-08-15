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
     * How many ancestor generations the pedigree view renders. Three is what
     * the thesis specifies; raising it grows the eager-load set exponentially.
     */
    'pedigree_generations' => 3,

    /* Shown on report headers and PDF footers. */
    'farm' => [
        'name' => env('GFMS_FARM_NAME', 'SSGuad Game Farm'),
        'address' => env('GFMS_FARM_ADDRESS', ''),
    ],

];
