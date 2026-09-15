<?php

declare(strict_types=1);

namespace Tests\Feature\Settings;

use App\Livewire\Settings\Farm;
use App\Models\FarmSetting;
use App\Models\User;
use App\Support\FarmProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * The farm's public identity, edited by the owner rather than deployed.
 *
 * These five fields were GFMS_FARM_* environment variables, which meant
 * changing the farm's phone number took a code change, a commit and a deploy -
 * and therefore took a developer. The owner, whose number it is, could not.
 *
 * WHAT IS ACTUALLY BEING TESTED is the bridge as much as the form.
 * App\Support\FarmProfile overwrites config('gfms.farm.*') at boot so that the
 * nine places already reading that config - the catalogue footer and header,
 * the console sidebar, the error pages, head-meta, the dashboard greeting and
 * the dompdf report layout - did not have to change. A save that updates the
 * row but not those pages would be the failure worth catching, so the
 * assertions go through real HTTP to the public page wherever they can.
 */
final class FarmProfileTest extends TestCase
{
    use RefreshDatabase;

    private function owner(): User
    {
        return User::factory()->owner()->create();
    }

    /**
     * Stop being the owner before looking at the public page.
     *
     * Livewire::actingAs() leaves that owner signed in for the rest of the
     * test, and Landing\Index::mount() redirects anyone internal to the
     * dashboard - staff should not land on the shop window every morning. So
     * every assertion below about what a VISITOR sees has to arrive as one,
     * or it asserts against a 302.
     */
    private function asVisitor(): self
    {
        auth()->logout();

        return $this;
    }

    // -----------------------------------------------------------------
    // Who may open it
    // -----------------------------------------------------------------

    public function test_the_owner_can_open_the_farm_profile(): void
    {
        $this->actingAs($this->owner())
            ->get(route('settings.edit'))
            ->assertOk()
            ->assertSee('Farm Profile');
    }

    /**
     * Staff are trusted with the records and not with the shopfront.
     *
     * A keeper can create and delete birds, which is the larger operation on
     * the data. This is the smaller operation with the wider blast radius: a
     * wrong number here is wrong for every visitor until somebody notices.
     */
    public function test_staff_cannot_open_the_farm_profile(): void
    {
        $this->actingAs(User::factory()->staff()->create())
            ->get(route('settings.edit'))
            ->assertForbidden();
    }

    public function test_a_customer_cannot_open_the_farm_profile(): void
    {
        $this->actingAs(User::factory()->customer()->create())
            ->get(route('settings.edit'))
            ->assertForbidden();
    }

    public function test_a_guest_is_sent_to_sign_in(): void
    {
        $this->get(route('settings.edit'))->assertRedirect(route('login'));
    }

    /**
     * Not a 403. EnsureUserIsActive runs ahead of the policy and signs a
     * deactivated account out entirely, so it never reaches FarmSettingPolicy
     * to be refused by it - the owner who deactivated them expects that to
     * take effect on their next click, not on their next login.
     */
    public function test_a_deactivated_owner_is_signed_out_rather_than_refused(): void
    {
        $this->actingAs(User::factory()->owner()->create(['is_active' => false]))
            ->get(route('settings.edit'))
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }

    // -----------------------------------------------------------------
    // Saving, and where it lands
    // -----------------------------------------------------------------

    public function test_the_form_opens_on_what_is_already_stored(): void
    {
        FarmSetting::current()->update([
            'phone' => '+639123456789',
            'email' => 'gfms_inquiries@gmail.com',
        ]);

        Livewire::actingAs($this->owner())
            ->test(Farm::class)
            ->assertSet('phone', '+639123456789')
            ->assertSet('email', 'gfms_inquiries@gmail.com');
    }

