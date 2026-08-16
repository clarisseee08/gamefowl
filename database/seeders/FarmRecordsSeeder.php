<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\BroodcockStatus;
use App\Enums\PerformanceEventType;
use App\Enums\PerformanceResult;
use App\Enums\Sex;
use App\Models\BreedingRecord;
use App\Models\Broodcock;
use App\Models\BroodcockPhoto;
use App\Models\HealthRecord;
use App\Models\MortalityRecord;
use App\Models\PerformanceRecord;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * The farm's OWN records, as supplied by SSGuad Game Farm.
 *
 * This replaces the generated demo data. It clears every record table and
 * loads the five birds the farm provided — nothing here is invented except
 * where explicitly marked below.
 *
 * USER ACCOUNTS AND PENS ARE KEPT. Wiping accounts would lock everyone out of
 * the system, and pens are farm infrastructure rather than sample data.
 *
 * ---------------------------------------------------------------------------
 * WHAT THE FARM'S DATA DID NOT INCLUDE, AND WHAT WAS DONE ABOUT IT
 * ---------------------------------------------------------------------------
 *
 * 1. NAMES. The records carry band numbers only, and `name` is required. Each
 *    bird is named after its band, which is how a keeper refers to it anyway.
 *    Rename any bird from Edit at any time.
 *
 * 2. SEX. Not stated. All five are recorded as male: they are broodcocks with
 *    fighting records, and a broodcock is by definition a male breeder. If any
 *    of these is actually a hen, change it on the bird's page.
 *
 * 3. HATCH DATES. The farm gave an AGE in months, not a date of birth. The
 *    system stores a birth date and computes age from it, which is the right
 *    way round — but it means each hatch date here is "today minus N months",
 *    anchored to the day this seeder ran. A bird recorded as 24 months will
 *    read as 27 months three months from now, which is correct behaviour and
 *    worth knowing.
 *
 * 4. FIGHT DATES. The win/loss COUNTS are the farm's real data. The dates are
 *    not — nothing was supplied — so each performance row is spread through the
 *    bird's life and its remarks say plainly that the date is a placeholder.
 *    The counts are true; the dates are scaffolding to be corrected.
 *
 * 5. DEATHS. Three birds are marked deceased, but no date or cause was given.
 *    They carry the Deceased status and NO mortality record, because inventing
 *    a death date would put a fabricated fact into a register the farm may
 *    later rely on. The consequence is visible and intentional: the Mortality
 *    screen is empty while three birds show as deceased. Record the real deaths
 *    from each bird's page and it resolves itself.
 *
 * 6. PARENTAGE. "mother 5k, father sweater" names BLOODLINES, not identified
 *    birds. `sire_id`/`dam_id` must point at real broodcock records, and those
 *    parents are not in the dataset, so the descriptions are kept verbatim in
 *    the notes and the links are left empty. The family tree will stay empty
 *    until the parents exist as records.
 */
final class FarmRecordsSeeder extends Seeder
{
    public function run(): void
    {
        $recorder = User::where('role', 'owner')->first()
            ?? User::first();

        if ($recorder === null) {
            $this->command?->error('No user accounts found. Run the user seeder first.');

            return;
        }

        $this->wipeRecords();

        foreach ($this->birds() as $data) {
            $bird = Broodcock::create([
                'band_number' => $data['band'],
                'name' => $data['band'],              // see note 1
                'sex' => Sex::Male,                   // see note 2
                'bloodline' => $data['bloodline'],
                'leg_color' => $data['leg_color'],
                'status' => $data['status'],
                'for_sale' => false,                  // every bird: "not for sale"
                'date_hatched' => now()->subMonths($data['age_months'])->startOfDay(),  // see note 3
                'notes' => $data['notes'],
            ]);

            $this->recordFights($bird, $data['wins'], $data['losses'], $recorder);
        }

        $this->command?->info('Loaded '.Broodcock::count().' birds from the farm\'s own records.');
        $this->command?->warn('Deceased birds have no mortality record - no death dates were supplied.');
    }

