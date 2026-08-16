<?php

declare(strict_types=1);

namespace Tests\Feature\Reports;

use App\Enums\BroodcockClass;
use App\Models\Broodcock;
use App\Models\MortalityRecord;
use App\Models\User;
use App\Reports\MortalityReport;
use App\Reports\ReportRegistry;
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

    // -----------------------------------------------------------------
    // Age at death
    // -----------------------------------------------------------------

    public function test_a_bird_with_no_hatch_date_shows_unknown_age_at_death(): void
    {
        $this->death('2026-03-01', 'Old age', ['date_hatched' => null, 'name' => 'Foundling']);

        $row = $this->report()->rows()->firstWhere('bird_name', 'Foundling');

        // "Unknown" is the truth. "0 mos" would invent a newborn.
        $this->assertSame('Unknown', $row['age_at_death']);
        $this->assertNotSame('0 mos', $row['age_at_death']);
        $this->assertNotSame(0, $row['age_at_death']);
    }

    public function test_a_bird_with_a_hatch_date_shows_a_real_age_at_death(): void
    {
        $this->death('2026-03-01', 'Disease', [
            'date_hatched' => '2024-03-01',
            'name' => 'Bantay',
        ]);

        $row = $this->report()->rows()->firstWhere('bird_name', 'Bantay');

        $this->assertSame('24 mos', $row['age_at_death']);
    }

    public function test_unknown_and_known_ages_coexist_without_crashing(): void
    {
        $this->death('2026-03-01', 'Disease', ['date_hatched' => null, 'name' => 'NoDate']);
        $this->death('2026-03-02', 'Disease', ['date_hatched' => '2025-03-02', 'name' => 'HasDate']);

        $rows = $this->report()->rows();

        $this->assertCount(2, $rows);
        $this->assertSame('Unknown', $rows->firstWhere('bird_name', 'NoDate')['age_at_death']);
        $this->assertSame('12 mos', $rows->firstWhere('bird_name', 'HasDate')['age_at_death']);
    }

    // -----------------------------------------------------------------
    // Filters
    // -----------------------------------------------------------------

    public function test_a_death_outside_the_date_range_is_genuinely_absent(): void
    {
        $this->death('2026-03-15', 'Disease', ['name' => 'InRange']);
        $this->death('2026-05-15', 'Disease', ['name' => 'AfterRange']);
        $this->death('2026-01-15', 'Disease', ['name' => 'BeforeRange']);

        $report = $this->report(['from' => '2026-03-01', 'to' => '2026-03-31']);
        $names = $report->rows()->pluck('bird_name');

        $this->assertContains('InRange', $names);
        $this->assertNotContains('AfterRange', $names);
        $this->assertNotContains('BeforeRange', $names);
        $this->assertSame(1, $report->totalDeaths());
    }

    public function test_the_cause_filter_matches_partially_and_case_insensitively(): void
    {
        $this->death('2026-03-01', 'Respiratory infection', ['name' => 'Sick']);
        $this->death('2026-03-02', 'Predator attack', ['name' => 'Bitten']);

        $names = $this->report(['cause' => 'respir'])->rows()->pluck('bird_name');

        $this->assertContains('Sick', $names);
        $this->assertNotContains('Bitten', $names);
    }

    public function test_the_bloodline_and_class_filters_narrow_the_register(): void
    {
        $this->death('2026-03-01', 'Disease', ['bloodline' => 'Sweater', 'class' => BroodcockClass::ClassA, 'name' => 'Keeper']);
        $this->death('2026-03-02', 'Disease', ['bloodline' => 'Kelso', 'class' => BroodcockClass::ClassA, 'name' => 'OtherLine']);
        $this->death('2026-03-03', 'Disease', ['bloodline' => 'Sweater', 'class' => BroodcockClass::Ordinary, 'name' => 'OtherClass']);

        $names = $this->report([
            'bloodline' => 'Sweater',
            'class' => BroodcockClass::ClassA->value,
        ])->rows()->pluck('bird_name');

        $this->assertSame(['Keeper'], $names->all());
    }

    public function test_the_filters_actually_applied_are_recorded_for_the_audit_row(): void
    {
        $report = $this->report(['from' => '2026-03-01', 'to' => '', 'cause' => ' Disease ', 'class' => null]);

        $this->assertSame(
            ['from' => '2026-03-01', 'cause' => 'Disease'],
            $report->appliedFilters()
        );
        $this->assertStringContainsString('Disease', $report->filterSummary());
    }

    // -----------------------------------------------------------------
    // Month grouping
    // -----------------------------------------------------------------

    public function test_month_grouping_buckets_correctly_across_a_year_boundary(): void
    {
        $this->death('2025-12-05', 'Disease');
        $this->death('2025-12-28', 'Injury');
        $this->death('2026-01-03', 'Disease');

        $byPeriod = $this->report(['from' => '2025-12-01', 'to' => '2026-01-31'])->byPeriod();

        $this->assertSame(['2025-12', '2026-01'], $byPeriod->pluck('period')->all());
        $this->assertSame([2, 1], $byPeriod->pluck('deaths')->all());
        $this->assertSame('December 2025', $byPeriod->first()['label']);
        $this->assertSame('January 2026', $byPeriod->last()['label']);
    }

    public function test_month_grouping_shows_a_quiet_month_as_zero_rather_than_omitting_it(): void
    {
        $this->death('2025-11-05', 'Disease');
        $this->death('2026-01-05', 'Disease');

        $byPeriod = $this->report(['from' => '2025-11-01', 'to' => '2026-01-31'])->byPeriod();

        $this->assertSame(['2025-11', '2025-12', '2026-01'], $byPeriod->pluck('period')->all());
        $this->assertSame(0, $byPeriod->firstWhere('period', '2025-12')['deaths']);
    }

    public function test_month_grouping_respects_the_other_filters(): void
    {
        $this->death('2025-12-05', 'Disease', ['bloodline' => 'Sweater']);
        $this->death('2025-12-06', 'Disease', ['bloodline' => 'Kelso']);

        $byPeriod = $this->report(['bloodline' => 'Sweater'])->byPeriod();

        $this->assertSame(1, $byPeriod->firstWhere('period', '2025-12')['deaths']);
    }

    // -----------------------------------------------------------------
    // The rate and its denominator
    // -----------------------------------------------------------------

    public function test_the_mortality_rate_divides_by_population_at_risk_not_by_the_death_count(): void
    {
        Broodcock::factory()->count(8)->create();          // alive, on farm
        $this->death('2026-03-01', 'Disease');
        $this->death('2026-03-02', 'Disease');             // 2 deaths in range

        $rate = $this->report(['from' => '2026-03-01', 'to' => '2026-03-31'])->mortalityRate();

        // 8 survivors + 2 deaths = 10 at risk; 2/10 = 20%.
        $this->assertSame(10, $rate['denominator']);
        $this->assertSame(2, $rate['deaths']);
        $this->assertEqualsWithDelta(20.0, $rate['percentage'], 0.01);
        $this->assertTrue($rate['is_proxy']);
    }

    public function test_the_rate_states_its_denominator_in_words(): void
    {
        Broodcock::factory()->count(3)->create();
        $this->death('2026-03-01', 'Disease');

        $rate = $this->report()->mortalityRate();

        $this->assertStringContainsString('population at risk', strtolower($rate['denominator_sentence']));
        $this->assertStringContainsString('4', $rate['denominator_sentence']);
        // The report must admit it is not a true average-flock-size rate.
        $this->assertStringContainsString('PROXY', $rate['caveat']);
        $this->assertStringContainsString('AVERAGE FLOCK SIZE', $rate['caveat']);
        $this->assertStringContainsString('proxy', strtolower($rate['label']));
    }

    public function test_the_rate_is_not_computable_when_there_is_no_flock_at_all(): void
    {
        $rate = $this->report()->mortalityRate();

        $this->assertSame(0, $rate['denominator']);
        $this->assertNull($rate['percentage']);
        $this->assertSame('Not computable', $rate['display']);
    }

    public function test_a_cause_filter_narrows_the_numerator_but_not_the_population(): void
    {
        Broodcock::factory()->count(6)->create();
        $this->death('2026-03-01', 'Disease');
        $this->death('2026-03-02', 'Injury');

        $rate = $this->report(['cause' => 'Disease'])->mortalityRate();

        // 1 disease death over 6 survivors + BOTH deaths - not 1/1 = 100%.
        $this->assertSame(1, $rate['deaths']);
        $this->assertSame(8, $rate['denominator']);
    }

    // -----------------------------------------------------------------
    // Summary tiles
    // -----------------------------------------------------------------

    public function test_the_summary_tiles_report_totals_this_month_cause_and_a_labelled_rate(): void
    {
        Broodcock::factory()->count(4)->create();
        $this->death(now()->startOfMonth()->addDay()->toDateString(), 'Heat stress');
        $this->death('2025-02-10', 'Disease');

        $summary = $this->report()->summary();

        $this->assertSame(2, $summary['Total Deaths in Range']);
        $this->assertSame(1, $summary['Deaths This Month']);
        $this->assertArrayHasKey('Mortality Rate (proxy)', $summary);
        $this->assertSame(4, count($summary));
    }

    // -----------------------------------------------------------------
    // N+1
    // -----------------------------------------------------------------

    public function test_the_query_count_does_not_grow_with_the_number_of_rows(): void
    {
        foreach (range(1, 3) as $i) {
            $this->death('2026-03-0'.$i, 'Disease');
        }

        $small = $this->countQueries(fn () => $this->report()->rows());
        $this->assertCount(3, $this->report()->rows());

        foreach (range(4, 9) as $i) {
            $this->death('2026-03-0'.$i, 'Disease');
        }

        $large = $this->countQueries(fn () => $this->report()->rows());

        $this->assertCount(9, $this->report()->rows());
        $this->assertSame($small, $large, 'rows() is N+1: the query count grew with the row count.');
        // The register itself plus the two eager loads. Nothing per row.
        $this->assertSame(3, $large);
    }

    public function test_rendering_every_row_touches_no_extra_query(): void
    {
        foreach (range(1, 5) as $i) {
            $this->death('2026-03-0'.$i, 'Disease', ['name' => 'Bird'.$i]);
        }

        $rows = $this->report()->rows();

        // Model::shouldBeStrict() is on in tests, so a missed eager load throws
        // rather than quietly issuing another round trip.
        $queries = $this->countQueries(function () use ($rows): void {
            foreach ($rows as $row) {
                $this->assertNotSame('', $row['recorded_by']);
                $this->assertNotSame('', $row['band_number']);
            }
        });

        $this->assertSame(0, $queries);
    }

    private function countQueries(callable $callback): int
    {
        $count = 0;
        DB::listen(function () use (&$count): void {
            $count++;
        });

        $callback();

        DB::flushQueryLog();

        return $count;
    }

    // -----------------------------------------------------------------
    // The report contract, and the PDF
    // -----------------------------------------------------------------

    public function test_the_report_satisfies_its_registry_contract(): void
    {
        $report = app(ReportRegistry::class)->make('mortality');

        $this->assertInstanceOf(MortalityReport::class, $report);
        $this->assertSame('mortality', $report->key());
        $this->assertSame('reports.pdf.mortality', $report->pdfView());
        $this->assertArrayHasKey('band_number', $report->columns());
        $this->assertArrayHasKey('age_at_death', $report->columns());
        $this->assertCount(10, $report->columns());
    }

    public function test_the_pdf_renders_and_states_its_denominator(): void
    {
        $user = User::factory()->staff()->create(['full_name' => 'Ka Ramon']);
        Broodcock::factory()->count(5)->create();
        $this->death('2025-12-20', 'Disease', ['date_hatched' => null, 'name' => 'Foundling'], ['recorded_by' => $user->id]);
        $this->death('2026-01-06', 'Predator attack', [], ['recorded_by' => $user->id]);

        $report = $this->report(['from' => '2025-12-01', 'to' => '2026-01-31']);

        $html = view($report->pdfView(), [
            'title' => $report->title(),
            'subtitle' => $report->description(),
            'filterSummary' => $report->filterSummary(),
            'columns' => $report->columns(),
            'rows' => $report->rows(),
            'summary' => $report->summary(),
            'generatedAt' => now(),
            'generatedBy' => $user->full_name,
        ])->render();

        $this->assertStringContainsString('Death Register', $html);
        $this->assertStringContainsString('Deaths by Cause', $html);
        $this->assertStringContainsString('Deaths by Month', $html);
        $this->assertStringContainsString('December 2025', $html);
        $this->assertStringContainsString('January 2026', $html);
        $this->assertStringContainsString('Unknown', $html);
        // The denominator has to be legible to a panel member, in words.
        $this->assertStringContainsString('population at risk', strtolower($html));
        $this->assertStringContainsString('PROXY', $html);
        // Tailwind and CSS 3 must never reach dompdf.
        $this->assertStringNotContainsString('oklch(', $html);
        $this->assertStringNotContainsString('display: flex', $html);
        $this->assertStringNotContainsString('display:flex', $html);

        $pdf = Pdf::loadHTML($html)->setPaper('a4', 'landscape')->output();

        $this->assertStringStartsWith('%PDF', $pdf);
        $this->assertGreaterThan(1000, strlen($pdf));
    }

    public function test_the_pdf_renders_when_there_are_no_deaths_at_all(): void
    {
        $report = $this->report(['from' => '2026-03-01', 'to' => '2026-03-31']);

        $html = view($report->pdfView(), [
            'title' => $report->title(),
            'subtitle' => $report->description(),
            'filterSummary' => $report->filterSummary(),
            'columns' => $report->columns(),
            'rows' => $report->rows(),
            'summary' => $report->summary(),
            'generatedAt' => now(),
            'generatedBy' => null,
        ])->render();

        $this->assertStringContainsString('No deaths were recorded for these filters.', $html);
        $this->assertStringStartsWith('%PDF', Pdf::loadHTML($html)->output());
    }
}
