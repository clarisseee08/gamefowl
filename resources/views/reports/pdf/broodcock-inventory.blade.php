{{--
    Broodcock Inventory - PDF.

    dompdf is a CSS 2.1 engine: tables only, no flexbox/grid/custom properties,
    sizes in pt/mm/%. See the header comment in _layout.blade.php.

    The layout supplies the running header, footer, title, filter line and all
    shared classes (table.data, table.summary, .num, .badge-*). Only the rules
    this report actually needs are added below.
--}}
@php
    /**
     * The configured report instance, shared into the container by
     * withFilters(). ReportController's view payload is the same for every
     * report and carries columns/rows/summary only, so the two distribution
     * breakdowns - which are grouped in SQL, not counted off $rows - are
     * fetched from the definition itself.
     */
    // ReportController passes the configured report instance in as $report.
    // The container lookup is only a fallback for rendering this view directly
    // (as the tests do) without going through the controller.
    $report = $report ?? app(App\Reports\BroodcockInventoryReport::class);
    $statusBreakdown = $report->statusBreakdown();
    $bloodlineBreakdown = $report->bloodlineBreakdown();
@endphp

@component('reports.pdf._layout', [
    'title' => $title,
    'subtitle' => $subtitle,
    'filterSummary' => $filterSummary,
    'generatedAt' => $generatedAt,
    'generatedBy' => $generatedBy,
])
    <style>
        /* Fourteen columns on A4 landscape is tight, so the census table runs
           a little smaller than the layout default. */
        table.data.inventory thead th,
        table.data.inventory tbody td {
            font-size: 7.5pt;
            padding: 1.4mm 1.6mm;
        }

        /* The two breakdowns sit side by side in one row of an outer table -
           this is the CSS 2.1 substitute for a two-column flex layout. */
        table.split {
            width: 100%;
            border-collapse: separate;
            border-spacing: 3mm 0;
        }

        table.split > tbody > tr > td {
            width: 50%;
            vertical-align: top;
            padding: 0;
        }

        h2.section {
            font-size: 10pt;
            margin: 0 0 2mm 0;
            color: #166534;
        }

        /* A pure-CSS share bar. No flexbox: a fixed-width track holding a
           single nested cell whose width is a percentage. */
        table.bar {
            width: 22mm;
            border-collapse: collapse;
            background-color: #e5e7eb;
            border: 0.5pt solid #d1d5db;
        }

        table.bar td {
            height: 2.6mm;
            padding: 0;
            border: 0;
            background-color: #166534;
        }
    </style>

    {{-- Headline figures. summary() hands back label => value already
         formatted, so the tiles never re-derive a number the report has
         already stated. --}}
    @if (! empty($summary))
        <table class="summary">
            <tr>
                @foreach ($summary as $label => $value)
                    <td>
                        <div class="label">{{ $label }}</div>
                        <div class="value">{{ $value }}</div>
                    </td>
                @endforeach
            </tr>
        </table>
    @endif

    @if ($rows->isEmpty())
        {{-- Never a blank page: say what was searched and what to do next. --}}
        <div class="empty">
            No birds match these filters.<br>
            Widen the filters above, or record a bird from the Broodcocks screen.
        </div>
    @else
        <table class="data inventory">
            {{-- thead repeats automatically on every page break. --}}
            <thead>
                <tr>
                    @foreach ($columns as $key => $heading)
                        <th @class(['num' => in_array($key, ['weight'], true)])>{{ $heading }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach ($rows as $row)
                    <tr>
                        @foreach ($columns as $key => $heading)
                            <td @class(['num' => $key === 'weight'])>{{ $row[$key] ?? '—' }}</td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="{{ count($columns) }}">
                        {{ $rows->count() }} {{ Str::plural('bird', $rows->count()) }} listed
                    </td>
                </tr>
            </tfoot>
        </table>

        {{-- Distribution. Both tallies are SQL group-bys against the same
             filtered query as the table above, so they agree with it by
             construction rather than by a second pass over the rows. --}}
        <table class="split">
            <tr>
                <td>
                    <h2 class="section">Birds by Status</h2>
                    <table class="data">
                        <thead>
                            <tr>
                                <th>Status</th>
                                <th class="num">Birds</th>
                                <th>Share</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($statusBreakdown as $entry)
                                <tr>
                                    <td>{{ $entry['label'] }}</td>
                                    <td class="num">{{ $entry['count'] }}</td>
                                    <td>
                                        <table class="bar">
                                            <tr>
                                                <td style="width: {{ $entry['share'] }}%"></td>
                                                <td style="background-color: transparent"></td>
                                            </tr>
                                        </table>
                                        {{ $entry['share'] }}%
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </td>
                <td>
                    <h2 class="section">Birds by Bloodline</h2>
                    <table class="data">
                        <thead>
                            <tr>
                                <th>Bloodline</th>
                                <th class="num">Birds</th>
                                <th>Share</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($bloodlineBreakdown as $entry)
                                <tr>
                                    <td>{{ $entry['label'] }}</td>
                                    <td class="num">{{ $entry['count'] }}</td>
                                    <td>
                                        <table class="bar">
                                            <tr>
                                                <td style="width: {{ $entry['share'] }}%"></td>
                                                <td style="background-color: transparent"></td>
                                            </tr>
                                        </table>
                                        {{ $entry['share'] }}%
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </td>
            </tr>
        </table>
    @endif
@endcomponent
