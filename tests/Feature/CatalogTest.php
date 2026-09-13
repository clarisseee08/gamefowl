<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\BroodcockStatus;
use App\Livewire\Catalog\Browse;
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

    /**
     * The catalogue is public. It used to require an account; it is the farm's
     * advertisement, and asking a prospective buyer to register before they can
     * look at stock defeats the point of having one.
     *
     * What a guest is shown, and what they are kept away from, is covered in
     * PublicCatalogueTest - this just pins that the door is open.
     */
    public function test_a_guest_can_browse_the_catalogue(): void
    {
        $this->get(route('catalog.index'))->assertOk();
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
            ->test(Browse::class)
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
            ->test(Browse::class)
            ->assertSee('Talisman')
            ->assertDontSee('INTERNAL-COST-DATA-DO-NOT-SHOW');
    }

    public function test_an_unbanded_bird_reads_clearly_rather_than_showing_a_blank(): void
    {
        Broodcock::factory()->unbanded()->create(['name' => 'Youngster']);

        Livewire::actingAs(User::factory()->customer()->create())
            ->test(Browse::class)
            ->assertSee('Youngster')
            ->assertSee('Not yet banded');
    }

    public function test_the_catalogue_can_be_filtered_by_bloodline(): void
    {
        Broodcock::factory()->create(['name' => 'Sweaterbird', 'bloodline' => 'Sweater']);
        Broodcock::factory()->create(['name' => 'Kelsobird', 'bloodline' => 'Kelso']);

        Livewire::actingAs(User::factory()->customer()->create())
            ->test(Browse::class)
            ->set('bloodline', 'Sweater')
            ->assertSee('Sweaterbird')
            ->assertDontSee('Kelsobird');
    }

    public function test_an_empty_catalogue_says_something_useful(): void
    {
        Livewire::actingAs(User::factory()->customer()->create())
            ->test(Browse::class)
            ->assertSee('No birds are listed yet');
    }

    public function test_the_catalogue_does_not_run_a_query_per_card(): void
    {
        $customer = User::factory()->customer()->create();

        Broodcock::factory()->count(3)->create();
        DB::flushQueryLog();
        DB::enableQueryLog();
        Livewire::actingAs($customer)->test(Browse::class)->html();
        $small = count(DB::getQueryLog());
        DB::disableQueryLog();

        Broodcock::factory()->count(8)->create();
        DB::flushQueryLog();
        DB::enableQueryLog();
        Livewire::actingAs($customer)->test(Browse::class)->html();
        $large = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertSame($small, $large, 'The photo relation is not eager-loaded on the catalogue grid.');
    }

    // -----------------------------------------------------------------
    // Availability
    //
    // `for_sale` was a write-only column: set on the bird form, shown on the
    // bird's own page, and consulted by nothing. Its index - ['for_sale',
    // 'status'] - was added specifically "to serve the catalogue", which never
    // read it.
    // -----------------------------------------------------------------

    public function test_the_catalogue_shows_every_bird_by_default_not_only_those_for_sale(): void
    {
        Broodcock::factory()->create(['name' => 'Bagwis', 'for_sale' => false]);
        Broodcock::factory()->create(['name' => 'Dalisay', 'for_sale' => true]);

        // The farm marks almost nothing for sale, so a catalogue that defaulted
        // to available-only would greet a customer with an empty page.
        Livewire::actingAs(User::factory()->customer()->create())
            ->test(Browse::class)
            ->assertSee('Bagwis')
            ->assertSee('Dalisay');
    }

    public function test_a_customer_can_narrow_the_catalogue_to_available_birds(): void
    {
        Broodcock::factory()->create(['name' => 'Bagwis', 'for_sale' => false]);
        Broodcock::factory()->create(['name' => 'Dalisay', 'for_sale' => true]);

        Livewire::actingAs(User::factory()->customer()->create())
            ->test(Browse::class)
            ->set('forSaleOnly', true)
            ->assertSee('Dalisay')
            ->assertDontSee('Bagwis');
    }

    public function test_a_bird_offered_for_sale_says_so_on_its_card(): void
    {
        Broodcock::factory()->create(['name' => 'Dalisay', 'for_sale' => true]);

        Livewire::actingAs(User::factory()->customer()->create())
            ->test(Browse::class)
            ->assertSee('For sale');
    }

    public function test_clearing_the_filters_restores_the_full_catalogue(): void
    {
        Broodcock::factory()->create(['name' => 'Bagwis', 'for_sale' => false]);

        Livewire::actingAs(User::factory()->customer()->create())
            ->test(Browse::class)
            ->set('forSaleOnly', true)
            ->call('clearFilters')
            ->assertSet('forSaleOnly', false)
            ->assertSee('Bagwis');
    }

    // -----------------------------------------------------------------
    // Which shell the catalogue renders inside
    //
    // A customer gets the bare catalogue shell - it is the whole application
    // to them, and a console rail would offer screens they cannot open. Staff
    // get the console shell, because for them this is one screen among many
    // and stripping the sidebar left them with a single "Console" button where
    // every other screen has navigation.
    // -----------------------------------------------------------------

    public function test_staff_keep_the_console_sidebar_on_the_catalogue(): void
    {
        Broodcock::factory()->create();

        $this->actingAs(User::factory()->staff()->create())
            ->get(route('catalog.index'))
            ->assertOk()
            // The sidebar, by the screens only it links to.
            ->assertSee('Broodcocks')
            ->assertSee('Reports');
    }

    public function test_an_owner_keeps_the_console_sidebar_on_the_catalogue(): void
    {
        Broodcock::factory()->create();

        $this->actingAs(User::factory()->owner()->create())
            ->get(route('catalog.index'))
            ->assertOk()
            ->assertSee('Broodcocks')
            // Owner-only nav, so this also proves the rail is role-aware here.
            ->assertSee('Users');
    }

    /** A customer must never be shown navigation for screens they cannot open. */
    public function test_a_customer_gets_the_bare_catalogue_shell(): void
    {
        Broodcock::factory()->create();

        $this->actingAs(User::factory()->customer()->create())
            ->get(route('catalog.index'))
            ->assertOk()
            ->assertDontSee('Main navigation', escape: false)
            ->assertDontSee('Mortality')
            ->assertDontSee('Breeding')
            ->assertDontSee('Users');
    }
}
