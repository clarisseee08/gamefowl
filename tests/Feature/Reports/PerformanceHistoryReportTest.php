<?php

declare(strict_types=1);

namespace Tests\Feature\Reports;

use App\Models\Broodcock;
use App\Models\PerformanceRecord;
use App\Models\User;
use App\Reports\PerformanceHistoryReport;
use App\Reports\ReportRegistry;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * The win rate is the figure a buyer decides on, so its denominator is tested
 * directly and repeatedly rather than inferred from the page that prints it.
 */
final class PerformanceHistoryReportTest extends TestCase
{
    use RefreshDatabase;

    private function report(array $filters = []): PerformanceHistoryReport
    {
        return (new PerformanceHistoryReport)->withFilters($filters);
    }

    /** The per-bird row for a given bird name. */
    private function birdRow(PerformanceHistoryReport $report, string $name): array
    {
        $row = $report->perBirdSummary()->first(
            fn (array $r): bool => str_starts_with($r['bird'], $name)
        );

        $this->assertNotNull($row, "Expected a per-bird summary row for {$name}.");

        return $row;
    }

    // -----------------------------------------------------------------
    // The dilution rule - the single most important behaviour here
    // -----------------------------------------------------------------

    public function test_non_contest_events_do_not_dilute_the_win_rate(): void
    {
        $bird = Broodcock::factory()->create(['name' => 'Thor']);

        PerformanceRecord::factory()->win()->for($bird)->create();
        PerformanceRecord::factory()->loss()->for($bird)->create();

        $before = $this->birdRow($this->report(), 'Thor');
        $this->assertSame(50.0, $before['win_rate']);

        // Three weigh-ins and two conditioning sessions. Nobody wins a weigh-in.
        // If these reached the denominator the win rate would read 1/6 = 16.7%,
        // and a sound bird would look like a loser on paper.
        PerformanceRecord::factory()->count(3)->weighIn()->for($bird)->create();
        PerformanceRecord::factory()->count(2)->conditioning()->for($bird)->create();

        $after = $this->birdRow($this->report(), 'Thor');

        $this->assertSame(50.0, $after['win_rate'], 'Weigh-ins and conditioning sessions must never enter the win-rate denominator.');
        $this->assertSame(2, $after['contests']);
        $this->assertSame(7, $after['events'], 'All seven events are still reported - they are excluded from the RATE, not from the record.');
        $this->assertSame(1, $after['wins']);
        $this->assertSame(1, $after['losses']);
    }

    public function test_a_bird_with_only_non_contest_events_has_a_null_win_rate_not_zero(): void
    {
        $bird = Broodcock::factory()->create(['name' => 'Rocky']);

        PerformanceRecord::factory()->count(4)->conditioning()->for($bird)->create();
        PerformanceRecord::factory()->count(2)->weighIn()->for($bird)->create();

        $row = $this->birdRow($this->report(), 'Rocky');

        $this->assertNull($row['win_rate'], '"Never competed" and "lost every fight" are different claims about a bird.');
        $this->assertNotSame(0, $row['win_rate']);
        $this->assertNotSame(0.0, $row['win_rate']);
        $this->assertSame(0, $row['contests']);
        $this->assertSame(6, $row['events']);
    }

    public function test_a_bird_that_lost_every_contest_has_a_zero_win_rate_not_null(): void
    {
        $bird = Broodcock::factory()->create(['name' => 'Bruno']);

        PerformanceRecord::factory()->count(3)->loss()->for($bird)->create();

        $row = $this->birdRow($this->report(), 'Bruno');

        // The other half of the same rule: 0% is a real, earned figure.
        $this->assertSame(0.0, $row['win_rate']);
        $this->assertSame(3, $row['contests']);
    }

    public function test_draws_count_as_contests_but_not_as_wins(): void
    {
        $bird = Broodcock::factory()->create(['name' => 'Duke']);

        PerformanceRecord::factory()->win()->for($bird)->create();
        PerformanceRecord::factory()->draw()->for($bird)->create();

        $row = $this->birdRow($this->report(), 'Duke');

        $this->assertSame(2, $row['contests']);
        $this->assertSame(1, $row['draws']);
        $this->assertSame(50.0, $row['win_rate']);
    }

