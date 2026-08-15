<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\PerformanceResult;
use App\Models\Broodcock;
use App\Models\PerformanceRecord;
use App\Models\User;
use Database\Factories\PerformanceRecordFactory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Forty performance entries: twenty-two contests (sparring and derby) with a
 * realistic 12-7-3 win-loss-draw split, plus conditioning sessions and
 * weigh-ins.
 *
 * Conditioning and weigh-in carry result `na` - they have no winner, and
 * PerformanceResult::countsTowardRecord() keeps them out of the win-rate
 * denominator. The factory's conditioning() and weighIn() states already
 * enforce that, so this seeder never sets the result by hand for them.
 */
final class PerformanceRecordSeeder extends Seeder
{
    private const COLUMNS = [
        'broodcock_id', 'event_date', 'event_type', 'weight', 'result',
        'duration_seconds', 'rating', 'remarks', 'recorded_by',
        'created_at', 'updated_at',
    ];

    /** Cocks old enough to be worked. Hens and chicks never appear here. */
    private const CONDITIONED_COCKS = [
        'Haring Agila', 'Dagitab', 'Sagisag', 'Agila', 'Kidlat', 'Sigaw',
        'Bagsik', 'Lakan', 'Haribon',
    ];

    public function run(): void
    {
        if (PerformanceRecord::withTrashed()->exists()) {
            return;
        }

        /** @var array<string, int> $birds */
        $birds = Broodcock::query()->pluck('id', 'name')->all();

        if ($birds === []) {
            return;
        }

        $recorder = User::query()->where('role', 'staff')->value('id');
        $rows = [];

        foreach ($this->contests() as $contest) {
            $factory = PerformanceRecord::factory();
            $factory = $contest['type'] === 'derby' ? $factory->derby() : $factory->sparring();

            $factory = match ($contest['result']) {
                PerformanceResult::Win => $factory->win(),
                PerformanceResult::Loss => $factory->loss(),
                default => $factory->draw(),
            };

            $rows[] = $this->row(
                $factory->rating($contest['rating']),
                $birds[$contest['bird']],
                $recorder,
                [
                    'event_date' => $contest['date'],
                    'weight' => $contest['weight'],
                    'duration_seconds' => $contest['seconds'],
                    'remarks' => $contest['remarks'] ?? null,
                ],
            );
        }

        // Ten conditioning sessions in the run-up to the last two derbies.
        for ($i = 0; $i < 10; $i++) {
            $name = self::CONDITIONED_COCKS[$i % count(self::CONDITIONED_COCKS)];

            $rows[] = $this->row(
                PerformanceRecord::factory()->conditioning()->rating(3 + ($i % 3)),
                $birds[$name],
                $recorder,
                [
                    'event_date' => today()->subDays(21 + ($i * 17))->toDateString(),
                    'weight' => round(2.05 + ($i % 5) * 0.11, 2),
                    'remarks' => 'Morning scratch work and cord training.',
                ],
            );
        }

        // Eight weigh-ins, the fight-morning record.
        for ($i = 0; $i < 8; $i++) {
            $name = self::CONDITIONED_COCKS[($i + 3) % count(self::CONDITIONED_COCKS)];

            $rows[] = $this->row(
                PerformanceRecord::factory()->weighIn(),
                $birds[$name],
                $recorder,
                [
                    'event_date' => today()->subDays(14 + ($i * 29))->toDateString(),
                    'weight' => round(2.12 + ($i % 6) * 0.09, 2),
                    'remarks' => null,
                ],
            );
        }

        DB::table('performance_records')->insert($rows);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function row(PerformanceRecordFactory $factory, int $broodcockId, ?int $recorder, array $overrides): array
    {
        $now = now();

        $attributes = $factory->make(array_merge($overrides, [
            'broodcock_id' => $broodcockId,
            'recorded_by' => $recorder,
        ]))->getAttributes();

        $attributes['created_at'] = $now;
        $attributes['updated_at'] = $now;

        $row = [];

        foreach (self::COLUMNS as $column) {
            $row[$column] = $attributes[$column] ?? null;
        }

        return $row;
    }

    /**
     * Twenty-two contests: 12 wins, 7 losses, 3 draws.
     *
     * @return list<array<string, mixed>>
     */
    private function contests(): array
    {
        $win = PerformanceResult::Win;
        $loss = PerformanceResult::Loss;
        $draw = PerformanceResult::Draw;

        return [
            ['bird' => 'Haribon', 'date' => '2024-09-14', 'type' => 'sparring', 'result' => $win, 'rating' => 4, 'weight' => 2.35, 'seconds' => 180],
            ['bird' => 'Lakan', 'date' => '2024-10-05', 'type' => 'sparring', 'result' => $win, 'rating' => 4, 'weight' => 2.28, 'seconds' => 210],
            ['bird' => 'Bagsik', 'date' => '2024-11-16', 'type' => 'sparring', 'result' => $loss, 'rating' => 2, 'weight' => 2.44, 'seconds' => 150],
            ['bird' => 'Agila', 'date' => '2024-12-07', 'type' => 'derby', 'result' => $win, 'rating' => 5, 'weight' => 2.31, 'seconds' => 95, 'remarks' => 'Cabanatuan 4-cock derby. Won on the first buckle.'],
            ['bird' => 'Haribon', 'date' => '2025-01-11', 'type' => 'derby', 'result' => $loss, 'rating' => 3, 'weight' => 2.37, 'seconds' => 240],
            ['bird' => 'Kidlat', 'date' => '2025-02-08', 'type' => 'sparring', 'result' => $win, 'rating' => 4, 'weight' => 2.19, 'seconds' => 165],
            ['bird' => 'Sigaw', 'date' => '2025-03-01', 'type' => 'sparring', 'result' => $draw, 'rating' => 3, 'weight' => 2.05, 'seconds' => 300],
            ['bird' => 'Lakan', 'date' => '2025-03-29', 'type' => 'derby', 'result' => $win, 'rating' => 5, 'weight' => 2.30, 'seconds' => 120],
            ['bird' => 'Bagsik', 'date' => '2025-04-19', 'type' => 'sparring', 'result' => $loss, 'rating' => 2, 'weight' => 2.41, 'seconds' => 135],
            ['bird' => 'Agila', 'date' => '2025-05-10', 'type' => 'sparring', 'result' => $win, 'rating' => 5, 'weight' => 2.33, 'seconds' => 175],
            ['bird' => 'Kidlat', 'date' => '2025-06-21', 'type' => 'derby', 'result' => $loss, 'rating' => 2, 'weight' => 2.22, 'seconds' => 88],
            ['bird' => 'Sagisag', 'date' => '2025-08-02', 'type' => 'sparring', 'result' => $win, 'rating' => 4, 'weight' => 2.16, 'seconds' => 190],
            ['bird' => 'Haring Agila', 'date' => '2025-09-13', 'type' => 'sparring', 'result' => $win, 'rating' => 5, 'weight' => 2.27, 'seconds' => 205, 'remarks' => 'Outstanding cutting. Marked for the December derby.'],
            ['bird' => 'Dagitab', 'date' => '2025-10-04', 'type' => 'sparring', 'result' => $draw, 'rating' => 3, 'weight' => 2.24, 'seconds' => 285],
            ['bird' => 'Sagisag', 'date' => '2025-11-08', 'type' => 'derby', 'result' => $loss, 'rating' => 2, 'weight' => 2.18, 'seconds' => 110],
            ['bird' => 'Haring Agila', 'date' => '2025-12-06', 'type' => 'derby', 'result' => $win, 'rating' => 5, 'weight' => 2.29, 'seconds' => 76, 'remarks' => 'Nueva Ecija 5-cock derby. Cleanest win of the season.'],
            ['bird' => 'Sigaw', 'date' => '2026-01-17', 'type' => 'sparring', 'result' => $loss, 'rating' => 2, 'weight' => 2.08, 'seconds' => 160],
            ['bird' => 'Dagitab', 'date' => '2026-02-14', 'type' => 'sparring', 'result' => $win, 'rating' => 4, 'weight' => 2.26, 'seconds' => 195],
            ['bird' => 'Haring Agila', 'date' => '2026-03-21', 'type' => 'sparring', 'result' => $win, 'rating' => 5, 'weight' => 2.31, 'seconds' => 215],
            ['bird' => 'Kidlat', 'date' => '2026-04-25', 'type' => 'sparring', 'result' => $draw, 'rating' => 3, 'weight' => 2.20, 'seconds' => 290],
            ['bird' => 'Dagitab', 'date' => '2026-05-30', 'type' => 'derby', 'result' => $loss, 'rating' => 3, 'weight' => 2.25, 'seconds' => 130],
            ['bird' => 'Haring Agila', 'date' => '2026-07-11', 'type' => 'derby', 'result' => $win, 'rating' => 5, 'weight' => 2.30, 'seconds' => 84, 'remarks' => 'Second derby win. Retired to the breeding pen after this.'],
        ];
    }
}
