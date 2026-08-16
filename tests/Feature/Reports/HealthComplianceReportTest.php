<?php

declare(strict_types=1);

namespace Tests\Feature\Reports;

use App\Enums\HealthRecordType;
use App\Models\Broodcock;
use App\Models\HealthRecord;
use App\Models\User;
use App\Reports\HealthComplianceReport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Health and Vaccination Compliance report.
 *
 * This is the report the farm acts on, so the tests are weighted towards
 * classification correctness - especially the boundary between "due today" and
 * "overdue", which is the whole point of the report and the easiest thing in it
 * to get wrong by one day.
 */
final class HealthComplianceReportTest extends TestCase
{
    use RefreshDatabase;

    // -----------------------------------------------------------------
    // The overdue boundary
    // -----------------------------------------------------------------

    /** Due yesterday is late. Due today is not - the day is not over yet. */
    public function test_a_follow_up_due_yesterday_is_overdue_but_one_due_today_is_not(): void
    {
        $yesterday = $this->record(nextDue: today()->subDay()->toDateString());
        $todayDue = $this->record(nextDue: today()->toDateString());

        $states = $this->report()->rows()->pluck('schedule_state', 'band_number');

        $this->assertSame('Overdue', $states[$yesterday->broodcock->band_number]);
        $this->assertNotSame('Overdue', $states[$todayDue->broodcock->band_number]);
        $this->assertSame('Due soon', $states[$todayDue->broodcock->band_number]);

        $this->assertSame(1, $this->report()->summary()['Overdue']);
    }

    /** The signed day count crosses zero on the same boundary. */
    public function test_days_until_due_is_negative_for_overdue_and_zero_on_the_due_day(): void
    {
        $late = $this->record(nextDue: today()->subDays(5)->toDateString());
        $due = $this->record(nextDue: today()->toDateString());

        $days = $this->report()->rows()->pluck('days_until_due', 'band_number');

        $this->assertSame(-5, $days[$late->broodcock->band_number]);
        $this->assertSame(0, $days[$due->broodcock->band_number]);
    }

    /** The last day inside the window is still "due soon"; the next day is not. */
    public function test_the_due_soon_window_uses_the_configured_warning_days(): void
    {
        config(['gfms.vaccination_warning_days' => 30]);

        $inside = $this->record(nextDue: today()->addDays(30)->toDateString());
        $outside = $this->record(nextDue: today()->addDays(31)->toDateString());

        $states = $this->report()->rows()->pluck('schedule_state', 'band_number');

        $this->assertSame('Due soon', $states[$inside->broodcock->band_number]);
        $this->assertSame('Scheduled', $states[$outside->broodcock->band_number]);
        $this->assertSame(1, $this->report()->summary()['Due within 30 days']);
    }

    // -----------------------------------------------------------------
    // Records with no follow-up
    // -----------------------------------------------------------------

    /**
     * A one-off check-up that never needed a second visit is not late, is not
     * upcoming, and is not evidence of anything about compliance.
     */
    public function test_a_record_without_a_follow_up_date_is_neither_overdue_nor_due_soon(): void
    {
        $this->record(nextDue: null, type: HealthRecordType::Checkup);

        $summary = $this->report()->summary();

        $this->assertSame(1, $summary['Records in range']);
        $this->assertSame(0, $summary['Overdue']);
        $this->assertSame(0, $summary['Due within 30 days']);
        $this->assertSame('No follow-up needed', $this->report()->rows()->first()['schedule_state']);
        $this->assertNull($this->report()->rows()->first()['days_until_due']);
    }

    /** It must not be counted as a miss in the denominator either. */
    public function test_records_without_a_follow_up_date_do_not_drag_the_compliance_rate_down(): void
    {
        $this->record(nextDue: today()->addDays(60)->toDateString());
        $this->record(nextDue: null, type: HealthRecordType::Checkup);
        $this->record(nextDue: null, type: HealthRecordType::Treatment);

        // One scheduled record, not overdue. The two follow-up-less records are
        // outside the fraction entirely, so this is 100% and not 33.3%.
        $this->assertSame(100.0, $this->report()->summary()['Compliance rate']);
    }

    // -----------------------------------------------------------------
    // Compliance rate
    // -----------------------------------------------------------------

    /**
     * "Nothing was scheduled" and "everything scheduled was missed" are
     * opposite findings. Reporting the first as 0% would send the farm chasing
     * birds that were never due.
     */
    public function test_the_compliance_rate_is_null_not_zero_when_nothing_has_a_follow_up_date(): void
    {
        $this->record(nextDue: null, type: HealthRecordType::Checkup);
        $this->record(nextDue: null, type: HealthRecordType::Treatment);

        $this->assertNull($this->report()->summary()['Compliance rate']);
    }

