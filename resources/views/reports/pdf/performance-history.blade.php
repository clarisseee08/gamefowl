{{--
    Performance History PDF.

    CSS 2.1 only - dompdf. No flexbox, no grid, no custom properties, no
    Tailwind. Everything below is laid out with <table> and sized in pt/mm/%.
    See the header comment in _layout.blade.php before changing anything.
--}}
@php
    /**
     * The per-bird aggregation.
     *
     * ReportController passes the same view payload to every report, so it
     * cannot know about this report's second table. The configured instance
     * binds itself into the container in withFilters(); we resolve it here and
     * then PROVE it is the right one by comparing its filter summary with the
     * one already rendered in the header. If they disagree we show nothing
     * rather than a table silently computed over unfiltered data - a per-bird
     * win rate that does not match the rows above it is worse than no table.
     */
    $report = app(\App\Reports\PerformanceHistoryReport::class);
    $perBird = $report->filterSummary() === $filterSummary
        ? $report->perBirdSummary()
        : null;
@endphp

@component('reports.pdf._layout', [
    'title' => $title,
    'subtitle' => $subtitle,
    'filterSummary' => $filterSummary,
    'generatedAt' => $generatedAt,
    'generatedBy' => $generatedBy,
])
    <table class="summary">
        <tr>
            @foreach (['Events', 'Contests', 'Birds'] as $tile)
                <td style="width: 33%;">
                    <div class="label">{{ $tile }}</div>
                    <div class="value">{{ $summary[$tile] ?? '-' }}</div>
                </td>
            @endforeach
        </tr>
    </table>

    <table class="summary">
        <tr>
            @foreach (['Record (W-L-D)', 'Win Rate', 'Average Rating'] as $tile)
                <td style="width: 33%;">
                    <div class="label">{{ $tile }}</div>
                    <div class="value" style="font-size: 12pt;">{{ $summary[$tile] ?? '-' }}</div>
                </td>
            @endforeach
        </tr>
    </table>

    {{-- Stated in words because a reader cannot see the denominator otherwise.
         The whole defensibility of this report rests on this sentence. --}}
    <p class="meta">
        Win rate is calculated over contest events only (sparring and derby).
        Conditioning sessions and weigh-ins have no winner and are excluded from
        the denominator. A bird with no contests shows &ldquo;No contests
        yet&rdquo;, never 0%.
    </p>

    @if ($rows->isEmpty())
        <div class="empty">
            No performance records match these filters. Try widening the date
            range, or clear the filters to see every recorded event.
        </div>
    @else
        <table class="data">
            <thead>
                <tr>
                    <th style="width: 9%;">{{ $columns['event_date'] }}</th>
                    <th style="width: 10%;">{{ $columns['band_number'] }}</th>
                    <th style="width: 12%;">{{ $columns['bird_name'] }}</th>
                    <th style="width: 10%;">{{ $columns['bloodline'] }}</th>
                    <th style="width: 10%;">{{ $columns['event_type'] }}</th>
                    <th style="width: 9%;">{{ $columns['result'] }}</th>
                    <th style="width: 8%;" class="num">{{ $columns['weight'] }}</th>
                    <th style="width: 8%;" class="num">{{ $columns['duration'] }}</th>
                    <th style="width: 8%;" class="center">{{ $columns['rating'] }}</th>
                    <th style="width: 16%;">{{ $columns['recorded_by'] }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($rows as $row)
                    <tr>
                        <td>{{ $row['event_date'] }}</td>
                        <td>{{ $row['band_number'] }}</td>
                        <td>{{ $row['bird_name'] }}</td>
                        <td>{{ $row['bloodline'] }}</td>
                        <td>{{ $row['event_type'] }}</td>
                        <td>
                            @if ($row['result'] === 'Win')
                                <span class="badge badge-ok">Win</span>
                            @elseif ($row['result'] === 'Loss')
                                <span class="badge badge-danger">Loss</span>
                            @elseif ($row['result'] === 'Draw')
                                <span class="badge badge-warn">Draw</span>
                            @else
                                {{ $row['result'] }}
                            @endif
                        </td>
                        <td class="num">{{ $row['weight'] }}</td>
                        <td class="num">{{ $row['duration'] }}</td>
                        <td class="center">{{ $row['rating'] }}</td>
                        <td>{{ $row['recorded_by'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    @if ($perBird !== null && $perBird->isNotEmpty())
        <h2 style="font-size: 12pt; margin: 6mm 0 2mm 0;">Per-Bird Summary</h2>
        <p class="meta">
            Contest record for each bird in the rows above. Wins, losses and
            draws are contests; conditioning and weigh-in events are counted
            under Events but never under Contests.
        </p>

        <table class="data">
            <thead>
                <tr>
                    <th style="width: 26%;">Bird</th>
                    <th style="width: 14%;">Bloodline</th>
                    <th style="width: 8%;" class="num">Events</th>
                    <th style="width: 9%;" class="num">Contests</th>
                    <th style="width: 7%;" class="num">Wins</th>
                    <th style="width: 7%;" class="num">Losses</th>
                    <th style="width: 7%;" class="num">Draws</th>
                    <th style="width: 11%;" class="num">Win Rate</th>
                    <th style="width: 11%;" class="num">Avg. Rating</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($perBird as $bird)
                    <tr>
                        <td>{{ $bird['bird'] }}</td>
                        <td>{{ $bird['bloodline'] }}</td>
                        <td class="num">{{ $bird['events'] }}</td>
                        <td class="num">{{ $bird['contests'] }}</td>
                        <td class="num">{{ $bird['wins'] }}</td>
                        <td class="num">{{ $bird['losses'] }}</td>
                        <td class="num">{{ $bird['draws'] }}</td>
                        <td class="num">
                            {{-- null is NOT 0%. A bird that never competed has
                                 an unknown record, not a perfect loss record. --}}
                            @if ($bird['win_rate'] === null)
                                No contests yet
                            @else
                                {{ number_format($bird['win_rate'], 1) }}%
                            @endif
                        </td>
                        <td class="num">
                            @if ($bird['average_rating'] === null)
                                Not rated yet
                            @else
                                {{ number_format($bird['average_rating'], 1) }} / 5
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
@endcomponent
