<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Broodcock;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * The farm's front door.
 *
 * `/` used to be a redirect: a guest was bounced to the catalogue and a member
 * of staff to the dashboard. That is still true for staff, whose working day
 * starts at the dashboard and who have no use for an advertisement. For
 * everyone else `/` is now a page - the farm's name and what it does, the
 * stock, and the way to arrange a visit.
 *
 * THE CATALOGUE IS ON IT, in full, with its filters. That was a deliberate
 * choice with a known cost: a page carrying both a filterable grid and a form
 * is heavier than either alone. What it buys is that a visitor never has to
 * leave the front page to see the birds.
 */
final class LandingTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_visitor_who_has_never_signed_in_gets_a_page(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee(config('gfms.farm.name'));
    }

    /** Staff start their day at the dashboard, not at the shop window. */
    public function test_staff_are_still_sent_to_the_dashboard(): void
    {
        $this->actingAs(User::factory()->staff()->create())
            ->get(route('home'))
            ->assertRedirect(route('dashboard'));
    }

    public function test_the_owner_is_still_sent_to_the_dashboard(): void
    {
        $this->actingAs(User::factory()->owner()->create())
            ->get(route('home'))
            ->assertRedirect(route('dashboard'));
    }

    public function test_a_customer_account_sees_the_landing_page(): void
    {
        $this->actingAs(User::factory()->customer()->create())
            ->get(route('home'))
            ->assertOk()
            ->assertSee(config('gfms.farm.name'));
    }

    public function test_the_stock_is_on_the_front_page(): void
    {
        Broodcock::factory()->create(['name' => 'Bagwis']);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Bagwis');
    }

    /** The same rule the catalogue itself follows, on the page that shows it. */
    public function test_a_bird_the_farm_does_not_own_is_not_advertised(): void
    {
        Broodcock::factory()->external()->create(['name' => 'Borrowed Hen']);

        $this->get(route('home'))
            ->assertOk()
            ->assertDontSee('Borrowed Hen');
    }

    public function test_the_front_page_says_how_to_arrange_a_visit(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Visit the farm');
    }

    /**
     * The performance guard on what becomes the most-hit page in the system.
     *
     * Every query against the production database costs roughly 240ms of round
     * trip regardless of what it asks for, so a per-bird lookup here is far
     * more expensive than it looks in a local test run.
     */
    public function test_the_front_page_does_not_run_a_query_per_bird(): void
    {
        Broodcock::factory()->count(3)->create();
        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->get(route('home'))->assertOk();
        $small = count(DB::getQueryLog());
        DB::disableQueryLog();

        Broodcock::factory()->count(8)->create();
        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->get(route('home'))->assertOk();
        $large = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertSame($small, $large, 'The landing page runs a query per bird.');
    }
}
