<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\BreedingRecord;
use App\Models\Broodcock;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Fifteen matings covering the range the breeding report has to display: a
 * strong pair near 95% fertility, a pair that barely produced anything, and
 * two matings whose chicks are still unregistered so the "Register chicks"
 * button has something to act on.
 *
 * Every row honours the CHECK constraints on the real database:
 * eggs_hatched <= eggs_fertile <= eggs_set, offspring_count <= eggs_hatched,
 * and sire_id <> dam_id.
 */
final class BreedingRecordSeeder extends Seeder
{
    private const COLUMNS = [
        'sire_id', 'dam_id', 'mating_date', 'eggs_set', 'eggs_fertile',
        'eggs_hatched', 'offspring_count', 'notes', 'recorded_by',
        'created_at', 'updated_at',
    ];

    public function run(): void
    {
        if (BreedingRecord::withTrashed()->exists()) {
            return;
        }

        /** @var array<string, int> $birds */
        $birds = Broodcock::query()->pluck('id', 'name')->all();

        if ($birds === []) {
            return;
        }

        $recorder = User::query()->where('role', 'staff')->value('id');
        $now = now();
        $rows = [];

        foreach ($this->matings() as $mating) {
            $factory = BreedingRecord::factory();

            $overrides = [
                'sire_id' => $birds[$mating['sire']],
                'dam_id' => $birds[$mating['dam']],
                'mating_date' => $mating['mating_date'],
                'notes' => $mating['notes'] ?? null,
                'recorded_by' => $recorder,
            ];

            if (isset($mating['unregistered'])) {
                // The factory state already lays out a consistent egg funnel
                // with offspring_count left at zero.
                $factory = $factory->withUnregisteredOffspring($mating['unregistered']);
            } else {
                $overrides += [
                    'eggs_set' => $mating['set'],
                    'eggs_fertile' => $mating['fertile'],
                    'eggs_hatched' => $mating['hatched'],
                    'offspring_count' => $mating['offspring'],
                ];
            }

            $attributes = $factory->make($overrides)->getAttributes();
            $attributes['created_at'] = $now;
            $attributes['updated_at'] = $now;

            $row = [];

            foreach (self::COLUMNS as $column) {
                $row[$column] = $attributes[$column] ?? null;
            }

            $rows[] = $row;
        }

        DB::table('breeding_records')->insert($rows);
    }

    /**
     * Matings in the order the farm actually made them, oldest first.
     *
     * @return list<array<string, mixed>>
     */
    private function matings(): array
    {
        return [
            ['sire' => 'Tandang', 'dam' => 'Marikit', 'mating_date' => '2021-11-20', 'set' => 18, 'fertile' => 17, 'hatched' => 16, 'offspring' => 1, 'notes' => 'Foundation Kelso mating. Produced Lakan.'],
            ['sire' => 'Kingpin', 'dam' => 'Reyna', 'mating_date' => '2021-12-05', 'set' => 20, 'fertile' => 19, 'hatched' => 18, 'offspring' => 1, 'notes' => 'Best fertility the farm has recorded. Produced Haribon.'],
            ['sire' => 'Bagwis', 'dam' => 'Dalisay', 'mating_date' => '2022-01-10', 'set' => 16, 'fertile' => 13, 'hatched' => 11, 'offspring' => 1],
            ['sire' => 'Bulalakaw', 'dam' => 'Liwayway', 'mating_date' => '2022-02-28', 'set' => 14, 'fertile' => 8, 'hatched' => 5, 'offspring' => 1, 'notes' => 'Poor fertility. Hen was off-feed for most of the cycle.'],
            ['sire' => 'Apoy', 'dam' => 'Amihan', 'mating_date' => '2022-04-15', 'set' => 15, 'fertile' => 12, 'hatched' => 10, 'offspring' => 1],
            ['sire' => 'Tigre', 'dam' => 'Sinag', 'mating_date' => '2022-06-08', 'set' => 12, 'fertile' => 6, 'hatched' => 3, 'offspring' => 1, 'notes' => 'Weakest mating on record. Cock was already past his prime.'],
            ['sire' => 'Haribon', 'dam' => 'Mayumi', 'mating_date' => '2023-07-14', 'set' => 19, 'fertile' => 18, 'hatched' => 17, 'offspring' => 1, 'notes' => 'Produced Agila, the current head of the Sweater pen.'],
            ['sire' => 'Lakan', 'dam' => 'Dalaga', 'mating_date' => '2023-08-22', 'set' => 17, 'fertile' => 15, 'hatched' => 13, 'offspring' => 1],
            ['sire' => 'Bagsik', 'dam' => 'Tala', 'mating_date' => '2023-10-02', 'set' => 16, 'fertile' => 14, 'hatched' => 12, 'offspring' => 2],
            ['sire' => 'Bagsik', 'dam' => 'Dalaga', 'mating_date' => '2024-05-16', 'set' => 11, 'fertile' => 4, 'hatched' => 1, 'offspring' => 0, 'notes' => 'Cross-line trial. Not repeated.'],
            ['sire' => 'Lakan', 'dam' => 'Tala', 'mating_date' => '2024-07-09', 'set' => 18, 'fertile' => 16, 'hatched' => 14, 'offspring' => 0],
            ['sire' => 'Agila', 'dam' => 'Bituin', 'mating_date' => '2024-11-05', 'set' => 20, 'fertile' => 19, 'hatched' => 18, 'offspring' => 3, 'notes' => 'The farm\'s strongest pair. Produced Haring Agila.'],
            ['sire' => 'Kidlat', 'dam' => 'Malaya', 'mating_date' => '2025-01-18', 'set' => 15, 'fertile' => 13, 'hatched' => 11, 'offspring' => 2],
            ['sire' => 'Sigaw', 'dam' => 'Diwata', 'mating_date' => '2025-02-24', 'set' => 13, 'fertile' => 9, 'hatched' => 6, 'offspring' => 1],
            // Chicks hatched but not yet entered as birds - these two drive the
            // "Register chicks" action on the breeding record screen.
            ['sire' => 'Agila', 'dam' => 'Diwata', 'mating_date' => '2025-09-12', 'unregistered' => 5, 'notes' => 'Chicks still in the brooder - not yet registered as birds.'],
            ['sire' => 'Kidlat', 'dam' => 'Bituin', 'mating_date' => '2026-01-20', 'unregistered' => 4, 'notes' => 'Latest hatch. Chicks not yet registered.'],
        ];
    }
}