    // -----------------------------------------------------------------
    // Ratings
    // -----------------------------------------------------------------

    public function test_the_average_rating_ignores_unrated_events(): void
    {
        $bird = Broodcock::factory()->create(['name' => 'Ace']);

        PerformanceRecord::factory()->win()->rating(5)->for($bird)->create();
        PerformanceRecord::factory()->win()->rating(3)->for($bird)->create();
        PerformanceRecord::factory()->count(2)->loss()->unrated()->for($bird)->create();

        $row = $this->birdRow($this->report(), 'Ace');

        // (5 + 3) / 2 = 4.0, not (5 + 3 + 0 + 0) / 4 = 2.0.
        $this->assertSame(4.0, $row['average_rating'], 'An unrated event is absent from the average, not a zero in it.');
    }

    public function test_a_bird_with_no_ratings_at_all_has_a_null_average_rating(): void
    {
        $bird = Broodcock::factory()->create(['name' => 'Ghost']);

        PerformanceRecord::factory()->count(3)->win()->unrated()->for($bird)->create();

        $this->assertNull($this->birdRow($this->report(), 'Ghost')['average_rating']);
    }

    // -----------------------------------------------------------------
    // Rows and columns
    // -----------------------------------------------------------------

    public function test_rows_carry_every_declared_column(): void
    {
        $staff = User::factory()->staff()->create(['full_name' => 'Maria Santos']);
        $bird = Broodcock::factory()->create([
            'name' => 'Titan',
            'band_number' => 'AA-1234',
            'bloodline' => 'Sweater',
        ]);

        PerformanceRecord::factory()->for($bird)->sparring()->win()->rating(4)->create([
            'event_date' => '2026-03-09',
            'weight' => 2.15,
            'duration_seconds' => 185,
            'recorded_by' => $staff->id,
        ]);

        $report = $this->report();
        $row = $report->rows()->sole();

        $this->assertSame(array_keys($report->columns()), array_keys($row));
        $this->assertSame('09 Mar 2026', $row['event_date']);
        $this->assertSame('AA-1234', $row['band_number']);
        $this->assertSame('Titan', $row['bird_name']);
        $this->assertSame('Sweater', $row['bloodline']);
        $this->assertSame('Sparring', $row['event_type']);
        $this->assertSame('Win', $row['result']);
        $this->assertSame('2.15', $row['weight']);
        // durationLabel() - 185 seconds is 3:05, not "185".
        $this->assertSame('3:05', $row['duration']);
        $this->assertSame('4', $row['rating']);
        $this->assertSame('Maria Santos', $row['recorded_by']);
    }

    public function test_a_non_contest_row_shows_no_result_rather_than_not_applicable(): void
    {
        $bird = Broodcock::factory()->create();

        PerformanceRecord::factory()->weighIn()->for($bird)->create();

        $this->assertSame('-', $this->report()->rows()->sole()['result']);
    }

    public function test_an_unrated_row_says_so_instead_of_printing_zero(): void
    {
        $bird = Broodcock::factory()->create();

        PerformanceRecord::factory()->win()->unrated()->for($bird)->create();

        $this->assertSame('Not rated', $this->report()->rows()->sole()['rating']);
    }

    public function test_the_headline_summary_reports_the_contest_denominator(): void
    {
        $bird = Broodcock::factory()->create();

        PerformanceRecord::factory()->count(3)->win()->rating(4)->for($bird)->create();
        PerformanceRecord::factory()->loss()->rating(2)->for($bird)->create();
        PerformanceRecord::factory()->count(5)->conditioning()->unrated()->for($bird)->create();

        $summary = $this->report()->summary();

        $this->assertSame(9, $summary['Events']);
        $this->assertSame(4, $summary['Contests']);
        $this->assertSame('3-1-0', $summary['Record (W-L-D)']);
        $this->assertSame('75.0%', $summary['Win Rate']);
        $this->assertSame(1, $summary['Birds']);
    }

    public function test_the_headline_win_rate_says_no_contests_yet_when_nothing_was_contested(): void
    {
        PerformanceRecord::factory()->count(3)->conditioning()->create();

        $this->assertSame('No contests yet', $this->report()->summary()['Win Rate']);
    }

    // -----------------------------------------------------------------
    // Filters
    // -----------------------------------------------------------------

