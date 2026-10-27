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
     * 800, raised from 400 because 400 was visibly soft. The arithmetic the
     * old value rested on was a catalogue card at "roughly 300px CSS"; the
     * redesigned card is 4:5 and up to 330px wide, so on a 2x screen - every
     * phone the farm browses on, and most laptops - it is drawn from 660
     * device pixels wide by 825 tall. A 400px source was being stretched to
     * twice its size in both directions, and that stretch is the blur people
     * noticed. 800 on the long edge covers the 825 within a few percent.
     */
    public const MAX_EDGE = 800;

    /**
     * JPEG quality.
     *
     * 82 rather than 75: at 800px the image is large enough on screen for
     * block artefacts in feathers - fine, high-contrast detail - to show.
     * The thumbnail is still a fraction of the original's size.
     */
    private const QUALITY = 82;

    /**
     * Unsharp mask applied after downscaling.
     *
     * Resampling averages neighbouring pixels, which is what makes a
     * reduced photograph look slightly out of focus - the edges of a hackle
     * feather lose their contrast. A light sharpen after the resize restores
     * it. This is GD's equivalent of what an image tool's "sharpen after
     * resize" does: a 3x3 kernel weighted toward the centre pixel. The
     * centre weight sets the strength - higher is gentler. 20 was tried
     * first and changed an edge by a single grey level, too little to see;
     * 12 overshoots an edge by four or five levels, which reads as crisp
     * without putting visible halos round a bird against a bright sky.
     *
     * Not applied when the image was already small enough to pass through
     * unresized; there is no softening to undo.
     */
    private const SHARPEN = [
        [-1, -1, -1],
        [-1, 12, -1],
        [-1, -1, -1],
    ];

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

        // Divisor is the kernel's sum (12 - 8 = 4), so overall brightness is
        // unchanged and only edge contrast moves.
        imageconvolution($target, self::SHARPEN, 4, 0);

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
