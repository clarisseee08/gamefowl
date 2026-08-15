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
    /** Fallback tone when no bloodline is recorded at all. */
    public const UNKNOWN_HEX = '#5E5B55';

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
            ? self::UNKNOWN_HEX
            : (self::bands()[$slot] ?? self::UNKNOWN_HEX);
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
