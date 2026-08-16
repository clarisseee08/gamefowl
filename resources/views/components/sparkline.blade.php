@props([
    'points' => [],      // list<int|float|null> — null means "no data that period"
    'label' => '',       // screen-reader description; the shape alone says nothing
    'tone' => 'primary', // primary | success | warning | destructive
])

@php
    /*
     * A sparkline drawn as a plain SVG polyline. No charting library: the thesis
     * describes a PHP/HTML/CSS system, and a JS chart dependency for twelve
     * points would contradict it for no gain.
     *
     * Nulls are real here. A month with no matings is not a month with 0%
     * fertility, and joining straight through the gap would draw a slope that
     * never happened - so the line breaks and the gap is left empty.
     */
    $values = array_values($points);
    $known = array_values(array_filter($values, fn ($v) => $v !== null));

    $w = 120;
    $h = 32;
    $pad = 2;

    $max = $known === [] ? 1 : max(max($known), 1);
    $min = 0;                              // rates and counts are read against zero
    $span = max($max - $min, 1);
    $step = count($values) > 1 ? ($w - $pad * 2) / (count($values) - 1) : 0;

    /*
     * Consecutive runs of real values become separate polylines, so a gap in the
     * data reads as a gap rather than as a straight line through it.
     *
     * A run of ONE cannot be a line, but it is still a reading that happened -
     * it becomes a dot. Dropping it made the component claim "not enough data"
     * next to a live figure on the dashboard, because this farm has two
     * non-adjacent months of matings in the last twelve.
     */
    $segments = [];
    $dots = [];
    $current = [];

    $flush = function () use (&$segments, &$dots, &$current) {
        if (count($current) > 1) {
            $segments[] = $current;
        } elseif (count($current) === 1) {
            $dots[] = $current[0];
        }
        $current = [];
    };

    foreach ($values as $i => $v) {
        if ($v === null) {
            $flush();

            continue;
        }

        $x = $pad + $i * $step;
        $y = $h - $pad - (($v - $min) / $span) * ($h - $pad * 2);
        $current[] = round($x, 1).','.round($y, 1);
    }

    $flush();

    $last = null;
    foreach (array_reverse($values, true) as $i => $v) {
        if ($v !== null) {
            $last = [
                'x' => round($pad + $i * $step, 1),
                'y' => round($h - $pad - (($v - $min) / $span) * ($h - $pad * 2), 1),
            ];
            break;
        }
    }

    $stroke = [
        'primary' => 'var(--color-primary)',
        'success' => 'var(--color-success)',
        'warning' => 'var(--color-warning)',
        'destructive' => 'var(--color-destructive)',
    ][$tone] ?? 'var(--color-primary)';
@endphp

@if ($segments !== [] || $dots !== [])
    <svg {{ $attributes->merge(['class' => 'h-8 w-[120px] overflow-visible']) }}
         viewBox="0 0 {{ $w }} {{ $h }}" fill="none" role="img"
         aria-label="{{ $label }}" preserveAspectRatio="none">
        @foreach ($segments as $segment)
            <polyline points="{{ implode(' ', $segment) }}"
                      stroke="{{ $stroke }}" stroke-width="1.5"
                      stroke-linecap="round" stroke-linejoin="round" vector-effect="non-scaling-stroke"/>
        @endforeach

        {{-- Isolated readings, drawn as dots. Two months of data a year apart
             is still information; it just is not a line. --}}
        @foreach ($dots as $dot)
            @php [$dx, $dy] = explode(',', $dot); @endphp
            <circle cx="{{ $dx }}" cy="{{ $dy }}" r="1.75" fill="{{ $stroke }}" opacity="0.75"/>
        @endforeach

        {{-- The endpoint is emphasised because "where it is now" is the thing a
             sparkline is actually read for. --}}
        @if ($last)
            <circle cx="{{ $last['x'] }}" cy="{{ $last['y'] }}" r="2" fill="{{ $stroke }}"/>
        @endif
    </svg>
@else
    <span class="text-[11px] text-muted-foreground">Not enough data yet</span>
@endif
