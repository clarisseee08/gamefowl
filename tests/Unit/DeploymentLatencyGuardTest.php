<?php

declare(strict_types=1);

namespace Tests\Unit;

use PDO;
use Tests\TestCase;

/**
 * Deployment settings that exist only because the database is far away.
 *
 * MEASURED AGAINST THE LIVE SERVICE. The web service runs in Render's
 * Singapore region and the database is a Supabase project in ap-northeast-1,
 * and the gap between them dominates every page:
 *
 *   static asset (nginx only) ......    88ms
 *   /up (PHP boot, no database) ....    82ms   <- PHP itself is not slow
 *   /catalog (PHP + four queries) .. 2,260ms
 *
 *   opening the connection ......... ~700ms, per request
 *   any single query ............... ~240ms, whatever it asks for
 *
 * So the settings below are not tuning. Each one removes round trips that the
 * application would otherwise pay on every request, and a well-meaning edit
 * that reverts one costs roughly a second a page with no visible error. That
 * is what these tests are here to catch.
 */
final class DeploymentLatencyGuardTest extends TestCase
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

    /**
     * A database session driver reads and writes the session on every single
     * request, so at ~240ms a round trip it taxes every authenticated page
     * before any of the application's own work begins.
     */
    public function test_the_deployed_session_driver_does_not_use_the_database(): void
    {
        $this->assertNotSame(
            'database',
            $this->renderEnv('SESSION_DRIVER'),
            'Sessions in the database cost a Tokyo round trip on every request.',
        );
    }

    /**
     * php-fpm discards the PDO handle at the end of each request, so without
     * this every page re-pays the ~700ms connection handshake.
     */
    public function test_the_deployment_asks_for_connections_to_be_reused(): void
    {
        $this->assertSame(
            'true',
            $this->renderEnv('DB_PERSISTENT'),
            'DB_PERSISTENT=true is what stops every request re-opening the connection.',
        );
    }

    /**
     * The blueprint setting above does nothing unless the connection actually
     * reads it, which is the half of this that is easy to delete by accident.
     */
    public function test_the_pgsql_connection_can_be_told_to_reuse_connections(): void
    {
        $this->assertArrayHasKey(
            PDO::ATTR_PERSISTENT,
            config('database.connections.pgsql.options'),
            'config/database.php must expose PDO::ATTR_PERSISTENT for DB_PERSISTENT to mean anything.',
        );
    }

    /** Off unless asked for, so a developer's machine is unaffected. */
    public function test_connection_reuse_is_off_by_default(): void
    {
        $this->assertFalse(
            config('database.connections.pgsql.options')[PDO::ATTR_PERSISTENT],
            'Persistent connections should be opt-in via DB_PERSISTENT.',
        );
    }
}
