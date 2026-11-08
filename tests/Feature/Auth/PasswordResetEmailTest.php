<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * The password-reset email, in the farm's own voice and colours.
 *
 * It used to be Laravel's stock notification: "Reset Password Notification",
 * "Hello!", and a signature reading APP_NAME - "Digital Broodcock Farm Record
 * Management System" in production - which is the software's name, not the
 * farm's. Someone on the farm opening it had no way to tell it came from
 * SSGuad. It now comes from the farm by name (config('gfms.farm.name'), which
 * the owner edits at /settings) and says plainly what to do.
 *
 * Email clients strip stylesheets, so the HTML carries inline hex from
 * config/gfms-brand.php - the same constraint, and the same source, as the
 * PDF reports.
 */
final class PasswordResetEmailTest extends TestCase
{
    use RefreshDatabase;

    private function mailFor(User $user, string $token = 'test-token-123'): MailMessage
    {
        return (new ResetPassword($token))->toMail($user);
    }

    public function test_the_email_comes_from_the_farm_by_name(): void
    {
        config(['gfms.farm.name' => 'SSGuad Game Farm', 'app.name' => 'Digital Broodcock Farm Record Management System']);

        $mail = $this->mailFor(User::factory()->create());
        $html = (string) $mail->render();

        $this->assertSame('Reset your password · SSGuad Game Farm', $mail->subject);
        $this->assertStringContainsString('SSGuad Game Farm', $html);
        $this->assertStringNotContainsString('Digital Broodcock', $html);
        $this->assertStringNotContainsString('Hello!', $html);
    }

    public function test_the_farm_name_follows_the_farm_settings(): void
    {
        config(['gfms.farm.name' => 'Bukid ni Mang Juan']);

        $mail = $this->mailFor(User::factory()->create());

        $this->assertSame('Reset your password · Bukid ni Mang Juan', $mail->subject);
        $this->assertStringContainsString('Bukid ni Mang Juan', (string) $mail->render());
    }

    public function test_the_email_says_what_to_do_in_plain_words(): void
    {
        $user = User::factory()->create(['email' => 'keeper@ssguad.test']);
        $html = (string) $this->mailFor($user)->render();

        $this->assertStringContainsString('Reset your password', $html);
        $this->assertStringContainsString('keeper@ssguad.test', $html);
        $this->assertStringContainsString('Choose a new password', $html);
        $this->assertStringContainsString('Didn’t ask for this?', $html);
    }

    public function test_the_link_opens_the_reset_screen_for_that_account(): void
    {
        $user = User::factory()->create(['email' => 'keeper@ssguad.test']);
        $html = (string) $this->mailFor($user, 'abc123token')->render();

        $url = route('password.reset', ['token' => 'abc123token', 'email' => 'keeper@ssguad.test']);

        $this->assertStringContainsString(e($url), $html);

        $this->get($url)
            ->assertOk()
            ->assertSee('Choose a new password')
            ->assertSee('keeper@ssguad.test');
    }

    public function test_the_email_states_how_long_the_link_lasts_from_config(): void
    {
        config(['auth.passwords.users.expire' => 45]);

        $html = (string) $this->mailFor(User::factory()->create())->render();

        $this->assertStringContainsString('45 minutes', $html);
    }

    public function test_the_farm_address_appears_only_when_the_farm_has_one(): void
    {
        config(['gfms.farm.address' => 'Brgy. Igcadlum, San Joaquin, Iloilo']);
        $this->assertStringContainsString('Brgy. Igcadlum, San Joaquin, Iloilo', (string) $this->mailFor(User::factory()->create())->render());

        config(['gfms.farm.address' => '']);
        $this->assertStringNotContainsString('Brgy.', (string) $this->mailFor(User::factory()->create())->render());
    }

    public function test_there_is_a_plain_text_version_with_the_same_link(): void
    {
        $user = User::factory()->create(['email' => 'keeper@ssguad.test']);
        $mail = $this->mailFor($user, 'abc123token');

        $this->assertIsArray($mail->view);
        $this->assertArrayHasKey('text', $mail->view);

        $text = view($mail->view['text'], $mail->viewData)->render();

        $this->assertStringContainsString(route('password.reset', ['token' => 'abc123token', 'email' => 'keeper@ssguad.test']), $text);
        $this->assertStringContainsString('Reset your password', $text);
        $this->assertStringNotContainsString('<', $text);
    }

    /**
     * Password-reset poisoning. trustProxies(at: '*') means a request's
     * X-Forwarded-Host decides what url() thinks the site is called. Built
     * from the request, the link in someone ELSE's reset email would follow
     * whatever host the person asking for it chose - a genuine farm email
     * whose button carries a real token to another site. Outside local
     * development the link is built from APP_URL alone.
     *
     * This sends a real request and reads the email actually produced: a faked
     * notification never builds the link, so it could not catch this.
     */
    public function test_a_forged_forwarded_host_cannot_change_the_link(): void
    {
        config(['app.url' => 'https://gamefowl.onrender.com', 'mail.default' => 'array']);
        User::factory()->create(['email' => 'keeper@ssguad.test']);

        $this->withHeaders(['X-Forwarded-Host' => 'attacker.example', 'X-Forwarded-Proto' => 'https'])
            ->post(route('password.email'), ['email' => 'keeper@ssguad.test'])
            ->assertSessionHasNoErrors();

        $sent = app('mailer')->getSymfonyTransport()->messages();
        $this->assertCount(1, $sent);

        $email = $sent->first()->getOriginalMessage();

        foreach ([$email->getHtmlBody(), $email->getTextBody()] as $body) {
            $this->assertStringContainsString('https://gamefowl.onrender.com/reset-password/', $body);
            $this->assertStringNotContainsString('attacker.example', $body);
        }
    }

    /** render.yaml asks for https://, but a bare host must still give a working link. */
    public function test_an_app_url_without_a_scheme_still_gives_an_https_link(): void
    {
        config(['app.url' => 'gamefowl.onrender.com']);

        $html = (string) $this->mailFor(User::factory()->create(['email' => 'keeper@ssguad.test']), 'abc123token')->render();

        $this->assertStringContainsString('https://gamefowl.onrender.com/reset-password/abc123token?email=keeper%40ssguad.test', $html);
    }

    public function test_requesting_a_reset_sends_the_farm_email(): void
    {
        Notification::fake();
        config(['gfms.farm.name' => 'SSGuad Game Farm']);

        $user = User::factory()->create(['email' => 'keeper@ssguad.test']);

        $this->post(route('password.email'), ['email' => 'keeper@ssguad.test'])->assertSessionHasNoErrors();

        Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use ($user): bool {
            return $notification->toMail($user)->subject === 'Reset your password · SSGuad Game Farm';
        });
    }
}
