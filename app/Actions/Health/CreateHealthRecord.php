<?php

declare(strict_types=1);

namespace App\Actions\Health;

use App\Models\HealthRecord;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Writes a new health record.
 *
 * The acting user is stamped here from the authenticated User object, never
 * taken from request input - so a forged `recorded_by` field cannot attribute
 * a treatment to someone who did not perform it.
 */
final class CreateHealthRecord
{
    /** @param array<string, mixed> $data Already validated by StoreHealthRecordRequest. */
    public function handle(array $data, User $actor): HealthRecord
    {
        return DB::transaction(function () use ($data, $actor): HealthRecord {
            unset($data['recorded_by']);

            return HealthRecord::create([
                ...$data,
                'recorded_by' => $actor->id,
            ]);
        });
    }
}
