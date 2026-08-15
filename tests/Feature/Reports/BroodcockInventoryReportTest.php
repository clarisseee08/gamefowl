<?php

declare(strict_types=1);

namespace Tests\Feature\Reports;

use App\Enums\BroodcockClass;
use App\Enums\BroodcockStatus;
use App\Models\Broodcock;
use App\Models\Pen;
use App\Reports\BroodcockInventoryReport;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * The inventory report is the farm's census, so the tests below are mostly
 * about it telling the truth: a filtered-out bird must be genuinely absent
 * rather than merely hidden, an unknown fact must read "Unknown" rather than
 * being invented, and the totals must match the rows they summarise.
 */
final class BroodcockInventoryReportTest extends TestCase
{
    use RefreshDatabase;

    private function report(array $filters = []): BroodcockInventoryReport
    {
        return (new BroodcockInventoryReport)->withFilters($filters);
    }

    /** @return array<int, string> */
    private function names(array $filters = []): array
    {
        return $this->report($filters)->rows()->pluck('name')->all();
    }

    // -----------------------------------------------------------------
    // Filters
    // -----------------------------------------------------------------

    public function test_rows_returns_every_bird_when_no_filters_are_applied(): void
    {
        Broodcock::factory()->count(3)->create();

        $this->assertCount(3, $this->report()->rows());
    }

    public function test_the_status_filter_excludes_birds_with_another_status(): void
    {
        Broodcock::factory()->create(['name' => 'Kept', 'status' => BroodcockStatus::Breeding]);
        Broodcock::factory()->create(['name' => 'Excluded', 'status' => BroodcockStatus::Sold]);

        $names = $this->names(['status' => 'breeding']);

        $this->assertSame(['Kept'], $names);
        $this->assertNotContains('Excluded', $names);
    }

    public function test_the_class_filter_excludes_birds_of_another_class(): void
    {
        Broodcock::factory()->create(['name' => 'Kept', 'class' => BroodcockClass::ClassA]);
        Broodcock::factory()->create(['name' => 'Excluded', 'class' => BroodcockClass::Ordinary]);

        $names = $this->names(['class' => 'class_a']);

        $this->assertSame(['Kept'], $names);
        $this->assertNotContains('Excluded', $names);
    }

    public function test_the_sex_filter_excludes_birds_of_the_other_sex(): void
    {
        Broodcock::factory()->female()->create(['name' => 'Kept']);
        Broodcock::factory()->male()->create(['name' => 'Excluded']);

        $names = $this->names(['sex' => 'female']);

        $this->assertSame(['Kept'], $names);
        $this->assertNotContains('Excluded', $names);
    }

    public function test_the_bloodline_filter_excludes_other_bloodlines(): void
    {
        Broodcock::factory()->bloodline('Sweater')->create(['name' => 'Kept']);
        Broodcock::factory()->bloodline('Kelso')->create(['name' => 'Excluded']);

        $names = $this->names(['bloodline' => 'Sweater']);

        $this->assertSame(['Kept'], $names);
        $this->assertNotContains('Excluded', $names);
    }

    public function test_the_breed_filter_excludes_other_breeds(): void
    {
        Broodcock::factory()->create(['name' => 'Kept', 'breed' => 'Asil']);
        Broodcock::factory()->create(['name' => 'Excluded', 'breed' => 'Shamo']);

        $names = $this->names(['breed' => 'Asil']);

        $this->assertSame(['Kept'], $names);
        $this->assertNotContains('Excluded', $names);
    }

    public function test_the_pen_filter_excludes_birds_in_other_pens(): void
    {
        $pen = Pen::factory()->create(['code' => 'P-101']);
        $other = Pen::factory()->create(['code' => 'P-202']);

        Broodcock::factory()->create(['name' => 'Kept', 'pen_id' => $pen->id]);
        Broodcock::factory()->create(['name' => 'Excluded', 'pen_id' => $other->id]);
        Broodcock::factory()->create(['name' => 'Unpenned', 'pen_id' => null]);

        $rows = $this->report(['pen_id' => (string) $pen->id])->rows();

        $this->assertSame(['Kept'], $rows->pluck('name')->all());
        $this->assertSame('P-101', $rows->first()['pen']);
    }

