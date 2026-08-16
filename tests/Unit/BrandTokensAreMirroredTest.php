<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\BandTag;
use PHPUnit\Framework\TestCase;

/**
 * The application and its PDF reports cannot share a stylesheet: dompdf is a
 * CSS 2.1 engine and cannot parse a custom property, oklch(), flexbox or grid.
 * So colour lives in config/gfms-brand.php, the PDF templates read it directly,
 * and resources/css/app.css mirrors the same values by hand.
 *
 * Hand-mirroring silently drifts. These tests are what stop it, and they are
 * the defensible answer to "how do you keep the reports looking like the app?".
 */
final class BrandTokensAreMirroredTest extends TestCase
{
    private function brand(): array
    {
        return require __DIR__.'/../../config/gfms-brand.php';
    }

    private function appCss(): string
    {
        return file_get_contents(__DIR__.'/../../resources/css/app.css');
    }

    /** Every scalar colour in the config must appear in the stylesheet. */
    public function test_every_brand_colour_appears_in_the_stylesheet(): void
    {
        $css = strtolower($this->appCss());
        $missing = [];

        foreach ($this->brand() as $key => $value) {
            if (! is_string($value) || ! str_starts_with($value, '#')) {
                continue;
            }
            if (! str_contains($css, strtolower($value))) {
                $missing[] = "{$key} ({$value})";
            }
        }

        $this->assertSame([], $missing,
            'config/gfms-brand.php defines colours that app.css does not mirror: '
            .implode(', ', $missing));
    }

    public function test_every_band_colour_appears_in_the_stylesheet(): void
    {
        $css = strtolower($this->appCss());

        foreach ($this->brand()['bands'] as $slot => $hex) {
            $this->assertStringContainsString(strtolower($hex), $css,
                "Band colour {$slot} ({$hex}) is missing from app.css.");
        }
    }

    /**
     * The band palette is deliberately bright, and three of the six cannot
     * carry white text — amber measures 2.08:1 against white. Rather than
     * darkening them back toward the muted set this direction replaced, the tag
     * resolves its own foreground. What must hold is that SOME foreground
     * passes for every band, including whatever the hash hands an
     * unanticipated bloodline.
     */
    public function test_every_band_is_legible_with_its_resolved_foreground(): void
    {
        $b = $this->brand();
        $light = $b['band_foreground_light'];
        $dark = $b['band_foreground_dark'];

        foreach ($b['bands'] as $slot => $hex) {
            $best = max(
                BandTag::contrast($hex, $light),
                BandTag::contrast($hex, $dark)
            );

            $this->assertGreaterThanOrEqual(4.5, $best,
                "Band {$slot} ({$hex}) is illegible against BOTH available "
                .'foregrounds. Every band must work with one of them.');
        }
    }

    /**
     * Console body text must clear 7:1 on every ground it can land on. The
     * Console is used outdoors in Philippine daylight, so this is a legibility
     * requirement rather than a preference.
     */
    public function test_console_text_clears_the_seven_to_one_floor_on_every_surface(): void
    {
        $b = $this->brand();

        foreach (['foreground', 'muted_foreground'] as $ink) {
            foreach (['background', 'card', 'muted'] as $ground) {
                $ratio = BandTag::contrast($b[$ink], $b[$ground]);

                $this->assertGreaterThanOrEqual(7.0, $ratio,
                    sprintf('%s on %s is %.2f:1 — below the Console 7:1 floor.', $ink, $ground, $ratio));
            }
        }
    }

    /**
     * The band tag's code chip tints the band so the two-letter code reads as a
     * separate element — and that tint has to move the ground AWAY from the
     * text, not toward it.
     *
     * The first version tinted with 12% of the text's own colour, which on an
     * ember band under dark ink darkened the chip toward the text and measured
     * 3.84:1 in the browser. A naive contrast script missed it entirely,
     * reporting 1.00:1, because it read the chip's own translucent background as
     * the ground instead of compositing it. So this test composites.
     */
    public function test_the_band_code_chip_stays_legible_on_every_band(): void
    {
        $b = $this->brand();
        $light = $b['band_foreground_light'];

        foreach ($b['bands'] as $slot => $hex) {
            $fg = $this->resolveForeground($hex, $b);

            // Mirrors the component: light text darkens the chip, dark text lightens it.
            [$tint, $alpha] = $fg === $light ? [[0, 0, 0], 0.22] : [[255, 255, 255], 0.45];

            $ground = $this->composite($tint, $alpha, $hex);
            $ratio = BandTag::contrast($fg, $ground);

            $this->assertGreaterThanOrEqual(4.5, $ratio,
                sprintf('Band %s: the code chip measures %.2f:1. The tint is pushing the '
                    .'ground toward the text instead of away from it.', $slot, $ratio));
        }
    }

    /** @param array{int,int,int} $tint */
    private function composite(array $tint, float $alpha, string $baseHex): string
    {
        $base = array_map(fn (int $i) => hexdec(substr($baseHex, $i, 2)), [1, 3, 5]);

        $out = array_map(
            fn (int $t, int $bs) => (int) round($t * $alpha + $bs * (1 - $alpha)),
            $tint,
            $base
        );

        return sprintf('#%02X%02X%02X', ...$out);
    }

    private function resolveForeground(string $hex, array $brand): string
    {
        return BandTag::contrast($hex, $brand['band_foreground_light']) >= 4.5
            ? $brand['band_foreground_light']
            : $brand['band_foreground_dark'];
    }

    /** Every semantic pair must be legible as foreground-on-its-own-ground. */
    public function test_every_semantic_pair_is_legible(): void
    {
        $b = $this->brand();

        foreach (['success', 'warning', 'destructive', 'info'] as $token) {
            $ratio = BandTag::contrast($b[$token], $b[$token.'_bg']);

            $this->assertGreaterThanOrEqual(4.5, $ratio,
                sprintf('%s on %s_bg is %.2f:1.', $token, $token, $ratio));
        }

        // Filled controls: the foreground the button actually uses.
        $this->assertGreaterThanOrEqual(4.5,
            BandTag::contrast($b['primary_foreground'], $b['primary']),
            'primary_foreground on primary is illegible.');

        $this->assertGreaterThanOrEqual(4.5,
            BandTag::contrast($b['destructive_foreground'], $b['destructive']),
            'destructive_foreground on destructive is illegible.');
    }
}
