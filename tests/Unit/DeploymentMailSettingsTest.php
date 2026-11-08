<?php

declare(strict_types=1);

namespace Tests\Unit;

use Tests\TestCase;

/**
 * The deployed mail settings, which fail without an error you can see.
 *
 * Render's free plan blocks outbound SMTP on ports 25, 465 and 587. A blocked
 * port does not refuse; the connection hangs until the 60-second timeout and
 * then the forgot-password page fails with a 500. Since 8 November 2026 the
 * farm's mail goes through Brevo on 2525, an alternative SMTP port Brevo
 * offers and Render does not block, and the first real reset email arrived
 * that way. A blueprint sync that put 587 back would quietly end password
 * resets, which is what this pins.
 */
final class DeploymentMailSettingsTest extends TestCase
{
    /** Read one `- key:` / `value:` pair out of the Render blueprint. */
    private function renderEnv(string $key): ?string
    {
        $blueprint = file_get_contents(base_path('render.yaml'));

        if ($blueprint === false) {
            $this->fail('render.yaml could not be read.');
        }

        preg_match('/-\s*key:\s*'.preg_quote($key, '/').'\s*\n\s*value:\s*"?([^"\n]+)"?/', $blueprint, $matches);

        return isset($matches[1]) ? trim($matches[1]) : null;
    }

    public function test_the_deployment_sends_mail_on_a_port_render_does_not_block(): void
    {
        $port = $this->renderEnv('MAIL_PORT');

        $this->assertSame('2525', $port, 'MAIL_PORT must be 2525: Render\'s free plan blocks 25, 465 and 587.');
    }

    /** 2525 speaks plain SMTP upgraded with STARTTLS, like 587 - not implicit TLS. */
    public function test_the_mail_scheme_matches_the_port(): void
    {
        $this->assertSame('smtp', $this->renderEnv('MAIL_SCHEME'));
    }

    public function test_the_deployment_sends_through_smtp(): void
    {
        $this->assertSame('smtp', $this->renderEnv('MAIL_MAILER'));
    }
}
