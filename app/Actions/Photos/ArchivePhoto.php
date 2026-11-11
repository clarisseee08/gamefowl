<?php

declare(strict_types=1);

namespace App\Actions\Photos;

use App\Models\BroodcockPhoto;
use Illuminate\Support\Facades\DB;

/**
 * Archives a photo: it leaves the bird's gallery but keeps its row and its
 * file, so the owner can restore it from the Archive. Nothing is erased.
 *
 * An archived photo stops being the main photo. If it was, the oldest
 * remaining photo takes over - a bird that still has photos must still have a
 * main one, or every list screen silently loses its thumbnail - and a photo
 * restored later never comes back as a second main photo.
 */
final class ArchivePhoto
{
    public function handle(BroodcockPhoto $photo): void
    {
        DB::transaction(function () use ($photo): void {
            $wasPrimary = $photo->is_primary;

            if ($wasPrimary) {
                $photo->update(['is_primary' => false]);
            }

            $photo->delete();

            if (! $wasPrimary) {
                return;
            }

            BroodcockPhoto::query()
                ->where('broodcock_id', $photo->broodcock_id)
                ->oldest('id')
                ->first()
                ?->update(['is_primary' => true]);
        });
    }
}
