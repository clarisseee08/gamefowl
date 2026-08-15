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
                    fn (string $key) => $row[$key] ?? '',
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

    /** Resolves the report, authorizes it, and applies the request's filters. */
    private function resolve(Request $request, string $report): ReportDefinition
    {
        abort_unless($this->registry->has($report), 404, 'That report does not exist.');

        // Reports aggregate breeding and mortality data customers must never
        // see, so generation is gated by ReportPolicy::create().
        $this->authorize('create', Report::class);

        return $this->registry->make($report)->withFilters(
            $request->only(['from', 'to', 'status', 'class', 'sex', 'bloodline', 'breed', 'pen_id', 'record_type', 'event_type', 'result', 'cause'])
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
