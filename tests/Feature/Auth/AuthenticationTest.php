<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Sign-in behaviour. This is the cheapest evidence for the ISO 25010 security
 * characteristic, so it is covered explicitly rather than assumed.
 */
final class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_login_screen_can_be_rendered(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSee('Sign in to your account');
    }

    public function test_a_user_can_sign_in_with_correct_credentials(): void
    {
        $user = User::factory()->staff()->create();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect('/dashboard');

        $this->assertAuthenticatedAs($user);
    }

    public function test_a_user_cannot_sign_in_with_a_wrong_password(): void
    {
        $user = User::factory()->staff()->create();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'not-the-password',
        ])->assertSessionHasErrors();

        $this->assertGuest();
    }

    /**
     * A deactivated account must be refused even though the password is
     * correct - otherwise "deactivate" is only cosmetic.
     */
    public function test_a_deactivated_user_cannot_sign_in(): void
    {
        $user = User::factory()->staff()->inactive()->create();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    /** A user deactivated mid-session is signed out on their next request. */
    public function test_a_user_deactivated_mid_session_is_signed_out(): void
    {
        $user = User::factory()->staff()->create();

        $this->actingAs($user)->get('/dashboard')->assertOk();

        $user->update(['is_active' => false]);

        $this->actingAs($user)->get('/dashboard')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_a_user_can_sign_out(): void
    {
        $user = User::factory()->owner()->create();

        $this->actingAs($user)->post('/logout')->assertRedirect();

        $this->assertGuest();
    }

    /**
     * Public self-registration is disabled - accounts are created by an owner.
     * If this test starts failing, someone re-enabled Features::registration().
     */
    public function test_public_registration_is_disabled(): void
    {
        $this->get('/register')->assertNotFound();
        $this->post('/register', [
            'full_name' => 'Intruder',
            'email' => 'intruder@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertNotFound();

        $this->assertDatabaseMissing('users', ['email' => 'intruder@example.com']);
    }

    public function test_sign_in_attempts_are_rate_limited(): void
    {
        $user = User::factory()->staff()->create();

        // The limiter allows 5 attempts per minute per email+IP.
        foreach (range(1, 5) as $ignored) {
            $this->post('/login', [
                'email' => $user->email,
                'password' => 'wrong-password',
            ]);
        }

        // The 6th attempt is rejected by Fortify's throttle middleware, which
        // aborts with 429 rather than returning a redirect with a session error.
        $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ])->assertStatus(429);

        $this->assertGuest();
    }

    // -----------------------------------------------------------------
    // Password reset
    //
    // Features::resetPasswords() is enabled, so every user is offered this
    // flow. It went untested, and in production it silently did nothing:
    // render.yaml set no MAIL_* keys, so Laravel fell back to its default
    // MAIL_MAILER of `log` and wrote every reset link to the container's
    // stderr while telling the user it had been emailed.
    //
    // These assert the FLOW, which is what the application controls. Whether
    // the transport actually delivers is a deployment concern, now configured
    // in render.yaml.
    // -----------------------------------------------------------------

    public function test_the_forgot_password_screen_can_be_rendered(): void
    {
        $this->get(route('password.request'))->assertOk();
    }

    public function test_requesting_a_reset_sends_a_link_to_a_known_address(): void
    {
        Notification::fake();

        $user = User::factory()->create(['email' => 'keeper@ssguad.test']);

        $this->post(route('password.email'), ['email' => 'keeper@ssguad.test'])
            ->assertSessionHasNoErrors();

        Notification::assertSentTo($user, ResetPassword::class);
    }

    /**
     * An unknown address must not reveal that it is unknown.
     *
     * Laravel returns the same confirmation either way; asserting it stops a
     * future "helpful" error message turning this screen into a way to check
     * whether a given person has an account on the farm's system.
     */
    public function test_requesting_a_reset_for_an_unknown_address_sends_nothing(): void
    {
        Notification::fake();

        $this->post(route('password.email'), ['email' => 'nobody@ssguad.test']);

        Notification::assertNothingSent();
    }
}
