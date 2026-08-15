<?php

declare(strict_types=1);

namespace App\Actions\Mortality;

use App\Enums\BroodcockStatus;
use App\Models\Broodcock;
use App\Models\MortalityRecord;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Recording a death is the one write in this system that must touch two tables
 * or neither.
 *
 * A mortality row without `broodcocks.status = deceased` leaves a dead bird in
 * the live inventory, still selectable as a breeding parent. The reverse - a
 * bird marked deceased with no mortality row - is a death with no cause, no
 * date and no audit trail, and because `broodcock_id` is UNIQUE the row can
 * never be added afterwards.
 *
 * Both writes therefore happen inside ONE DB::transaction(). If the status
 * update throws, the mortality INSERT is rolled back with it.
 */
final class RecordMortality
{
    /**
     * @param  array<string, mixed>  $data  Already validated by StoreMortalityRecordRequest.
     */
    public function handle(Broodcock $broodcock, array $data, User $actor): MortalityRecord
    {
        return DB::transaction(function () use ($broodcock, $data, $actor): MortalityRecord {
            $attributes = [
                'date_of_death' => $data['date_of_death'],
                'cause_of_death' => $data['cause_of_death'],
                'disposal_method' => $data['disposal_method'] ?? null,
                'remarks' => $data['remarks'] ?? null,
                // Never taken from the request - always the signed-in user.
                'recorded_by' => $actor->id,
            ];

            // The UNIQUE index on broodcock_id is not partial: it still counts
            // soft-deleted rows. Without this, a death that was recorded, then
            // deleted, could never be recorded again - the INSERT would hit the
            // constraint even though no live record exists. Reviving the old
            // row keeps both the constraint and the audit trail intact.
            $trashed = MortalityRecord::onlyTrashed()
                ->where('broodcock_id', $broodcock->id)
                ->first();

            if ($trashed !== null) {
                $trashed->restore();
                $trashed->fill($attributes)->save();
                $record = $trashed;
            } else {
                $record = $broodcock->mortalityRecord()->create($attributes);
            }

            $broodcock->update(['status' => BroodcockStatus::Deceased]);

            return $record;
        });
    }
}
