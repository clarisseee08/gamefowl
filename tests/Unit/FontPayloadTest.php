<?php

declare(strict_types=1);

namespace Tests\Unit;

use Tests\TestCase;

/**
 * The font setup, guarded - because the failure modes are silent.
 *
 * The application uses exactly two faces, Lexend for display and Public Sans
 * for text, loaded from Google Fonts with preconnect in the shared head
 * partial. That replaced a self-hosted @fontsource stack (Poppins, Inter,
 * JetBrains Mono) whose guard this file used to be.
 *
 * Three things go wrong quietly and nothing else reports them:
 *
 *   - an old @import or @font-face left behind keeps downloading a face no
 *     element is set in, which costs bandwidth on Philippine mobile data and
 *     shows nowhere on screen;
 *   - a weight the stylesheet asks for but the link does not load gets
 *     FAKE-BOLDED by the browser, smeared from the nearest real weight;
 *   - dropping the preconnect costs a full DNS-and-TLS round trip before the
 *     first heading can render in its own face.
 */
final class FontPayloadTest extends TestCase
{
    private function stylesheet(): string
    {
        return (string) file_get_contents(resource_path('css/app.css'));
    }

    private function headPartial(): string
    {
        return (string) file_get_contents(resource_path('views/components/partials/head-meta.blade.php'));
    }

    public function test_no_retired_font_is_still_imported_or_declared(): void
    {
        $css = $this->stylesheet();

        $this->assertDoesNotMatchRegularExpression("/@import\\s+'@fontsource/", $css,
            'An @fontsource import survives. The faces are loaded from Google Fonts now; '
            .'a self-hosted import downloads a face nothing is set in.');

        $this->assertStringNotContainsString('@font-face', $css,
            'A hand-declared @font-face survives from the old self-hosted stack.');
    }

    public function test_the_two_faces_are_loaded_with_preconnect(): void
    {
        $head = $this->headPartial();

        $this->assertStringContainsString('rel="preconnect" href="https://fonts.googleapis.com"', $head);
        $this->assertStringContainsString('rel="preconnect" href="https://fonts.gstatic.com" crossorigin', $head);
        $this->assertStringContainsString('family=Lexend:', $head);
        $this->assertStringContainsString('family=Public+Sans:', $head);
    }

    /**
     * Only 400-700 are loaded, so nothing may ask for 800 or 900: the browser
     * would synthesise it by smearing the 700, which is visibly worse.
     */
    public function test_no_weight_heavier_than_the_loaded_ones_is_requested(): void
    {
        $this->assertDoesNotMatchRegularExpression('/wght@[^"&]*\b[89]00\b/', $this->headPartial(),
            'The font link requests an 800 or 900 weight the design system does not use.');

        $this->assertDoesNotMatchRegularExpression('/font-weight:\s*[89]00\b|font-(?:extrabold|black)\b/', $this->stylesheet(),
            'The stylesheet asks for 800/900, which is not loaded and will be fake-bolded.');
    }
}
