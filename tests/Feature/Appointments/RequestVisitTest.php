<?php

declare(strict_types=1);

namespace Tests\Feature\Appointments;

use App\Enums\AppointmentStatus;
use App\Livewire\Appointments\RequestForm;
use App\Models\Appointment;
use App\Models\Broodcock;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Asking to visit the farm.
 *
 * THE FIRST THING IN THIS SYSTEM AN ANONYMOUS STRANGER CAN WRITE. Everything
 * else is behind auth and a Policy, so the protections tested here - a rate
 * limit and a honeypot - exist nowhere else in the application and are easy to
 * remove by accident while tidying.
 *
 * A CAPTCHA is not among them, deliberately. The Content-Security-Policy in
 * docker/nginx.conf is partial because Livewire ships Alpine and Alpine needs
 * unsafe-eval; adding a third-party script widget to a page otherwise free of
 * them is the wrong trade for a farm's contact form. A honeypot costs no
 * script at all.
 */
final class RequestVisitTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // The limiter is process state, not database state, so RefreshDatabase
        // does not reset it and one test's attempts would count against the next.
        RateLimiter::clear('appointment:127.0.0.1');
    }

    /** @return array<string, mixed> */
    private function validRequest(): array
    {
        return [
            'name' => 'Marites Santos',
            'contact_number' => '0917 123 4567',
            'email' => 'marites@example.test',
            'preferred_date' => now()->addWeek()->toDateString(),
            'preferred_time' => 'morning',
            'party_size' => 2,
            'message' => 'Interested in the Sweater cocks.',
        ];
    }

    private function form(array $overrides = []): Testable
    {
        $component = Livewire::test(RequestForm::class);

        foreach (array_merge($this->validRequest(), $overrides) as $field => $value) {
            $component->set($field, $value);
        }

        return $component;
    }

    public function test_a_visitor_can_ask_to_come_and_see_the_birds(): void
    {
        $this->form()->call('submit')->assertHasNoErrors();

        $appointment = Appointment::query()->first();

        $this->assertNotNull($appointment);
        $this->assertSame('Marites Santos', $appointment->name);
        $this->assertSame('0917 123 4567', $appointment->contact_number);
        $this->assertSame(2, $appointment->party_size);
        $this->assertSame(AppointmentStatus::Pending, $appointment->status);
    }

    public function test_the_visitor_is_told_the_farm_will_ring_them(): void
    {
        $this->form()->call('submit')
            ->assertHasNoErrors()
            // Never "we have emailed you" - the farm cannot send email.
            ->assertSee('ring');
    }

    public function test_a_request_can_name_the_bird_it_is_about(): void
    {
        $bird = Broodcock::factory()->create(['name' => 'Bagwis']);

        $this->form(['broodcock_id' => (string) $bird->id])->call('submit')->assertHasNoErrors();

        $this->assertSame($bird->id, Appointment::query()->first()->broodcock_id);
    }

    // -----------------------------------------------------------------
    // What is refused
    // -----------------------------------------------------------------

    public function test_a_visit_cannot_be_asked_for_in_the_past(): void
    {
        $this->form(['preferred_date' => now()->subDay()->toDateString()])
            ->call('submit')
            ->assertHasErrors('preferred_date');

        $this->assertSame(0, Appointment::query()->count());
    }

    public function test_a_request_needs_a_name(): void
    {
        $this->form(['name' => ''])->call('submit')->assertHasErrors('name');
        $this->assertSame(0, Appointment::query()->count());
    }

    /** The phone number is the entire reply channel, so it is not optional. */
    public function test_a_request_needs_a_contact_number(): void
    {
        $this->form(['contact_number' => ''])->call('submit')->assertHasErrors('contact_number');
        $this->assertSame(0, Appointment::query()->count());
    }

    public function test_an_email_address_is_optional(): void
    {
        $this->form(['email' => ''])->call('submit')->assertHasNoErrors();
        $this->assertSame(1, Appointment::query()->count());
    }

    public function test_a_party_of_nobody_is_refused(): void
    {
        $this->form(['party_size' => 0])->call('submit')->assertHasErrors('party_size');
    }

    public function test_an_implausible_party_is_refused(): void
    {
        $this->form(['party_size' => 51])->call('submit')->assertHasErrors('party_size');
    }

    // -----------------------------------------------------------------
    // The protections that exist nowhere else in this application
    // -----------------------------------------------------------------

    /**
     * A caught bot is thanked, not corrected.
     *
     * Telling it that it failed is how it learns to fill the form properly
     * next time, so the response is indistinguishable from a real success and
     * nothing is written.
     */
    public function test_a_filled_honeypot_writes_nothing_and_says_nothing(): void
    {
        $this->form(['website' => 'http://spam.example'])
            ->call('submit')
            ->assertHasNoErrors()
            ->assertSee('ring');

        $this->assertSame(0, Appointment::query()->count(), 'A honeypot submission was stored.');
    }

    public function test_the_sixth_request_in_an_hour_is_refused(): void
    {
        for ($i = 1; $i <= 5; $i++) {
            $this->form(['name' => "Visitor {$i}"])->call('submit')->assertHasNoErrors();
        }

        $this->form(['name' => 'Visitor 6'])->call('submit')->assertHasErrors();

        $this->assertSame(5, Appointment::query()->count(), 'The rate limit did not hold.');
    }

    public function test_the_refusal_says_how_long_to_wait(): void
    {
        for ($i = 1; $i <= 5; $i++) {
            $this->form(['name' => "Visitor {$i}"])->call('submit');
        }

        $this->form(['name' => 'Visitor 6'])
            ->call('submit')
            ->assertSee('minute');
    }
}
