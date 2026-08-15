<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Pen;
use Illuminate\Database\Seeder;

/**
 * The four pens SSGuad Game Farm actually runs. Keyed on the UNIQUE `code`
 * through updateOrCreate(), so a second seeding run updates the four rows
 * instead of colliding.
 */
final class PenSeeder extends Seeder
{
    /** @var list<array{code: string, name: string, location: string, capacity: int, notes: string}> */
    public const PENS = [
        [
            'code' => 'BP-01',
            'name' => 'Breeding Pen A',
            'location' => 'North Yard',
            'capacity' => 8,
            'notes' => 'Main breeding pen. One broodcock per hen, single mating.',
        ],
        [
            'code' => 'BP-02',
            'name' => 'Breeding Pen B',
            'location' => 'North Yard',
            'capacity' => 8,
            'notes' => 'Second breeding pen, used for the Kelso and Hatch pairs.',
        ],
        [
            'code' => 'GO-01',
            'name' => 'Grow-out Pen',
            'location' => 'South Yard',
            'capacity' => 20,
            'notes' => 'Young stock from weaning until banding age.',
        ],
        [
            'code' => 'CD-01',
            'name' => 'Conditioning Pen',
            'location' => 'East Shed',
            'capacity' => 12,
            'notes' => 'Cocks under conditioning ahead of a derby.',
        ],
    ];

    public function run(): void
    {
        foreach (self::PENS as $pen) {
            Pen::withTrashed()->updateOrCreate(
                ['code' => $pen['code']],
                [
                    'name' => $pen['name'],
                    'location' => $pen['location'],
                    'capacity' => $pen['capacity'],
                    'notes' => $pen['notes'],
                ],
            );
        }
    }
}
