<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\BroodcockStatus;
use App\Models\Broodcock;
use App\Models\BroodcockPhoto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * The catalogue is the farm's public advertisement - no account required.
 *
 * THE RULE THIS IS BUILT ON: a guest is treated as the equivalent of an active
 * customer. Not a new visibility tier - the same surface that role was already
 * designed and tested against. So opening these screens up changes WHO can
 * reach them, never WHAT they render, and the internal fields stay hidden by
 * the checks that were already in the views.
 *
 * The part that needed new thinking is enumeration. Ids in URLs are guessable,
 * so "the catalogue does not list it" is not the same as "the public cannot
 * reach it" - hence BroodcockPolicy::view() restricting a guest to exactly the
 * birds the catalogue itself would show.
 */
final class PublicCatalogueTest extends TestCase
{
    use RefreshDatabase;

    // -----------------------------------------------------------------
    // What the public may reach
    // -----------------------------------------------------------------

    public function test_a_visitor_who_is_not_signed_in_can_browse_the_catalogue(): void
    {
        Broodcock::factory()->create(['name' => 'Bagwis']);

        $this->get(route('catalog.index'))
            ->assertOk()
            ->assertSee('Bagwis');
    }

    public function test_a_visitor_can_open_a_birds_page(): void
    {
        $bird = Broodcock::factory()->create(['name' => 'Bagwis']);

        $this->get(route('broodcocks.show', $bird))
            ->assertOk()
            ->assertSee('Bagwis');
    }

    /**
     * The family tree is public deliberately: it is the farm's actual selling
     * point, and an advertisement showing photographs with nothing to say about
     * breeding is a weaker advertisement.
     */
    public function test_a_visitor_can_open_the_family_tree(): void
    {
        $bird = Broodcock::factory()->create(['name' => 'Bagwis']);

        $this->get(route('broodcocks.pedigree', $bird))->assertOk();
    }

    /** The front door is the shop window, not a login form. */
    /*
     * The root was a redirect to the catalogue until the farm got a front page.
     * It is now a page carrying the farm's name, its story and the same stock.
     */
    public function test_the_site_root_gives_a_visitor_the_front_page(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee(config('gfms.farm.name'));
    }

    public function test_the_site_root_sends_staff_to_the_dashboard(): void
    {
        $this->actingAs(User::factory()->staff()->create())
            ->get('/')
            ->assertRedirect(route('dashboard'));
    }

    // -----------------------------------------------------------------
    // What the public may NOT reach
    //
    // These are the enumeration cases. The catalogue never lists these birds,
    // but a URL is a URL.
    // -----------------------------------------------------------------

    public function test_a_visitor_cannot_open_a_bird_that_has_died(): void
    {
        $dead = Broodcock::factory()->create(['status' => BroodcockStatus::Deceased]);

        $this->get(route('broodcocks.show', $dead))->assertForbidden();
    }

    public function test_a_visitor_cannot_open_a_bird_that_was_sold(): void
    {
        $sold = Broodcock::factory()->create(['status' => BroodcockStatus::Sold]);

        $this->get(route('broodcocks.show', $sold))->assertForbidden();
    }

    /** A borrowed hen belongs to another farm; she is not this farm's shop window. */
    public function test_a_visitor_cannot_open_a_bird_the_farm_does_not_own(): void
    {
        $outside = Broodcock::factory()->external()->create();

        $this->get(route('broodcocks.show', $outside))->assertForbidden();
    }

