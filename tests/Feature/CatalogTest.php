<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\BroodcockStatus;
use App\Livewire\Catalog\Index;
use App\Models\Broodcock;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * The read-only customer portal.
 *
 * The interesting assertions here are about what is NOT shown: internal
 * vocabulary, off-farm birds, and anything a customer has no business seeing.
 */
final class CatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_customer_can_browse_the_catalogue(): void
    {
        $this->actingAs(User::factory()->customer()->create())
            ->get(route('catalog.index'))->assertOk();
    }

    public function test_staff_can_also_view_the_catalogue(): void
    {
        // Useful in practice: staff need to see what a customer sees.
        $this->actingAs(User::factory()->staff()->create())
            ->get(route('catalog.index'))->assertOk();
    }

    public function test_a_guest_cannot_browse_the_catalogue(): void
    {
        $this->get(route('catalog.index'))->assertRedirect(route('login'));
    }

    public function test_a_deactivated_customer_cannot_browse_the_catalogue(): void
    {
        $this->actingAs(User::factory()->customer()->inactive()->create())
            ->get(route('catalog.index'))->assertRedirect(route('login'));
    }

    // -----------------------------------------------------------------
    // What the catalogue shows
    // -----------------------------------------------------------------

    public function test_sold_and_deceased_birds_are_not_listed(): void
    {
        Broodcock::factory()->create(['name' => 'Onfarmbird', 'status' => BroodcockStatus::Active]);
        Broodcock::factory()->create(['name' => 'Soldbird', 'status' => BroodcockStatus::Sold]);
        Broodcock::factory()->create(['name' => 'Deadbird', 'status' => BroodcockStatus::Deceased]);

        Livewire::actingAs(User::factory()->customer()->create())
            ->test(Index::class)
            ->assertSee('Onfarmbird')
            ->assertDontSee('Soldbird')
            ->assertDontSee('Deadbird');
    }

    public function test_internal_notes_are_never_rendered_in_the_catalogue(): void
    {
        Broodcock::factory()->create([
            'name' => 'Talisman',
            'notes' => 'INTERNAL-COST-DATA-DO-NOT-SHOW',
        ]);

        Livewire::actingAs(User::factory()->customer()->create())
            ->test(Index::class)
            ->assertSee('Talisman')
            ->assertDontSee('INTERNAL-COST-DATA-DO-NOT-SHOW');
    }

    public function test_an_unbanded_bird_reads_clearly_rather_than_showing_a_blank(): void
    {
        Broodcock::factory()->unbanded()->create(['name' => 'Youngster']);

        Livewire::actingAs(User::factory()->customer()->create())
            ->test(Index::class)
            ->assertSee('Youngster')
            ->assertSee('Not yet banded');
    }

    public function test_the_catalogue_can_be_filtered_by_bloodline(): void
    {
        Broodcock::factory()->create(['name' => 'Sweaterbird', 'bloodline' => 'Sweater']);
        Broodcock::factory()->create(['name' => 'Kelsobird', 'bloodline' => 'Kelso']);

        Livewire::actingAs(User::factory()->customer()->create())
            ->test(Index::class)
            ->set('bloodline', 'Sweater')
            ->assertSee('Sweaterbird')
            ->assertDontSee('Kelsobird');
    }

    public function test_an_empty_catalogue_says_something_useful(): void
    {
        Livewire::actingAs(User::factory()->customer()->create())
            ->test(Index::class)
            ->assertSee('No birds are listed yet');
    }

    public function test_the_catalogue_does_not_run_a_query_per_card(): void
    {
        $customer = User::factory()->customer()->create();

        Broodcock::factory()->count(3)->create();
        DB::flushQueryLog();
        DB::enableQueryLog();
        Livewire::actingAs($customer)->test(Index::class)->html();
        $small = count(DB::getQueryLog());
        DB::disableQueryLog();

        Broodcock::factory()->count(8)->create();
        DB::flushQueryLog();
        DB::enableQueryLog();
        Livewire::actingAs($customer)->test(Index::class)->html();
        $large = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertSame($small, $large, 'The photo relation is not eager-loaded on the catalogue grid.');
    }
}