    public function test_the_date_range_filters_on_date_acquired(): void
    {
        Broodcock::factory()->create(['name' => 'TooEarly', 'date_acquired' => '2024-01-05']);
        Broodcock::factory()->create(['name' => 'InRange', 'date_acquired' => '2024-06-15']);
        Broodcock::factory()->create(['name' => 'TooLate', 'date_acquired' => '2025-03-01']);

        $names = $this->names(['from' => '2024-02-01', 'to' => '2024-12-31']);

        $this->assertSame(['InRange'], $names);
        $this->assertNotContains('TooEarly', $names);
        $this->assertNotContains('TooLate', $names);
    }

    public function test_filters_combine_rather_than_replace_one_another(): void
    {
        Broodcock::factory()->male()->bloodline('Hatch')->create(['name' => 'Match']);
        Broodcock::factory()->female()->bloodline('Hatch')->create(['name' => 'WrongSex']);
        Broodcock::factory()->male()->bloodline('Grey')->create(['name' => 'WrongBloodline']);

        $this->assertSame(['Match'], $this->names(['sex' => 'male', 'bloodline' => 'Hatch']));
    }

    public function test_an_unrecognised_enum_value_is_dropped_rather_than_silently_recorded(): void
    {
        Broodcock::factory()->count(2)->create();

        $report = $this->report(['status' => 'not-a-status', 'sex' => '']);

        // The junk filter must not appear in the audit parameters, and must not
        // quietly narrow the report either.
        $this->assertSame([], $report->appliedFilters());
        $this->assertCount(2, $report->rows());
    }

    public function test_applied_filters_records_exactly_what_was_used(): void
    {
        $report = $this->report([
            'status' => 'active',
            'bloodline' => '  Kelso  ',
            'from' => '2024-01-01',
            'breed' => '',
        ]);

        $this->assertSame(
            ['status' => 'active', 'bloodline' => 'Kelso', 'from' => '2024-01-01'],
            $report->appliedFilters()
        );
    }

    public function test_the_filter_summary_states_the_filters_in_plain_words(): void
    {
        $summary = $this->report(['status' => 'breeding', 'sex' => 'male'])->filterSummary();

        $this->assertStringContainsString('Status: Breeding', $summary);
        $this->assertStringContainsString('Sex: Male', $summary);

        // An unfiltered report must say so rather than show an empty box.
        $this->assertSame('All birds (no filters applied)', $this->report()->filterSummary());
    }

    // -----------------------------------------------------------------
    // Cell values that must never be blank or invented
    // -----------------------------------------------------------------

    public function test_an_unbanded_bird_reads_not_yet_banded_rather_than_an_empty_cell(): void
    {
        Broodcock::factory()->unbanded()->create(['name' => 'Nameless']);

        $row = $this->report()->rows()->firstWhere('name', 'Nameless');

        $this->assertSame('Not yet banded', $row['band_number']);
        $this->assertNotSame('', $row['band_number']);
    }

    public function test_a_bird_with_no_hatch_date_shows_an_unknown_age_and_no_negative_number(): void
    {
        Broodcock::factory()->create(['name' => 'Foundling', 'date_hatched' => null]);

        $row = $this->report()->rows()->firstWhere('name', 'Foundling');

        $this->assertSame('Unknown', $row['age']);
        $this->assertSame('Unknown', $row['date_hatched']);
        $this->assertStringNotContainsString('-', $row['age']);
    }

    public function test_a_bird_with_a_hatch_date_shows_a_real_age(): void
    {
        Broodcock::factory()->create([
            'name' => 'Veteran',
            'date_hatched' => Carbon::now()->subMonths(26)->toDateString(),
        ]);

        $row = $this->report()->rows()->firstWhere('name', 'Veteran');

        $this->assertSame('2 yrs 2 mos', $row['age']);
    }

