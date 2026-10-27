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

    /**
     * The reported bug: ticking "Available birds only" emptied the catalogue.
     *
     * THE FILTER WAS NEVER BROKEN. It returned nothing because not one bird on
     * the farm was marked for sale - eighteen in production, every one false.
     * What was broken is that the page offered a control that could only ever
     * empty itself, and then said "No birds match your search", which blames
     * the visitor for a state they could not have avoided.
     *
     * These three tests pin the fix: the control is disabled when it cannot
     * succeed, it stays live the moment one bird is offered, and the empty
     * state tells the truth rather than suggesting a different bloodline.
     */
    public function test_the_for_sale_filter_is_disabled_when_no_bird_is_offered(): void
    {
        Broodcock::factory()->count(3)->create(['for_sale' => false]);

        Livewire::test(Browse::class)
            ->assertSee('No birds are marked for sale just now.')
            ->assertSee('disabled', escape: false);
    }

    public function test_the_for_sale_filter_is_live_as_soon_as_one_bird_is_offered(): void
    {
        Broodcock::factory()->create(['for_sale' => false]);
        Broodcock::factory()->create(['name' => 'Dalisay', 'for_sale' => true]);

        Livewire::test(Browse::class)
            ->assertDontSee('No birds are marked for sale just now.')
            ->set('forSaleOnly', true)
            ->assertSee('Dalisay');
    }

    public function test_the_empty_state_says_nothing_is_for_sale_rather_than_blaming_the_search(): void
    {
        Broodcock::factory()->count(2)->create(['for_sale' => false]);

        Livewire::test(Browse::class)
            ->set('forSaleOnly', true)
            ->assertSee('No birds are marked for sale just now')
            ->assertSee('Show every bird on the farm')
            // The old copy sent the reader after a bloodline, which could not
            // have helped: no bloodline on this farm is for sale either.
            ->assertDontSee('No birds match your search')
            ->assertDontSee('Try a different bloodline');
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

    public function test_the_catalogue_sorts_youngest_first_with_undated_birds_last(): void
    {
        Broodcock::factory()->create(['name' => 'Old', 'date_hatched' => '2022-01-01']);
        Broodcock::factory()->create(['name' => 'Young', 'date_hatched' => '2025-06-01']);
        Broodcock::factory()->create(['name' => 'Aardvark', 'date_hatched' => null]);

        Livewire::test(Browse::class)
            ->set('sort', 'youngest')
            ->assertSeeInOrder(['Young', 'Old', 'Aardvark']);

        Livewire::test(Browse::class)
            ->set('sort', 'oldest')
            ->assertSeeInOrder(['Old', 'Young', 'Aardvark']);
    }

    public function test_the_catalogue_sorts_by_bloodline(): void
    {
        Broodcock::factory()->create(['name' => 'First', 'bloodline' => 'Sweater']);
        Broodcock::factory()->create(['name' => 'Second', 'bloodline' => 'Kelso']);

        Livewire::test(Browse::class)
            ->set('sort', 'bloodline')
            ->assertSeeInOrder(['Second', 'First']);
    }

    public function test_clearing_the_filters_keeps_the_chosen_order(): void
    {
        Livewire::test(Browse::class)
            ->set('sort', 'oldest')
            ->set('bloodline', 'Kelso')
            ->call('clearFilters')
            ->assertSet('sort', 'oldest')
            ->assertSet('bloodline', '');
    }
}
