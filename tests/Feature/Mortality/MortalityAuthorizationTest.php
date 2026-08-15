<?php

declare(strict_types=1);

namespace Tests\Feature\Mortality;

use App\Enums\BroodcockStatus;
use App\Enums\Sex;
use App\Livewire\Mortality\Form;
use App\Livewire\Mortality\Index;
use App\Models\Broodcock;
use App\Models\MortalityRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Who may see and change mortality data.
 *
 * Mortality is internal farm information. A customer browsing the catalogue
 * must not learn that a bird died, let alone why - so this is checked at the
 * component, not only by hiding a link in the navigation.
 *
 * One test per role per protected action, asserting both the response and that
 * the data did not change.
 */
final class MortalityAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // routes/web.php is owned by another vertical - register the routes
        // here only if they do not exist yet.
        if (! Route::has('mortality.index')) {
            Route::middleware(['web', 'auth', 'active'])->group(function (): void {
                Route::livewire('/mortality', Index::class)->name('mortality.index');
                Route::livewire('/mortality/create/{broodcock?}', Form::class)->name('mortality.create');
            });

            // Names are indexed when the application boots, which already
            // happened above - re-index so route() can find these.
            Route::getRoutes()->refreshNameLookups();
        }
    }

    // -----------------------------------------------------------------
    // Viewing the register
    // -----------------------------------------------------------------

    public function test_the_owner_can_view_the_mortality_register(): void
    {
        $this->actingAs(User::factory()->owner()->create())
            ->get(route('mortality.index'))
            ->assertOk();
    }

    public function test_staff_can_view_the_mortality_register(): void
    {
        $this->actingAs(User::factory()->staff()->create())
            ->get(route('mortality.index'))
            ->assertOk();
    }

    /** The whole point of the policy: a customer must not see mortality at all. */
    public function test_a_customer_cannot_view_the_mortality_register(): void
    {
        $customer = User::factory()->customer()->create();
        $bird = $this->makeBroodcock(['name' => 'Kalabaw']);
        MortalityRecord::factory()->forBroodcock($bird)
            ->recordedBy(User::factory()->staff()->create())
            ->cause('Disease')
            ->create();

        $response = $this->actingAs($customer)->get(route('mortality.index'));

        $response->assertForbidden();
        $response->assertDontSee('Kalabaw');
        $response->assertDontSee('Disease');
    }

    public function test_a_customer_cannot_reach_the_register_component_directly(): void
    {
        // Livewire converts an authorization failure into a 403 RESPONSE
        // rather than letting AuthorizationException bubble out, so the
        // assertion is assertForbidden() - not expectException().
        Livewire::actingAs(User::factory()->customer()->create())
            ->test(Index::class)
            ->assertForbidden();
    }

    public function test_a_guest_is_redirected_to_login(): void
    {
        $this->get(route('mortality.index'))->assertRedirect(route('login'));
    }

    // -----------------------------------------------------------------
    // Recording a death
    // -----------------------------------------------------------------

    public function test_staff_can_open_the_recording_form(): void
    {
        $this->actingAs(User::factory()->staff()->create())
            ->get(route('mortality.create'))
            ->assertOk();
    }

    public function test_a_customer_cannot_open_the_recording_form(): void
    {
        $this->actingAs(User::factory()->customer()->create())
            ->get(route('mortality.create'))
            ->assertForbidden();
    }

    /**
     * A customer cannot record a death even by posting straight at the
     * component - the button they never saw is not the protection.
     */
    public function test_a_customer_cannot_record_a_death(): void
    {
        $customer = User::factory()->customer()->create();
        $bird = $this->makeBroodcock();

        // The component refuses at mount(), so a customer cannot even reach
        // the point of setting fields - which is the strongest possible
        // outcome here.
        Livewire::actingAs($customer)
            ->test(Form::class)
            ->assertForbidden();

        $this->assertDatabaseCount('mortality_records', 0);
        $this->assertSame(BroodcockStatus::Active, $bird->fresh()->status);
    }

    /** A deactivated account keeps its role but loses every permission. */
    public function test_a_deactivated_staff_member_cannot_record_a_death(): void
    {
        $staff = User::factory()->staff()->inactive()->create();

        Livewire::actingAs($staff)
            ->test(Form::class)
            ->assertForbidden();
    }

    /**
     * BroodcockPolicy::recordMortality() refuses a bird that is already
     * deceased. The bird is not offered in the list, so reaching this means
     * the request was tampered with or the page was stale.
     */
    public function test_a_bird_that_is_already_deceased_cannot_be_recorded_again(): void
    {
        $staff = User::factory()->staff()->create();
        $bird = $this->makeBroodcock(['status' => BroodcockStatus::Deceased]);

        // mount() passes (staff may create mortality records in general), but
        // save() re-checks recordMortality() against THIS bird and refuses,
        // because it is already deceased.
        Livewire::actingAs($staff)
            ->test(Form::class)
            ->set('broodcock_id', (string) $bird->id)
            ->set('date_of_death', '2026-08-01')
            ->set('cause_of_death', 'Disease')
            ->call('save')
            ->assertForbidden();

        $this->assertDatabaseCount('mortality_records', 0);
    }

    public function test_opening_the_form_for_a_deceased_bird_is_refused(): void
    {
        $staff = User::factory()->staff()->create();
        $bird = $this->makeBroodcock(['status' => BroodcockStatus::Deceased]);

        $this->actingAs($staff)
            ->get(route('mortality.create', $bird))
            ->assertForbidden();
    }

    // -----------------------------------------------------------------
    // Deleting - owner only
    // -----------------------------------------------------------------

    public function test_the_owner_can_delete_a_mortality_record(): void
    {
        $owner = User::factory()->owner()->create();
        $bird = $this->makeBroodcock(['status' => BroodcockStatus::Deceased]);
        $record = MortalityRecord::factory()->forBroodcock($bird)->recordedBy($owner)->create();

        Livewire::actingAs($owner)
            ->test(Index::class)
            ->call('confirmDelete', $record->id)
            ->call('delete');

        $this->assertSoftDeleted('mortality_records', ['id' => $record->id]);
        $this->assertSame(BroodcockStatus::Active, $bird->fresh()->status);
    }

    /** Staff may record deaths but may not erase them. */
    public function test_staff_cannot_delete_a_mortality_record(): void
    {
        $staff = User::factory()->staff()->create();
        $bird = $this->makeBroodcock(['status' => BroodcockStatus::Deceased]);
        $record = MortalityRecord::factory()->forBroodcock($bird)->recordedBy($staff)->create();

        Livewire::actingAs($staff)
            ->test(Index::class)
            ->call('confirmDelete', $record->id)
            ->call('delete')
            ->assertForbidden();

        // Refused AND nothing changed - a 403 that still deleted would be worse
        // than no check at all.
        $this->assertNotSoftDeleted('mortality_records', ['id' => $record->id]);
        $this->assertSame(BroodcockStatus::Deceased, $bird->fresh()->status);
    }

    /** Staff never even see the Delete button - a courtesy, not the gate. */
    public function test_the_delete_button_is_not_shown_to_staff(): void
    {
        $staff = User::factory()->staff()->create();
        $bird = $this->makeBroodcock(['status' => BroodcockStatus::Deceased]);
        MortalityRecord::factory()->forBroodcock($bird)->recordedBy($staff)->create();

        Livewire::actingAs($staff)
            ->test(Index::class)
            ->assertDontSee('Delete Record')
            ->assertDontSeeHtml('wire:click="confirmDelete');
    }

    /**
     * BroodcockFactory belongs to another vertical, so birds are built by hand.
     *
     * @param  array<string, mixed>  $attributes
     */
    private function makeBroodcock(array $attributes = []): Broodcock
    {
        static $counter = 0;
        $counter++;

        return Broodcock::create(array_merge([
            'band_number' => 'AZ-'.str_pad((string) $counter, 4, '0', STR_PAD_LEFT),
            'name' => 'Bird '.$counter,
            'sex' => Sex::Male,
            'status' => BroodcockStatus::Active,
            'date_hatched' => '2023-01-15',
        ], $attributes));
    }
}
