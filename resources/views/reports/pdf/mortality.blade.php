{{--
    Mortality report PDF.

    CSS 2.1 ONLY - dompdf. No flexbox, no grid, no custom properties, no
    oklch(), no Tailwind. Everything below lays out with <table> and uses
    pt/mm/% sizing, per resources/views/reports/pdf/_layout.blade.php.

    The by-cause and by-period tables are aggregated in SQL by
    App\Reports\MortalityReport. ReportController passes this view only
    columns/rows/summary, so the configured report instance is read back from
    the container key it published itself under - see MortalityReport::CURRENT.
--}}
@php
    /** @var \App\Reports\MortalityReport $report */
    $report = app()->bound(\App\Reports\MortalityReport::CURRENT)
        ? app(\App\Reports\MortalityReport::CURRENT)
        : app(\App\Reports\MortalityReport::class);

    $byCause = $report->byCause();
    $byPeriod = $report->byPeriod();
    $rate = $report->mortalityRate();

    $columnCount = count($columns);
    $peakPeriod = $byPeriod->sortByDesc('deaths')->first();
@endphp

@component('reports.pdf._layout', [
    'title' => $title,
    'subtitle' => $subtitle,
    'filterSummary' => $filterSummary,
    'generatedAt' => $generatedAt,
    'generatedBy' => $generatedBy,
])

    {{-- Summary tiles. A table, not flexbox - dompdf cannot lay out flex. --}}
    <table class="summary">
        <tr>
            @foreach ($summary as $label => $value)
                <td>
                    <div class="label">{{ $label }}</div>
                    <div class="value">{{ $value === null || $value === '' ? '—' : $value }}</div>
                </td>
            @endforeach
        </tr>
    </table>

    {{--
        The denominator, in words.

        A rate whose denominator is not stated is not a rate a panel can check.
        This block says exactly what was divided by what, and says plainly that
        the figure is a proxy rather than a true average-flock-size rate.
    --}}
    <table style="width: 100%; border-collapse: collapse; margin-bottom: 5mm;">
        <tr>
            <td style="border: 0.5pt solid #fca5a5; background-color: #fef2f2; padding: 3mm; font-size: 8pt;">
                <strong style="color: #991b1b;">How the mortality rate was calculated</strong><br>
                <span>{{ $rate['denominator_sentence'] }}</span><br>
                <span style="color: #6b7280;">
                    "On the farm today" counts birds whose status is
                    {{ implode(', ', $report->onFarmStatuses()) }} &mdash; it excludes sold and deceased birds.
                </span>
            </td>
        </tr>
        <tr>
            <td style="border: 0.5pt solid #fcd34d; border-top: 0; background-color: #fffbeb; padding: 3mm; font-size: 7.5pt; color: #92400e;">
                <strong>Why this is a proxy:</strong> {{ $rate['caveat'] }}
            </td>
        </tr>
    </table>

    {{-- ---------------------------------------------------------------
         1. The death register itself.
    ---------------------------------------------------------------- --}}
    <h2 style="font-size: 11pt; margin: 0 0 2mm 0;">Death Register</h2>

    @if ($rows->isEmpty())
        <div class="empty">
            No deaths were recorded for these filters.<br>
            Widen the date range, or clear the cause and bloodline filters, to see more.
        </div>
    @else
        <table class="data">
            {{-- <thead> repeats automatically on every page break. --}}
            <thead>
                <tr>
                    @foreach ($columns as $key => $heading)
                        <th @class(['num' => $key === 'age_at_death'])>{{ $heading }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach ($rows as $row)
                    <tr>
                        @foreach ($columns as $key => $heading)
                            <td @class(['num' => $key === 'age_at_death'])>
                                @if ($key === 'age_at_death' && $row[$key] === 'Unknown')
                                    {{-- No hatch date on file: the age is genuinely
                                         unknown, and "0 months" would be a lie. --}}
                                    <span style="color: #6b7280;">Unknown</span>
                                @else
                                    {{ $row[$key] ?? '—' }}
                                @endif
                            </td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="{{ $columnCount }}">
                        {{ $rows->count() }} {{ \Illuminate\Support\Str::plural('death', $rows->count()) }} listed
                    </td>
                </tr>
            </tfoot>
        </table>
    @endif

    {{-- ---------------------------------------------------------------
         2. Deaths by cause.
    ---------------------------------------------------------------- --}}
    <h2 style="font-size: 11pt; margin: 6mm 0 2mm 0;">Deaths by Cause</h2>

    @if ($byCause->isEmpty())
        <div class="empty">No causes to break down for these filters.</div>
    @else
        <table class="data" style="width: 70%;">
            <thead>
                <tr>
                    <th style="width: 50%;">Cause of Death</th>
                    <th class="num" style="width: 25%;">Number of Deaths</th>
                    <th class="num" style="width: 25%;">% of Deaths in Range</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($byCause as $cause)
                    <tr>
                        <td>{{ $cause['cause'] }}</td>
                        <td class="num">{{ number_format($cause['deaths']) }}</td>
                        <td class="num">{{ number_format($cause['percentage'], 1) }}%</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td>Total</td>
                    <td class="num">{{ number_format($byCause->sum('deaths')) }}</td>
                    <td class="num">{{ number_format($byCause->sum('percentage'), 1) }}%</td>
                </tr>
            </tfoot>
        </table>
        <p style="font-size: 7.5pt; color: #6b7280; margin: 0 0 4mm 0;">
            Percentages are of the {{ number_format($byCause->sum('deaths')) }}
            {{ \Illuminate\Support\Str::plural('death', $byCause->sum('deaths')) }}
            matching these filters, not of all deaths ever recorded. Rounding to one
            decimal place can leave the total a tenth either side of 100%.
        </p>
    @endif

    {{-- ---------------------------------------------------------------
         3. Deaths by calendar month.
    ---------------------------------------------------------------- --}}
    <h2 style="font-size: 11pt; margin: 6mm 0 2mm 0;">Deaths by Month</h2>

    @if ($byPeriod->isEmpty())
        <div class="empty">No months to chart for these filters.</div>
    @else
        <table class="data" style="width: 70%;">
            <thead>
                <tr>
                    <th style="width: 40%;">Month</th>
                    <th class="num" style="width: 20%;">Deaths</th>
                    <th style="width: 40%;">Share of Range</th>
                </tr>
            </thead>
            <tbody>
                @php $periodTotal = max(1, (int) $byPeriod->sum('deaths')); @endphp
                @foreach ($byPeriod as $period)
                    <tr>
                        <td>{{ $period['label'] }}</td>
                        <td class="num">{{ number_format($period['deaths']) }}</td>
                        <td>
                            @if ($period['deaths'] === 0)
                                <span style="color: #6b7280;">No deaths</span>
                            @else
                                {{-- A bar drawn as a table cell with a width -
                                     dompdf has no flexbox to size one with. --}}
                                <table style="width: 100%; border-collapse: collapse;">
                                    <tr>
                                        <td style="width: {{ round($period['deaths'] / $periodTotal * 100) }}%; background-color: #166534; height: 2.6mm; border: 0;"></td>
                                        <td style="border: 0;"></td>
                                    </tr>
                                </table>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td>Total</td>
                    <td class="num">{{ number_format($byPeriod->sum('deaths')) }}</td>
                    <td>
                        @if ($peakPeriod !== null && $peakPeriod['deaths'] > 0)
                            Worst month: {{ $peakPeriod['label'] }} ({{ $peakPeriod['deaths'] }})
                        @endif
                    </td>
                </tr>
            </tfoot>
        </table>
        <p style="font-size: 7.5pt; color: #6b7280; margin: 0;">
            Months inside the filtered range with no recorded deaths are shown as zero
            rather than omitted, so a quiet month cannot be mistaken for missing data.
        </p>
    @endif

@endcomponent
