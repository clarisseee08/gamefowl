<?php

declare(strict_types=1);

namespace Tests\Feature\Reports;

use App\Models\BreedingRecord;
use App\Models\Broodcock;
use App\Reports\BreedingPerformanceReport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * The number this report exists to get right is the WEIGHTED aggregate rate.
 * Everything else here is scaffolding around
 * test_aggregate_rates_are_weighted_by_eggs_set_not_averaged_per_mating.
 */
final class BreedingPerformanceReportTest extends TestCase
{
    use RefreshDatabase;

    private function report(array $filters = []): BreedingPerformanceReport
    {
        return (new BreedingPerformanceReport)->withFilters($filters);
    }

    /** @return array{0: Broodcock, 1: Broodcock} */
    private function pair(string $bloodline = 'Sweater'): array
    {
        return [
            Broodcock::factory()->male()->bloodline($bloodline)->create(),
            Broodcock::factory()->female()->create(),
        ];
    }

    private function mating(Broodcock $sire, Broodcock $dam, int $set, int $fertile, int $hatched, string $date = '2026-03-01'): BreedingRecord
    {
        return BreedingRecord::factory()->create([
            'sire_id' => $sire->id,
            'dam_id' => $dam->id,
            'mating_date' => $date,
            'eggs_set' => $set,
            'eggs_fertile' => $fertile,
            'eggs_hatched' => $hatched,
            'offspring_count' => 0,
        ]);
    }

    /** @return array{0: mixed, 1: int} */
    private function countQueries(callable $callback): array
    {
        DB::flushQueryLog();
        DB::enableQueryLog();

        $result = $callback();

        $count = count(DB::getQueryLog());
        DB::disableQueryLog();

        return [$result, $count];
    }

    // =================================================================
    // THE test.
    // =================================================================

    /**
     * A 2-egg clutch and a 100-egg clutch must NOT count equally.
     *
     * Naive AVG(fertility_rate) reports (100 + 50) / 2 = 75.0%. The farm
     * actually set 102 eggs and got 52 fertile, which is 50.98%. Anyone acting
     * on 75% would keep a pair that is barely breaking even.
     */
    public function test_aggregate_rates_are_weighted_by_eggs_set_not_averaged_per_mating(): void
    {
        [$sire, $dam] = $this->pair();

        // 2 set / 2 fertile = 100% on its own.
        $tiny = $this->mating($sire, $dam, set: 2, fertile: 2, hatched: 2);
        // 100 set / 50 fertile = 50% on its own.
        $large = $this->mating($sire, $dam, set: 100, fertile: 50, hatched: 25);

        // Guard the fixture itself: the per-record rates really are 100 and 50,
        // so the naive mean really would be 75.
        $this->assertSame(100.0, $tiny->fertilityRate());
        $this->assertSame(50.0, $large->fertilityRate());
        $naiveMean = ($tiny->fertilityRate() + $large->fertilityRate()) / 2;
        $this->assertSame(75.0, $naiveMean);

        $row = $this->report()->rows()->sole();

        // 52 / 102 = 50.98%.
        $this->assertSame(102, $row['eggs_set']);
        $this->assertSame(52, $row['eggs_fertile']);
        $this->assertEqualsWithDelta(50.98, $row['fertility_rate'], 0.01);
        $this->assertNotEqualsWithDelta(
            $naiveMean,
            $row['fertility_rate'],
            0.01,
            'The pair fertility rate equals the unweighted mean of the per-mating rates. '.
            'It must be SUM(eggs_fertile) / SUM(eggs_set), not AVG(rate).'
        );

        // Hatch rate is hatched / FERTILE: 27 / 52 = 51.92%. The naive mean of
        // 100% and 50% would again be 75%.
        $this->assertSame(27, $row['eggs_hatched']);
        $this->assertEqualsWithDelta(51.92, $row['hatch_rate'], 0.01);

        // The summary tiles must agree with the table, or the PDF contradicts
        // itself on its own front page.
        $summary = $this->report()->summary();
        $this->assertEqualsWithDelta(50.98, $summary['Overall Fertility Rate'], 0.01);
        $this->assertEqualsWithDelta(51.92, $summary['Overall Hatch Rate'], 0.01);
        $this->assertSame(2, $summary['Total Matings']);
        $this->assertSame(102, $summary['Total Eggs Set']);
    }

