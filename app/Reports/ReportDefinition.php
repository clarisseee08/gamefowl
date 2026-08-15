<?php

declare(strict_types=1);

namespace App\Reports;

use Illuminate\Support\Collection;

/**
 * The contract every report implements.
 *
 * A report is a pure query + presentation description. It does NOT know how to
 * emit CSV or PDF, and it does not write the audit row - ReportController does
 * both, so every report is exported and recorded identically and no report can
 * accidentally skip the audit trail.
 */
interface ReportDefinition
{
    /** Stable machine key, stored in reports.report_type. e.g. 'broodcock_inventory'. */
    public function key(): string;

    /** Human title, shown on screen and as the PDF heading. */
    public function title(): string;

    /** One sentence explaining what the report answers. */
    public function description(): string;

    /**
     * Apply the caller's filters. Always called before rows()/summary().
     *
     * @param  array<string, mixed>  $filters
     */
    public function withFilters(array $filters): static;

    /**
     * The filters actually applied, normalised - persisted verbatim into
     * reports.parameters so the report can be explained and reproduced later.
     *
     * @return array<string, mixed>
     */
    public function appliedFilters(): array;

    /** Readable one-line rendering of the filters, for the PDF header. */
    public function filterSummary(): string;

    /**
     * Column headings, in order. Keys are used as CSV headers and array keys
     * into each row returned by rows().
     *
     * @return array<string, string>
     */
    public function columns(): array;

    /**
     * The report rows, already flattened to scalars.
     *
     * MUST eager-load anything it reads - every query is a network round trip
     * to Supabase, and Model::shouldBeStrict() makes a lazy load throw.
     *
     * @return Collection<int, array<string, string|int|float|null>>
     */
    public function rows(): Collection;

    /**
     * Headline figures shown above the table, as label => value.
     *
     * @return array<string, string|int|float|null>
     */
    public function summary(): array;

    /** Blade view used for the PDF, e.g. 'reports.pdf.broodcock-inventory'. */
    public function pdfView(): string;
}