    public function test_a_visitor_cannot_open_the_family_tree_of_a_hidden_bird(): void
    {
        $dead = Broodcock::factory()->create(['status' => BroodcockStatus::Deceased]);

        $this->get(route('broodcocks.pedigree', $dead))->assertForbidden();
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function internalRoutes(): array
    {
        return [
            'broodcock list' => ['broodcocks.index'],
            'add a bird' => ['broodcocks.create'],
            'dashboard' => ['dashboard'],
            'reports' => ['reports.index'],
            'health' => ['health.index'],
            'breeding' => ['breeding.index'],
            'mortality' => ['mortality.index'],
            'users' => ['users.index'],
            'profile' => ['profile.edit'],
        ];
    }

    #[DataProvider('internalRoutes')]
    public function test_the_console_still_requires_an_account(string $route): void
    {
        $this->get(route($route))->assertRedirect(route('login'));
    }

    // -----------------------------------------------------------------
    // Photos
    //
    // Photo ids are sequential. This is the difference between "the public
    // catalogue has pictures" and "anyone can page through every photograph
    // the farm has ever taken".
    // -----------------------------------------------------------------

    public function test_a_visitor_can_fetch_a_photo_of_a_bird_they_may_see(): void
    {
        $bird = Broodcock::factory()->create();
        $photo = BroodcockPhoto::factory()->for($bird)->create();

        // The file itself is not the point here - reaching the route is.
        $this->get(route('photos.show', $photo))->assertNotFound();
        // 404 = authorized, file absent. A 403 would mean the policy refused.
    }

    public function test_a_visitor_cannot_fetch_a_photo_of_a_hidden_bird(): void
    {
        $dead = Broodcock::factory()->create(['status' => BroodcockStatus::Deceased]);
        $photo = BroodcockPhoto::factory()->for($dead)->create();

        $this->get(route('photos.show', $photo))->assertForbidden();
    }

    // -----------------------------------------------------------------
    // The public page must stay the CUSTOMER view, not the staff one
    // -----------------------------------------------------------------

    public function test_a_visitor_is_shown_no_internal_information(): void
    {
        $bird = Broodcock::factory()->create([
            'name' => 'Bagwis',
            'notes' => 'Bought cheap from a neighbour who was giving up.',
        ]);

        $response = $this->get(route('broodcocks.show', $bird));

        $response->assertOk()
            ->assertSee('Bagwis')
            // Internal notes are the clearest tell: they are farm business.
            ->assertDontSee('Bought cheap from a neighbour')
            // The offspring tab is flagged internal in the view.
            ->assertDontSee('Offspring')
            // And nothing that writes.
            ->assertDontSee('Edit Bird');
    }

    /**
     * A visitor gets the shop-window shell, not the console rail.
     *
     * The console sidebar would advertise Reports, Breeding and Mortality to
     * someone whose next click is a login form.
     */
    public function test_a_visitor_gets_the_public_shell_not_the_console(): void
    {
        $bird = Broodcock::factory()->create();

        $response = $this->get(route('broodcocks.show', $bird));

        $response->assertDontSee('Main navigation', escape: false)
            ->assertDontSee('Mortality')
            ->assertDontSee('Reports');
    }

    public function test_staff_opening_the_same_page_keep_the_console(): void
    {
        $bird = Broodcock::factory()->create();

        $this->actingAs(User::factory()->staff()->create())
            ->get(route('broodcocks.show', $bird))
            ->assertOk()
            ->assertSee('Reports')
            ->assertSee('Mortality');
    }

    /** A visitor is offered a way in; they are not offered a way out. */
    public function test_the_public_shell_offers_sign_in_rather_than_sign_out(): void
    {
        Broodcock::factory()->create();

        $this->get(route('catalog.index'))
            ->assertOk()
            ->assertSee('Sign in')
            ->assertDontSee('Sign out');
    }

    // -----------------------------------------------------------------
    // The rule the whole thing rests on
    // -----------------------------------------------------------------

    /**
     * Public visibility and the catalogue's own filter must not drift apart.
     *
     * If selling a bird removed it from the catalogue but left its page
     * reachable, the leak would be invisible - the bird simply stops appearing
     * while its URL keeps working.
     */
    public function test_public_visibility_matches_exactly_what_the_catalogue_lists(): void
    {
        foreach (BroodcockStatus::cases() as $status) {
            $bird = Broodcock::factory()->create(['status' => $status]);

            $listed = Broodcock::query()->farmStock()->onFarm()->whereKey($bird->id)->exists();

            $this->assertSame(
                $listed,
                $bird->isPubliclyVisible(),
                "Status [{$status->value}] is listed by the catalogue but not publicly visible, or vice versa."
            );
        }
    }

    public function test_an_outside_bird_is_never_publicly_visible(): void
    {
        $outside = Broodcock::factory()->external()->create(['status' => BroodcockStatus::Active]);

        $this->assertFalse($outside->isPubliclyVisible());
    }
}
