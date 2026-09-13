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

    /** Builds a complete 3-generation tree: 1 bird + 2 + 4 + 8 = 15 birds. */
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
        // plus one per generation = 4. Nested eager loading (with('sire.sire.
        // sire', ...)) would cost 14 - one per relation path - and walking it
        // lazily costs more still. The bound is deliberately tight so either
        // regression fails here.
        $this->assertLessThanOrEqual(
            8,
            $queryCount,
            "Rendering a full 3-generation pedigree took {$queryCount} queries; it should take about 4. ".
            'The tree must be loaded one generation at a time (see Pedigree::ancestors()). '.
            'If someone replaced that with nested with() eager loading, this is the symptom.'
        );
    }

    public function test_the_pedigree_renders_every_ancestor_it_loaded(): void
    {
        $bird = $this->buildFullPedigree();
        $this->actingAs(User::factory()->staff()->create());

        $html = Livewire::test(Pedigree::class, ['broodcock' => $bird])->html();

        // Its grandsire's sire - i.e. a great-grandparent - must actually
        // appear, proving three generations are rendered and not just two.
        $greatGrandsire = $bird->sire->sire->sire;

        $this->assertNotNull($greatGrandsire, 'Fixture did not build three generations.');
        $this->assertStringContainsString($greatGrandsire->name, $html);
    }

    public function test_pedigree_completeness_is_reported_accurately(): void
    {
        $this->actingAs(User::factory()->staff()->create());

        $orphan = Broodcock::factory()->create(['sire_id' => null, 'dam_id' => null]);

        // The ratio only, not the trailing word: "3 of 14" is one figure and is
        // set in one .datum span, so the word "ancestors" that follows it sits
        // outside that span and is no longer contiguous in the HTML. The number
        // is what this test is about.
        Livewire::test(Pedigree::class, ['broodcock' => $orphan])
            ->assertSee('0 of 14');

        $full = $this->buildFullPedigree();

        Livewire::test(Pedigree::class, ['broodcock' => $full])
            ->assertSee('14 of 14');
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