    public function test_the_date_filter_is_respected_by_both_tables(): void
    {
        $bird = Broodcock::factory()->create(['name' => 'Ranger']);

        PerformanceRecord::factory()->win()->for($bird)->on('2026-01-15')->create();
        PerformanceRecord::factory()->loss()->for($bird)->on('2026-06-20')->create();
        PerformanceRecord::factory()->loss()->for($bird)->on('2025-11-02')->create();

        $report = $this->report(['from' => '2026-01-01', 'to' => '2026-03-31']);

        $this->assertCount(1, $report->rows());
        $this->assertSame(1, $this->birdRow($report, 'Ranger')['contests']);
        $this->assertSame(100.0, $this->birdRow($report, 'Ranger')['win_rate']);
    }

    public function test_the_event_type_filter_is_respected(): void
    {
        $bird = Broodcock::factory()->create();

        PerformanceRecord::factory()->count(2)->derby()->win()->for($bird)->create();
        PerformanceRecord::factory()->count(3)->sparring()->loss()->for($bird)->create();

        $report = $this->report(['event_type' => 'derby']);

        $this->assertCount(2, $report->rows());
        $this->assertSame(['Derby'], $report->rows()->pluck('event_type')->unique()->all());
    }

    public function test_the_result_filter_is_respected(): void
    {
        $bird = Broodcock::factory()->create();

        PerformanceRecord::factory()->count(2)->win()->for($bird)->create();
        PerformanceRecord::factory()->count(4)->loss()->for($bird)->create();

        $this->assertCount(2, $this->report(['result' => 'win'])->rows());
    }

    public function test_the_bloodline_filter_is_respected(): void
    {
        $sweater = Broodcock::factory()->bloodline('Sweater')->create();
        $kelso = Broodcock::factory()->bloodline('Kelso')->create();

        PerformanceRecord::factory()->count(2)->win()->for($sweater)->create();
        PerformanceRecord::factory()->count(5)->loss()->for($kelso)->create();

        $report = $this->report(['bloodline' => 'Sweater']);

        $this->assertCount(2, $report->rows());
        $this->assertCount(1, $report->perBirdSummary());
        $this->assertSame(100.0, $report->perBirdSummary()->sole()['win_rate']);
    }

    public function test_the_broodcock_filter_is_respected(): void
    {
        $bird = Broodcock::factory()->create();
        $other = Broodcock::factory()->create();

        PerformanceRecord::factory()->count(3)->win()->for($bird)->create();
        PerformanceRecord::factory()->count(7)->loss()->for($other)->create();

        $report = $this->report(['broodcock_id' => (string) $bird->id]);

        $this->assertCount(3, $report->rows());
        $this->assertCount(1, $report->perBirdSummary());
    }

    public function test_blank_filters_are_dropped_from_the_audit_parameters(): void
    {
        $report = $this->report([
            'from' => '2026-01-01',
            'to' => '',
            'event_type' => '  ',
            'result' => 'win',
            'cause' => 'ignored',
        ]);

        // Only the filters this report actually applied are persisted, so the
        // reports table can be used to reproduce the export later.
        $this->assertSame(['from' => '2026-01-01', 'result' => 'win'], $report->appliedFilters());
    }

    public function test_the_filter_summary_states_the_parameters_in_words(): void
    {
        $this->assertSame(
            'All performance records, no filters applied',
            $this->report()->filterSummary(),
        );

        $summary = $this->report([
            'from' => '2026-01-01',
            'to' => '2026-06-30',
            'event_type' => 'derby',
            'result' => 'win',
            'bloodline' => 'Kelso',
        ])->filterSummary();

        $this->assertStringContainsString('Events from 2026-01-01 to 2026-06-30', $summary);
        $this->assertStringContainsString('Derby', $summary);
        $this->assertStringContainsString('Win', $summary);
        $this->assertStringContainsString('Kelso', $summary);
    }

    public function test_soft_deleted_records_are_excluded(): void
    {
        $bird = Broodcock::factory()->create(['name' => 'Shadow']);

        PerformanceRecord::factory()->win()->for($bird)->create();
        PerformanceRecord::factory()->loss()->for($bird)->create()->delete();

        $report = $this->report();

        $this->assertCount(1, $report->rows());
        $this->assertSame(100.0, $this->birdRow($report, 'Shadow')['win_rate']);
    }

