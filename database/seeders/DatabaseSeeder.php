<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Demo data for the thesis defense.
 *
 * Two properties matter here and both are deliberate:
 *
 * 1. IDEMPOTENT. `php artisan db:seed` can be run twice without duplicating a
 *    row or tripping a UNIQUE index. Users and pens are keyed on their natural
 *    unique column; the bulk tables are guarded by a sentinel check and return
 *    early once seeded.
 *
 * 2. FEW ROUND TRIPS. The database is a Supabase Postgres in Tokyo, so every
 *    statement costs real latency. Each seeder builds its rows in memory and
 *    writes them with one batched INSERT rather than one INSERT per row.
 *
 * The whole run is wrapped in a single transaction, which is what makes the
 * sentinel guards safe: a run that fails halfway leaves nothing behind, so the
 * database is never in the partially-seeded state the guards cannot detect.
 */
final class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        DB::transaction(function (): void {
            $this->call([
                UserSeeder::class,
                PenSeeder::class,
                // Broodcocks must exist before anything that references a bird.
                BroodcockSeeder::class,
                HealthRecordSeeder::class,
                BreedingRecordSeeder::class,
                PerformanceRecordSeeder::class,
                // Last: this one flips three birds to `deceased`, and running it
                // after the record seeders keeps their history intact.
                MortalitySeeder::class,
            ]);
        });
    }
}
