<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\BroodcockPhoto;
use App\Support\LocalPhotoCopy;
use App\Support\Thumbnail;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Filesystem\AwsS3V3Adapter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

/**
 * Serves broodcock photos through the application instead of linking at the
 * storage bucket.
 *
 * The Supabase bucket is private, so a direct link is not an option at all.
 * The available alternative - a SigV4 presigned URL - would work, but it is
 * bearer authority in a query string: once minted it is valid for anyone who
 * has the link until it expires, it survives in browser history, referrer
 * headers and chat messages, and it cannot be revoked when an account is
 * deactivated. Streaming through here means BroodcockPhotoPolicy::view() is
 * consulted on every single image request, by the signed-in user, every time.
 */
final class BroodcockPhotoController extends Controller
{
    /**
     * How long a browser may reuse an image without asking again.
     *
     * `private` is load-bearing: the response is authorized per user, so a
     * shared cache (proxy, CDN) must never keep a copy and hand it to the next
     * person. Photos are immutable once written - a replacement gets a new
     * UUID path - so thirty days in the user's own cache is safe. It was an
     * hour, which sent every returning visitor back to Tokyo for the same
     * pictures each morning.
     */
    private const CACHE_CONTROL = 'private, max-age=2592000, immutable';

    public function show(Request $request, BroodcockPhoto $photo): StreamedResponse
    {
        // The route binds archived photos too, for the owner's Archive. To
        // anyone else an archived photo simply does not exist.
        abort_if($photo->trashed() && ! Gate::allows('view-archive'), 404);

        $this->authorize('view', $photo);

        $disk = Storage::disk($photo->disk);

        // `?size=thumb` asks for the small copy written at upload time. It is
        // a REQUEST, not a guarantee: photos uploaded before thumbnails
        // existed have none, and Thumbnail::fromBytes() is allowed to decline
        // any image it cannot handle. Either way the original is served, which
        // is exactly the behaviour this route had before.
        $wantsThumbnail = $request->query('size') === 'thumb';

        $path = $wantsThumbnail ? Thumbnail::pathFor($photo->path) : $photo->path;

        $stream = $this->open($disk, $photo->disk, $path);

        if ($stream === null && $wantsThumbnail) {
            $path = $photo->path;
            $stream = $this->open($disk, $photo->disk, $path);
        }

        abort_if($stream === null, 404, 'This photo is no longer available.');

        return response()->stream(function () use ($stream): void {
            fpassthru($stream);

            if (is_resource($stream)) {
                fclose($stream);
            }
        }, 200, [
            'Content-Type' => $this->contentType($path),
            'Cache-Control' => self::CACHE_CONTROL,
            // Show it, never download it, and never under a name the uploader chose.
            'Content-Disposition' => 'inline; filename="'.basename($path).'"',
            // Refuse to let a browser re-interpret a mislabelled upload as
            // HTML or script - that is how an image upload becomes stored XSS.
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    /**
     * Opens a file, or null if it is not there.
     *
     * A photo in the remote bucket is read through LocalPhotoCopy, which keeps
     * the first download on this server so later views skip the trip to
     * Tokyo. A photo already on a local disk is simply opened.
     *
     * The supabase disk is configured with `throw => true`, so a missing object
     * raises rather than returning false - and a missing thumbnail is an
     * ordinary, expected condition here rather than a fault.
     */
    private function open(Filesystem $disk, string $diskName, string $path): mixed
    {
        if ($disk instanceof AwsS3V3Adapter) {
            return LocalPhotoCopy::make()->open($disk, $diskName, $path);
        }

        try {
            $stream = $disk->readStream($path);
        } catch (Throwable) {
            return null;
        }

        return ($stream === false || $stream === null) ? null : $stream;
    }

    /**
     * Derived from the stored extension rather than asking the disk.
     *
     * StorePhoto writes the extension from the file's verified type, so it is
     * trustworthy - and on Supabase a mimeType() lookup is an extra HTTP round
     * trip to Tokyo for every thumbnail on the page.
     *
     * Takes the PATH rather than the photo, because the path actually served
     * may be the thumbnail, which is always JPEG whatever the original was.
     */
    private function contentType(string $path): string
    {
        return match (strtolower(pathinfo($path, PATHINFO_EXTENSION))) {
            'png' => 'image/png',
            'webp' => 'image/webp',
            default => 'image/jpeg',
        };
    }
}
