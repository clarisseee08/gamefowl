<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Every image the views reference must exist AND be in the repository.
 *
 * This exists because of a real failure: `.gitignore` contained a blanket
 * `*.png`, added to keep design screenshots out of the repo, which silently
 * excluded every brand image under public/images/. The developer's machine was
 * fine - the files were there - so nothing looked wrong locally. A fresh clone
 * rendered with no logo anywhere, no favicon, and no error message explaining
 * why, because a missing image is a 404 the page does not report.
 *
 * "It works on my machine" is exactly the class of bug a test can catch and a
 * human cannot, so this checks both halves: the file is on disk, and git is
 * willing to track it.
 */
final class PublicAssetsAreCommittedTest extends TestCase
{
    private function root(string $path = ''): string
    {
        return __DIR__.'/../../'.$path;
    }

    /**
     * Asset paths referenced from Blade, e.g. asset('images/brand/logo-64.png').
     *
     * @return list<string>
     */
    private function referencedAssets(): array
    {
        $found = [];
        $base = $this->root('resources/views');

        $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($base));

        foreach ($it as $file) {
            if (! $file->isFile() || ! str_ends_with($file->getFilename(), '.blade.php')) {
                continue;
            }

            $body = file_get_contents($file->getPathname());

            // asset('...') and asset("...") with a file extension.
            if (preg_match_all('/asset\(\s*[\'"]([^\'"]+\.[a-z0-9]{2,4})[\'"]/i', $body, $m)) {
                foreach ($m[1] as $path) {
                    // Skip interpolated paths - "images/brand/logo-{$file}.png"
                    // is a template, not a filename. The sizes it can resolve to
                    // are listed explicitly below.
                    if (str_contains($path, '{') || str_contains($path, '$')) {
                        continue;
                    }

                    $found[] = $path;
                }
            }
        }

        // Interpolated paths (logo-{$file}.png) cannot be read statically, so the
        // generated set is listed explicitly rather than left uncovered.
        foreach ([32, 64, 128, 192, 512] as $size) {
            $found[] = "images/brand/logo-{$size}.png";
        }

        return array_values(array_unique($found));
    }

    public function test_every_referenced_public_asset_exists_on_disk(): void
    {
        $missing = [];

        foreach ($this->referencedAssets() as $path) {
            if (! is_file($this->root('public/'.$path))) {
                $missing[] = $path;
            }
        }

        $this->assertSame([], $missing,
            'Views reference files that are not in public/. A missing image is a silent '
            ."404 - the page renders with a blank space and reports nothing:\n"
            .implode("\n", $missing));
    }

    public function test_every_referenced_public_asset_is_tracked_by_git(): void
    {
        if (! is_dir($this->root('.git'))) {
            $this->markTestSkipped('Not a git working copy.');
        }

        $ignored = [];

        foreach ($this->referencedAssets() as $path) {
            $full = $this->root('public/'.$path);

            if (! is_file($full)) {
                continue;   // covered by the test above
            }

            /*
             * --no-index evaluates the ignore RULES alone, rather than reporting
             * "not ignored" for anything already tracked.
             *
             * Without it this test cannot fail once someone has force-added the
             * files, which is precisely the fragile state worth catching: the
             * asset is in the repo only because it was forced past a rule that
             * still says exclude it, and the next person to add a sibling file
             * silently loses it again.
             *
             * `git check-ignore` exits 0 when the path IS ignored.
             */
            $escaped = escapeshellarg($full);
            exec('git -C '.escapeshellarg($this->root())." check-ignore --no-index {$escaped} 2>&1", $out, $code);

            if ($code === 0) {
                $ignored[] = $path;
            }
        }

        $this->assertSame([], $ignored,
            'These assets exist locally but are EXCLUDED from the repository, so a fresh '
            ."clone will not have them and every page referencing them renders broken:\n"
            .implode("\n", $ignored));
    }
}
