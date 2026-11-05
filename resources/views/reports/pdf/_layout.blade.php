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

    /*
     * THE TYPE: an editorial document, set the way a report written by Claude
     * reads - a serif for the title and the prose (Source Serif 4, an open
     * cousin of the serif Claude writes in; Claude's own faces are
     * proprietary) and Inter, the app's face, for tables and figures, whose
     * tabular digits keep a column of numbers aligned.
     *
     * Both are OFL-licensed TTFs in resources/fonts (licences beside them),
     * read by dompdf from inside its chroot. Forward slashes: dompdf takes a
     * path, and a Windows backslash is not one it can open.
     */
    $font = fn (string $file): string => str_replace('\\', '/', resource_path('fonts/'.$file));
@endphp
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        @font-face { font-family: 'Source Serif'; font-style: normal; font-weight: normal; src: url('{{ $font('source-serif-4-latin-400-normal.ttf') }}') format('truetype'); }
        @font-face { font-family: 'Source Serif'; font-style: normal; font-weight: bold; src: url('{{ $font('source-serif-4-latin-600-normal.ttf') }}') format('truetype'); }
        @font-face { font-family: 'Source Serif'; font-style: italic; font-weight: normal; src: url('{{ $font('source-serif-4-latin-400-italic.ttf') }}') format('truetype'); }
        @font-face { font-family: 'Inter'; font-style: normal; font-weight: normal; src: url('{{ $font('inter-latin-400-normal.ttf') }}') format('truetype'); }
        @font-face { font-family: 'Inter'; font-style: normal; font-weight: bold; src: url('{{ $font('inter-latin-600-normal.ttf') }}') format('truetype'); }

        @page {
            margin: 24mm 16mm 20mm 16mm;
        }

        body {
            font-family: 'Inter', DejaVu Sans, sans-serif;
            font-size: 8.5pt;
            line-height: 1.45;
            color: {{ $b['foreground'] }};
            margin: 0;
        }

        /* Running header - repeats on every page because it is position:fixed.
           A quiet line of small type over a hairline, as a document's header. */
        .page-header {
            position: fixed;
            top: -15mm;
            left: 0;
            right: 0;
            height: 9mm;
            border-bottom: 0.5pt solid {{ $b['print']['rule_strong'] }};
        }

        .page-header .farm {
            font-family: 'Source Serif', serif;
            font-size: 10.5pt;
            font-weight: bold;
            color: {{ $b['foreground'] }};
        }

        .page-header .subtitle {
            font-size: 7.5pt;
            color: {{ $b['muted_foreground'] }};
        }

        .page-footer {
            position: fixed;
            bottom: -13mm;
            left: 0;
            right: 0;
            height: 8mm;
            border-top: 0.5pt solid {{ $b['print']['rule'] }};
            padding-top: 2mm;
            font-size: 7pt;
            color: {{ $b['muted_foreground'] }};
        }


        /* The title block: a small spaced eyebrow, the title in the serif,
           the subtitle in its italic. */
        .eyebrow {
            font-size: 7pt;
            font-weight: bold;
            letter-spacing: 1.2pt;
            text-transform: uppercase;
            color: {{ $b['brand'] }};
            margin: 0 0 1.5mm 0;
        }

        h1 {
            font-family: 'Source Serif', serif;
            font-size: 22pt;
            font-weight: bold;
            line-height: 1.15;
            margin: 0 0 1.5mm 0;
            color: {{ $b['foreground'] }};
        }

        .meta {
            font-family: 'Source Serif', serif;
            font-style: italic;
            font-size: 10pt;
            color: {{ $b['muted_foreground'] }};
            margin: 0 0 5mm 0;
        }

        h2, h3 {
            font-family: 'Source Serif', serif;
            font-weight: bold;
            color: {{ $b['foreground'] }};
            margin: 6mm 0 2.5mm 0;
        }

        h2 { font-size: 13pt; }
        h3 { font-size: 11pt; }

        /* Filter summary - a report is not defensible unless it states the
           exact parameters it was generated with. A margin note, not a box. */
        .filters {
            border-left: 2pt solid {{ $b['brand'] }};
            padding: 1mm 0 1mm 3mm;
            margin: 0 0 6mm 0;
            font-size: 8pt;
            color: {{ $b['muted_foreground'] }};
        }

        .filters strong {
            color: {{ $b['foreground'] }};
        }

        /* Tables: hairlines, an airy header row, no zebra boxes. */
        table.data {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 6mm;
        }

        table.data thead th {
            color: {{ $b['muted_foreground'] }};
            font-size: 7pt;
            font-weight: bold;
            letter-spacing: 0.4pt;
            text-transform: uppercase;
            text-align: left;
            padding: 2mm 2mm 2mm 0;
            border-bottom: 1pt solid {{ $b['foreground'] }};
        }

        table.data tbody td {
            padding: 2mm 2mm 2mm 0;
            border-bottom: 0.5pt solid {{ $b['print']['rule'] }};
            vertical-align: top;
        }

        table.data tfoot td {
            padding: 2.2mm 2mm 2.2mm 0;
            border-top: 1pt solid {{ $b['foreground'] }};
            font-weight: bold;
        }

        .num {
            text-align: right;
        }

        .center {
            text-align: center;
        }

        /* Summary figures. Laid out as a TABLE, not flexbox - see the note
           above. The figure large, its label small and spaced beneath a
           hairline, the way a written report states its headline numbers. */
        table.summary {
            width: 100%;
            border-collapse: separate;
            border-spacing: 3mm 0;
            margin: 0 -3mm 6mm -3mm;
        }

        table.summary td {
            border-top: 1.5pt solid {{ $b['brand'] }};
            padding: 2.5mm 0 0 0;
            width: 25%;
            vertical-align: top;
        }

        table.summary .label {
            font-size: 6.8pt;
            font-weight: bold;
            letter-spacing: 0.8pt;
            color: {{ $b['muted_foreground'] }};
            text-transform: uppercase;
        }

        table.summary .value {
            font-family: 'Source Serif', serif;
            font-size: 17pt;
            font-weight: bold;
            line-height: 1.2;
            color: {{ $b['foreground'] }};
        }

        .empty {
            padding: 12mm;
            text-align: center;
            font-family: 'Source Serif', serif;
            font-style: italic;
            color: {{ $b['muted_foreground'] }};
            border-top: 0.5pt solid {{ $b['print']['rule'] }};
            border-bottom: 0.5pt solid {{ $b['print']['rule'] }};
        }

        .badge {
            padding: 0.3mm 1.6mm;
            font-size: 7pt;
            border: 0.5pt solid {{ $b['print']['rule_strong'] }};
            background-color: {{ $b['muted'] }};
        }

        .badge-danger { background-color: {{ $b['destructive_bg'] }}; border-color: {{ $b['destructive'] }}; color: {{ $b['destructive'] }}; }
        .badge-warn   { background-color: {{ $b['warning_bg'] }}; border-color: {{ $b['warning'] }}; color: {{ $b['warning'] }}; }
        .badge-ok     { background-color: {{ $b['success_bg'] }}; border-color: {{ $b['success'] }}; color: {{ $b['success'] }}; }
    </style>
</head>
<body>
    <div class="page-header">
        <span class="farm">{{ config('gfms.farm.name') }}</span>
        @if (config('gfms.farm.address'))
            <span class="subtitle">&nbsp;&middot;&nbsp;{{ config('gfms.farm.address') }}</span>
        @endif
        <span class="subtitle" style="float: right;">{{ config('gfms.system.name') }}</span>
    </div>

    <div class="page-footer">
        <span>Generated {{ $generatedAt->format('j F Y, g:i a') }}@if (! empty($generatedBy)) by {{ $generatedBy }}@endif</span>
        {{-- "Page X of Y" is stamped here by ReportController after
             rendering: dompdf cannot fill counter(pages). --}}
    </div>

    <p class="eyebrow">Report</p>
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
