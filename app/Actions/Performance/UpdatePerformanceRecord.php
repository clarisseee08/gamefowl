<?php

declare(strict_types=1);

namespace App\Actions\Performance;

use App\Enums\PerformanceEventType;
use App\Enums\PerformanceResult;
use App\Models\PerformanceRecord;
use Illuminate\Support\Facades\DB;

final class UpdatePerformanceRecord
{
    /**
     * @param  array<string, mixed>  $data  Already validated by UpdatePerformanceRecordRequest.
     *
     * There is no $actor parameter: an edit does not reassign `recorded_by`,
     * and who made the change is captured by the activity log's causer.
     */
    public function handle(PerformanceRecord $record, array $data): PerformanceRecord
    {
        return DB::transaction(function () use ($record, $data): PerformanceRecord {
            $record->update($this->normalise($data));

            return $record->refresh();
        });
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function normalise(array $data): array
    {
        // An edit never reassigns authorship. The original recorder stays on
        // the record; who changed it is what the activity log is for.
        unset($data['recorded_by']);

        $type = $data['event_type'] ?? null;
        $type = $type instanceof PerformanceEventType ? $type : PerformanceEventType::tryFrom((string) $type);

        if ($type !== null && ! $type->hasContestResult()) {
            $data['result'] = PerformanceResult::NotApplicable;
        }

        return $data;
    }
}
