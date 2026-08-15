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
@php
    /*
     * Colour comes from config/gfms-brand.php, the SAME source the app's
     * stylesheet mirrors - which is the whole reason that config file exists.
     * dompdf cannot read a custom property, so it cannot share app.css; before
     * this, these templates carried a hand-typed copy of the OLD stock-grey
     * palette (#6b7280, #d1d5db, #111827 are Tailwind's gray-500/300/900), so
     * every printed report still looked like the design that was retired.
     *
     * tests/Unit/BrandTokensAreMirroredTest.php asserts the config and the
     * stylesheet agree; reading the config here puts the reports on the same
     * guarantee.
     */
    $b = config('gfms-brand');
@endphp
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
            color: {{ $b['ink'] }};
            margin: 0;
        }

        /* Running header - repeats on every page because it is position:fixed. */
        .page-header {
            position: fixed;
            top: -18mm;
            left: 0;
            right: 0;
            height: 14mm;
            border-bottom: 1pt solid {{ $b['rule_strong'] }};
        }

        .page-header .farm {
            font-size: 13pt;
            font-weight: bold;
            color: {{ $b['ink'] }};
        }

        .page-header .subtitle {
            font-size: 8pt;
            color: {{ $b['ink_muted'] }};
        }

        .page-footer {
            position: fixed;
            bottom: -14mm;
            left: 0;
            right: 0;
            height: 10mm;
            border-top: 1pt solid {{ $b['rule_strong'] }};
            padding-top: 2mm;
            font-size: 7.5pt;
            color: {{ $b['ink_muted'] }};
        }

        /* dompdf resolves these counters at render time. */
        .page-number:after {
            content: "Page " counter(page) " of " counter(pages);
        }

        h1 {
            font-size: 15pt;
            margin: 0 0 2mm 0;
            color: {{ $b['ink'] }};
        }

        .meta {
            font-size: 8pt;
            color: {{ $b['ink_muted'] }};
            margin-bottom: 4mm;
        }

        /* Filter summary - a report is not defensible unless it states the
           exact parameters it was generated with. */
        .filters {
            background-color: {{ $b['sunk'] }};
            border: 0.5pt solid {{ $b['rule_strong'] }};
            padding: 2.5mm 3mm;
            margin-bottom: 5mm;
            font-size: 8pt;
        }

        .filters strong {
            color: {{ $b['ink'] }};
        }

        table.data {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 5mm;
        }

        table.data thead th {
            background-color: {{ $b['ink'] }};
            color: #ffffff;
            font-size: 8pt;
            font-weight: bold;
            text-align: left;
            padding: 2mm;
            border: 0.5pt solid {{ $b['ink'] }};
        }

        table.data tbody td {
            padding: 1.8mm 2mm;
            border: 0.5pt solid {{ $b['rule'] }};
            vertical-align: top;
        }

        /* dompdf supports :nth-child on table rows for zebra striping. */
        table.data tbody tr:nth-child(even) td {
            background-color: {{ $b['paper'] }};
        }

        table.data tfoot td {
            padding: 2mm;
            border: 0.5pt solid {{ $b['rule_strong'] }};
            background-color: {{ $b['sunk'] }};
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
            background-color: {{ $b['sunk'] }};
            border: 0.5pt solid {{ $b['rule_strong'] }};
            padding: 2.5mm;
            width: 25%;
        }

        table.summary .label {
            font-size: 7.5pt;
            color: {{ $b['ink_muted'] }};
            text-transform: uppercase;
        }

        table.summary .value {
            font-size: 14pt;
            font-weight: bold;
            color: {{ $b['ink'] }};
        }

        .empty {
            padding: 12mm;
            text-align: center;
            color: {{ $b['ink_muted'] }};
            border: 0.5pt dashed {{ $b['rule_strong'] }};
        }

        .badge {
            padding: 0.4mm 1.4mm;
            font-size: 7.5pt;
            border: 0.5pt solid {{ $b['rule_strong'] }};
            background-color: {{ $b['sunk'] }};
        }

        .badge-danger { background-color: {{ $b['alert_wash'] }}; border-color: {{ $b['alert'] }}; color: {{ $b['alert'] }}; }
        .badge-warn   { background-color: {{ $b['warn_wash'] }}; border-color: {{ $b['warn'] }}; color: {{ $b['warn'] }}; }
        .badge-ok     { background-color: {{ $b['ok_wash'] }}; border-color: {{ $b['ok'] }}; color: {{ $b['ok'] }}; }
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