    /** The same weighting must survive the roll-up to bloodline level. */
    public function test_bloodline_rates_are_weighted_across_different_pairs(): void
    {
        [$sireA, $damA] = $this->pair('Kelso');
        $damB = Broodcock::factory()->female()->create();

        // Two DIFFERENT pairs of the same bloodline, one tiny and one large.
        $this->mating($sireA, $damA, set: 2, fertile: 2, hatched: 2);
        $this->mating($sireA, $damB, set: 100, fertile: 50, hatched: 25);

        $bloodline = $this->report()->rowsByBloodline()->sole();

        $this->assertSame('Kelso', $bloodline['bloodline']);
        $this->assertSame(2, $bloodline['pairs']);
        $this->assertSame(2, $bloodline['matings']);
        $this->assertSame(102, $bloodline['eggs_set']);
        $this->assertEqualsWithDelta(50.98, $bloodline['fertility_rate'], 0.01);
        $this->assertNotEqualsWithDelta(75.0, $bloodline['fertility_rate'], 0.01);
    }

    // =================================================================
    // Grouping
    // =================================================================

    public function test_the_same_pair_is_one_row_however_many_times_it_was_mated(): void
    {
        [$sire, $dam] = $this->pair();

        $this->mating($sire, $dam, set: 10, fertile: 8, hatched: 6, date: '2026-01-10');
        $this->mating($sire, $dam, set: 20, fertile: 15, hatched: 10, date: '2026-02-10');
        $this->mating($sire, $dam, set: 30, fertile: 27, hatched: 24, date: '2026-03-10');

        $rows = $this->report()->rows();

        $this->assertCount(1, $rows, 'Three matings of one pair must collapse into one row.');
        $this->assertSame(3, $rows[0]['matings']);
        $this->assertSame(60, $rows[0]['eggs_set']);
        $this->assertSame(50, $rows[0]['eggs_fertile']);
        $this->assertSame(40, $rows[0]['eggs_hatched']);
    }

    public function test_a_sire_mated_to_two_dams_produces_two_rows(): void
    {
        [$sire, $damA] = $this->pair();
        $damB = Broodcock::factory()->female()->create();

        $this->mating($sire, $damA, set: 10, fertile: 5, hatched: 5);
        $this->mating($sire, $damB, set: 10, fertile: 9, hatched: 9);

        $rows = $this->report()->rows();

        $this->assertCount(2, $rows);
        $this->assertSame([1, 1], $rows->pluck('matings')->all());
    }

    public function test_each_row_names_the_pair_with_band_numbers_and_the_sires_bloodline(): void
    {
        $sire = Broodcock::factory()->male()->bloodline('Hatch')->create(['name' => 'Sultan', 'band_number' => 'AB-1234']);
        $dam = Broodcock::factory()->female()->bloodline('Grey')->create(['name' => 'Maria', 'band_number' => 'CD-5678']);

        $this->mating($sire, $dam, set: 8, fertile: 6, hatched: 4);

        $row = $this->report()->rows()->sole();

        $this->assertSame('Sultan (AB-1234)', $row['sire']);
        $this->assertSame('Maria (CD-5678)', $row['dam']);
        $this->assertSame('Hatch', $row['bloodline'], 'The bloodline reported is the SIRE\'s.');
    }

    public function test_an_unbanded_parent_is_labelled_rather_than_left_blank(): void
    {
        $sire = Broodcock::factory()->male()->unbanded()->create(['name' => 'Bagsik']);
        $dam = Broodcock::factory()->female()->create(['name' => 'Ligaya', 'band_number' => 'ZZ-0001']);

        $this->mating($sire, $dam, set: 4, fertile: 2, hatched: 1);

        $this->assertSame('Bagsik (Not yet banded)', $this->report()->rows()->sole()['sire']);
    }

    // =================================================================
    // Division guards
    // =================================================================

    public function test_a_pair_with_no_eggs_set_reports_null_rates_not_zero_percent(): void
    {
        [$sire, $dam] = $this->pair();

        $this->mating($sire, $dam, set: 0, fertile: 0, hatched: 0);

        $row = $this->report()->rows()->sole();

        $this->assertSame(0, $row['eggs_set']);
        $this->assertNull($row['fertility_rate'], '"No eggs were set" is an unknown fertility rate, not a 0% one.');
        $this->assertNull($row['hatch_rate']);

        $summary = $this->report()->summary();
        $this->assertNull($summary['Overall Fertility Rate']);
        $this->assertNull($summary['Overall Hatch Rate']);
        $this->assertSame(1, $summary['Total Matings'], 'The mating still happened and must still be counted.');

        $this->assertNull($this->report()->rowsByBloodline()->sole()['fertility_rate']);
    }

    public function test_a_pair_whose_eggs_were_all_infertile_has_a_zero_fertility_rate_but_an_unknown_hatch_rate(): void
    {
        [$sire, $dam] = $this->pair();

        // 20 eggs set, none fertile. Fertility genuinely IS 0% - that is a
        // measured result. Hatch rate divides by fertile eggs, so it is unknown.
        $this->mating($sire, $dam, set: 20, fertile: 0, hatched: 0);

        $row = $this->report()->rows()->sole();

        $this->assertSame(0.0, $row['fertility_rate']);
        $this->assertNull($row['hatch_rate']);
    }

