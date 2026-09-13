<?php

declare(strict_types=1);

namespace Tests\Feature\Appointments;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Broodcock;
use App\Models\User;
use Illuminate\Database\Eloquent\MassAssignmentException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * A request to come and see the birds.
 *
 * The farm's customers arrange visits by phone, which works until the person
 * who took the call is not the person who remembers it. This records the
 * request so it survives the conversation.
 *
 * NOTHING HERE NOTIFIES ANYONE. MAIL_* is unset on Render, so Laravel falls
 * back to the log mailer and an email is accepted, reported as sent, and
 * written to stderr. A confirmation nobody receives is worse than no
 * confirmation, so the owner works through the queue and rings the number the
 * visitor left.
 */
final class AppointmentRecordTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_new_request_starts_as_pending(): void
    {
        $appointment = Appointment::factory()->create();

        $this->assertSame(AppointmentStatus::Pending, $appointment->status);
        $this->assertNull($appointment->handled_by);
        $this->assertNull($appointment->handled_at);
    }

    public function test_the_preferred_date_is_a_date(): void
    {
        $appointment = Appointment::factory()->create(['preferred_date' => '2026-10-01']);

        $this->assertSame('2026-10-01', $appointment->preferred_date->toDateString());
    }

    public function test_a_request_can_name_the_bird_it_is_about(): void
    {
        $bird = Broodcock::factory()->create(['name' => 'Bagwis']);
        $appointment = Appointment::factory()->create(['broodcock_id' => $bird->id]);

        $this->assertSame('Bagwis', $appointment->broodcock->name);
    }

    /**
     * A removed bird must not take the visit request with it.
     *
     * Someone is still expecting a phone call. The bird is nulled and the
     * appointment stands, which is why the foreign key is nullOnDelete rather
     * than cascade.
     */
    public function test_removing_the_bird_leaves_the_request_standing(): void
    {
        $bird = Broodcock::factory()->create();
        $appointment = Appointment::factory()->create(['broodcock_id' => $bird->id]);

        $bird->forceDelete();

        $this->assertNotNull($appointment->fresh(), 'The visit request was deleted with the bird.');
        $this->assertNull($appointment->fresh()->broodcock_id);
    }

    /**
     * Marking one handled is assignment, not a mass update, and that is the
     * point rather than a detail.
     *
     * `status`, `handled_by` and `handled_at` are absent from $fillable so
     * that a crafted request from the public cannot arrive pre-confirmed.
     * ->update() on them therefore throws, which is correct: the console sets
     * these three properties explicitly, so there is no route by which a
     * visitor's own input could ever reach them.
     */
    public function test_a_request_can_be_marked_handled(): void
    {
        $owner = User::factory()->owner()->create();
        $appointment = Appointment::factory()->create();

        $appointment->status = AppointmentStatus::Confirmed;
        $appointment->handled_by = $owner->id;
        $appointment->handled_at = now();
        $appointment->save();

        $this->assertSame($owner->id, $appointment->fresh()->handledBy->id);
        $this->assertSame(AppointmentStatus::Confirmed, $appointment->fresh()->status);
    }

    /**
     * The other half of that rule, stated as a test rather than a comment.
     *
     * This application runs Eloquent strictly, so mass-assigning an attribute
     * outside $fillable raises rather than being quietly dropped. That is the
     * stronger of the two behaviours and worth pinning: a request that tries
     * to arrive pre-confirmed fails loudly instead of being silently sanitised
     * into one that looks legitimate.
     */
    public function test_a_visitor_cannot_mass_assign_their_way_to_a_confirmed_request(): void
    {
        $this->expectException(MassAssignmentException::class);

        Appointment::create([
            'name' => 'Someone',
            'contact_number' => '09171234567',
            'preferred_date' => now()->addWeek()->toDateString(),
            'preferred_time' => 'morning',
            'status' => AppointmentStatus::Confirmed,
        ]);
    }

    public function test_the_pending_scope_returns_only_what_needs_action(): void
    {
        Appointment::factory()->create();
        Appointment::factory()->create(['status' => AppointmentStatus::Confirmed]);

        $this->assertCount(1, Appointment::query()->pending()->get());
    }

    // -----------------------------------------------------------------
    // Who may work through the queue
    // -----------------------------------------------------------------

    public function test_the_owner_may_review_requests(): void
    {
        $this->assertTrue(User::factory()->owner()->create()->can('viewAny', Appointment::class));
    }

    public function test_staff_may_review_requests(): void
    {
        $this->assertTrue(User::factory()->staff()->create()->can('viewAny', Appointment::class));
    }

    /** A customer may ASK to visit; the queue of everyone's requests is not theirs. */
    public function test_a_customer_may_not_review_requests(): void
    {
        $this->assertFalse(User::factory()->customer()->create()->can('viewAny', Appointment::class));
    }

    /**
     * The badge vocabulary is a contract shared with the stylesheet.
     *
     * Tailwind emits nothing and warns about nothing for a class app.css does
     * not define, so a status whose badge does not exist renders as an
     * unstyled pill and nothing fails.
     */
    public function test_every_status_has_a_badge_the_stylesheet_defines(): void
    {
        $css = file_get_contents(base_path('resources/css/app.css'));

        foreach (AppointmentStatus::cases() as $status) {
            $this->assertNotSame('', $status->label());

            foreach (explode(' ', $status->badgeClasses()) as $class) {
                if (! str_starts_with($class, 'badge-')) {
                    continue;
                }

                $this->assertStringContainsString(
                    '.'.$class,
                    $css,
                    "app.css does not define .{$class}, so {$status->value} renders as an unstyled pill.",
                );
            }
        }
    }
}