    public function test_the_compliance_rate_is_null_when_there_are_no_records_at_all(): void
    {
        $this->assertNull($this->report()->summary()['Compliance rate']);
        $this->assertSame(0, $this->report()->summary()['Records in range']);
    }

    /** And a genuine total failure really is 0.0, not null. */
    public function test_the_compliance_rate_is_zero_when_every_scheduled_follow_up_is_overdue(): void
    {
        $this->record(nextDue: today()->subDays(3)->toDateString());
        $this->record(nextDue: today()->subDays(9)->toDateString());

        $this->assertSame(0.0, $this->report()->summary()['Compliance rate']);
    }

    public function test_the_compliance_rate_counts_only_records_with_a_follow_up_date(): void
    {
        $this->record(nextDue: today()->subDay()->toDateString());        // overdue
        $this->record(nextDue: today()->addDays(5)->toDateString());      // due soon
        $this->record(nextDue: today()->addDays(90)->toDateString());     // scheduled
        $this->record(nextDue: null, type: HealthRecordType::Checkup);    // excluded

        // 2 of the 3 scheduled records are not overdue.
        $this->assertSame(66.7, $this->report()->summary()['Compliance rate']);
    }

    // -----------------------------------------------------------------
    // The compliance filter partitions the result set
    // -----------------------------------------------------------------

    /**
     * Every record is in exactly one bucket. If the three filters ever summed
     * to more than the total, the farm would be double-counting its own birds.
     */
    public function test_the_compliance_filter_partitions_the_records_without_double_counting(): void
    {
        $this->record(nextDue: today()->subDays(1)->toDateString());
        $this->record(nextDue: today()->subDays(40)->toDateString());
        $this->record(nextDue: today()->toDateString());
        $this->record(nextDue: today()->addDays(10)->toDateString());
        $this->record(nextDue: today()->addDays(30)->toDateString());
        $this->record(nextDue: today()->addDays(31)->toDateString());
        $this->record(nextDue: null, type: HealthRecordType::Checkup);
        $this->record(nextDue: null, type: HealthRecordType::Treatment);

        $all = $this->idsFor([]);
        $overdue = $this->idsFor(['compliance' => 'overdue']);
        $dueSoon = $this->idsFor(['compliance' => 'due_soon']);
        $compliant = $this->idsFor(['compliance' => 'compliant']);

        $this->assertCount(8, $all);
        $this->assertCount(2, $overdue);
        $this->assertCount(3, $dueSoon);   // due today, +10, +30
        $this->assertCount(3, $compliant); // +31, and the two with no follow-up

        // Disjoint...
        $this->assertEmpty(array_intersect($overdue, $dueSoon));
        $this->assertEmpty(array_intersect($overdue, $compliant));
        $this->assertEmpty(array_intersect($dueSoon, $compliant));

        // ...and exhaustive.
        $union = array_merge($overdue, $dueSoon, $compliant);
        sort($union);
        sort($all);
        $this->assertSame($all, $union);
    }

    public function test_an_unrecognised_compliance_value_is_ignored_rather_than_returning_nothing(): void
    {
        $this->record(nextDue: today()->subDay()->toDateString());

        $report = $this->report(['compliance' => 'not_a_state']);

        $this->assertCount(1, $report->rows());
        $this->assertArrayNotHasKey('compliance', $report->appliedFilters());
    }

    // -----------------------------------------------------------------
    // Date and type filters
    // -----------------------------------------------------------------

    public function test_the_date_filters_apply_to_the_checkup_date(): void
    {
        $this->record(checkup: '2026-01-10', nextDue: '2026-04-10');
        $inRange = $this->record(checkup: '2026-03-15', nextDue: '2026-06-15');
        $this->record(checkup: '2026-06-01', nextDue: '2026-09-01');

        $rows = $this->report(['from' => '2026-03-01', 'to' => '2026-03-31'])->rows();

        $this->assertCount(1, $rows);
        $this->assertSame($inRange->broodcock->band_number, $rows->first()['band_number']);
    }

    public function test_the_range_boundaries_are_inclusive(): void
    {
        $this->record(checkup: '2026-03-01', nextDue: '2026-06-01');
        $this->record(checkup: '2026-03-31', nextDue: '2026-06-30');

        $this->assertCount(2, $this->report(['from' => '2026-03-01', 'to' => '2026-03-31'])->rows());
    }

    public function test_the_record_type_filter_narrows_the_report(): void
    {
        $this->record(type: HealthRecordType::Vaccination);
        $this->record(type: HealthRecordType::Deworming);
        $this->record(type: HealthRecordType::Deworming);

        $rows = $this->report(['record_type' => 'deworming'])->rows();

        $this->assertCount(2, $rows);
        $this->assertSame(['Deworming'], $rows->pluck('record_type')->unique()->all());
    }

