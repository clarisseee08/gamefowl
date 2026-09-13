<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The diagnostics page.
 *
 * Built because Render's free plan has no shell and no one-off jobs, so there
 * was no way to ask the running container anything - and every production-only
 * failure on this deployment turned out to be exactly that kind of question.
 * The nginx temp-directory permission that broke every photo upload was
 * invisible for days; it is one row on this page.
 *
 * The tests that matter here are the ones about what it must NOT do. A page
 * that reports the environment is a page that can leak the environment, and a
 * leaked Supabase key is a worse problem than the one it was built to solve.
 */
final class DiagnosticsTest extends TestCase
{
    use RefreshDatabase;

    // -----------------------------------------------------------------
    // Access
    // -----------------------------------------------------------------

    public function test_the_owner_can_open_it(): void
    {
        $this->actingAs(User::factory()->owner()->create())
            ->get(route('diagnostics'))
            ->assertOk()
            ->assertSee('Diagnostics');
    }

    /** Not staff. This reports infrastructure, which is the owner's business. */
    public function test_a_record_keeper_cannot_open_it(): void
    {
        $this->actingAs(User::factory()->staff()->create())
            ->get(route('diagnostics'))
            ->assertForbidden();
    }

    public function test_a_customer_cannot_open_it(): void
    {
        $this->actingAs(User::factory()->customer()->create())
            ->get(route('diagnostics'))
            ->assertForbidden();
    }

    /** Especially not now that parts of this site are public. */
    public function test_a_guest_cannot_open_it(): void
    {
        $this->get(route('diagnostics'))->assertRedirect(route('login'));
    }

    // -----------------------------------------------------------------
    // What it must never print
    // -----------------------------------------------------------------

    /**
     * Credentials are reported as configured/missing and never by value.
     *
     * Not even the length: a page built to diagnose a broken upload must not
     * become the reason the storage bucket has to be rekeyed.
     */
    public function test_it_never_prints_a_secret(): void
    {
        // NOT app.key - overriding it with a value that is not a real 32-byte
        // key breaks the encrypter and takes the session cookie with it. The
        // controller never reports APP_KEY in any form, which is the point.
        config([
            'filesystems.disks.supabase.key' => 'AKIAEXAMPLEKEYVALUE123',
            'filesystems.disks.supabase.secret' => 'super-secret-do-not-print-me',
            'mail.mailers.smtp.password' => 'mail-password-do-not-print-me',
        ]);

        $response = $this->actingAs(User::factory()->owner()->create())
            ->get(route('diagnostics'));

        $response->assertOk();

        foreach ([
            'AKIAEXAMPLEKEYVALUE123',
            'super-secret-do-not-print-me',
            'mail-password-do-not-print-me',
        ] as $secret) {
            $response->assertDontSee($secret);
        }

        // It still reports that they are SET - that is the useful half.
        $response->assertSee('configured');
    }

    public function test_it_reports_a_missing_credential_without_inventing_one(): void
    {
        config(['mail.mailers.smtp.host' => null]);

        $this->actingAs(User::factory()->owner()->create())
            ->get(route('diagnostics'))
            ->assertOk()
            ->assertSee('MISSING');
    }

    // -----------------------------------------------------------------
    // What it must report
    // -----------------------------------------------------------------

    /**
     * The row that would have found the upload bug in a minute rather than a
     * week: nginx spools large POST bodies to this directory, and it was not
     * writable by the user nginx's workers run as.
     */
    public function test_it_reports_the_nginx_upload_spool_directory(): void
    {
        $this->actingAs(User::factory()->owner()->create())
            ->get(route('diagnostics'))
            ->assertOk()
            ->assertSee('client_body', escape: false);
    }

    public function test_it_reports_the_extensions_uploads_depend_on(): void
    {
        $response = $this->actingAs(User::factory()->owner()->create())
            ->get(route('diagnostics'));

        // fileinfo drives MIME detection on every temporary upload; gd and
        // exif drive thumbnailing and its rotation fix.
        $response->assertSee('fileinfo')
            ->assertSee('gd')
            ->assertSee('exif');
    }

    public function test_it_reports_the_mail_transport(): void
    {
        // `log` here is the silent password-reset failure, so it is worth
        // being able to read off a page rather than infer from behaviour.
        config(['mail.default' => 'log']);

        $this->actingAs(User::factory()->owner()->create())
            ->get(route('diagnostics'))
            ->assertOk()
            ->assertSee('Mail mailer');
    }
}
