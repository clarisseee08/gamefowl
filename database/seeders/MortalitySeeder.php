<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Actions\Mortality\RecordMortality;
use App\Models\Broodcock;
use App\Models\MortalityRecord;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Three deaths.
 *
 * These go through RecordMortality rather than writing the two rows by hand:
 * the mortality row and `broodcocks.status = deceased` must be set together or
 * not at all, and that rule belongs in the Action, not duplicated here. Three
 * birds is six statements, which is cheap enough to justify keeping the domain
 * rule in one place.
 */
final class MortalitySeeder extends Seeder
{
    /** @var list<array{band: string, date: string, cause: string, disposal: string, remarks: string}> */
    private const DEATHS = [
        [
            'band' => 'HT-3003',
            'date' => '2026-01-14',
            'cause' => 'Old age',
            'disposal' => 'Buried',
            'remarks' => 'Found down in the morning. Five years old, no signs of disease.',
        ],
        [
            'band' => 'HT-3004',
            'date' => '2025-11-08',
            'cause' => 'Disease',
            'disposal' => 'Sent for laboratory examination',
            'remarks' => 'Sudden death after three days off-feed. Samples sent to the provincial veterinary office.',
        ],
        [
            'band' => 'SW-3103',
            'date' => '2026-05-19',
            'cause' => 'Predator attack',
            'disposal' => 'Burned',
            'remarks' => 'Night raid on the grow-out pen. Perimeter wire repaired the same week.',
        ],
    ];

    public function __construct(private readonly RecordMortality $recordMortality) {}

    public function run(): void
    {
        if (MortalityRecord::withTrashed()->exists()) {
            return;
        }

        $actor = User::query()->where('role', 'owner')->first();

        if (! $actor instanceof User) {
            return;
        }

        $bands = array_column(self::DEATHS, 'band');

        $birds = Broodcock::query()
            ->whereIn('band_number', $bands)
            ->get()
            ->keyBy('band_number');

        foreach (self::DEATHS as $death) {
            $bird = $birds->get($death['band']);

            if (! $bird instanceof Broodcock) {
                continue;
            }

            // The Action writes the mortality row and flips the bird's status
            // to deceased inside one transaction.
            $this->recordMortality->handle($bird, [
                'date_of_death' => $death['date'],
                'cause_of_death' => $death['cause'],
                'disposal_method' => $death['disposal'],
                'remarks' => $death['remarks'],
            ], $actor);
        }
    }
}
