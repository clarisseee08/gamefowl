<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\BroodcockClass;
use App\Enums\BroodcockStatus;
use App\Enums\Sex;
use App\Models\Broodcock;
use App\Models\Pen;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Thirty birds across three bloodlines and four tiers, laid out so the pedigree
 * screen has something real to draw.
 *
 * Tier 1 are founders with no recorded parents. Tier 2 are bred from tier-1
 * pairs, tier 3 from tier-2 pairs, tier 4 from tier-3 pairs - which is what
 * fills all fourteen ancestor slots (2 parents + 4 grandparents + 8
 * great-grandparents) for the tier-4 birds.
 *
 * `SW-4001 Haring Agila` is the demo bird: every one of its fourteen ancestor
 * slots resolves. Others - SW-3103, KL-3202, HT-4002, KL-4003 - are
 * deliberately left with gaps so the "Not recorded" state is visible too.
 *
 * Each tier is written with ONE batch INSERT and read back with ONE SELECT.
 * Against Supabase in Tokyo every statement is a round trip, so a row-at-a-time
 * loop over thirty birds would cost thirty of them.
 */
final class BroodcockSeeder extends Seeder
{
    /** Presence of this band number means the flock has already been seeded. */
    public const SENTINEL_BAND = 'SW-1001';

    /** The bird whose three-generation ancestry is complete - used by the tests. */
    public const COMPLETE_PEDIGREE_BAND = 'SW-4001';

    /** Column order every batched row is normalised to before insert. */
    private const COLUMNS = [
        'band_number', 'name', 'breed', 'bloodline', 'class', 'sex',
        'date_hatched', 'date_acquired', 'weight', 'color', 'comb_type',
        'leg_color', 'distinguishing_marks', 'status', 'sire_id', 'dam_id',
        'pen_id', 'notes', 'created_at', 'updated_at',
    ];

    public function run(): void
    {
        if (Broodcock::withTrashed()->where('band_number', self::SENTINEL_BAND)->exists()) {
            return;
        }

        $pens = Pen::query()->pluck('id', 'code')->all();

        // Tier 1 - founders. No parents: this is where every pedigree ends.
        $ids = $this->insertTier($this->founders(), $pens, []);

        // Each later tier is inserted only after the previous one has been read
        // back, because a child needs its parents' primary keys.
        $ids += $this->insertTier($this->secondGeneration(), $pens, $ids);
        $ids += $this->insertTier($this->thirdGeneration(), $pens, $ids);
        $this->insertTier($this->fourthGeneration(), $pens, $ids);
    }

    /**
     * Build one tier's rows, insert them in a single statement, then read back
     * the generated ids keyed by the bird's name.
     *
     * @param  list<array<string, mixed>>  $specs
     * @param  array<string, int>  $pens  Pen id keyed by pen code.
     * @param  array<string, int>  $ids  Bird id keyed by name, for parent lookups.
     * @return array<string, int>
     */
    private function insertTier(array $specs, array $pens, array $ids): array
    {
        $now = now();
        $rows = [];
        $names = [];

        foreach ($specs as $spec) {
            $rows[] = $this->row($spec, $pens, $ids, $now);
            $names[] = $spec['name'];
        }

        // One INSERT for the whole tier rather than one per bird.
        DB::table('broodcocks')->insert($rows);

        return Broodcock::query()
            ->whereIn('name', $names)
            ->pluck('id', 'name')
            ->all();
    }

    /**
     * Turn one bird's specification into a database row.
     *
     * The factory supplies the cosmetic fields - colour, comb, legs - so the
     * seeded flock does not read as thirty identical birds; everything that the
     * pedigree or the CHECK constraints depend on is stated explicitly here.
     *
     * @param  array<string, mixed>  $spec
     * @param  array<string, int>  $pens
     * @param  array<string, int>  $ids
     * @return array<string, mixed>
     */
    private function row(array $spec, array $pens, array $ids, mixed $now): array
    {
        $factory = Broodcock::factory()
            ->bloodline($spec['bloodline'])
            ->status($spec['status']);

        $factory = $spec['sex'] === Sex::Male ? $factory->male() : $factory->female();

        if ($spec['band'] === null) {
            $factory = $factory->unbanded();
        }

        // Parentage is set through the id map rather than bredFrom(), which
        // needs hydrated models - the whole point of batching is that the
        // parents were never hydrated.
        $attributes = $factory->make([
            'band_number' => $spec['band'],
            'name' => $spec['name'],
            'breed' => $spec['breed'],
            'class' => $spec['class'],
            'date_hatched' => $spec['hatched'],
            // Farm-bred birds were never "acquired" on a later date, so the two
            // dates match - which also satisfies date_hatched <= date_acquired.
            'date_acquired' => $spec['acquired'] ?? $spec['hatched'],
            'sire_id' => isset($spec['sire']) ? $ids[$spec['sire']] : null,
            'dam_id' => isset($spec['dam']) ? $ids[$spec['dam']] : null,
            'pen_id' => $pens[$spec['pen']] ?? null,
            'notes' => $spec['notes'] ?? null,
        ])->getAttributes();

        $attributes['created_at'] = $now;
        $attributes['updated_at'] = $now;

        // Normalise to a fixed column order: a batch INSERT takes its column
        // list from the first row, so every row must carry the same keys.
        $row = [];

        foreach (self::COLUMNS as $column) {
            $row[$column] = $attributes[$column] ?? null;
        }

        return $row;
    }

