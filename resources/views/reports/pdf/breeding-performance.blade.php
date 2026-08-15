{{--
    Breeding Performance PDF.

    CSS 2.1 only - see the note at the top of _layout.blade.php. Everything here
    is laid out with <table>; there is no flexbox, no grid, no custom property
    and no Tailwind class in this file.

    ReportController is shared by every report and passes only title, subtitle,
    filterSummary, columns, rows, summary, generatedAt and generatedBy. The
    second table (by bloodline) is therefore folded from $rows rather than
    re-queried - see BreedingPerformanceReport::foldByBloodline(). Both the
    fold and the tfoot totals add up whole-number egg counts and divide once at
    the end; neither averages a percentage.
--}}
@php
    use App\Reports\BreedingPerformanceReport;

    $bloodlineRows = BreedingPerformanceReport::foldByBloodline($rows);

    $totals = [
        'matings' => (int) $rows->sum('matings'),
        'eggs_set' => (int) $rows->sum('eggs_set'),
        'eggs_fertile' => (int) $rows->sum('eggs_fertile'),
        'eggs_hatched' => (int) $rows->sum('eggs_hatched'),
    ];

    $formatRate = static fn (?float $rate): string => $rate === null ? '—' : number_format($rate, 1) . '%';
@endphp

@component('reports.pdf._layout', [
    'title' => $title,
    'subtitle' => $subtitle,
    'filterSummary' => $filterSummary,
    'generatedAt' => $generatedAt,
    'generatedBy' => $generatedBy,
])
    {{-- Summary tiles. A table, not flexbox. --}}
    <table class="summary">
        <tr>
            @foreach ($summary as $label => $value)
                <td>
                    <div class="label">{{ $label }}</div>
                    <div class="value">
                        @if ($value === null)
                            &mdash;
                        @elseif (str_contains((string) $label, 'Rate'))
                            {{ number_format((float) $value, 1) }}%
                        @else
                            {{ number_format((float) $value) }}
                        @endif
                    </div>
                </td>
            @endforeach
        </tr>
    </table>

    {{-- ------------------------------------------------------------------
         TABLE 1 - by pair. This is also what the CSV export contains.
         ------------------------------------------------------------------ --}}
    <h2 style="font-size: 11pt; margin: 0 0 2mm 0;">By Pair</h2>

    @if ($rows->isEmpty())
        <div class="empty">
            No matings match these filters. Widen the date range, or record a mating first.
        </div>
    @else
        <table class="data">
            <thead>
                <tr>
                    <th style="width: 19%;">Sire</th>
                    <th style="width: 19%;">Dam</th>
                    <th style="width: 12%;">Bloodline</th>
                    <th class="num" style="width: 7%;">Matings</th>
                    <th class="num" style="width: 8%;">Eggs Set</th>
                    <th class="num" style="width: 8%;">Fertile</th>
                    <th class="num" style="width: 8%;">Hatched</th>
                    <th class="num" style="width: 9%;">Fertility %</th>
                    <th class="num" style="width: 10%;">Hatch %</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($rows as $row)
                    <tr>
                        <td>{{ $row['sire'] }}</td>
                        <td>{{ $row['dam'] }}</td>
                        <td>{{ $row['bloodline'] }}</td>
                        <td class="num">{{ number_format((int) $row['matings']) }}</td>
                        <td class="num">{{ number_format((int) $row['eggs_set']) }}</td>
                        <td class="num">{{ number_format((int) $row['eggs_fertile']) }}</td>
                        <td class="num">{{ number_format((int) $row['eggs_hatched']) }}</td>
                        {{-- An em dash, never 0%: no eggs set is an absent
                             measurement, not a total breeding failure. --}}
                        <td class="num">{{ $formatRate($row['fertility_rate']) }}</td>
                        <td class="num">{{ $formatRate($row['hatch_rate']) }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="3">All pairs</td>
                    <td class="num">{{ number_format($totals['matings']) }}</td>
                    <td class="num">{{ number_format($totals['eggs_set']) }}</td>
                    <td class="num">{{ number_format($totals['eggs_fertile']) }}</td>
                    <td class="num">{{ number_format($totals['eggs_hatched']) }}</td>
                    <td class="num">{{ $formatRate($summary['Overall Fertility Rate']) }}</td>
                    <td class="num">{{ $formatRate($summary['Overall Hatch Rate']) }}</td>
                </tr>
            </tfoot>
        </table>
    @endif

    {{-- ------------------------------------------------------------------
         TABLE 2 - by bloodline.

         Folded from the pair rows above by summing their EGG COUNTS and
         dividing once - never by averaging their percentages, which would
         weight a 2-egg pair the same as a 200-egg one. These figures are
         SUM(fertile) / SUM(set).
         ------------------------------------------------------------------ --}}
    <h2 style="font-size: 11pt; margin: 4mm 0 2mm 0;">By Bloodline</h2>

    <p class="meta" style="margin-bottom: 2mm;">
        Bloodline of the sire. Rates are weighted by eggs actually set, so a
        single large clutch counts for more than a single small one.
    </p>

    @if ($bloodlineRows->isEmpty())
        <div class="empty">
            No bloodline totals to show for these filters.
        </div>
    @else
        <table class="data">
            <thead>
                <tr>
                    <th style="width: 24%;">Bloodline</th>
                    <th class="num" style="width: 9%;">Pairs</th>
                    <th class="num" style="width: 9%;">Matings</th>
                    <th class="num" style="width: 12%;">Eggs Set</th>
                    <th class="num" style="width: 12%;">Fertile</th>
                    <th class="num" style="width: 12%;">Hatched</th>
                    <th class="num" style="width: 11%;">Fertility %</th>
                    <th class="num" style="width: 11%;">Hatch %</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($bloodlineRows as $row)
                    <tr>
                        <td>{{ $row['bloodline'] }}</td>
                        <td class="num">{{ number_format((int) $row['pairs']) }}</td>
                        <td class="num">{{ number_format((int) $row['matings']) }}</td>
                        <td class="num">{{ number_format((int) $row['eggs_set']) }}</td>
                        <td class="num">{{ number_format((int) $row['eggs_fertile']) }}</td>
                        <td class="num">{{ number_format((int) $row['eggs_hatched']) }}</td>
                        <td class="num">{{ $formatRate($row['fertility_rate']) }}</td>
                        <td class="num">{{ $formatRate($row['hatch_rate']) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <p class="meta">
        Fertility rate is fertile eggs divided by eggs set. Hatch rate is chicks
        hatched divided by <em>fertile</em> eggs, so it measures incubation alone
        and does not double-count infertility already reported to its left.
        A dash means no eggs were set (or none proved fertile) - that is an
        unknown rate, not a zero one.
    </p>
@endcomponent