    public function test_the_owner_can_change_the_contact_details(): void
    {
        Livewire::actingAs($this->owner())
            ->test(Farm::class)
            ->set('farm_name', 'SSGuad Game Farm')
            ->set('phone', '+639123456789')
            ->set('email', 'gfms_inquiries@gmail.com')
            ->set('address', 'Guimbal, Iloilo')
            ->set('hours', 'Monday to Saturday, 8AM to 5PM')
            ->call('save')
            ->assertHasNoErrors();

        $stored = FarmSetting::current();

        $this->assertSame('+639123456789', $stored->phone);
        $this->assertSame('gfms_inquiries@gmail.com', $stored->email);
        $this->assertSame('Guimbal, Iloilo', $stored->address);
        $this->assertSame('Monday to Saturday, 8AM to 5PM', $stored->hours);
    }

    /** Saving twice must not leave two farms for FarmSetting::current() to choose between. */
    public function test_saving_never_creates_a_second_row(): void
    {
        $component = Livewire::actingAs($this->owner())->test(Farm::class);

        $component->set('farm_name', 'First Name')->call('save')->assertHasNoErrors();
        $component->set('farm_name', 'Second Name')->call('save')->assertHasNoErrors();

        $this->assertSame(1, FarmSetting::query()->count());
        $this->assertSame('Second Name', FarmSetting::current()->farm_name);
    }

    /**
     * The whole point: the public page must follow.
     *
     * Not an assertion about the model - an assertion that the config bridge
     * carried the new row all the way to a rendered page nobody touched.
     */
    public function test_what_the_owner_saves_appears_on_the_public_page(): void
    {
        Livewire::actingAs($this->owner())
            ->test(Farm::class)
            ->set('farm_name', 'SSGuad Game Farm')
            ->set('phone', '+639123456789')
            ->set('email', 'gfms_inquiries@gmail.com')
            ->set('address', 'Guimbal, Iloilo')
            ->set('hours', 'Monday to Saturday, 8AM to 5PM')
            ->call('save')
            ->assertHasNoErrors();

        $this->asVisitor()->get(route('home'))
            ->assertOk()
            ->assertSee('+639123456789')
            ->assertSee('gfms_inquiries@gmail.com')
            ->assertSee('Guimbal, Iloilo')
            ->assertSee('Monday to Saturday, 8AM to 5PM');
    }

    public function test_a_note_to_visitors_reaches_the_public_page(): void
    {
        Livewire::actingAs($this->owner())
            ->test(Farm::class)
            ->set('farm_name', 'SSGuad Game Farm')
            ->set('phone', '+639123456789')
            ->set('visitor_note', 'Ring ahead if you are coming on a Sunday.')
            ->call('save')
            ->assertHasNoErrors();

        $this->asVisitor()->get(route('home'))
            ->assertOk()
            ->assertSee('Ring ahead if you are coming on a Sunday.');
    }

    /**
     * Clearing a field is how the owner removes it, so it must actually remove
     * it - a bare "Visiting hours" heading with nothing under it is worse than
     * no heading at all, and there is no other delete control to reach for.
     */
    public function test_a_cleared_field_disappears_from_the_public_page(): void
    {
        $owner = $this->owner();

        $fill = fn (): Testable => Livewire::actingAs($owner)
            ->test(Farm::class)
            ->set('farm_name', 'SSGuad Game Farm')
            ->set('phone', '+639123456789');

        $fill()->set('hours', 'Monday to Saturday, 8AM to 5PM')->call('save')->assertHasNoErrors();

        $this->asVisitor()->get(route('home'))->assertSee('Visiting hours');

        /*
         * A FRESH COMPONENT, and re-authenticated, because asVisitor() above
         * logged the owner out - reusing the first instance would have called
         * save() as a guest and been refused by the policy, which looks exactly
         * like "clearing the field did not work".
         */
        $fill()->set('hours', '')->call('save')->assertHasNoErrors();

        $this->asVisitor()->get(route('home'))
            ->assertOk()
            ->assertDontSee('Visiting hours')
            ->assertSee('+639123456789');
    }

    // -----------------------------------------------------------------
    // What is refused
    // -----------------------------------------------------------------

    public function test_the_farm_cannot_be_left_without_a_name(): void
    {
        Livewire::actingAs($this->owner())
            ->test(Farm::class)
            ->set('farm_name', '')
            ->call('save')
            ->assertHasErrors('farm_name');
    }

