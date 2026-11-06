<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Contracts\Filesystem\Filesystem;
use Throwable;

/**
 * A copy, on the web server's own disk, of every photo it has served.
 *
 * WHY. Photos live in a private Supabase bucket in Tokyo and the site runs in
 * Singapore, so every <img> on a page cost a download from Tokyo before the
 * first byte reached the browser - 1 to 2 seconds a photo, measured on the
 * live site, with a grid of eight queueing behind the free instance's four
 * PHP workers. A stored photo never changes (a replacement is a new file), so
 * the first download can be kept and every later view read locally.
 *
 * WHAT IT DOES NOT CHANGE. Who may see a photo: BroodcockPhotoController still
 * runs the policy on every request before this is asked for anything. This
 * only decides where the bytes come from once that check has passed.
 *
 * The copies live under storage/framework/cache/photos, which is wiped with
 * the container on every deploy and restart - a cache, never the record.
 */
final class LocalPhotoCopy
{
    /**
     * How long "this file is not in the bucket" is remembered. Older photos
     * have no thumbnail, and asking Tokyo again on every view only to be told
     * no is the cost this class exists to remove. A day, so a thumbnail
     * backfilled later is still picked up.
     */
    private const MISSING_TTL_SECONDS = 86400;

    public function __construct(private readonly string $directory) {}

    public static function make(): self
    {
        return new self(storage_path('framework/cache/photos'));
    }

    /**
     * A readable stream of the file, or null if the disk does not have it.
     *
     * @return resource|null
     */
    public function open(Filesystem $disk, string $diskName, string $path): mixed
    {
        $copy = $this->copyPathFor($diskName, $path);

        if (is_file($copy)) {
            $stream = @fopen($copy, 'rb');

            if ($stream !== false) {
                return $stream;
            }
        }

        if ($this->knownMissing($copy)) {
            return null;
        }

        $remote = $this->read($disk, $path);

        if ($remote === null) {
            $this->markMissing($copy);

            return null;
        }

        return $this->keep($remote, $copy) ?? $this->read($disk, $path);
    }

    /** Where the copy of one file on one disk is kept. */
    public function copyPathFor(string $diskName, string $path): string
    {
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION)) ?: 'bin';

        return $this->directory.DIRECTORY_SEPARATOR.sha1($diskName.'|'.$path).'.'.$extension;
    }

    /**
     * Writes the downloaded bytes to the copy and opens it.
     *
     * Through a temporary file and a rename, so a request arriving mid-write
     * never serves half a photo. Any failure here - a full disk, a permission
     * problem - returns null and the photo is fetched from the bucket again:
     * a copy that cannot be kept must never cost the visitor the photo.
     *
     * @param  resource  $remote
     * @return resource|null
     */
    private function keep(mixed $remote, string $copy): mixed
    {
        $temporary = $copy.'.'.bin2hex(random_bytes(6)).'.tmp';

        try {
            if (! is_dir($this->directory)) {
                @mkdir($this->directory, 0775, true);
            }

            $out = @fopen($temporary, 'wb');

            if ($out === false) {
                return null;
            }

            $copied = stream_copy_to_stream($remote, $out);
            fclose($out);

            if ($copied === false || ! @rename($temporary, $copy)) {
                @unlink($temporary);

                return null;
            }

            $stream = @fopen($copy, 'rb');

            return $stream === false ? null : $stream;
        } catch (Throwable) {
            @unlink($temporary);

            return null;
        } finally {
            if (is_resource($remote)) {
                fclose($remote);
            }
        }
    }

    /**
     * Opens a file on the disk, or null if it is not there.
     *
     * The supabase disk is configured with `throw => true`, so a missing object
     * raises rather than returning false - and a missing thumbnail is an
     * ordinary, expected condition rather than a fault.
     *
     * @return resource|null
     */
    private function read(Filesystem $disk, string $path): mixed
    {
        try {
            $stream = $disk->readStream($path);
        } catch (Throwable) {
            return null;
        }

        return ($stream === false || $stream === null) ? null : $stream;
    }

    private function knownMissing(string $copy): bool
    {
        $marker = $copy.'.missing';

        return is_file($marker) && (time() - (int) filemtime($marker)) < self::MISSING_TTL_SECONDS;
    }

    private function markMissing(string $copy): void
    {
        if (! is_dir($this->directory)) {
            @mkdir($this->directory, 0775, true);
        }

        @touch($copy.'.missing');
    }
}
