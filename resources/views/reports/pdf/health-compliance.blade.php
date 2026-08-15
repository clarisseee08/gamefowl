{{--
    Health and Vaccination Compliance - PDF.

    CSS 2.1 only. See the header comment in _layout.blade.php: no flexbox, no
    grid, no custom properties, no Tailwind. Everything below lays out with
    <table> and sizes in pt / mm / %.

    Overdue rows are shaded with an INLINE style rather than a class. The
    layout's zebra stripe (`table.data tbody tr:nth-child(even) td`) sets a td
    background at equal specificity, so a class of mine would win or lose
    depending on source order inside dompdf's cascade. An inline style always
    wins, which is the difference between "the overdue birds are obvious" and
    "the overdue birds are obvious on odd-numbered rows".

    Received from ReportController: $title, $subtitle, $filterSummary,
    $columns, $rows, $summary, $generatedAt, $generatedBy.
--}}
@component('reports.pdf._layout', [
    'title' => $title,
    'subtitle' => $subtitle,
    'filterSummary' => $filterSummary,
    'generatedAt' => $generatedAt,
    'generatedBy' => $generatedBy,
])

    {{-- Headline figures. Laid out as a table because dompdf has no flexbox. --}}
    <table class="summary">
        <tr>
            @foreach ($summary as $label => $value)
                <td>
                    <div class="label">{{ $label }}</div>
                    <div class="value">
                        @if ($value === null)
                            {{-- Deliberately not "0%". No bird has a follow-up
                                 scheduled, which is a different finding from
                                 every follow-up having been missed. --}}
                            No data
                        @elseif ($label === 'Compliance rate')
                            {{ number_format((float) $value, 1) }}%
                        @else
                            {{ $value }}
                        @endif
                    </div>
                    @if ($label === 'Compliance rate')
                        <div class="label" style="text-transform: none;">
                            @if ($value === null)
                                No record in range has a follow-up date
                            @else
                                Of records with a follow-up date, share not overdue
                            @endif
                        </div>
                    @endif
                </td>
            @endforeach
        </tr>
    </table>

    @if ($rows->isEmpty())
        <div class="empty">
            No health records match these filters. Try widening the date range,
            or clear the compliance filter to see every record.
        </div>
    @else
        <table class="data">
            {{-- <thead> repeats automatically on every page break. --}}
            <thead>
                <tr>
                    <th style="width: 7%;">{{ $columns['band_number'] }}</th>
                    <th style="width: 10%;">{{ $columns['bird_name'] }}</th>
                    <th style="width: 8%;">{{ $columns['record_type'] }}</th>
                    <th style="width: 12%;">{{ $columns['product_name'] }}</th>
                    <th style="width: 6%;">{{ $columns['dosage'] }}</th>
                    <th style="width: 8%;">{{ $columns['checkup_date'] }}</th>
                    <th style="width: 9%;">{{ $columns['next_due_date'] }}</th>
                    <th style="width: 9%;">{{ $columns['schedule_state'] }}</th>
                    <th style="width: 7%;" class="num">{{ $columns['days_until_due'] }}</th>
                    <th style="width: 10%;">{{ $columns['recorded_by'] }}</th>
                    <th style="width: 14%;">{{ $columns['remarks'] }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($rows as $row)
                    @php
                        $state = $row['schedule_state'];
                        $days = $row['days_until_due'];

                        $badge = match ($state) {
                            'Overdue' => 'badge badge-danger',
                            'Due soon' => 'badge badge-warn',
                            'Scheduled' => 'badge badge-ok',
                            default => 'badge',
                        };

                        // Inline, so it beats the layout's zebra stripe on every row.
                        // Already inside @php, so this is plain PHP - Blade's {{ }} does
                        // not apply here and would be emitted as literal text.
                        $cell = $state === 'Overdue'
                            ? ' style="background-color: '.config('gfms-brand.alert_wash').';"'
                            : '';
                    @endphp
                    <tr>
                        <td{!! $cell !!}>{{ $row['band_number'] }}</td>
                        <td{!! $cell !!}>{{ $row['bird_name'] }}</td>
                        <td{!! $cell !!}>{{ $row['record_type'] }}</td>
                        <td{!! $cell !!}>{{ $row['product_name'] }}</td>
                        <td{!! $cell !!}>{{ $row['dosage'] }}</td>
                        <td{!! $cell !!}>{{ $row['checkup_date'] }}</td>
                        <td{!! $cell !!}>{{ $row['next_due_date'] }}</td>
                        <td{!! $cell !!}><span class="{{ $badge }}">{{ $state }}</span></td>
                        <td class="num"{!! $cell !!}>
                            @if ($days === null)
                                &mdash;
                            @elseif ($days < 0)
                                {{-- Spelled out, not just "-12". A farm reading
                                     this at 5am should not have to decode a
                                     minus sign to know the bird is late. --}}
                                <strong>{{ abs((int) $days) }} late</strong>
                            @elseif ($days === 0)
                                Due today
                            @else
                                {{ $days }}
                            @endif
                        </td>
                        <td{!! $cell !!}>{{ $row['recorded_by'] }}</td>
                        {{-- Internal remarks. This report is internal-only:
                             ReportPolicy::create() denies customers, and the
                             controller authorizes before the query runs. --}}
                        <td{!! $cell !!}>{{ $row['remarks'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        {{-- Counts by record type. Kept below the main table because it
             explains the mix behind the totals rather than replacing them. --}}
        @php($typeCounts = \App\Reports\HealthComplianceReport::countsByType($rows))
        <table class="data" style="width: 60%;">
            <thead>
                <tr>
                    <th>Record Type</th>
                    <th class="num" style="width: 25%;">Records</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($typeCounts as $type)
                    <tr>
                        <td>{{ $type['label'] }}</td>
                        <td class="num">{{ $type['count'] }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td>All types</td>
                    <td class="num">{{ $rows->count() }}</td>
                </tr>
            </tfoot>
        </table>
    @endif

@endcomponent