    public function test_an_address_that_is_not_an_email_is_refused(): void
    {
        Livewire::actingAs($this->owner())
            ->test(Farm::class)
            ->set('farm_name', 'SSGuad Game Farm')
            ->set('email', 'not-an-email')
            ->call('save')
            ->assertHasErrors('email');
    }

    /**
     * A phone number is a string and is not pattern-matched.
     *
     * "+639123456789", "0912 345 6789" and "(033) 396 1234" are all things a
     * farm legitimately writes. A rule that took the first two and refused the
     * third would be one the owner has to fight.
     */
    public function test_a_locally_formatted_phone_number_is_accepted(): void
    {
        Livewire::actingAs($this->owner())
            ->test(Farm::class)
            ->set('farm_name', 'SSGuad Game Farm')
            ->set('phone', '(033) 396 1234')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('(033) 396 1234', FarmSetting::current()->phone);
    }

    // -----------------------------------------------------------------
    // The bridge itself
    // -----------------------------------------------------------------

    /**
     * FarmProfile must not take the application down when there is nothing to
     * read.
     *
     * This runs in AppServiceProvider::boot(), which runs BEFORE `artisan
     * migrate` does. A query here against a table that migration has not
     * created yet would break the one command that could fix it - so the
     * failure case has to leave the config defaults standing and say nothing.
     */
    public function test_the_bridge_falls_back_to_config_when_there_is_no_row(): void
    {
        FarmSetting::query()->delete();
        config(['gfms.farm.phone' => 'fallback number']);

        FarmProfile::refresh();

        $this->assertSame('fallback number', config('gfms.farm.phone'));
    }

    /**
     * An unreachable CACHE STORE must not take the application down either.
     *
     * THIS IS A REGRESSION TEST FOR A BUILD FAILURE, not a hypothetical.
     * config/cache.php defaults CACHE_STORE to `database`; production sets it
     * to `file`, so on any machine with a .env this reads a file. CI has no
     * .env and neither does the image build, so there Cache::get() is a SQL
     * query - and it ran during `artisan package:discover`, a composer
     * post-autoload-dump hook, against a SQLite file composer install had not
     * created yet. `composer install` exited 1 and took the whole build with
     * it. The guard existed but sat around the model query only.
     *
     * A cache that cannot be reached must degrade to reading the row, not to
     * the environment fallback - so this asserts the stored value still
     * arrives, which is the half a bare "does not throw" would miss.
     */
    public function test_the_bridge_survives_a_cache_store_it_cannot_reach(): void
    {
        FarmSetting::current()->update(['phone' => '+639123456789']);

        config([
            'gfms.farm.phone' => 'fallback number',
            'cache.default' => 'database',
        ]);

        // Drop the store out from under it, exactly as the build found it.
        Cache::purge('database');
        Schema::drop('cache');

        FarmProfile::apply();

        $this->assertSame(
            '+639123456789',
            config('gfms.farm.phone'),
            'An unreachable cache should fall back to reading the row, not to the config default.'
        );
    }

    /** A stored value wins over the config default - that is the whole job. */
    public function test_a_stored_value_overrides_the_config_default(): void
    {
        config(['gfms.farm.phone' => 'config number']);

        FarmSetting::current()->update(['phone' => '+639123456789']);

        $this->assertSame('+639123456789', config('gfms.farm.phone'));
    }

    /**
     * The console shell reads the same config, so a rename must reach it too -
     * this is the assertion that would fail if somebody "tidied" the bridge
     * into the public views only.
     */
    public function test_renaming_the_farm_reaches_the_console_shell(): void
    {
        $owner = $this->owner();

        Livewire::actingAs($owner)
            ->test(Farm::class)
            ->set('farm_name', 'Guadalupe Breeding Farm')
            ->call('save')
            ->assertHasNoErrors();

        $this->actingAs($owner)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Guadalupe Breeding Farm');
    }
}