    public function test_unparseable_filters_are_dropped_rather_than_trusted(): void
    {
        $this->record();

        $report = $this->report(['from' => 'yesterdayish', 'record_type' => 'nonsense', 'to' => '']);

        $this->assertCount(1, $report->rows());
        $this->assertSame(['warning_days' => 30], $report->appliedFilters());
    }

    /** The audit row must be able to explain the numbers it recorded. */
    public function test_applied_filters_record_the_warning_window_used(): void
    {
        config(['gfms.vaccination_warning_days' => 14]);

        $filters = $this->report(['compliance' => 'overdue', 'record_type' => 'vaccination'])->appliedFilters();

        $this->assertSame([
            'record_type' => 'vaccination',
            'compliance' => 'overdue',
            'warning_days' => 14,
        ], $filters);
    }

    // -----------------------------------------------------------------
    // Shape and ordering
    // -----------------------------------------------------------------

    public function test_every_column_is_present_in_every_row(): void
    {
        $this->record();

        $report = $this->report();
        $row = $report->rows()->first();

        foreach (array_keys($report->columns()) as $key) {
            $this->assertArrayHasKey($key, $row, "Row is missing the [{$key}] column.");
        }
    }

    /**
     * Internal remarks are included on purpose - this report is internal-only
     * (ReportPolicy denies customers) and the remark is usually the reason the
     * follow-up matters.
     */
    public function test_internal_remarks_are_included(): void
    {
        $this->record(remarks: 'Reacted badly to the first dose - half dose next time.');

        $this->assertArrayHasKey('remarks', $this->report()->columns());
        $this->assertSame(
            'Reacted badly to the first dose - half dose next time.',
            $this->report()->rows()->first()['remarks']
        );
    }

    /** The most overdue bird is the one at most risk, so it goes first. */
    public function test_the_most_overdue_record_is_listed_first_and_unscheduled_ones_last(): void
    {
        $mild = $this->record(nextDue: today()->subDays(2)->toDateString());
        $worst = $this->record(nextDue: today()->subDays(60)->toDateString());
        $none = $this->record(nextDue: null, type: HealthRecordType::Checkup);

        $order = $this->report()->rows()->pluck('band_number')->all();

        $this->assertSame([
            $worst->broodcock->band_number,
            $mild->broodcock->band_number,
            $none->broodcock->band_number,
        ], $order);
    }

    public function test_the_row_counts_by_record_type_cover_every_type_including_the_empty_ones(): void
    {
        $this->record(type: HealthRecordType::Vaccination);
        $this->record(type: HealthRecordType::Vaccination);
        $this->record(type: HealthRecordType::Deworming);

        $counts = HealthComplianceReport::countsByType($this->report()->rows())
            ->pluck('count', 'label');

        $this->assertCount(count(HealthRecordType::cases()), $counts);
        $this->assertSame(2, $counts['Vaccination']);
        $this->assertSame(1, $counts['Deworming']);
        $this->assertSame(0, $counts['Check-up']);
        $this->assertSame(0, $counts['Treatment']);
    }

    public function test_the_report_identifies_itself_for_the_registry_and_the_audit_row(): void
    {
        $report = $this->report();

        $this->assertSame('health_compliance', $report->key());
        $this->assertSame('reports.pdf.health-compliance', $report->pdfView());
        $this->assertNotSame('', $report->title());
        $this->assertStringContainsString('Due-soon window: 30 days', $report->filterSummary());
    }

    // -----------------------------------------------------------------
    // No N+1
    // -----------------------------------------------------------------

    /**
     * The query count must be a function of the report, not of the flock. If it
     * grew per row, every added bird would cost another round trip to Supabase.
     */
    public function test_the_query_count_does_not_grow_with_the_number_of_rows(): void
    {
        $this->makeRecords(3);
        $withThree = $this->countQueries(function (): void {
            $report = new HealthComplianceReport;
            $report->withFilters([])->rows()->each(fn () => null);
            $report->summary();
        });

        $this->makeRecords(20);
        $withTwentyThree = $this->countQueries(function (): void {
            $report = new HealthComplianceReport;
            $report->withFilters([])->rows()->each(fn () => null);
            $report->summary();
        });

        $this->assertSame(
            $withThree,
            $withTwentyThree,
            'The report issues more queries as rows are added - broodcock or recordedBy is not eager-loaded.'
        );

        // The records, the birds, the recorders. Nothing else.
        $this->assertSame(3, $withThree);
    }