    public function test_an_empty_report_is_safe_and_reports_nothing_rather_than_zero(): void
    {
        $report = $this->report();

        $this->assertTrue($report->rows()->isEmpty());
        $this->assertTrue($report->rowsByBloodline()->isEmpty());

        $summary = $report->summary();
        $this->assertSame(0, $summary['Total Matings']);
        $this->assertSame(0, $summary['Total Eggs Set']);
        $this->assertNull($summary['Overall Fertility Rate']);
        $this->assertNull($summary['Overall Hatch Rate']);
    }

    // =================================================================
    // Filters
    // =================================================================

    public function test_the_date_filters_apply_to_the_mating_date(): void
    {
        [$sire, $dam] = $this->pair();

        $this->mating($sire, $dam, set: 10, fertile: 10, hatched: 10, date: '2025-12-31');
        $this->mating($sire, $dam, set: 20, fertile: 4, hatched: 2, date: '2026-01-15');
        $this->mating($sire, $dam, set: 40, fertile: 40, hatched: 40, date: '2026-06-01');

        $row = $this->report(['from' => '2026-01-01', 'to' => '2026-01-31'])->rows()->sole();

        $this->assertSame(1, $row['matings']);
        $this->assertSame(20, $row['eggs_set']);
        $this->assertSame(20.0, $row['fertility_rate']);
    }

    public function test_the_bloodline_filter_matches_the_sires_bloodline(): void
    {
        [$sweaterSire, $damA] = $this->pair('Sweater');
        [$kelsoSire, $damB] = $this->pair('Kelso');

        $this->mating($sweaterSire, $damA, set: 10, fertile: 9, hatched: 8);
        $this->mating($kelsoSire, $damB, set: 10, fertile: 1, hatched: 0);

        $rows = $this->report(['bloodline' => 'Sweater'])->rows();

        $this->assertCount(1, $rows);
        $this->assertSame('Sweater', $rows[0]['bloodline']);
        $this->assertSame(90.0, $rows[0]['fertility_rate']);
        $this->assertSame(10, $this->report(['bloodline' => 'Sweater'])->summary()['Total Eggs Set']);
    }

    public function test_the_sire_and_dam_filters_narrow_to_one_bird_each(): void
    {
        [$sireA, $damA] = $this->pair();
        $sireB = Broodcock::factory()->male()->create();
        $damB = Broodcock::factory()->female()->create();

        $this->mating($sireA, $damA, set: 10, fertile: 10, hatched: 10);
        $this->mating($sireA, $damB, set: 20, fertile: 10, hatched: 5);
        $this->mating($sireB, $damA, set: 30, fertile: 3, hatched: 1);

        $this->assertCount(2, $this->report(['sire_id' => $sireA->id])->rows());
        $this->assertCount(2, $this->report(['dam_id' => $damA->id])->rows());

        $row = $this->report(['sire_id' => $sireA->id, 'dam_id' => $damB->id])->rows()->sole();
        $this->assertSame(20, $row['eggs_set']);
    }

    public function test_blank_filters_are_ignored_rather_than_matching_nothing(): void
    {
        [$sire, $dam] = $this->pair();
        $this->mating($sire, $dam, set: 10, fertile: 5, hatched: 5);

        $report = $this->report(['from' => '', 'to' => '  ', 'bloodline' => '', 'sire_id' => '', 'dam_id' => null]);

        $this->assertCount(1, $report->rows());
        $this->assertSame([], $report->appliedFilters());
        $this->assertSame('All matings on record', $report->filterSummary());
    }

    public function test_the_applied_filters_are_recorded_for_the_audit_row(): void
    {
        $report = $this->report(['from' => '2026-01-01', 'to' => '2026-06-30', 'bloodline' => 'Kelso', 'sire_id' => '7']);

        $this->assertSame(
            ['from' => '2026-01-01', 'to' => '2026-06-30', 'bloodline' => 'Kelso', 'sire_id' => 7],
            $report->appliedFilters()
        );
        $this->assertStringContainsString('2026-01-01', $report->filterSummary());
        $this->assertStringContainsString('Kelso', $report->filterSummary());
    }

    public function test_a_soft_deleted_mating_is_excluded(): void
    {
        [$sire, $dam] = $this->pair();

        $this->mating($sire, $dam, set: 10, fertile: 10, hatched: 10);
        $this->mating($sire, $dam, set: 90, fertile: 0, hatched: 0)->delete();

        $row = $this->report()->rows()->sole();

        $this->assertSame(1, $row['matings']);
        $this->assertSame(10, $row['eggs_set']);
        $this->assertSame(100.0, $row['fertility_rate']);
    }

