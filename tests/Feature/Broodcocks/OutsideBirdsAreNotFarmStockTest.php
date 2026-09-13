<?php

declare(strict_types=1);

namespace Tests\Feature\Broodcocks;

use App\Livewire\Broodcocks\Index;
use App\Livewire\Catalog\Browse as Catalog;
use App\Livewire\Dashboard\Overview;
use App\Models\Broodcock;
use App\Models\User;
use App\Reports\BroodcockInventoryReport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * A bird the farm does not own must not be counted as the farm's.
 *
 * The breeding form creates a real broodcock row for a borrowed or visiting
 * parent, because sire_id and dam_id are foreign keys - a free-text name would
 * cut the pedigree off above that bird, which is the one feature the system is
 * built around. The `is_external` flag is what tells the two apart afterwards.
 *
 * WHY THIS FILE EXISTS. Broodcock::scopeFarmStock() was written, documented and
 * unit-tested, and then never called from a single screen. The scope's own test
 * passed happily while the dashboard, the catalogue and the inventory report all
 * counted borrowed hens as farm stock - because a scope test proves the scope
 * works, not that anything uses it. These tests assert the CALL SITES.
 */
final class OutsideBirdsAreNotFarmStockTest extends TestCase
{
    use RefreshDatabase;

    private function staff(): User
    {
        return User::factory()->staff()->create();
    }

    // -----------------------------------------------------------------
    // The customer catalogue
    // -----------------------------------------------------------------

    /**
     * The worst of the three: offering a customer a bird the farm cannot sell
     * is a conversation the farm then has to walk back.
     */
    public function test_the_catalogue_does_not_offer_a_bird_the_farm_does_not_own(): void
    {
        Broodcock::factory()->create(['name' => 'Bagwis']);
        Broodcock::factory()->external()->create(['name' => 'Borrowed Hen']);

        Livewire::actingAs(User::factory()->customer()->create())
            ->test(Catalog::class)
            ->assertSee('Bagwis')
            ->assertDontSee('Borrowed Hen');
    }

    public function test_the_catalogue_bloodline_filter_ignores_outside_birds(): void
    {
        Broodcock::factory()->external()->create(['bloodline' => 'Borrowed Line']);

        $options = Livewire::actingAs(User::factory()->customer()->create())
            ->test(Catalog::class)
            ->instance()
            ->bloodlineOptions;

        $this->assertNotContains('Borrowed Line', $options);
    }

    // -----------------------------------------------------------------
    // The dashboard
    // -----------------------------------------------------------------

    public function test_the_flock_counts_exclude_outside_birds(): void
    {
        Broodcock::factory()->count(3)->create();
        Broodcock::factory()->external()->count(2)->create();

        $flock = Livewire::actingAs($this->staff())
            ->test(Overview::class)
            ->instance()
            ->flock;

        $this->assertSame(3, $flock['total'], 'Borrowed birds were counted as the farm\'s own stock.');
    }

    /**
     * An outside bird has no parents BY DEFINITION - she exists only so the
     * tree keeps the branch above her - so counting her here invented a backlog
     * of pedigree work that nobody could ever complete, and which grew every
     * time a borrowed hen was recorded.
     */
    public function test_the_pedigree_gap_count_excludes_outside_birds(): void
    {
        Broodcock::factory()->external()->count(4)->create();

        $gaps = Livewire::actingAs($this->staff())
            ->test(Overview::class)
            ->instance()
            ->birdsWithoutPedigree;

        $this->assertSame(0, $gaps);
    }

    // -----------------------------------------------------------------
    // The inventory report
    // -----------------------------------------------------------------

    /** An inventory is what the farm OWNS. The migration says so explicitly. */
    public function test_the_inventory_report_excludes_outside_birds(): void
    {
        Broodcock::factory()->count(2)->create();
        Broodcock::factory()->external()->create(['name' => 'Borrowed Hen']);

        $report = app(BroodcockInventoryReport::class)->withFilters([]);

        $this->assertSame(2, $report->summary()['Total Birds']);
        $this->assertNotContains('Borrowed Hen', $report->rows()->pluck('name')->all());
    }

    // -----------------------------------------------------------------
    // The staff list
    //
    // Here they are EXCLUDED BY DEFAULT rather than hidden: a keeper still has
    // to be able to correct a borrowed hen's name or bloodline, and a bird you
    // cannot find is a bird you cannot fix.
    // -----------------------------------------------------------------

    public function test_the_broodcock_list_shows_farm_stock_by_default(): void
    {
        Broodcock::factory()->create(['name' => 'Bagwis']);
        Broodcock::factory()->external()->create(['name' => 'Borrowed Hen']);

        Livewire::actingAs($this->staff())
            ->test(Index::class)
            ->assertSee('Bagwis')
            ->assertDontSee('Borrowed Hen');
    }

    public function test_outside_birds_remain_reachable_through_the_ownership_filter(): void
    {
        Broodcock::factory()->create(['name' => 'Bagwis']);
        Broodcock::factory()->external()->create(['name' => 'Borrowed Hen']);

        Livewire::actingAs($this->staff())
            ->test(Index::class)
            ->set('ownership', 'outside')
            ->assertSee('Borrowed Hen')
            ->assertDontSee('Bagwis');
    }

    public function test_both_can_be_listed_together_and_outside_birds_are_marked(): void
    {
        Broodcock::factory()->create(['name' => 'Bagwis']);
        Broodcock::factory()->external()->create(['name' => 'Borrowed Hen']);

        Livewire::actingAs($this->staff())
            ->test(Index::class)
            ->set('ownership', 'all')
            ->assertSee('Bagwis')
            ->assertSee('Borrowed Hen')
            // Mixed in with farm stock, an outside bird has to be identifiable
            // or the list is quietly misleading.
            ->assertSee('Outside');
    }

    /**
     * The ownership value arrives from the query string, so it is user input.
     * A value nobody recognises must show LESS, not everything.
     */
    public function test_an_unknown_ownership_value_falls_back_to_farm_stock(): void
    {
        Broodcock::factory()->create(['name' => 'Bagwis']);
        Broodcock::factory()->external()->create(['name' => 'Borrowed Hen']);

        Livewire::actingAs($this->staff())
            ->test(Index::class)
            ->set('ownership', 'everything-please')
            ->assertSee('Bagwis')
            ->assertDontSee('Borrowed Hen');
    }

    public function test_clearing_the_filters_returns_to_farm_stock(): void
    {
        Broodcock::factory()->external()->create(['name' => 'Borrowed Hen']);

        Livewire::actingAs($this->staff())
            ->test(Index::class)
            ->set('ownership', 'all')
            ->call('clearFilters')
            ->assertSet('ownership', 'farm')
            ->assertDontSee('Borrowed Hen');
    }
}
