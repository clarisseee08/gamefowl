<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Resolves a bloodline to its band colour.
 *
 * THE PROBLEM THIS SOLVES: `broodcocks.bloodline` is a free-text
 * `varchar(120) nullable` column, not an enum. Seed data contains three
 * values, the factory defines five, and a record keeper can type anything at
 * all. A fixed name-to-colour map therefore fails the moment someone adds a
 * bloodline - the tag renders unstyled and the encoding silently breaks.
 *
 * So resolution is in three tiers:
 *   1. Curated map (config/gfms-brand.php) for the farm's known stock, so
 *      those colours are stable and recognisable.
 *   2. Deterministic hash for anything else - the SAME bloodline always gets
 *      the SAME colour, on every screen, in every session, on every machine.
 *   3. No bloodline recorded -> the neutral tone, never a random colour.
 *
 * Colour is never the only channel: every tag also carries a two-letter code
 * and the bloodline name is rendered as text beside it, so the encoding
 * survives colour-vision deficiency and a monochrome printout.
 */
final class BandTag
{
    /**
     * Fallback tone when no bloodline is recorded at all.
     *
     * Read from config rather than declared as a literal: config/gfms-brand.php
     * is the single source of colour, and DesignSystemGuardTest fails on any
     * hardcoded hex outside it.
     */
    public static function unknownHex(): string
    {
        return config('gfms-brand.muted_foreground');
    }

    /** Normalises free text to a lookup key. */
    private static function key(?string $bloodline): string
    {
        return mb_strtolower(trim((string) $bloodline));
    }

    /** @return array<string, string> slot name => hex */
    private static function bands(): array
    {
        return config('gfms-brand.bands', []);
    }

    /**
     * The band slot name for a bloodline, e.g. 'cobalt'.
     * Returns null when no bloodline is recorded.
     */
    public static function slot(?string $bloodline): ?string
    {
        $key = self::key($bloodline);

        if ($key === '') {
            return null;
        }

        $curated = config('gfms-brand.bloodlines.'.$key);

        if (is_array($curated) && isset($curated['slot'])) {
            return $curated['slot'];
        }

        // Deterministic fallback. crc32 is stable across PHP versions and
        // platforms, which is what makes the colour reproducible - a hash
        // that varied per request would make the whole encoding meaningless.
        $slots = array_keys(self::bands());

        if ($slots === []) {
            return null;
        }

        return $slots[crc32($key) % count($slots)];
    }

    /** The hex for a bloodline, falling back to the neutral tone. */
    public static function hex(?string $bloodline): string
    {
        $slot = self::slot($bloodline);

        return $slot === null
            ? self::unknownHex()
            : (self::bands()[$slot] ?? self::unknownHex());
    }

    /**
     * The text colour that belongs on this band, resolved rather than assumed.
     *
     * The band palette is deliberately bright, and three of the six cannot
     * carry white text - amber sits at 2.08:1 against white, which is
     * unreadable. Darkening them until white worked would have undone the
     * saturation the palette exists for. So each tag picks the foreground that
     * actually passes.
     *
     * This is not just for the six curated colours: an unanticipated bloodline
     * gets its colour from a hash, so the foreground has to be computed rather
     * than tabulated or the fallback path ships an illegible tag.
     */
    public static function foreground(?string $bloodline): string
    {
        $light = config('gfms-brand.band_foreground_light');
        $dark = config('gfms-brand.band_foreground_dark');
        $bg = self::hex($bloodline);

        return self::contrast($bg, $light) >= 4.5 ? $light : $dark;
    }

    /** WCAG relative-luminance contrast ratio between two hex colours. */
    public static function contrast(string $a, string $b): float
    {
        $luminance = static function (string $hex): float {
            $channels = array_map(
                static fn (int $offset): float => hexdec(substr($hex, $offset, 2)) / 255,
                [1, 3, 5]
            );

            $channels = array_map(
                static fn (float $c): float => $c <= 0.03928
                    ? $c / 12.92
                    : (($c + 0.055) / 1.055) ** 2.4,
                $channels
            );

            return 0.2126 * $channels[0] + 0.7152 * $channels[1] + 0.0722 * $channels[2];
        };

        $x = $luminance($a);
        $y = $luminance($b);

        return (max($x, $y) + 0.05) / (min($x, $y) + 0.05);
    }

    /**
     * The two-letter code shown inside the tag.
     *
     * This is what carries the encoding when colour cannot: a colour-blind
     * keeper, a photocopied report, a projector washing out hue. Curated
     * bloodlines get a hand-picked code; anything else takes its first two
     * letters, which is predictable enough to learn.
     */
    public static function code(?string $bloodline): string
    {
        $key = self::key($bloodline);

        if ($key === '') {
            return '--';
        }

        $curated = config('gfms-brand.bloodlines.'.$key);

        if (is_array($curated) && isset($curated['code'])) {
            return $curated['code'];
        }

        return mb_strtoupper(mb_substr($key, 0, 2));
    }

    /** Screen-reader text, so the tag is never colour-only. */
    public static function label(?string $bloodline, ?string $bandNumber): string
    {
        $blood = trim((string) $bloodline) !== '' ? $bloodline : 'bloodline not recorded';

        return trim((string) $bandNumber) !== ''
            ? "Band {$bandNumber}, {$blood}"
            : "Not yet banded, {$blood}";
    }
}
