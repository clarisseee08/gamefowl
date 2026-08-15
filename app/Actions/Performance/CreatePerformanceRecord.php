<?php

declare(strict_types=1);

namespace App\Actions\Performance;

use App\Enums\PerformanceEventType;
use App\Enums\PerformanceResult;
use App\Models\PerformanceRecord;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class CreatePerformanceRecord
{
    /**
     * @param  array<string, mixed>  $data  Already validated by StorePerformanceRecordRequest.
     */
    public function handle(array $data, User $actor): PerformanceRecord
    {
        // The insert plus its activity-log entry are two tables, so the write
        // is transactional: a record that exists without its audit trail is
        // worse than no record at all in a system sold on traceability.
        return DB::transaction(function () use ($data, $actor): PerformanceRecord {
            return PerformanceRecord::create([
                ...$this->normalise($data),
                // Provenance comes from the session, never from the payload.
                'recorded_by' => $actor->id,
            ]);
        });
    }

    /**
     * Enforce the one domain rule the database cannot: a weigh-in or a
     * conditioning session has no winner, so its result is forced to
     * "not applicable" no matter what was submitted.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function normalise(array $data): array
    {
        unset($data['recorded_by']);

        $type = $data['event_type'] ?? null;
        $type = $type instanceof PerformanceEventType ? $type : PerformanceEventType::tryFrom((string) $type);

        if ($type !== null && ! $type->hasContestResult()) {
            $data['result'] = PerformanceResult::NotApplicable;
        }

        return $data;
    }
}
