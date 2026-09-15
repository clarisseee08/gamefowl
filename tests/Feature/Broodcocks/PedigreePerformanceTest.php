<?php

declare(strict_types=1);

namespace Tests\Feature\Broodcocks;

use App\Livewire\Broodcocks\Index;
use App\Livewire\Broodcocks\Pedigree;
use App\Models\Broodcock;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Performance-efficiency evidence for ISO 25010.
 *
 * Every query in this application is a network round trip to Supabase in
 * Tokyo, so an N+1 loop is far more expensive here than it would be against a
 * local database. These tests assert query COUNTS, which is the only way to
 * catch an N+1 regression automatically - a page can look perfectly correct
 * while issuing fifty queries.
 */
final class PedigreePerformanceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Builds a complete 3-generation tree: 1 bird + 2 + 4 + 8 = 15 birds.
     *
     * DELIBERATELY DEEPER THAN THE CHART. The pedigree renders one generation
     * now, so a fixture that stopped at the parents could not tell a chart that
     * correctly stops from a chart that has quietly lost its grandparents. The
     * extra twelve birds exist to be absent from the page.
     */
    private function buildFullPedigree(): Broodcock
    {
        $make = function (int $depth) use (&$make): ?Broodcock {
            if ($depth === 0) {
                return null;
            }

            $sire = $make($depth - 1);
            $dam = $make($depth - 1);

            return Broodcock::factory()->create([
                'sire_id' => $sire?->id,
                'dam_id' => $dam?->id,
            ]);
        };

        // Depth 4 = the subject plus three ancestor generations.
        return $make(4);
    }

    /** @return array{0: mixed, 1: int} */
    private function countQueries(callable $callback): array
    {
        DB::flushQueryLog();
        DB::enableQueryLog();

        $result = $callback();

        $count = count(DB::getQueryLog());
        DB::disableQueryLog();

        return [$result, $count];
    }

    public function test_the_pedigree_loads_the_whole_tree_in_a_constant_number_of_queries(): void
    {
        $bird = $this->buildFullPedigree();
        $this->actingAs(User::factory()->staff()->create());

        [, $queryCount] = $this->countQueries(function () use ($bird) {
            return Livewire::test(Pedigree::class, ['broodcock' => $bird])
                ->assertOk()
                ->html();
        });

        // The component loads the tree breadth-first: one query for the root
        // plus one per rendered generation. At the current depth of 1 that is
        // 2. Nested eager loading (with('sire.sire.sire', ...)) would cost one
        // query per relation path, and walking the tree lazily costs more
        // still. The bound is deliberately tight so either regression fails
        // here - it is the fixture's twelve unrendered ancestors that would
        // drag the count up if the loader ever started following them.
        $this->assertLessThanOrEqual(
            4,
            $queryCount,
            "Rendering the pedigree took {$queryCount} queries; it should take about 2. ".
            'The tree must be loaded one generation at a time (see Pedigree::ancestors()). '.
            'If someone replaced that with nested with() eager loading, this is the symptom.'
        );
    }

    /**
     * The chart shows the parents and stops there.
     *
     * The farm asked for grandparents and great-grandparents to be removed, so
     * this asserts BOTH halves of that: the sire and dam are on the page, and
     * the generation above them is not. Only checking the first half would let
     * a stray config change put four columns back without failing anything.
     */
    public function test_the_pedigree_shows_the_parents_and_nothing_above_them(): void
    {
        $bird = $this->buildFullPedigree();
        $this->actingAs(User::factory()->staff()->create());

        $html = Livewire::test(Pedigree::class, ['broodcock' => $bird])->html();

        $this->assertStringContainsString($bird->sire->name, $html, 'The sire is missing from the chart.');
        $this->assertStringContainsString($bird->dam->name, $html, 'The dam is missing from the chart.');

        $grandsire = $bird->sire->sire;
        $greatGrandsire = $grandsire->sire;

        $this->assertNotNull($greatGrandsire, 'Fixture did not build three generations.');
        $this->assertStringNotContainsString(
            $grandsire->name,
            $html,
            'A grandparent is still being rendered; the chart is meant to stop at the parents.'
        );
        $this->assertStringNotContainsString($greatGrandsire->name, $html);
    }

    /** The column headings must match the depth that is actually rendered. */
    public function test_the_chart_is_not_headed_by_columns_it_no_longer_draws(): void
    {
        $this->actingAs(User::factory()->staff()->create());

        Livewire::test(Pedigree::class, ['broodcock' => $this->buildFullPedigree()])
            ->assertSee('Parents')
            ->assertDontSee('Grandparents')
            ->assertDontSee('Great-Grandparents');
    }

    public function test_pedigree_completeness_is_reported_accurately(): void
    {
        $this->actingAs(User::factory()->staff()->create());

        $orphan = Broodcock::factory()->create(['sire_id' => null, 'dam_id' => null]);

        // The ratio only, not the trailing word: "1 of 2" is one figure and is
        // set in one .datum span, so the word "ancestors" that follows it sits
        // outside that span and is no longer contiguous in the HTML. The number
        // is what this test is about.
        //
        // Two, not fourteen - the denominator is the number of slots the chart
        // DRAWS, and it now draws a sire and a dam. A bird with both recorded
        // reads 100% even though the farm knows nothing about its grandparents,
        // which is the honest figure for what this screen now claims to show.
        Livewire::test(Pedigree::class, ['broodcock' => $orphan])
            ->assertSee('0 of 2');

        $full = $this->buildFullPedigree();

        Livewire::test(Pedigree::class, ['broodcock' => $full])
            ->assertSee('2 of 2');
    }

    /**
     * The list must not issue extra queries as rows are added. This is the
     * classic N+1: one query per row to fetch its pen and primary photo.
     */
    public function test_the_broodcock_list_query_count_does_not_grow_with_the_number_of_rows(): void
    {
        $this->actingAs(User::factory()->staff()->create());

        Broodcock::factory()->count(3)->create();
        [, $withThree] = $this->countQueries(
            fn () => Livewire::test(Index::class)->html()
        );

        Broodcock::factory()->count(12)->create();
        [, $withFifteen] = $this->countQueries(
            fn () => Livewire::test(Index::class)->html()
        );

        $this->assertSame(
            $withThree,
            $withFifteen,
            "Listing 3 birds took {$withThree} queries but listing 15 took {$withFifteen}. ".
            'The query count must be constant - something is loading a relation per row.'
        );
    }
}
