<?php

declare(strict_types=1);

namespace Tests\Unit;

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
     * Every band colour must carry white text at >= 4.5:1, because the tag
     * knocks its number out in white and the colour is chosen by data - we do
     * not get to pick which bloodline lands on which slot.
     */
    public function test_every_band_colour_is_legible_with_white_text(): void
    {
        foreach ($this->brand()['bands'] as $slot => $hex) {
            $this->assertGreaterThanOrEqual(4.5, $this->contrast($hex, '#FFFFFF'),
                "Band colour {$slot} ({$hex}) fails 4.5:1 against white text.");
        }
    }

    /**
     * A token is only legible against a specific GROUND, and this system has
     * three. ink_faint clears 4.5:1 on parchment and canvas but measures 4.37:1
     * on pearl - found by measuring the pedigree screen, where the root card
     * sits on pearl, not by reading the palette. This pins the combination down
     * so the next person does not rediscover it on a different screen.
     */
    public function test_ink_faint_is_legible_on_light_grounds_and_not_on_pearl(): void
    {
        $b = $this->brand();

        foreach (['paper', 'card'] as $ground) {
            $this->assertGreaterThanOrEqual(4.5, $this->contrast($b['ink_faint'], $b[$ground]),
                "ink_faint should be usable on {$ground}.");
        }

        $this->assertLessThan(4.5, $this->contrast($b['ink_faint'], $b['sunk']),
            'ink_faint now passes on pearl. If the palette changed deliberately, delete this '
            .'assertion and the warning in SKILL.md together - otherwise the documented rule is wrong.');
    }

    /** Console body text must clear 7:1 on the app background (outdoor glare). */
    public function test_console_ink_clears_the_seven_to_one_floor(): void
    {
        $b = $this->brand();

        foreach (['ink', 'ink_muted', 'action'] as $token) {
            $this->assertGreaterThanOrEqual(7.0, $this->contrast($b[$token], $b['paper']),
                "{$token} ({$b[$token]}) fails the Console 7:1 floor on paper.");
        }
    }

    private function contrast(string $a, string $b): float
    {
        $l = function (string $hex): float {
            $c = array_map(
                fn (int $i) => hexdec(substr($hex, $i, 2)) / 255,
                [1, 3, 5]
            );
            $c = array_map(
                fn (float $v) => $v <= 0.03928 ? $v / 12.92 : (($v + 0.055) / 1.055) ** 2.4,
                $c
            );

            return 0.2126 * $c[0] + 0.7152 * $c[1] + 0.0722 * $c[2];
        };

        $x = $l($a);
        $y = $l($b);

        return (max($x, $y) + 0.05) / (min($x, $y) + 0.05);
    }
}