    /**
     * Tier 1 - the twelve foundation birds, bought in as breeders.
     *
     * @return list<array<string, mixed>>
     */
    private function founders(): array
    {
        $a = BroodcockClass::ClassA;
        $b = BroodcockClass::ClassB;
        $o = BroodcockClass::Ordinary;
        $retired = BroodcockStatus::Retired;

        return [
            ['band' => 'SW-1001', 'name' => 'Kingpin', 'sex' => Sex::Male, 'bloodline' => 'Sweater', 'breed' => 'American Game', 'class' => $a, 'hatched' => '2020-03-14', 'acquired' => '2020-08-20', 'status' => $retired, 'pen' => 'BP-01', 'notes' => 'Foundation Sweater cock, bought from Guadalupe Farm, Nueva Ecija.'],
            ['band' => 'SW-1002', 'name' => 'Reyna', 'sex' => Sex::Female, 'bloodline' => 'Sweater', 'breed' => 'American Game', 'class' => $a, 'hatched' => '2020-04-02', 'acquired' => '2020-08-20', 'status' => $retired, 'pen' => 'BP-01'],
            ['band' => 'SW-1003', 'name' => 'Bagwis', 'sex' => Sex::Male, 'bloodline' => 'Sweater', 'breed' => 'American Game', 'class' => $b, 'hatched' => '2020-05-19', 'acquired' => '2020-11-05', 'status' => $retired, 'pen' => 'BP-01'],
            ['band' => 'SW-1004', 'name' => 'Dalisay', 'sex' => Sex::Female, 'bloodline' => 'Sweater', 'breed' => 'American Game', 'class' => $b, 'hatched' => '2020-06-11', 'acquired' => '2020-11-05', 'status' => $retired, 'pen' => 'BP-01'],

            ['band' => 'KL-2001', 'name' => 'Tandang', 'sex' => Sex::Male, 'bloodline' => 'Kelso', 'breed' => 'American Game', 'class' => $a, 'hatched' => '2020-02-27', 'acquired' => '2020-07-15', 'status' => $retired, 'pen' => 'BP-02', 'notes' => 'Foundation Kelso cock. Sired the farm\'s best cutting line.'],
            ['band' => 'KL-2002', 'name' => 'Marikit', 'sex' => Sex::Female, 'bloodline' => 'Kelso', 'breed' => 'American Game', 'class' => $a, 'hatched' => '2020-03-30', 'acquired' => '2020-07-15', 'status' => $retired, 'pen' => 'BP-02'],
            ['band' => 'KL-2003', 'name' => 'Bulalakaw', 'sex' => Sex::Male, 'bloodline' => 'Kelso', 'breed' => 'American Game', 'class' => $b, 'hatched' => '2020-07-08', 'acquired' => '2021-01-12', 'status' => BroodcockStatus::Sold, 'pen' => 'BP-02', 'notes' => 'Sold to a fellow breeder in San Leonardo after the 2025 season.'],
            ['band' => 'KL-2004', 'name' => 'Liwayway', 'sex' => Sex::Female, 'bloodline' => 'Kelso', 'breed' => 'American Game', 'class' => $b, 'hatched' => '2020-08-21', 'acquired' => '2021-01-12', 'status' => $retired, 'pen' => 'BP-02'],

            ['band' => 'HT-3001', 'name' => 'Apoy', 'sex' => Sex::Male, 'bloodline' => 'Hatch', 'breed' => 'Asil', 'class' => $a, 'hatched' => '2021-01-16', 'acquired' => '2021-06-04', 'status' => $retired, 'pen' => 'BP-02'],
            ['band' => 'HT-3002', 'name' => 'Amihan', 'sex' => Sex::Female, 'bloodline' => 'Hatch', 'breed' => 'Asil', 'class' => $b, 'hatched' => '2021-02-09', 'acquired' => '2021-06-04', 'status' => $retired, 'pen' => 'BP-02'],
            ['band' => 'HT-3003', 'name' => 'Tigre', 'sex' => Sex::Male, 'bloodline' => 'Hatch', 'breed' => 'Shamo', 'class' => $b, 'hatched' => '2020-11-23', 'acquired' => '2021-04-18', 'status' => $retired, 'pen' => 'CD-01'],
            ['band' => 'HT-3004', 'name' => 'Sinag', 'sex' => Sex::Female, 'bloodline' => 'Hatch', 'breed' => 'Asil', 'class' => $o, 'hatched' => '2021-03-05', 'acquired' => '2021-08-27', 'status' => $retired, 'pen' => 'BP-02'],
        ];
    }