    // -----------------------------------------------------------------
    // Query cost
    // -----------------------------------------------------------------

    public function test_the_query_count_does_not_grow_with_the_number_of_rows(): void
    {
        $staff = User::factory()->staff()->create();

        $small = Broodcock::factory()->create();
        PerformanceRecord::factory()->count(2)->for($small)->create(['recorded_by' => $staff->id]);

        $report = $this->report(['broodcock_id' => (string) $small->id]);

        DB::flushQueryLog();
        DB::enableQueryLog();
        $report->rows();
        $report->summary();
        $report->perBirdSummary();
        $smallCount = count(DB::getQueryLog());
        DB::disableQueryLog();

        $large = Broodcock::factory()->create();
        PerformanceRecord::factory()->count(60)->for($large)->create(['recorded_by' => $staff->id]);

        $bigReport = $this->report(['broodcock_id' => (string) $large->id]);

        DB::flushQueryLog();
        DB::enableQueryLog();
        $bigRows = $bigReport->rows();
        $bigReport->summary();
        $bigReport->perBirdSummary();
        $bigCount = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertCount(60, $bigRows);
        $this->assertSame($smallCount, $bigCount, 'Thirty times the rows must not cost a single extra query.');
        // records + broodcock + recordedBy eager loads, plus the grouped
        // per-bird aggregate. summary() re-uses the loaded rows and costs none.
        $this->assertLessThanOrEqual(4, $bigCount);
    }

    public function test_reading_a_row_never_triggers_a_lazy_load(): void
    {
        // Model::shouldBeStrict() makes an un-eager-loaded relation throw, so
        // simply building the rows proves broodcock and recordedBy were loaded.
        PerformanceRecord::factory()->count(5)->create();

        $rows = $this->report()->rows();

        $this->assertCount(5, $rows);
        $this->assertNotSame('-', $rows->first()['bird_name']);
        $this->assertNotSame('Unknown', $rows->first()['recorded_by']);
    }

    // -----------------------------------------------------------------
    // The PDF actually renders
    // -----------------------------------------------------------------

    public function test_the_pdf_view_renders_with_the_shared_layout(): void
    {
        $bird = Broodcock::factory()->create(['name' => 'Apollo', 'band_number' => 'ZZ-9001']);
        $trainee = Broodcock::factory()->create(['name' => 'Novice']);

        PerformanceRecord::factory()->win()->for($bird)->create();
        PerformanceRecord::factory()->loss()->for($bird)->create();
        PerformanceRecord::factory()->count(2)->conditioning()->for($trainee)->create();

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

        $this->assertStringContainsString('Performance History', $html);
        $this->assertStringContainsString('Per-Bird Summary', $html);
        $this->assertStringContainsString('Apollo (ZZ-9001)', $html);
        // The dilution rule, visible on the page itself.
        $this->assertStringContainsString('50.0%', $html);
        $this->assertStringContainsString('No contests yet', $html);
        // CSS 2.1 only - dompdf silently drops anything below and the layout
        // collapses into an unreadable column.
        $this->assertStringNotContainsString('display: flex', $html);
        $this->assertStringNotContainsString('oklch(', $html);
        $this->assertStringNotContainsString('--tw-', $html);
    }

    public function test_the_report_produces_a_real_pdf_file(): void
    {
        PerformanceRecord::factory()->count(3)->win()->create();

        $report = $this->report();

        $binary = Pdf::loadView($report->pdfView(), [
            'title' => $report->title(),
            'subtitle' => $report->description(),
            'filterSummary' => $report->filterSummary(),
            'columns' => $report->columns(),
            'rows' => $report->rows(),
            'summary' => $report->summary(),
            'generatedAt' => now(),
            'generatedBy' => 'Test Owner',
        ])->setPaper('a4', 'landscape')->output();

        $this->assertStringStartsWith('%PDF', $binary);
        $this->assertGreaterThan(1000, strlen($binary));
    }

    public function test_the_report_is_registered_under_its_stable_key(): void
    {
        $report = app(ReportRegistry::class)->make('performance_history');

        $this->assertInstanceOf(PerformanceHistoryReport::class, $report);
        $this->assertSame('performance_history', $report->key());
    }
}
