<?php

declare(strict_types=1);

namespace Tests\Unit;

use Tests\TestCase;

/**
 * A missing value is stated in words, never as a dash.
 *
 * This is one of the oldest rules in the design brief - "never a blank cell,
 * never a dash, never an error style" - written for the band tag, because a
 * bird is banded at an age rather than at hatch and "not yet banded" is an
 * honest fact rather than missing data.
 *
 * The rule was kept scrupulously in the band tag and nowhere else. Twenty-two
 * other places rendered a bare em-dash: a bloodline nobody had filled in, a
 * hatch date, a weight, a product name, who recorded a reading, a fertility
 * rate with no eggs behind it. To a keeper reading a column of them, a dash is
 * indistinguishable from a rendering bug, and it is unreadable to a screen
 * reader, which announces it as nothing at all or as "em dash".
 *
 * WHAT THIS DOES NOT BAN. An em-dash in prose is punctuation and is fine - this
 * looks only for the QUOTED STRING LITERAL, which is what a fallback value is.
 * Sentence punctuation is written as the character in the copy or as &mdash;,
 * and neither of those matches.
 *
 * THE PDF TEMPLATES ARE EXEMPT, deliberately and not by oversight.
 *
 * They are a different medium under a different engine. dompdf is CSS 2.1: no
 * flexible layout, so a printed table's columns are sized once and cannot
 * reflow. The inventory report prints twelve columns across a page, and
 * "Not recorded" thirty times down a sheet does not make it clearer, it makes
 * the table unreadable and pushes the columns that carry data off the edge.
 *
 * A printed register also carries its own context - a caption, a legend, a
 * date - in a way a single cell in a live table does not. The dash is a
 * printed-table convention there rather than a shrug.
 */
final class AbsenceIsAWordTest extends TestCase
{
    public function test_no_view_renders_a_bare_dash_for_a_missing_value(): void
    {
        $offenders = [];
        $scanned = 0;

        foreach ($this->bladeFiles() as $path) {
            $scanned++;
            $lines = file($path) ?: [];

            foreach ($lines as $number => $line) {
                if (preg_match("/'—'|\"—\"/u", $line) !== 1) {
                    continue;
                }

                $offenders[] = sprintf('%s:%d  %s',
                    str_replace(base_path().DIRECTORY_SEPARATOR, '', $path),
                    $number + 1,
                    trim($line));
            }
        }

        // A guard that stops finding files would pass forever.
        $this->assertGreaterThan(40, $scanned,
            'This test scanned almost no Blade files, so it is broken rather than satisfied.');

        $this->assertSame([], $offenders,
            "A dash is not a value. Say what is actually absent - 'Not recorded' for a\n"
            ."field nobody filled in, 'No data' for a rate with nothing to compute it\n"
            ."from. Both are already this application's own words:\n  "
            .implode("\n  ", $offenders));
    }

    /** @return list<string> */
    private function bladeFiles(): array
    {
        $found = [];
        $directory = new \RecursiveDirectoryIterator(resource_path('views'));

        /** @var \SplFileInfo $file */
        $print = resource_path('views'.DIRECTORY_SEPARATOR.'reports'.DIRECTORY_SEPARATOR.'pdf');

        foreach (new \RecursiveIteratorIterator($directory) as $file) {
            if (! $file->isFile() || ! str_ends_with($file->getFilename(), '.blade.php')) {
                continue;
            }

            if (str_starts_with($file->getPathname(), $print)) {
                continue;   // see the note on the class - print is a different medium
            }

            $found[] = $file->getPathname();
        }

        return $found;
    }
}