    /**
     * Tier 2 - first farm-bred generation. Every bird has two tier-1 parents.
     *
     * @return list<array<string, mixed>>
     */
    private function secondGeneration(): array
    {
        $breeding = BroodcockStatus::Breeding;

        return [
            ['band' => 'SW-2101', 'name' => 'Haribon', 'sex' => Sex::Male, 'bloodline' => 'Sweater', 'breed' => 'American Game', 'class' => BroodcockClass::ClassA, 'hatched' => '2022-03-18', 'status' => $breeding, 'pen' => 'BP-01', 'sire' => 'Kingpin', 'dam' => 'Reyna'],
            ['band' => 'SW-2102', 'name' => 'Mayumi', 'sex' => Sex::Female, 'bloodline' => 'Sweater', 'breed' => 'American Game', 'class' => BroodcockClass::ClassA, 'hatched' => '2022-04-22', 'status' => $breeding, 'pen' => 'BP-01', 'sire' => 'Bagwis', 'dam' => 'Dalisay'],
            ['band' => 'KL-2201', 'name' => 'Lakan', 'sex' => Sex::Male, 'bloodline' => 'Kelso', 'breed' => 'American Game', 'class' => BroodcockClass::ClassA, 'hatched' => '2022-02-14', 'status' => $breeding, 'pen' => 'BP-02', 'sire' => 'Tandang', 'dam' => 'Marikit'],
            ['band' => 'KL-2202', 'name' => 'Dalaga', 'sex' => Sex::Female, 'bloodline' => 'Kelso', 'breed' => 'American Game', 'class' => BroodcockClass::ClassB, 'hatched' => '2022-05-30', 'status' => $breeding, 'pen' => 'BP-02', 'sire' => 'Bulalakaw', 'dam' => 'Liwayway'],
            ['band' => 'HT-2301', 'name' => 'Bagsik', 'sex' => Sex::Male, 'bloodline' => 'Hatch', 'breed' => 'Asil', 'class' => BroodcockClass::ClassB, 'hatched' => '2022-07-11', 'status' => $breeding, 'pen' => 'BP-02', 'sire' => 'Apoy', 'dam' => 'Amihan'],
            ['band' => 'HT-2302', 'name' => 'Tala', 'sex' => Sex::Female, 'bloodline' => 'Hatch', 'breed' => 'Asil', 'class' => BroodcockClass::ClassB, 'hatched' => '2022-09-02', 'status' => BroodcockStatus::Resting, 'pen' => 'BP-02', 'sire' => 'Tigre', 'dam' => 'Sinag'],
        ];
    }