    // =================================================================
    // The two by-bloodline implementations must never disagree
    // =================================================================

    public function test_folding_the_pair_rows_matches_the_sql_bloodline_aggregation(): void
    {
        foreach (['Sweater', 'Kelso', 'Grey'] as $bloodline) {
            foreach (range(1, 3) as $i) {
                [$sire, $dam] = $this->pair($bloodline);
                $this->mating($sire, $dam, set: $i * 7, fertile: $i * 4, hatched: $i * 2);
                $this->mating($sire, $dam, set: $i * 13, fertile: $i * 11, hatched: $i * 9);
            }
        }

        $report = $this->report();

        // The PDF builds its second table with foldByBloodline($rows) because
        // ReportController never hands it the report instance. If that fold
        // ever diverges from the SQL grouping, the PDF and any other consumer
        // would print different fertility rates for the same bloodline.
        $this->assertEquals(
            $report->rowsByBloodline()->all(),
            BreedingPerformanceReport::foldByBloodline($report->rows())->all(),
        );
    }

    // =================================================================
    // Performance
    // =================================================================

    public function test_the_query_count_does_not_grow_with_the_number_of_rows(): void
    {
        $build = function (int $pairs): void {
            foreach (range(1, $pairs) as $i) {
                [$sire, $dam] = $this->pair();
                $this->mating($sire, $dam, set: 10, fertile: 8, hatched: 6);
                $this->mating($sire, $dam, set: 12, fertile: 9, hatched: 7);
            }
        };

        $run = fn () => $this->countQueries(function (): void {
            $report = $this->report();
            $rows = $report->rows();
            $report->rowsByBloodline();
            $report->summary();
            // Touch every cell, which is where a lazy load would fire.
            $rows->each(fn (array $row) => implode('|', array_map(strval(...), $row)));
        });

        $build(2);
        [, $withTwo] = $run();

        $build(20);
        [, $withTwentyTwo] = $run();

        $this->assertSame(
            $withTwo,
            $withTwentyTwo,
            "2 pairs cost {$withTwo} queries but 22 cost {$withTwentyTwo}. ".
            'The aggregation must happen in SQL - something is querying per row.'
        );

        // One query per grouping (pairs, bloodlines, totals) and no more.
        $this->assertSame(3, $withTwentyTwo);
    }

    public function test_repeated_calls_reuse_the_result_instead_of_requerying(): void
    {
        [$sire, $dam] = $this->pair();
        $this->mating($sire, $dam, set: 10, fertile: 8, hatched: 6);

        $report = $this->report();

        [, $queries] = $this->countQueries(function () use ($report): void {
            $report->rows();
            $report->rows();
            $report->rows();
        });

        $this->assertSame(1, $queries, 'rows() must be memoised; ReportController calls it and then the view reads it.');
    }

    // =================================================================
    // The PDF actually renders
    // =================================================================

    public function test_the_pdf_template_renders_both_tables(): void
    {
        [$sire, $dam] = $this->pair('Sweater');
        $sire->update(['name' => 'Sultan', 'band_number' => 'AB-1234']);

        $this->mating($sire, $dam, set: 2, fertile: 2, hatched: 2);
        $this->mating($sire, $dam, set: 100, fertile: 50, hatched: 25);

        $empty = Broodcock::factory()->male()->bloodline('Grey')->create();
        $this->mating($empty, $dam, set: 0, fertile: 0, hatched: 0);

        $report = $this->report();

        $html = view($report->pdfView(), [
            'title' => $report->title(),
            'subtitle' => $report->description(),
            'filterSummary' => $report->filterSummary(),
            'columns' => $report->columns(),
            'rows' => $report->rows(),
            'summary' => $report->summary(),
            'generatedAt' => now(),
            'generatedBy' => 'Test Owner',
        ])->render();

        $this->assertStringContainsString('By Pair', $html);
        $this->assertStringContainsString('By Bloodline', $html);
        $this->assertStringContainsString('Sultan (AB-1234)', $html);
        $this->assertStringContainsString('Sweater', $html);
        $this->assertStringContainsString('Grey', $html);

        // The weighted figure, not the naive 75.0%.
        $this->assertStringContainsString('51.0%', $html);
        $this->assertStringNotContainsString('75.0%', $html);

        // The zero-egg pair renders an em dash, never "0.0%".
        $this->assertStringContainsString('—', $html);

        // No Tailwind or CSS 3 leaked into a dompdf template.
        foreach (['oklch(', 'display:flex', 'display: flex', 'grid-template', 'var(--'] as $forbidden) {
            $this->assertStringNotContainsString($forbidden, $html, "The PDF template must be CSS 2.1 only; found {$forbidden}.");
        }
    }
}
