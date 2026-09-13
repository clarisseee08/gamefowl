<?php

declare(strict_types=1);

namespace App\Support;

use GdImage;
use Throwable;

/**
 * Makes a small JPEG copy of an uploaded photo.
 *
 * WHY THIS EXISTS. Every grid in this application - the broodcock table, the
 * customer catalogue, the photo gallery - rendered the ORIGINAL file in a box a
 * couple of hundred pixels wide. A phone photo is routinely 4 MB, the catalogue
 * shows twelve per page, and each one is streamed out of Supabase in Tokyo,
 * through php-fpm, on a container with four workers and 512 MB of RAM. That is
 * roughly 48 MB of transfer to draw one page of thumbnails, and four
 * simultaneous image requests are enough to occupy every worker the site has.
 *
 * A single ~400px JPEG per photo turns that into a few hundred kilobytes.
 *
 * DESIGN RULES, in order of importance:
 *
 *  1. A FAILED THUMBNAIL MUST NEVER FAIL AN UPLOAD. Photographing a bird in a
 *     pen on farm wifi is the expensive part; losing that because GD disliked a
 *     progressive JPEG would be indefensible. Every path here returns null on
 *     failure and the caller carries on with the original.
 *
 *  2. Always JPEG, whatever came in. Thumbnails have no transparency to
 *     preserve and JPEG is the smallest and most universally decodable option.
 *     The ORIGINAL keeps its own format untouched.
 *
 *  3. Orientation is honoured. Phones record rotation in EXIF rather than in
 *     the pixels, and resampling silently discards it - so a portrait photo of
 *     a cock would come out on its side in every list while looking correct on
 *     the bird's own page. That is worse than no thumbnail.
 */
final class Thumbnail
{
    /**
     * Longest edge, in pixels.
     *
     * 400 covers the largest place a thumbnail is used - a catalogue card at
     * roughly 300px CSS - with enough left over to stay sharp on a 2x phone
     * screen, which is what the farm actually browses on.
     */
    public const MAX_EDGE = 400;

    /**
     * JPEG quality.
     *
     * 75 is the usual point where artefacts stop being visible at this size;
     * higher buys bytes rather than appearance on a 400px image.
     */
    private const QUALITY = 75;

    /**
     * A hard ceiling on pixels we will attempt to decode.
     *
     * GD decodes to roughly 4 bytes per pixel regardless of how small the
     * compressed file is, so a "decompression bomb" - a 2 MB file that expands
     * to 100 megapixels - is 400 MB of RAM on a 192 MB limit. That is an OOM
     * kill of the whole php-fpm worker, not an exception, so it has to be
     * refused before the decode rather than caught after it.
     */
    private const MAX_PIXELS = 40_000_000;

    /** The thumbnail's path, derived from the original's. Never stored. */
    public static function pathFor(string $originalPath): string
    {
        $directory = pathinfo($originalPath, PATHINFO_DIRNAME);
        $name = pathinfo($originalPath, PATHINFO_FILENAME);

        return ($directory === '.' ? '' : $directory.'/').$name.'-thumb.jpg';
    }

    /**
     * Encoded JPEG bytes, or null if a thumbnail could not be made.
     *
     * Null is a normal answer, not an error: the caller serves the original
     * instead, which is exactly what happened before thumbnails existed.
     */
    public static function fromBytes(?string $bytes): ?string
    {
        if ($bytes === null || $bytes === '') {
            return null;
        }

        if (! function_exists('imagecreatefromstring')) {
            // gd is absent. Local development without the extension should not
            // be a broken upload, only a slower page.
            return null;
        }

        try {
            if (self::wouldExhaustMemory($bytes)) {
                return null;
            }

            $source = @imagecreatefromstring($bytes);

            if (! $source instanceof GdImage) {
                return null;
            }

            try {
                $source = self::applyExifOrientation($source, $bytes);
                $resized = self::resize($source);

                if ($resized === null) {
                    return null;
                }

                try {
                    return self::encode($resized);
                } finally {
                    // Not the same handle when a resize happened, and freeing
                    // the source twice is a fatal in some PHP builds.
                    if ($resized !== $source) {
                        imagedestroy($resized);
                    }
                }
            } finally {
                imagedestroy($source);
            }
        } catch (Throwable) {
            // Deliberately swallowed. See rule 1 - the upload outranks this.
            return null;
        }
    }

    /** Reads the dimensions from the header without decoding the pixels. */
    private static function wouldExhaustMemory(string $bytes): bool
    {
        $info = @getimagesizefromstring($bytes);

        if ($info === false) {
            return true;
        }

        return ((int) $info[0] * (int) $info[1]) > self::MAX_PIXELS;
    }

    private static function resize(GdImage $source): ?GdImage
    {
        $width = imagesx($source);
        $height = imagesy($source);

        if ($width < 1 || $height < 1) {
            return null;
        }

        $scale = self::MAX_EDGE / max($width, $height);

        // Already small enough. Re-encoding it would cost quality for nothing,
        // so the source is handed straight to the encoder.
        if ($scale >= 1) {
            return $source;
        }

        $target = imagecreatetruecolor((int) max(1, round($width * $scale)), (int) max(1, round($height * $scale)));

        if (! $target instanceof GdImage) {
            return null;
        }

        // A PNG or WebP with transparency becomes black without this, because
        // a true-colour canvas starts filled with black rather than nothing.
        // JPEG has no alpha, so the transparent areas have to become SOMETHING
        // - and white is what reads as "no background" on every screen here.
        $white = imagecolorallocate($target, 255, 255, 255);
        imagefilledrectangle($target, 0, 0, imagesx($target) - 1, imagesy($target) - 1, $white);

        imagecopyresampled(
            $target, $source,
            0, 0, 0, 0,
            imagesx($target), imagesy($target),
            $width, $height
        );

        return $target;
    }

    private static function encode(GdImage $image): ?string
    {
        ob_start();
        $ok = imagejpeg($image, null, self::QUALITY);
        $data = ob_get_clean();

        return ($ok && is_string($data) && $data !== '') ? $data : null;
    }

    /**
     * Rotates the image to match its EXIF orientation tag.
     *
     * Only JPEGs carry this, and only some of them. Anything unreadable is left
     * alone rather than guessed at - an unrotated thumbnail is a much smaller
     * problem than a wrongly rotated one.
     */
    private static function applyExifOrientation(GdImage $image, string $bytes): GdImage
    {
        if (! function_exists('exif_read_data')) {
            return $image;
        }

        try {
            $exif = @exif_read_data('data://image/jpeg;base64,'.base64_encode($bytes));
        } catch (Throwable) {
            return $image;
        }

        $orientation = is_array($exif) ? ($exif['Orientation'] ?? null) : null;

        $degrees = match ((int) $orientation) {
            3 => 180,
            6 => -90,
            8 => 90,
            default => 0,
        };

        if ($degrees === 0) {
            return $image;
        }

        $rotated = @imagerotate($image, $degrees, 0);

        if (! $rotated instanceof GdImage) {
            return $image;
        }

        imagedestroy($image);

        return $rotated;
    }
}
