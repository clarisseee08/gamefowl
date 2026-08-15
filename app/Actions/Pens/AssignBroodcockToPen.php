<?php

declare(strict_types=1);

namespace App\Actions\Pens;

use App\Models\Broodcock;
use App\Models\Pen;
use Illuminate\Support\Facades\DB;

/**
 * Move birds into a pen, or out of every pen when $pen is null.
 *
 * Capacity is deliberately NOT enforced here. Farms overfill pens - a hard
 * block would leave staff unable to record what is physically true, so the
 * over-capacity condition is surfaced as a warning in the UI instead and the
 * write is still allowed through.
 */
final class AssignBroodcockToPen
{
    /**
     * @param  array<int, int|string>  $broodcockIds
     * @return int the number of birds actually moved
     */
    public function handle(array $broodcockIds, ?Pen $pen): int
    {
        $ids = array_values(array_unique(array_map('intval', $broodcockIds)));

        if ($ids === []) {
            return 0;
        }

        return DB::transaction(function () use ($ids, $pen): int {
            $birds = Broodcock::query()->whereIn('id', $ids)->get();

            $moved = 0;

            foreach ($birds as $bird) {
                // Saving one model at a time rather than a mass update keeps
                // the activity-log entry per bird - the audit trail is the
                // point of this system, and a bulk UPDATE fires no events.
                if ($bird->pen_id === $pen?->id) {
                    continue;
                }

                $bird->update(['pen_id' => $pen?->id]);
                $moved++;
            }

            return $moved;
        });
    }
}
