<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * The system's display name lives in exactly one place: config/gfms.system.
 *
 * Name drift is the kind of thing a panel opens with — a PDF footer still
 * saying the old name while the login screen says the new one. It is also
 * invisible to every other test in the suite, because nothing else asserts on
 * chrome text.
 *
 * NOTE ON SCOPE: internals are deliberately NOT renamed. config/gfms-brand.php,
 * the GFMS_* env keys, route names, table names, CSS prefixes and test
 * filenames all keep the old prefix — renaming them buys nothing visible and
 * risks the suite. So this checks what a USER can read, not what a developer
 * greps.
 */
final class SystemNameTest extends TestCase
{
    /** @return list<string> */
    private function userFacingFiles(): array
    {
        $roots = [
            __DIR__.'/../../resources/views',
            __DIR__.'/../../lang',
        ];

        $files = [];

        foreach ($roots as $root) {
            if (! is_dir($root)) {
                continue;
            }

            $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root));
            foreach ($it as $file) {
                if ($file->isFile() && preg_match('/\.(blade\.php|php)$/', $file->getFilename())) {
                    $files[] = $file->getPathname();
                }
            }
        }

        sort($files);

        return $files;
    }

    /**
     * The retired name, assembled rather than written whole.
     *
     * If the literal string sat in this file, a future grep for "is the old name
     * gone?" would keep finding this test and reporting a false positive.
     *
     * NOTE ON DIRECTION: this guard used to forbid the Broodcock title and
     * require the Gamefowl one. The thesis document keeps the Broodcock title,
     * so the application was moved back to match it rather than the other way
     * round - the paper is the thing a panel reads, and the internals
     * (config/gfms.php, the GFMS_* env keys, the repository name) were never
     * renamed, so this direction leaves everything consistent.
     */
    private function retiredName(): string
    {
        return 'Gamefowl'.' '.'Breeding'.' '.'Management';
    }

    public function test_no_user_facing_file_hardcodes_the_retired_system_name(): void
    {
        $needle = $this->retiredName();
        $offenders = [];

        foreach ($this->userFacingFiles() as $file) {
            if (str_contains(file_get_contents($file), $needle)) {
                $offenders[] = str_replace('\\', '/', $file);
            }
        }

        $this->assertSame([], $offenders,
            'The retired system name is hardcoded in user-facing files. It must come from '
            ."config('gfms.system.name'):\n".implode("\n", $offenders));
    }

    public function test_no_user_facing_file_hardcodes_the_retired_short_name(): void
    {
        $short = 'GB'.'MS';
        $offenders = [];

        foreach ($this->userFacingFiles() as $file) {
            $body = file_get_contents($file);

            // config('gfms.*') and gfms-brand lookups are internals, not display
            // text, and those keys are staying as they are on purpose.
            $body = preg_replace("/config\(['\"]gfms[^)]*\)/i", '', $body) ?? $body;
            $body = str_replace(['gfms-sidebar', 'gfms-bc-cols', 'gfms-brand', 'gfms-pulse', 'gfms-pop'], '', $body);

            if (preg_match('/\b'.$short.'\b/', $body)) {
                $offenders[] = str_replace('\\', '/', $file);
            }
        }

        $this->assertSame([], $offenders,
            "The retired short name appears as display text. Use config('gfms.system.short'):\n"
            .implode("\n", $offenders));
    }

    /** The name must actually be configured, not left on the framework default. */
    public function test_the_system_name_is_configured(): void
    {
        $config = require __DIR__.'/../../config/gfms.php';

        $this->assertArrayHasKey('system', $config);

        // Matches the thesis document exactly. If the paper is ever retitled,
        // this test is the thing that forces the application to follow.
        $this->assertSame('Digital Broodcock Farm Record Management System', $config['system']['name']);
        $this->assertSame('DBFRMS', $config['system']['short']);
        $this->assertNotSame('Laravel', $config['system']['name']);
    }
}
