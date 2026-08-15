<?php

declare(strict_types=1);

namespace App\Actions\Health;

use App\Models\HealthRecord;
use Illuminate\Support\Facades\DB;

/**
 * Updates an existing health record.
 *
 * `recorded_by` is stripped rather than re-stamped: it records who originally
 * performed the treatment, which editing the row later does not change.
 */
final class UpdateHealthRecord
{
    /** @param array<string, mixed> $data Already validated by UpdateHealthRecordRequest. */
    public function handle(HealthRecord $record, array $data): HealthRecord
    {
        return DB::transaction(function () use ($record, $data): HealthRecord {
            unset($data['recorded_by']);

            $record->update($data);

            return $record->refresh();
        });
    }
}
