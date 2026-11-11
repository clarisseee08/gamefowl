<?php

declare(strict_types=1);

namespace App\Actions\Archive;

use App\Enums\BroodcockStatus;
use App\Models\BroodcockPhoto;
use App\Models\MortalityRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

/**
 * Brings an archived record back exactly as it was archived.
 *
 * Most records simply restore. Two carry a consequence that archiving undid
 * and restoring must redo:
 *
 *  - a death record: archiving it put the bird back on the active list, so
 *    restoring it marks the bird Deceased again;
 *  - a photo: it returns to its bird's gallery only if the bird has room
 *    for it, and becomes the main photo if the bird has none.
 *
 * The restore is recorded in the activity log by the models' own logging.
 */
final class RestoreFromArchive
{
    /**
     * @throws RuntimeException with a message for the owner when the record
     *                          cannot come back as it is
     */
    public function handle(Model $record): void
    {
        if (! in_array(SoftDeletes::class, class_uses_recursive($record), true)) {
            throw new InvalidArgumentException($record::class.' is not an archivable record.');
        }

        DB::transaction(function () use ($record): void {
            match (true) {
                $record instanceof BroodcockPhoto => $this->restorePhoto($record),
                $record instanceof MortalityRecord => $this->restoreDeath($record),
                default => $record->restore(),
            };
        });
    }

    private function restorePhoto(BroodcockPhoto $photo): void
    {
        $max = (int) config('gfms.photos.max_per_broodcock');
        $onBird = BroodcockPhoto::query()->where('broodcock_id', $photo->broodcock_id)->count();

        if ($onBird >= $max) {
            throw new RuntimeException(
                "That bird already has the most photos allowed ({$max}). Archive one of its photos first, then restore this one."
            );
        }

        $hasMain = BroodcockPhoto::query()
            ->where('broodcock_id', $photo->broodcock_id)
            ->where('is_primary', true)
            ->exists();

        $photo->restore();

        if (! $hasMain) {
            $photo->update(['is_primary' => true]);
        }
    }

    private function restoreDeath(MortalityRecord $record): void
    {
        $record->restore();

        $record->loadMissing(['broodcock' => fn ($query) => $query->withTrashed()]);
        $record->broodcock?->update(['status' => BroodcockStatus::Deceased]);
    }
}
