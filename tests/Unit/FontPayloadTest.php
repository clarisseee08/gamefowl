<?php

declare(strict_types=1);

namespace Tests\Unit;

use Tests\TestCase;

/**
 * The font payload, guarded - because the failure mode is silent and expensive.
 *
 * @fontsource ships one CSS file per subset and NONE of them declare a
 * unicode-range. `latin-ext-400.css` and `latin-400.css` are therefore two
 * identical @font-face rules - same family, same weight, nothing to tell them
 * apart - and the later import simply wins.
 *
 * Measured on the dashboard: the browser fetched inter-latin-400 (23.1 KB) AND
 * inter-latin-ext-400 (34.2 KB), and the same again at 500. 69.4 KB on every
 * page load, a third of all font weight, to render zero characters in the
 * latin-ext range.
 *
 * Nothing reports this. The page looks perfect, the fonts render correctly, and
 * the only symptom is bandwidth - on a free-tier instance serving a farm on
 * Philippine mobile data. Re-adding one @import brings it all back.
 *
 * The latin-ext faces are still available: app.css declares them by hand WITH
 * the range, so the browser fetches them on demand if a Latin Extended-A glyph
 * is ever actually rendered. Verified in the browser: zero downloads on a normal
 * page, 34.2 KB fetched the instant one is.
 */
final class FontPayloadTest extends TestCase
{
    private function stylesheet(): string
    {
        return (string) file_get_contents(resource_path('css/app.css'));
    }

    public function test_no_subset_stylesheet_is_imported_without_a_unicode_range(): void
    {
        preg_match_all("/@import\s+'@fontsource\/([^']+)';/", $this->stylesheet(), $matches);

        $offenders = array_values(array_filter(
            $matches[1],
            static fn (string $import): bool => str_contains($import, 'latin-ext'),
        ));

        $this->assertNotEmpty($matches[1],
            'No @fontsource imports found at all, so this test is reading the wrong file.');

        $this->assertSame([], $offenders,
            "A @fontsource latin-ext stylesheet declares no unicode-range, so it overrides the\n"
            ."plain latin face of the same weight and downloads on every page load for glyphs\n"
            ."nobody is rendering. Declare the @font-face by hand with a unicode-range instead\n"
            .'- app.css already does, directly below the imports: '.implode(', ', $offenders));
    }

    /**
     * The weight ladder stops at 600, so a 700 face is bytes nothing can use.
     *
     * DesignSystemGuardTest already forbids font-bold in markup; this is the
     * other half - the face itself must not be shipped either.
     */
    public function test_no_font_weight_the_design_system_forbids_is_shipped(): void
    {
        $this->assertStringNotContainsString('latin-700.css', $this->stylesheet(),
            'A 700 face is imported, but the type ladder stops at 600 and no view uses '
            .'font-bold - so this is a font file that can never be selected.');
    }

    /** The hand-declared latin-ext faces must keep their range, or they are the bug again. */
    public function test_the_hand_declared_latin_ext_faces_carry_a_range(): void
    {
        $css = $this->stylesheet();

        preg_match_all('/@font-face\s*\{[^}]*\}/s', $css, $matches);

        $rangeless = [];

        foreach ($matches[0] as $face) {
            if (str_contains($face, 'latin-ext') && ! str_contains($face, 'unicode-range')) {
                $rangeless[] = trim(explode("\n", $face)[0]);
            }
        }

        $this->assertSame([], $rangeless,
            'A hand-declared latin-ext @font-face lost its unicode-range, which makes it '
            .'override the latin face again and download unconditionally.');
    }
}
