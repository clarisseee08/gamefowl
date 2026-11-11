<?php

declare(strict_types=1);

namespace Tests\Unit;

use Tests\TestCase;

/**
 * The farm's site is https://gamefowl.onrender.com.
 *
 * The Render service is still NAMED gfms-laravel, and gfms-laravel.onrender.com
 * is an older, separate service that answers too. Until 12 November 2026 the
 * blueprint and the keep-warm workflow both named that older host, so the
 * workflow kept the wrong site awake and the blueprint's APP_URL pointed
 * password-reset links at it. Neither fails loudly, which is why these pin it.
 */
final class DeploymentHostTest extends TestCase
{
    private const PRODUCTION = 'https://gamefowl.onrender.com';

    private function read(string $relativePath): string
    {
        $contents = file_get_contents(base_path($relativePath));

        if ($contents === false) {
            $this->fail("{$relativePath} could not be read.");
        }

        return $contents;
    }

    /** Read one `- key:` / `value:` pair out of the Render blueprint. */
    private function renderEnv(string $key): ?string
    {
        preg_match('/-\s*key:\s*'.preg_quote($key, '/').'\s*\n\s*value:\s*"?([^"\n]+)"?/', $this->read('render.yaml'), $matches);

        return isset($matches[1]) ? trim($matches[1]) : null;
    }

    public function test_the_blueprint_builds_links_to_the_production_host(): void
    {
        $this->assertSame(self::PRODUCTION, $this->renderEnv('APP_URL'));
        $this->assertSame(self::PRODUCTION, $this->renderEnv('ASSET_URL'));
    }

    public function test_the_keep_warm_workflow_pings_the_production_host(): void
    {
        $workflow = $this->read('.github/workflows/keep-render-warm.yml');

        $this->assertMatchesRegularExpression('/^\s*URL:\s*'.preg_quote(self::PRODUCTION.'/up', '/').'\s*$/m', $workflow);
    }

    public function test_nothing_deployable_points_at_the_old_service(): void
    {
        foreach (['render.yaml', '.github/workflows/keep-render-warm.yml'] as $file) {
            $settings = preg_replace('/^\s*#.*$/m', '', $this->read($file));

            $this->assertStringNotContainsString(
                'gfms-laravel.onrender.com',
                (string) $settings,
                "{$file} still points a setting at the old gfms-laravel.onrender.com service.",
            );
        }
    }

    /**
     * The boot script caches config, routes, views AND events. Running the
     * caches one by one used to leave events out, so every request re-scanned
     * app/Listeners.
     */
    public function test_the_boot_script_warms_every_cache(): void
    {
        $this->assertMatchesRegularExpression('/^php artisan optimize\s*$/m', $this->read('docker/entrypoint.sh'));
    }
}
