<?php

declare(strict_types=1);

namespace Tests\Feature\Appointments;

use App\Enums\AppointmentStatus;
use App\Livewire\Appointments\Index;
use App\Models\Appointment;
use App\Models\Broodcock;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Working through the visit requests.
 *
 * The other half of the feature, and the half that makes it more than a form
 * that writes a row. A request arrives pending; somebody decides; the record
 * says who decided and when.
 *
 * CONFIRMING DOES NOT TELL THE VISITOR ANYTHING. There is no email, because
 * MAIL_* is unset. The status is the farm's own note of what it agreed on the
 * phone, not a message sent to anyone - which is why the screen says to ring
 * them rather than implying the system has.
 */
final class ReviewQueueTest extends TestCase
{
    use RefreshDatabase;

    private function owner(): User
    {
        return User::factory()->owner()->create();
    }

    // -----------------------------------------------------------------
    // Who may open it
    // -----------------------------------------------------------------

    public function test_the_owner_can_open_the_queue(): void
    {
        $this->actingAs($this->owner())
            ->get(route('appointments.index'))
            ->assertOk();
    }

    public function test_staff_can_open_the_queue(): void
    {
        $this->actingAs(User::factory()->staff()->create())
            ->get(route('appointments.index'))
            ->assertOk();
    }

    /** A customer may ask to visit; other people's requests are not theirs. */
    public function test_a_customer_is_refused(): void
    {
        $this->actingAs(User::factory()->customer()->create())
            ->get(route('appointments.index'))
            ->assertForbidden();
    }

    public function test_a_visitor_who_is_not_signed_in_is_sent_to_the_login(): void
    {
        $this->get(route('appointments.index'))->assertRedirect(route('login'));
    }

    // -----------------------------------------------------------------
    // What it shows
    // -----------------------------------------------------------------

    public function test_a_pending_request_is_listed(): void
    {
        Appointment::factory()->create(['name' => 'Marites Santos']);

        Livewire::actingAs($this->owner())
            ->test(Index::class)
            ->assertSee('Marites Santos');
    }

    /** The screen opens on what needs action, not on everything ever asked. */
    public function test_the_queue_opens_on_what_still_needs_a_decision(): void
    {
        Appointment::factory()->create(['name' => 'Still Waiting']);
        Appointment::factory()->confirmed()->create(['name' => 'Already Handled']);

        Livewire::actingAs($this->owner())
            ->test(Index::class)
            ->assertSee('Still Waiting')
            ->assertDontSee('Already Handled');
    }

    public function test_another_status_can_be_looked_at(): void
    {
        Appointment::factory()->confirmed()->create(['name' => 'Already Handled']);

        Livewire::actingAs($this->owner())
            ->test(Index::class)
            ->set('status', AppointmentStatus::Confirmed->value)
            ->assertSee('Already Handled');
    }

    /**
     * "All requests" is the way out of a default that hides everything.
     *
     * Opening on Pending is right for working a queue, but on a farm whose
     * queue is momentarily empty the screen read "No visit requests to show"
     * while the farm was holding a confirmed visit for Tuesday. Nothing on the
     * page admitted the other records existed.
     */
    public function test_every_request_can_be_seen_at_once(): void
    {
        Appointment::factory()->create(['name' => 'Still Waiting']);
        Appointment::factory()->confirmed()->create(['name' => 'Already Handled']);
        Appointment::factory()->declined()->create(['name' => 'Turned Away']);

        Livewire::actingAs($this->owner())
            ->test(Index::class)
            ->set('status', '')
            ->assertSee('Still Waiting')
            ->assertSee('Already Handled')
            ->assertSee('Turned Away');
    }

    /**
     * A visit that already happened is history, and offers nothing to decide.
     *
     * The buttons were written as "not Confirmed", "is Confirmed" and "not
     * Declined", which is correct for a pending row and wrong for a completed
     * one: it matched both negatives, so the farm was offered Confirm and
     * Decline on a visit the family had already made.
     *
     * It was unreachable until "All requests" existed, because the only way to
     * put a completed row on screen was to pick that status deliberately.
     */
    public function test_a_completed_visit_offers_nothing_to_decide(): void
    {
        Appointment::factory()->create([
            'name' => 'Came Already',
            'status' => AppointmentStatus::Completed,
        ]);

        Livewire::actingAs($this->owner())
            ->test(Index::class)
            ->set('status', AppointmentStatus::Completed->value)
            ->assertSee('Came Already')
            ->assertSee('Nothing to do')
            // The CONTROLS, not their labels. "Decline" is a substring of the
            // "Declined" option in the Showing dropdown, which is on the page
            // whatever the row offers - so asserting the word tests the filter
            // rather than the buttons.
            ->assertDontSee('wire:click="confirm(', false)
            ->assertDontSee('wire:click="markVisited(', false)
            ->assertDontSee('wire:click="decline(', false);
    }

    public function test_a_confirmed_visit_is_not_offered_confirming_again(): void
    {
        Appointment::factory()->confirmed()->create(['name' => 'Agreed Already']);

        Livewire::actingAs($this->owner())
            ->test(Index::class)
            ->set('status', AppointmentStatus::Confirmed->value)
            ->assertSee('Agreed Already')
            ->assertSee('wire:click="markVisited(', false)
            ->assertDontSee('wire:click="confirm(', false);
    }

    public function test_the_bird_is_named_when_the_request_was_about_one(): void
    {
        $bird = Broodcock::factory()->create(['name' => 'Bagwis']);
        Appointment::factory()->create(['broodcock_id' => $bird->id]);

        Livewire::actingAs($this->owner())
            ->test(Index::class)
            ->assertSee('Bagwis');
    }

    // -----------------------------------------------------------------
    // Deciding
    // -----------------------------------------------------------------

    public function test_confirming_records_who_decided_and_when(): void
    {
        $owner = $this->owner();
        $appointment = Appointment::factory()->create();

        Livewire::actingAs($owner)
            ->test(Index::class)
            ->call('confirm', $appointment->id);

        $appointment->refresh();

        $this->assertSame(AppointmentStatus::Confirmed, $appointment->status);
        $this->assertSame($owner->id, $appointment->handled_by);
        $this->assertNotNull($appointment->handled_at);
    }

    public function test_declining_records_who_decided_and_when(): void
    {
        $owner = $this->owner();
        $appointment = Appointment::factory()->create();

        Livewire::actingAs($owner)
            ->test(Index::class)
            ->call('decline', $appointment->id);

        $appointment->refresh();

        $this->assertSame(AppointmentStatus::Declined, $appointment->status);
        $this->assertSame($owner->id, $appointment->handled_by);
        $this->assertNotNull($appointment->handled_at);
    }

    public function test_an_empty_queue_says_so(): void
    {
        Livewire::actingAs($this->owner())
            ->test(Index::class)
            ->assertSee('No visit requests');
    }
}