    /** Strict mode turns a missed eager load into an exception, so render the whole thing. */
    public function test_reading_every_row_never_lazy_loads(): void
    {
        $this->makeRecords(5);

        $rows = $this->report()->rows();

        $this->assertCount(5, $rows);
        $this->assertNotEmpty($rows->first()['recorded_by']);
    }

    // -----------------------------------------------------------------
    // The PDF template
    // -----------------------------------------------------------------

    public function test_the_pdf_template_renders_and_marks_overdue_rows(): void
    {
        $this->record(nextDue: today()->subDays(7)->toDateString());
        $this->record(nextDue: today()->addDays(3)->toDateString());
        $this->record(nextDue: null, type: HealthRecordType::Checkup);

        $html = $this->renderPdfView($this->report());

        $this->assertStringContainsString('Health and Vaccination Compliance', $html);
        $this->assertStringContainsString('badge-danger', $html);   // overdue
        $this->assertStringContainsString('badge-warn', $html);     // due soon
        $this->assertStringContainsString('7 late', $html);
        $this->assertStringContainsString('No follow-up needed', $html);
        $this->assertStringContainsString('Record Type', $html);   // the counts-by-type table

        // The shared layout actually wrapped the slot - running header, the
        // filter statement and the page footer are all part of the contract.
        $this->assertStringContainsString(config('gfms.farm.name'), $html);
        $this->assertStringContainsString('Filters applied:', $html);
        $this->assertStringContainsString('page-number', $html);

        // Overdue shading is inline so it outranks the layout's zebra stripe.
        // Asserted against the config rather than a literal: the point of the
        // test is that the shading is INLINE, not that the palette is any
        // particular colour, and hard-coding the hex made a palette change look
        // like a broken report.
        $this->assertStringContainsString(
            'background-color: '.config('gfms-brand.destructive_bg').';',
            $html
        );

        // CSS 2.1 only - dompdf silently ignores these, which is worse than an error.
        $this->assertStringNotContainsString('display:flex', str_replace(' ', '', $html));
        $this->assertStringNotContainsString('oklch(', $html);
        $this->assertStringNotContainsString('var(--', $html);
    }

    public function test_the_pdf_template_shows_no_data_rather_than_zero_percent(): void
    {
        $this->record(nextDue: null, type: HealthRecordType::Checkup);

        $html = $this->renderPdfView($this->report());

        $this->assertStringContainsString('No data', $html);
        $this->assertStringNotContainsString('0.0%', $html);
    }

    public function test_the_pdf_template_renders_an_empty_state_rather_than_a_blank_table(): void
    {
        $html = $this->renderPdfView($this->report(['from' => '2020-01-01', 'to' => '2020-01-02']));

        $this->assertStringContainsString('No health records match these filters', $html);
    }

    // -----------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------

    /** @param array<string, mixed> $filters */
    private function report(array $filters = []): HealthComplianceReport
    {
        return (new HealthComplianceReport)->withFilters($filters);
    }

    /** @param array<string, mixed> $filters @return array<int, string> */
    private function idsFor(array $filters): array
    {
        return $this->report($filters)->rows()->pluck('band_number')->all();
    }

    private function record(
        ?string $nextDue = null,
        ?string $checkup = null,
        ?HealthRecordType $type = null,
        ?string $remarks = null,
    ): HealthRecord {
        static $sequence = 0;
        $sequence++;

        $checkup ??= today()->subDays(90)->toDateString();

        return HealthRecord::factory()->create([
            'broodcock_id' => Broodcock::factory()->create([
                'band_number' => sprintf('BAND-%03d', $sequence),
                'name' => "Bird {$sequence}",
            ]),
            'record_type' => $type ?? HealthRecordType::Vaccination,
            'checkup_date' => $checkup,
            'next_due_date' => $nextDue,
            'remarks' => $remarks,
            'recorded_by' => User::factory()->staff(),
        ]);
    }

    private function makeRecords(int $count): void
    {
        for ($i = 0; $i < $count; $i++) {
            $this->record(nextDue: today()->addDays($i)->toDateString());
        }
    }

    private function renderPdfView(HealthComplianceReport $report): string
    {
        return view($report->pdfView(), [
            'title' => $report->title(),
            'subtitle' => $report->description(),
            'filterSummary' => $report->filterSummary(),
            'columns' => $report->columns(),
            'rows' => $report->rows(),
            'summary' => $report->summary(),
            'generatedAt' => now(),
            'generatedBy' => 'Test Runner',
        ])->render();
    }

    private function countQueries(callable $callback): int
    {
        DB::flushQueryLog();
        DB::enableQueryLog();

        $callback();

        $count = count(DB::getQueryLog());
        DB::disableQueryLog();

        return $count;
    }
}
