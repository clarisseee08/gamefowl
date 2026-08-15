<?php

declare(strict_types=1);

namespace Tests\Feature\Reports;

use App\Enums\BroodcockClass;
use App\Models\Broodcock;
use App\Models\MortalityRecord;
use App\Models\User;
use App\Reports\MortalityReport;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class MortalityReportTest extends TestCase
{
    use RefreshDatabase;

    private function report(array $filters = []): MortalityReport
    {
        return app(MortalityReport::class)->withFilters($filters);
    }

    /** A dead bird plus its death row. A bird can only die once, so each needs its own. */
    private function death(string $date, string $cause, array $bird = [], array $record = []): MortalityRecord
    {
        return MortalityRecord::factory()->create([
            'broodcock_id' => Broodcock::factory()->deceased()->create($bird)->id,
            'date_of_death' => $date,
            'cause_of_death' => $cause,
            ...$record,
        ]);
    }

    // -----------------------------------------------------------------
    // By-cause percentages
    // -----------------------------------------------------------------

    public function test_by_cause_percentages_sum_to_one_hundred(): void
    {
        $this->death('2026-03-01', 'Disease');
        $this->death('2026-03-02', 'Disease');
        $this->death('2026-03-03', 'Injury');

        $byCause = $this->report()->byCause();

        $this->assertEqualsWithDelta(100.0, $byCause->sum('percentage'), 0.5);
        $this->assertSame(3, $byCause->sum('deaths'));
    }

    public function test_by_cause_percentages_use_the_filtered_total_not_every_death_ever(): void
    {
        // Inside the range: 2 Disease, 1 Injury.
        $this->death('2026-03-01', 'Disease');
        $this->death('2026-03-02', 'Disease');
        $this->death('2026-03-03', 'Injury');

        // Outside it: ten more Disease deaths that must not touch the maths.
        for ($i = 1; $i <= 10; $i++) {
            $this->death('2025-01-'.str_pad((string) $i, 2, '0', STR_PAD_LEFT), 'Disease');
        }

        $byCause = $this->report(['from' => '2026-03-01', 'to' => '2026-03-31'])->byCause();

        $disease = $byCause->firstWhere('cause', 'Disease');
        $injury = $byCause->firstWhere('cause', 'Injury');

        $this->assertSame(2, $disease['deaths']);
        // 2/3 of the FILTERED set, not 2/13 of every death on record.
        $this->assertEqualsWithDelta(66.7, $disease['percentage'], 0.1);
        $this->assertEqualsWithDelta(33.3, $injury['percentage'], 0.1);
        $this->assertEqualsWithDelta(100.0, $byCause->sum('percentage'), 0.5);
    }

    public function test_most_common_cause_leads_the_by_cause_breakdown(): void
    {
        $this->death('2026-03-01', 'Injury');
        $this->death('2026-03-02', 'Disease');
        $this->death('2026-03-03', 'Disease');

        $this->assertSame('Disease', $this->report()->byCause()->first()['cause']);
        $this->assertSame('Disease', $this->report()->summary()['Most Common Cause']);
    }
}
