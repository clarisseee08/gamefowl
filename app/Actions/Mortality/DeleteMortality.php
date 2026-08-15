<?php

declare(strict_types=1);

namespace App\Actions\Mortality;

use App\Enums\BroodcockStatus;
use App\Models\MortalityRecord;
use Illuminate\Support\Facades\DB;

/**
 * Undoing a death record.
 *
 * Deleting the mortality row alone would leave the bird sitting at
 * `deceased` with nothing left to explain why - invisible in every on-farm
 * list, unusable for breeding, and with no record to delete a second time.
 * The status is therefore restored to Active in the SAME transaction that
 * soft-deletes the row.
 *
 * Active, not the bird's previous status: the previous status is not stored
 * anywhere, and Active is the one value that is always safe to return to.
 * Staff can move the bird on from there.
 */
final class DeleteMortality
{
    public function handle(MortalityRecord $record): void
    {
        DB::transaction(function () use ($record): void {
            // loadMissing(), not $record->broodcock - Model::shouldBeStrict()
            // makes an un-eager-loaded relation throw, and this Action must
            // work whether or not the caller already loaded the bird.
            $record->loadMissing('broodcock');

            $broodcock = $record->broodcock;

            $record->delete();

            $broodcock?->update(['status' => BroodcockStatus::Active]);
        });
    }
}
