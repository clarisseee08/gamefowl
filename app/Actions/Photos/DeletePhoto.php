<?php

declare(strict_types=1);

namespace App\Actions\Photos;

use App\Models\BroodcockPhoto;
use App\Support\Thumbnail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Removes a photo: the row first, then the file.
 *
 * `broodcock_photos` has no soft deletes by design, so this is a real delete.
 *
 * Order matters and the choice is deliberate: the transaction commits the row
 * removal (and any promotion of a replacement primary photo) and ONLY then is
 * the file removed from the disk. If the disk call then fails, the worst case
 * is an unreferenced file that no screen can reach and no query can find - it
 * costs storage and nothing else.
 *
 * The other ordering - delete the file, roll the row back on failure - fails
 * worse. Object storage deletes are not transactional and cannot be undone, so
 * a rollback after a successful delete leaves a row whose file is gone: a
 * permanently broken image, an audit trail that lies, and a photo the farm
 * believes it still has.
 */
final class DeletePhoto
{
    public function handle(BroodcockPhoto $photo): void
    {
        $disk = $photo->disk;
        $path = $photo->path;

        DB::transaction(function () use ($photo): void {
            $wasPrimary = $photo->is_primary;
            $broodcockId = $photo->broodcock_id;

            $photo->delete();

            if (! $wasPrimary) {
                return;
            }

            // A bird that still has photos must still have a primary one,
            // otherwise every list screen silently loses its thumbnail.
            $replacement = BroodcockPhoto::query()
                ->where('broodcock_id', $broodcockId)
                ->oldest('id')
                ->first();

            $replacement?->update(['is_primary' => true]);
        });

        // Past this point the database is already consistent. A storage
        // failure is logged for the administrator, never surfaced to the user
        // as if the deletion had failed - because it did not.
        try {
            // The thumbnail goes with it. Its path is derived rather than
            // stored, so there is no row to consult - and an orphaned thumbnail
            // would be invisible storage nobody ever reclaims.
            Storage::disk($disk)->delete([$path, Thumbnail::pathFor($path)]);
        } catch (Throwable $e) {
            Log::warning('Photo row deleted but its file could not be removed.', [
                'disk' => $disk,
                'path' => $path,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
