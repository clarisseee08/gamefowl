<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\HealthRecordType;
use App\Models\Broodcock;
use App\Models\HealthRecord;
use App\Models\User;
use Database\Factories\HealthRecordFactory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Sixty health entries spread over the past eighteen months, built so the
 * vaccination schedule screen is populated on every axis it can filter on:
 * genuinely overdue follow-ups, follow-ups falling due inside the warning
 * window, future-scheduled ones, and historical one-off treatments.
 *
 * Rows are built with the factory's states but written with a single batched
 * INSERT - sixty individual saves would be sixty round trips to Tokyo.
 */
final class HealthRecordSeeder extends Seeder
{
    /** Follow-ups whose due date has already passed, by how many days. */
    private const OVERDUE_DAYS = [4, 11, 23, 47, 88, 141];

    /** Follow-ups landing inside the 30-day warning window. */
    private const DUE_SOON_DAYS = [2, 5, 9, 14, 18, 23, 27, 29];

    private const COLUMNS = [
        'broodcock_id', 'record_type', 'product_name', 'dosage', 'checkup_date',
        'next_due_date', 'condition', 'remarks', 'recorded_by',
        'created_at', 'updated_at',
    ];

    public function run(): void
    {
        if (HealthRecord::withTrashed()->exists()) {
            return;
        }

        /** @var list<int> $birds */
        $birds = Broodcock::query()->orderBy('id')->pluck('id')->all();

        if ($birds === []) {
            return;
        }

        $recorders = User::query()
            ->whereIn('role', ['owner', 'staff'])
            ->orderBy('id')
            ->pluck('id')
            ->all();

        $rows = [];
        $bird = 0;

        // Overdue follow-ups - the ones the compliance report must shout about.
        foreach (self::OVERDUE_DAYS as $late) {
            $rows[] = $this->row(
                HealthRecord::factory()->overdue($late),
                $birds[$bird++ % count($birds)],
                $recorders,
            );
        }

        // Due inside the warning window.
        foreach (self::DUE_SOON_DAYS as $inDays) {
            $rows[] = $this->row(
                HealthRecord::factory()->dueSoon($inDays),
                $birds[$bird++ % count($birds)],
                $recorders,
            );
        }

        // Comfortably scheduled vaccinations and dewormings - the healthy case,
        // so the screen is not made entirely of warnings.
        for ($i = 0; $i < 12; $i++) {
            $checkup = today()->subDays(20 + ($i * 9));
            $rows[] = $this->row(
                HealthRecord::factory()->vaccination(),
                $birds[$bird++ % count($birds)],
                $recorders,
                [
                    'checkup_date' => $checkup->toDateString(),
                    'next_due_date' => today()->addDays(38 + ($i * 11))->toDateString(),
                    'dosage' => '0.5 ml',
                ],
            );
        }

        for ($i = 0; $i < 8; $i++) {
            $checkup = today()->subDays(30 + ($i * 13));
            $rows[] = $this->row(
                HealthRecord::factory()->deworming(),
                $birds[$bird++ % count($birds)],
                $recorders,
                [
                    'checkup_date' => $checkup->toDateString(),
                    'next_due_date' => today()->addDays(45 + ($i * 17))->toDateString(),
                    'dosage' => '1 ml',
                ],
            );
        }

        // Historical one-off entries, walked back across eighteen months.
        // Non-recurring types carry no next_due_date, which is exactly what
        // HealthRecordType::expectsNextDueDate() says they should do.
        $oneOffTypes = [
            HealthRecordType::Checkup,
            HealthRecordType::Treatment,
            HealthRecordType::Medication,
        ];

        for ($i = 0; $i < 26; $i++) {
            $rows[] = $this->row(
                HealthRecord::factory()->ofType($oneOffTypes[$i % 3]),
                $birds[$bird++ % count($birds)],
                $recorders,
                ['checkup_date' => today()->subDays(30 + ($i * 20))->toDateString()],
            );
        }

        // One statement for all sixty rows.
        DB::table('health_records')->insert($rows);
    }

    /**
     * @param  list<int>  $recorders
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function row(HealthRecordFactory $factory, int $broodcockId, array $recorders, array $overrides = []): array
    {
        $now = now();

        // broodcock_id and recorded_by are passed in so the factory's related
        // factories are never resolved - make() must not touch the database.
        $attributes = $factory->make(array_merge($overrides, [
            'broodcock_id' => $broodcockId,
            'recorded_by' => $recorders === [] ? null : $recorders[$broodcockId % count($recorders)],
        ]))->getAttributes();

        $attributes['created_at'] = $now;
        $attributes['updated_at'] = $now;

        $row = [];

        foreach (self::COLUMNS as $column) {
            $row[$column] = $attributes[$column] ?? null;
        }

        return $row;
    }
}
