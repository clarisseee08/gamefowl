@props([
    /** What is counted. Sentence case - the tile sets it in small capitals. */
    'label',
    /** The figure, already formatted. null shows a dash, read out as "No data". */
    'value' => null,
    /** A Lucide name beside the label. */
    'icon' => null,
    /** What the figure is counted over: "Across 26 birds on the farm". Never decoration. */
    'caption' => null,
    /** The change against the prior period, as a number. null means there is no prior period: no chip, never a fake 0. */
    'delta' => null,
    /** Appended to the change: '%', ' pts', ' birds'. */
    'deltaUnit' => '',
    /** Whether a rise is good news. Decides the chip's colour; the arrow always follows the sign. */
    'higherIsBetter' => true,
    /** Tints the figure for an alarm count, such as an overdue total. */
    'alarm' => false,
    /** Drill-down: the tile becomes a link. */
    'href' => null,
    /** What the drill-down does, for the tooltip and screen readers: "Show the overdue vaccinations". */
    'action' => null,
    'loading' => false,
])

{{--
    THE KPI TILE (EMS StatTile, Part B §6.6) - the quiet figure card built
    for rows of four, above a screen's main surface. Put a row of them in
    <x-ui.kpi-row>.

    The label, the change chip and the caption are 11px here where EMS has
    9.5 to 10.5px: nothing on the console is set under 11px. The change chip
    takes the status pairs the badges already use, so a good month reads in
    the same green as a vaccinated bird.
--}}
@php
    $deltaState = match (true) {
        $delta === null => null,
        (float) $delta === 0.0 => 'flat',
        ((float) $delta > 0) === (bool) $higherIsBetter => 'good',
        default => 'bad',
    };

    $deltaNumber = $delta === null ? '' : rtrim(rtrim(number_format(abs((float) $delta), 1, '.', ','), '0'), '.');

    $tile = [
        'flex flex-col gap-0 rounded-xl border border-border/60 bg-card px-3.5 py-3 text-card-foreground shadow-none transition-colors',
        $href ? 'cursor-pointer outline-none hover:border-primary/40 focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:ring-offset-background' : 'hover:border-border',
    ];
@endphp

@if ($loading)
    <div {{ $attributes->class($tile) }} aria-busy="true">
        <x-ui.skeleton class="h-2.5 w-24" />
        <x-ui.skeleton class="mt-3 h-6 w-20" />
        <x-ui.skeleton class="mt-2.5 h-2.5 w-28" />
        <span class="sr-only">Loading {{ $label }}</span>
    </div>
@else
    <{{ $href ? 'a' : 'div' }} @if ($href) href="{{ $href }}" @if ($action) title="{{ $action }}" aria-label="{{ $label }}: {{ $value ?? 'no data' }}. {{ $action }}" @endif @endif
        {{ $attributes->class($tile) }}>
        <div class="flex items-center gap-1.5 text-muted-foreground">
            @if ($icon)
                <x-ui.icon :name="$icon" class="size-3" />
            @endif
            <span class="text-[11px] leading-none font-semibold tracking-[0.08em] uppercase">{{ $label }}</span>
        </div>

        <div class="mt-2.5 flex flex-wrap items-baseline gap-x-2 gap-y-1">
            @if ($value === null || $value === '')
                <span class="datum text-[19px] leading-none font-bold text-foreground">&mdash;<span class="sr-only">No data</span></span>
            @else
                <span @class(['datum text-[19px] leading-none font-bold', $alarm ? 'text-destructive' : 'text-foreground'])>{{ $value }}</span>
            @endif

            @if ($deltaState)
                <span @class([
                    'inline-flex items-center gap-0.5 rounded-full px-1.5 py-0.5 text-[11px] font-semibold tabular-nums',
                    'bg-success-bg text-success' => $deltaState === 'good',
                    'bg-destructive-bg text-destructive' => $deltaState === 'bad',
                    'bg-muted/60 text-muted-foreground' => $deltaState === 'flat',
                ])>
                    @if ($deltaState === 'flat')
                        <x-ui.icon name="minus" :stroke="3" class="size-2.5" />
                        no change
                    @else
                        <x-ui.icon :name="(float) $delta > 0 ? 'arrow-up' : 'arrow-down'" :stroke="3" class="size-2.5" />
                        <span class="sr-only">{{ (float) $delta > 0 ? 'Up' : 'Down' }}</span>
                        {{ $deltaNumber }}{{ $deltaUnit }}
                    @endif
                </span>
            @endif
        </div>

        @if ($caption)
            <p class="mt-1.5 text-[11px] leading-snug font-medium text-muted-foreground">{{ $caption }}</p>
        @endif
    </{{ $href ? 'a' : 'div' }}>
@endif