    public function test_missing_parents_and_pens_are_labelled_rather_than_left_blank(): void
    {
        Broodcock::factory()->create([
            'name' => 'Orphan',
            'sire_id' => null,
            'dam_id' => null,
            'pen_id' => null,
            'weight' => null,
            'breed' => null,
            'bloodline' => null,
        ]);

        $row = $this->report()->rows()->firstWhere('name', 'Orphan');

        $this->assertSame('Unknown', $row['sire']);
        $this->assertSame('Unknown', $row['dam']);
        $this->assertSame('Unassigned', $row['pen']);

        foreach ($row as $column => $value) {
            $this->assertNotSame('', (string) $value, "Column [{$column}] rendered an empty cell.");
        }
    }

    public function test_parent_names_are_resolved_for_a_bird_that_has_them(): void
    {
        $sire = Broodcock::factory()->male()->create(['name' => 'Thunder']);
        $dam = Broodcock::factory()->female()->create(['name' => 'Ruby']);

        Broodcock::factory()->bredFrom($sire, $dam)->create(['name' => 'Chick']);

        $row = $this->report()->rows()->firstWhere('name', 'Chick');

        $this->assertSame('Thunder', $row['sire']);
        $this->assertSame('Ruby', $row['dam']);
    }

    public function test_every_declared_column_is_present_on_every_row(): void
    {
        Broodcock::factory()->count(2)->create();

        $columns = array_keys($this->report()->columns());

        foreach ($this->report()->rows() as $row) {
            $this->assertSame($columns, array_keys($row));
        }
    }

    // -----------------------------------------------------------------
    // Summary and breakdowns
    // -----------------------------------------------------------------

    public function test_the_summary_tiles_count_correctly(): void
    {
        Broodcock::factory()->count(4)->bloodline('Sweater')->create();
        Broodcock::factory()->count(2)->bloodline('Kelso')->status(BroodcockStatus::Sold)->create();
        Broodcock::factory()->unbanded()->bloodline('Sweater')->create();

        $summary = $this->report()->summary();

        $this->assertSame(7, $summary['Total Birds']);
        $this->assertSame(5, $summary['Active Birds']);   // the 2 sold birds are not active
        $this->assertSame(2, $summary['Bloodlines']);
        $this->assertSame('6 / 1', $summary['Banded / Unbanded']);
    }

    public function test_the_summary_respects_the_filters(): void
    {
        Broodcock::factory()->count(3)->bloodline('Sweater')->create();
        Broodcock::factory()->count(5)->bloodline('Kelso')->create();

        $summary = $this->report(['bloodline' => 'Sweater'])->summary();

        $this->assertSame(3, $summary['Total Birds']);
        $this->assertSame(1, $summary['Bloodlines']);
    }

    public function test_the_status_breakdown_is_grouped_and_totals_the_row_count(): void
    {
        Broodcock::factory()->count(3)->status(BroodcockStatus::Active)->create();
        Broodcock::factory()->count(2)->status(BroodcockStatus::Sold)->create();

        $breakdown = $this->report()->statusBreakdown();

        $this->assertSame(3, $breakdown->firstWhere('label', 'Active')['count']);
        $this->assertSame(2, $breakdown->firstWhere('label', 'Sold')['count']);
        $this->assertSame(5, $breakdown->sum('count'));
        $this->assertSame(60.0, $breakdown->firstWhere('label', 'Active')['share']);
    }

    public function test_the_bloodline_breakdown_labels_birds_with_no_bloodline(): void
    {
        Broodcock::factory()->count(2)->bloodline('Hatch')->create();
        Broodcock::factory()->create(['bloodline' => null]);

        $breakdown = $this->report()->bloodlineBreakdown();

        $this->assertSame(2, $breakdown->firstWhere('label', 'Hatch')['count']);
        $this->assertSame(1, $breakdown->firstWhere('label', 'Not recorded')['count']);

        // No bird may be dropped from a census.
        $this->assertSame(3, $breakdown->sum('count'));
    }