    /**
     * Tier 3. Malaya and Sigaw each have one parent missing on purpose, so the
     * pedigree tree's "Not recorded" branch is demonstrable.
     *
     * @return list<array<string, mixed>>
     */
    private function thirdGeneration(): array
    {
        return [
            ['band' => 'SW-3101', 'name' => 'Agila', 'sex' => Sex::Male, 'bloodline' => 'Sweater', 'breed' => 'American Game', 'class' => BroodcockClass::ClassA, 'hatched' => '2023-10-05', 'status' => BroodcockStatus::Breeding, 'pen' => 'BP-01', 'sire' => 'Haribon', 'dam' => 'Mayumi', 'notes' => 'Best-conformed cock on the farm. Head of the Sweater breeding pen.'],
            ['band' => 'KL-3201', 'name' => 'Bituin', 'sex' => Sex::Female, 'bloodline' => 'Kelso', 'breed' => 'American Game', 'class' => BroodcockClass::ClassA, 'hatched' => '2023-11-19', 'status' => BroodcockStatus::Breeding, 'pen' => 'BP-01', 'sire' => 'Lakan', 'dam' => 'Dalaga'],
            ['band' => 'HT-3301', 'name' => 'Kidlat', 'sex' => Sex::Male, 'bloodline' => 'Hatch', 'breed' => 'Asil', 'class' => BroodcockClass::ClassB, 'hatched' => '2024-01-08', 'status' => BroodcockStatus::Active, 'pen' => 'CD-01', 'sire' => 'Bagsik', 'dam' => 'Tala'],
            ['band' => 'HT-3302', 'name' => 'Diwata', 'sex' => Sex::Female, 'bloodline' => 'Hatch', 'breed' => 'Asil', 'class' => BroodcockClass::ClassB, 'hatched' => '2024-02-16', 'status' => BroodcockStatus::Active, 'pen' => 'BP-02', 'sire' => 'Bagsik', 'dam' => 'Tala'],
            ['band' => 'SW-3103', 'name' => 'Malaya', 'sex' => Sex::Female, 'bloodline' => 'Sweater', 'breed' => 'American Game', 'class' => BroodcockClass::Ordinary, 'hatched' => '2024-03-24', 'status' => BroodcockStatus::Active, 'pen' => 'GO-01', 'sire' => 'Haribon', 'notes' => 'Dam not recorded - hen was bought in already mated.'],
            ['band' => 'KL-3202', 'name' => 'Sigaw', 'sex' => Sex::Male, 'bloodline' => 'Kelso', 'breed' => 'American Game', 'class' => BroodcockClass::Ordinary, 'hatched' => '2024-04-30', 'status' => BroodcockStatus::Active, 'pen' => 'CD-01', 'dam' => 'Dalaga', 'notes' => 'Sire not recorded - pen mating, sire uncertain.'],
        ];
    }

    /**
     * Tier 4. Haring Agila and Dagitab carry a complete fourteen-slot ancestry;
     * the rest inherit a gap from a tier-3 parent. The last two are young stock
     * still in the grow-out pen and not yet banded.
     *
     * @return list<array<string, mixed>>
     */
    private function fourthGeneration(): array
    {
        $active = BroodcockStatus::Active;
        $ordinary = BroodcockClass::Ordinary;

        return [
            ['band' => 'SW-4001', 'name' => 'Haring Agila', 'sex' => Sex::Male, 'bloodline' => 'Sweater', 'breed' => 'American Game', 'class' => BroodcockClass::ClassA, 'hatched' => '2025-02-11', 'status' => $active, 'pen' => 'CD-01', 'sire' => 'Agila', 'dam' => 'Bituin', 'notes' => 'Complete three-generation pedigree on both sides. The farm\'s showcase cock.'],
            ['band' => 'SW-4004', 'name' => 'Dagitab', 'sex' => Sex::Male, 'bloodline' => 'Sweater', 'breed' => 'American Game', 'class' => BroodcockClass::ClassA, 'hatched' => '2025-03-07', 'status' => $active, 'pen' => 'CD-01', 'sire' => 'Agila', 'dam' => 'Bituin'],
            ['band' => 'HT-4002', 'name' => 'Sagisag', 'sex' => Sex::Male, 'bloodline' => 'Hatch', 'breed' => 'Asil', 'class' => BroodcockClass::ClassB, 'hatched' => '2025-04-19', 'status' => $active, 'pen' => 'CD-01', 'sire' => 'Kidlat', 'dam' => 'Malaya'],
            ['band' => 'KL-4003', 'name' => 'Tanikala', 'sex' => Sex::Female, 'bloodline' => 'Kelso', 'breed' => 'American Game', 'class' => $ordinary, 'hatched' => '2025-06-02', 'status' => $active, 'pen' => 'GO-01', 'sire' => 'Sigaw', 'dam' => 'Diwata'],
            ['band' => null, 'name' => 'Bunso', 'sex' => Sex::Male, 'bloodline' => 'Sweater', 'breed' => 'American Game', 'class' => $ordinary, 'hatched' => '2026-03-15', 'status' => $active, 'pen' => 'GO-01', 'sire' => 'Agila', 'dam' => 'Bituin', 'notes' => 'Too young to band. Banding scheduled once the wing feathers set.'],
            ['band' => null, 'name' => 'Munti', 'sex' => Sex::Female, 'bloodline' => 'Hatch', 'breed' => 'Asil', 'class' => $ordinary, 'hatched' => '2026-04-21', 'status' => $active, 'pen' => 'GO-01', 'sire' => 'Kidlat', 'dam' => 'Malaya', 'notes' => 'Too young to band.'],
        ];
    }
}
