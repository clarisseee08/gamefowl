<?php

declare(strict_types=1);

namespace App\Actions\Photos;

use App\Models\BroodcockPhoto;
use Illuminate\Support\Facades\DB;

/**
 * Promotes one photo to be the bird's primary photo.
 *
 * "Exactly one primary per bird" is an invariant across several rows of the
 * same table, so demotion and promotion happen inside one transaction. Doing
 * them as two loose statements leaves a window in which a list screen renders
 * two thumbnails for one bird - or none, if the process dies between them.
 */
final class SetPrimaryPhoto
{
    public function handle(BroodcockPhoto $photo): BroodcockPhoto
    {
        return DB::transaction(function () use ($photo): BroodcockPhoto {
            BroodcockPhoto::query()
                ->where('broodcock_id', $photo->broodcock_id)
                ->where('is_primary', true)
                ->whereKeyNot($photo->getKey())
                // Row locks held to commit, so two staff tapping "Make main
                // photo" at the same moment serialise instead of interleaving.
                ->lockForUpdate()
                ->get()
                // At most one row by this invariant, so this is not an N+1 -
                // and saving each as a model keeps the demotion in the
                // activity log, which a mass update() would skip.
                ->each(fn (BroodcockPhoto $previous) => $previous->update(['is_primary' => false]));

            $photo->update(['is_primary' => true]);

            return $photo;
        });
    }
}
