<?php

declare(strict_types=1);

namespace App\Actions\Photos;

use App\Models\Broodcock;
use App\Models\BroodcockPhoto;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

/**
 * Puts one uploaded photo on the configured disk and records it.
 *
 * Ordering: the file is written FIRST, then the row is inserted inside a
 * transaction. If the insert fails the just-written file is removed. The
 * alternative ordering would leave a row pointing at a file that was never
 * written, which renders as a broken image forever; this ordering can at worst
 * leave an unreferenced file, which nobody ever sees.
 */
final class StorePhoto
{
    /**
     * @param  array{caption?: string|null}  $data  Already validated.
     */
    public function handle(Broodcock $broodcock, UploadedFile $file, User $actor, array $data = []): BroodcockPhoto
    {
        $disk = self::disk();
        $path = self::pathFor($broodcock, $file);

        // storeAs() streams to the disk and returns the stored path. On the
        // supabase disk `throw => true`, so a failed PUT raises here rather
        // than silently returning false and losing the photo.
        $file->storeAs(dirname($path), basename($path), ['disk' => $disk]);

        try {
            return DB::transaction(function () use ($broodcock, $path, $disk, $actor, $data): BroodcockPhoto {
                // The first photo a bird ever gets becomes its primary, so the
                // list screens always have a thumbnail without anyone having to
                // know that "primary" is a thing they need to set.
                $isFirst = ! $broodcock->photos()->exists();

                return $broodcock->photos()->create([
                    'path' => $path,
                    'disk' => $disk,
                    'caption' => $data['caption'] ?? null,
                    'is_primary' => $isFirst,
                    // Never from the request - the uploader is whoever is signed in.
                    'uploaded_by' => $actor->id,
                ]);
            });
        } catch (Throwable $e) {
            // Compensate: the row never landed, so the file must not linger.
            Storage::disk($disk)->delete($path);

            throw $e;
        }
    }

    /** The disk chosen at runtime - never hard-coded, see config/gfms.php. */
    public static function disk(): string
    {
        return (string) config('gfms.photo_disk');
    }

    /**
     * `broodcocks/{id}/{uuid}.{ext}`.
     *
     * The user-supplied filename is discarded entirely: it is attacker input,
     * it collides, and it can contain path separators. The extension is taken
     * from the file's real type, falling back to the client extension only for
     * the local disk where no MIME guess is available.
     */
    public static function pathFor(Broodcock $broodcock, UploadedFile $file): string
    {
        $extension = strtolower($file->extension() ?: $file->getClientOriginalExtension() ?: 'jpg');

        return sprintf('broodcocks/%d/%s.%s', $broodcock->id, Str::uuid()->toString(), $extension);
    }
}
