<?php

declare(strict_types=1);

namespace Tests\Unit;

use Tests\TestCase;

/**
 * Body text on a tinted status ground is foreground ink, not the variant ink.
 *
 * WHY. Measured against config/gfms-brand.php:
 *
 *   success     #1F7A4D on #E8F4EE    4.71:1
 *   warning     #755006 on #F1E5A1    5.66:1
 *   destructive #D32F2F on #FEF5F4    4.64:1
 *   foreground  #161C19 on those      13.56 - 16.13:1
 *
 * Three of the four variant pairs are under the Console 7:1 floor when the
 * variant ink is used as the text ON its own ground. These screens are read
 * outdoors in Philippine daylight, which is the whole reason that floor exists.
 *
 * x-alert already solved this and says so in its own header - the tint is the
 * only colour, only the glyph carries the ink, the text stays foreground.
 * Fourteen hand-rolled panels re-introduced the exact failure the component was
 * written to prevent, which is what happens when a solved problem lives in a
 * component nobody is obliged to use.
 *
 * WHAT IS DELIBERATELY NOT COVERED.
 *
 * BADGES. `.badge-warn` really is `bg-warning-bg text-warning`, and that is a
 * decision rather than an oversight: BrandTokensAreMirroredTest holds the
 * semantic PAIRS to 4.5:1 and holds body text to 7:1. A badge is a short label
 * on a pill, priced at the pair's bar; a paragraph is not. So an element
 * carrying `badge` is exempt, and the pairs are measured by that other test.
 *
 * HOVER STATES. A danger button that tints its own ground on hover is an
 * affordance rather than a passage of text, and its resting state is what
 * anyone actually reads.
 *
 * WHAT IT CANNOT SEE, stated so nobody mistakes a pass for proof. This reads
 * one class attribute at a time, so it catches the ground and the ink only when
 * they sit on the SAME element. Seven real failures were split across a parent
 * and its child - the tinted panel, and a <ul> or <p> inside it carrying the
 * variant ink - and contrast does not care which element holds which class.
 * Those were found by hand and fixed; catching them automatically would mean
 * rendering each page and walking computed styles, which is a browser's job
 * rather than a unit test's.
 */
final class TintedPanelsKeepTheConsoleFloorTest extends TestCase
{
    private const VARIANTS = ['success', 'warning', 'destructive', 'info'];

    public function test_no_panel_sets_variant_text_on_its_own_variant_ground(): void
    {
        $offenders = [];
        $scanned = 0;

        foreach ($this->bladeFiles() as $path) {
            $scanned++;
            $lines = file($path) ?: [];

            foreach ($lines as $number => $line) {
                foreach ($this->offendingClassAttributes($line) as $attribute) {
                    $offenders[] = sprintf('%s:%d  %s',
                        str_replace(base_path().DIRECTORY_SEPARATOR, '', $path),
                        $number + 1,
                        $attribute);
                }
            }
        }

        $this->assertGreaterThan(40, $scanned,
            'This test scanned almost no Blade files, so it is broken rather than satisfied.');

        $this->assertSame([], $offenders,
            "Variant ink on its own tinted ground is under the Console 7:1 floor.\n"
            ."Keep the tint and set the text to text-foreground, which measures 13.5:1\n"
            ."or better on all four grounds - or use <x-alert>, which already does:\n  "
            .implode("\n  ", $offenders));
    }

    /**
     * Class attributes on this line that pair a variant ground with its own ink.
     *
     * @return list<string>
     */
    private function offendingClassAttributes(string $line): array
    {
        if (preg_match_all('/class="([^"]*)"/', $line, $matches) === 0) {
            return [];
        }

        $found = [];

        foreach ($matches[1] as $classes) {
            // A badge is priced at the semantic pair's own 4.5:1 bar - see the
            // note on this class.
            if (preg_match('/\bbadge\b/', $classes) === 1) {
                continue;
            }

            foreach (self::VARIANTS as $variant) {
                $ground = preg_match('/(?<!:)\bbg-'.$variant.'-bg\b/', $classes) === 1;
                $ink = preg_match('/(?<!:)\btext-'.$variant.'\b/', $classes) === 1;

                if ($ground && $ink) {
                    $found[] = trim($classes);

                    break;
                }
            }
        }

        return $found;
    }

    /** @return list<string> */
    private function bladeFiles(): array
    {
        $found = [];
        $print = resource_path('views'.DIRECTORY_SEPARATOR.'reports'.DIRECTORY_SEPARATOR.'pdf');
        $directory = new \RecursiveDirectoryIterator(resource_path('views'));

        /** @var \SplFileInfo $file */
        foreach (new \RecursiveIteratorIterator($directory) as $file) {
            if (! $file->isFile() || ! str_ends_with($file->getFilename(), '.blade.php')) {
                continue;
            }

            // dompdf renders its own palette from plain hex and never sees these
            // utility classes at all.
            if (str_starts_with($file->getPathname(), $print)) {
                continue;
            }

            $found[] = $file->getPathname();
        }

        return $found;
    }
}
