<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Providers\AppServiceProvider;
use PHPUnit\Framework\TestCase;

/**
 * migrate:fresh must be refused on a DEVELOPER's machine, not just in production.
 *
 * This is the inverse of the usual rule, and it is deliberate. There is no
 * separate production database - render.yaml and every developer's .env point
 * at one Supabase project. So the command that destroys the farm's records is
 * not a deploy; it is `php artisan migrate:fresh` typed locally to reset "my"
 * database. The original guard was gated on isProduction(), which is false
 * precisely there, so it protected the only environment that was never at risk.
 */
final class DestructiveDatabaseGuardTest extends TestCase
{
    /** The case the old isProduction() guard left wide open. */
    public function test_a_developer_machine_is_prohibited_by_default(): void
    {
        $this->assertTrue(AppServiceProvider::shouldProhibitDestructiveCommands(
            runningUnitTests: false,
            explicitlyAllowed: false,
        ));
    }

    /**
     * RefreshDatabase runs migrate:fresh against in-memory SQLite on every
     * suite run. A guard that breaks the test suite gets deleted within a day,
     * so this carve-out is what makes the rest of the rule survivable.
     */
    public function test_the_test_suite_is_never_prohibited(): void
    {
        $this->assertFalse(AppServiceProvider::shouldProhibitDestructiveCommands(
            runningUnitTests: true,
            explicitlyAllowed: false,
        ));
    }

    /** Rebuilding a database you know is disposable stays possible. */
    public function test_an_explicit_opt_in_lifts_the_prohibition(): void
    {
        $this->assertFalse(AppServiceProvider::shouldProhibitDestructiveCommands(
            runningUnitTests: false,
            explicitlyAllowed: true,
        ));
    }

    /** The opt-in has to be chosen; it is never the shipped default. */
    public function test_the_opt_in_defaults_to_off(): void
    {
        $config = require __DIR__.'/../../config/gfms.php';

        $this->assertArrayHasKey('allow_destructive_db', $config);
        $this->assertFalse(
            $config['allow_destructive_db'],
            'GFMS_ALLOW_DESTRUCTIVE_DB must default to false - a fresh clone points at the live farm database.'
        );
    }
}
