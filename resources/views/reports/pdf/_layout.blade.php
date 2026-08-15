{{--
    Shared PDF layout for every report.

    IMPORTANT - READ BEFORE EDITING.

    This template is rendered by dompdf, which is a CSS 2.1 engine. It does NOT
    support flexbox, CSS grid, gap, custom properties (--var), oklch()/color-mix(),
    transform, position:sticky, or viewport units. Tailwind v4's generated
    stylesheet is built on custom properties and oklch() colours, so the app's
    compiled CSS is unusable here and is deliberately NOT linked.

    Rules for anything added below:
      * Lay out with <table>, colspan/rowspan and floats.
      * Sizes in pt / mm / % - never rem, never vh.
      * <thead> repeats automatically across page breaks; use it.
      * A table ROW cannot split across pages, so keep rows short.
      * position: fixed repeats on every page - that is how the footer works.
--}}
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        @page {
            margin: 26mm 14mm 20mm 14mm;
        }

        body {
            font-family: DejaVu Sans, sans-serif;   /* ships with dompdf; handles non-ASCII */
            font-size: 9pt;
            color: #111827;
            margin: 0;
        }

        /* Running header - repeats on every page because it is position:fixed. */
        .page-header {
            position: fixed;
            top: -18mm;
            left: 0;
            right: 0;
            height: 14mm;
            border-bottom: 1pt solid #d1d5db;
        }

        .page-header .farm {
            font-size: 13pt;
            font-weight: bold;
            color: #166534;
        }

        .page-header .subtitle {
            font-size: 8pt;
            color: #6b7280;
        }

        .page-footer {
            position: fixed;
            bottom: -14mm;
            left: 0;
            right: 0;
            height: 10mm;
            border-top: 1pt solid #d1d5db;
            padding-top: 2mm;
            font-size: 7.5pt;
            color: #6b7280;
        }

        /* dompdf resolves these counters at render time. */
        .page-number:after {
            content: "Page " counter(page) " of " counter(pages);
        }

        h1 {
            font-size: 15pt;
            margin: 0 0 2mm 0;
            color: #111827;
        }

        .meta {
            font-size: 8pt;
            color: #6b7280;
            margin-bottom: 4mm;
        }

        /* Filter summary - a report is not defensible unless it states the
           exact parameters it was generated with. */
        .filters {
            background-color: #f3f4f6;
            border: 0.5pt solid #d1d5db;
            padding: 2.5mm 3mm;
            margin-bottom: 5mm;
            font-size: 8pt;
        }

        .filters strong {
            color: #374151;
        }

        table.data {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 5mm;
        }

        table.data thead th {
            background-color: #166534;
            color: #ffffff;
            font-size: 8pt;
            font-weight: bold;
            text-align: left;
            padding: 2mm;
            border: 0.5pt solid #14532d;
        }

        table.data tbody td {
            padding: 1.8mm 2mm;
            border: 0.5pt solid #e5e7eb;
            vertical-align: top;
        }

        /* dompdf supports :nth-child on table rows for zebra striping. */
        table.data tbody tr:nth-child(even) td {
            background-color: #f9fafb;
        }

        table.data tfoot td {
            padding: 2mm;
            border: 0.5pt solid #d1d5db;
            background-color: #f3f4f6;
            font-weight: bold;
        }

        .num {
            text-align: right;
        }

        .center {
            text-align: center;
        }

        /* Summary tiles. Laid out as a TABLE, not flexbox - see the note above. */
        table.summary {
            width: 100%;
            border-collapse: separate;
            border-spacing: 2mm 0;
            margin-bottom: 5mm;
        }

        table.summary td {
            background-color: #f3f4f6;
            border: 0.5pt solid #d1d5db;
            padding: 2.5mm;
            width: 25%;
        }

        table.summary .label {
            font-size: 7.5pt;
            color: #6b7280;
            text-transform: uppercase;
        }

        table.summary .value {
            font-size: 14pt;
            font-weight: bold;
            color: #111827;
        }

        .empty {
            padding: 12mm;
            text-align: center;
            color: #6b7280;
            border: 0.5pt dashed #d1d5db;
        }

        .badge {
            padding: 0.4mm 1.4mm;
            font-size: 7.5pt;
            border: 0.5pt solid #d1d5db;
            background-color: #f3f4f6;
        }

        .badge-danger { background-color: #fee2e2; border-color: #fca5a5; color: #991b1b; }
        .badge-warn   { background-color: #fef3c7; border-color: #fcd34d; color: #92400e; }
        .badge-ok     { background-color: #dcfce7; border-color: #86efac; color: #166534; }
    </style>
</head>
<body>
    <div class="page-header">
        <span class="farm">{{ config('gfms.farm.name') }}</span>
        @if (config('gfms.farm.address'))
            <span class="subtitle">&nbsp;&middot;&nbsp;{{ config('gfms.farm.address') }}</span>
        @endif
        <span class="subtitle" style="float: right;">Broodcock Farm Record Management System</span>
    </div>

    <div class="page-footer">
        <span>Generated {{ $generatedAt->format('j F Y, g:i a') }}@if (! empty($generatedBy)) by {{ $generatedBy }}@endif</span>
        <span style="float: right;" class="page-number"></span>
    </div>

    <h1>{{ $title }}</h1>

    @if (! empty($subtitle))
        <p class="meta">{{ $subtitle }}</p>
    @endif

    {{-- The exact parameters this report was run with. A report that cannot
         state its own filters cannot be defended or reproduced. --}}
    @if (! empty($filterSummary))
        <div class="filters">
            <strong>Filters applied:</strong> {{ $filterSummary }}
        </div>
    @endif

    {{ $slot }}
</body>
</html>