    public function test_the_breakdowns_respect_the_filters(): void
    {
        Broodcock::factory()->count(2)->bloodline('Grey')->status(BroodcockStatus::Active)->create();
        Broodcock::factory()->count(4)->bloodline('Kelso')->status(BroodcockStatus::Active)->create();

        $breakdown = $this->report(['bloodline' => 'Grey'])->bloodlineBreakdown();

        $this->assertCount(1, $breakdown);
        $this->assertSame(2, $breakdown->first()['count']);
    }

    // -----------------------------------------------------------------
    // Performance - ISO 25010 evidence
    // -----------------------------------------------------------------

    public function test_the_query_count_does_not_grow_with_the_number_of_rows(): void
    {
        $count = function (): int {
            DB::flushQueryLog();
            DB::enableQueryLog();
            $this->report()->rows();
            $queries = count(DB::getQueryLog());
            DB::disableQueryLog();

            return $queries;
        };

        $pen = Pen::factory()->create();
        $sire = Broodcock::factory()->male()->create();
        $dam = Broodcock::factory()->female()->create();

        Broodcock::factory()->count(3)->bredFrom($sire, $dam)->create(['pen_id' => $pen->id]);
        $withThree = $count();

        Broodcock::factory()->count(20)->bredFrom($sire, $dam)->create(['pen_id' => $pen->id]);
        $withTwentyThree = $count();

        $this->assertSame(
            $withThree,
            $withTwentyThree,
            "Listing 5 birds took {$withThree} queries but listing 25 took {$withTwentyThree}. ".
            'pen, sire and dam must be eager-loaded - something is loading a relation per row.'
        );

        // One query for the birds plus one per eager-loaded relation.
        $this->assertLessThanOrEqual(4, $withThree);
    }

    public function test_reading_a_row_never_lazy_loads_a_relation(): void
    {
        // Model::shouldBeStrict() is on, so a missing eager load throws rather
        // than silently costing a query. Building rows must not raise.
        $sire = Broodcock::factory()->male()->create();
        $dam = Broodcock::factory()->female()->create();
        Broodcock::factory()->bredFrom($sire, $dam)->create([
            'pen_id' => Pen::factory()->create()->id,
        ]);

        $this->assertCount(3, $this->report()->rows());
    }

    // -----------------------------------------------------------------
    // PDF
    // -----------------------------------------------------------------

    private function renderPdf(BroodcockInventoryReport $report): string
    {
        return Pdf::loadView($report->pdfView(), [
            'title' => $report->title(),
            'subtitle' => $report->description(),
            'filterSummary' => $report->filterSummary(),
            'columns' => $report->columns(),
            'rows' => $report->rows(),
            'summary' => $report->summary(),
            'generatedAt' => Carbon::now(),
            'generatedBy' => 'Test Runner',
        ])->setPaper('a4', 'landscape')->output();
    }

    public function test_the_pdf_template_actually_renders(): void
    {
        $pen = Pen::factory()->create();
        Broodcock::factory()->count(6)->create(['pen_id' => $pen->id]);
        Broodcock::factory()->unbanded()->create(['date_hatched' => null, 'bloodline' => null]);

        $bytes = $this->renderPdf($this->report(['status' => 'active']));

        $this->assertStringStartsWith('%PDF', $bytes);
        $this->assertGreaterThan(1000, strlen($bytes));
    }

    public function test_the_pdf_renders_when_no_birds_match(): void
    {
        // The empty state is the likeliest template to be left untested and the
        // likeliest to be hit in a real farm office.
        $bytes = $this->renderPdf($this->report(['status' => 'deceased']));

        $this->assertStringStartsWith('%PDF', $bytes);
    }
}