    /**
     * Clears every record table, keeping users and pens.
     *
     * Ordered child-first so foreign keys never block a delete. Truncate is
     * avoided deliberately: these tables use soft deletes and foreign keys, and
     * a plain delete respects both.
     */
    private function wipeRecords(): void
    {
        DB::transaction(function (): void {
            PerformanceRecord::withTrashed()->forceDelete();
            HealthRecord::withTrashed()->forceDelete();
            BreedingRecord::withTrashed()->forceDelete();
            MortalityRecord::withTrashed()->forceDelete();
            BroodcockPhoto::query()->delete();

            // Parent links point at other broodcocks, so they are cleared before
            // the rows themselves to avoid a self-referencing FK failure.
            Broodcock::withTrashed()->update(['sire_id' => null, 'dam_id' => null]);
            Broodcock::withTrashed()->forceDelete();
        });
    }

    /**
     * The farm's five birds, transcribed exactly.
     *
     * @return list<array<string, mixed>>
     */
    private function birds(): array
    {
        return [
            [
                'band' => '2335',
                'bloodline' => '5K Sweater',
                'age_months' => 24,
                'leg_color' => 'Yellow',
                'status' => BroodcockStatus::Deceased,
                'wins' => 2,
                'losses' => 1,
                'notes' => "Breeding history as supplied by the farm: mother 5K, father Sweater.\n"
                    .'Parent birds are not yet recorded in the system, so the family tree is empty for this bird.',
            ],
            [
                'band' => '1445',
                'bloodline' => 'Lemon Hatch',
                'age_months' => 18,
                'leg_color' => 'Yellow',
                'status' => BroodcockStatus::Active,
                'wins' => 1,
                'losses' => 0,
                'notes' => "Breeding history as supplied by the farm: Lemon Hatch on both sides.\n"
                    .'Parent birds are not yet recorded in the system, so the family tree is empty for this bird.',
            ],
            [
                'band' => '1096',
                'bloodline' => 'Kelso',
                'age_months' => 24,
                'leg_color' => 'White',
                'status' => BroodcockStatus::Deceased,
                'wins' => 1,
                'losses' => 0,
                'notes' => "Breeding history as supplied by the farm: mother Kelso, father broodcock.\n"
                    .'Parent birds are not yet recorded in the system, so the family tree is empty for this bird.',
            ],
            [
                'band' => '0220',
                'bloodline' => 'Kelso',
                'age_months' => 20,
                'leg_color' => 'White',
                'status' => BroodcockStatus::Active,
                'wins' => 1,
                'losses' => 0,
                'notes' => "Breeding history as supplied by the farm: mother Kelso, father broodcock.\n"
                    .'Parent birds are not yet recorded in the system, so the family tree is empty for this bird.',
            ],
            [
                'band' => '0113',
                'bloodline' => 'McLean Grey',
                'age_months' => 24,
                'leg_color' => 'Blue',
                'status' => BroodcockStatus::Deceased,
                'wins' => 2,
                'losses' => 1,
                'notes' => "Breeding history as supplied by the farm: mother McLean Grey, father broodcock.\n"
                    .'Parent birds are not yet recorded in the system, so the family tree is empty for this bird.',
            ],
        ];
    }

    /**
     * Writes one performance row per recorded win and loss.
     *
     * The counts are the farm's data. The dates are NOT - see note 4 - so each
     * row says so in its own remarks rather than presenting a made-up date as
     * fact.
     */
    private function recordFights(Broodcock $bird, int $wins, int $losses, User $recorder): void
    {
        $results = array_merge(
            array_fill(0, $wins, PerformanceResult::Win),
            array_fill(0, $losses, PerformanceResult::Loss),
        );

        foreach ($results as $index => $result) {
            PerformanceRecord::create([
                'broodcock_id' => $bird->id,
                'event_type' => PerformanceEventType::Derby,
                'result' => $result,
                // Spread backwards from three months ago, so the dates sit
                // inside the bird's life rather than in the future.
                'event_date' => now()->subMonths(3 + ($index * 2))->startOfDay(),
                'recorded_by' => $recorder->id,
                'remarks' => 'Result supplied by the farm. Date is a placeholder - '
                    .'no event date was recorded. Please correct it.',
            ]);
        }
    }
}
