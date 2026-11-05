@props([
    'title',
    /** The figure, already formatted. null shows a dash, read out as "No data". */
    'value' => null,
    /** A Lucide name, drawn in the tinted chip at the top right. */
    'icon' => null,
    /** The chip's tint: primary · success · warning · danger · info · muted */
    'tone' => 'primary',
    /** One line under the figure. "+3 since last month" renders as a trend: arrow, and the change in colour. */
    'footer' => null,
    /** Tints the figure for an alarm count, such as an overdue total. */
    'alarm' => false,
    'href' => null,
    'action' => null,
    'loading' => false,
])

{{--
    THE SUMMARY CARD (EMS SummaryCard, Part B §6.6) - the KPI card with an
    icon chip, for a figure that wants a little more presence than a tile.

    Tones are the status pairs, plus primary and muted. EMS's extra teal,
    purple and amber accents are not here: colour means bloodline and
    status, and an accent that means neither would be a third meaning.

    A footer that opens with a signed, non-zero number - "+3 since last
    month" - is drawn as a trend: an arrow, and that first word in the
    success or destructive colour. Anything else is a plain caption.
--}}
@php
    $chip = [
        'primary' => 'bg-primary/10 text-primary',
        'success' => 'bg-success-bg text-success',
        'warning' => 'bg-warning-bg text-warning',
        'danger' => 'bg-destructive-bg text-destructive',
        'info' => 'bg-info-bg text-info',
        'muted' => 'bg-muted text-muted-foreground',
    ][$tone] ?? 'bg-primary/10 text-primary';

    $trend = null;
    if ($footer && preg_match('/^([+-])(\d+(?:[.,]\d+)?)(\S*)\s*(.*)$/u', (string) $footer, $parts) && (float) str_replace(',', '.', $parts[2]) !== 0.0) {
        $trend = ['up' => $parts[1] === '+', 'first' => $parts[1].$parts[2].$parts[3], 'rest' => $parts[4]];
    }

    $card = [
        'group relative flex flex-col gap-0 rounded-xl border border-border bg-card px-3.5 py-3 text-card-foreground shadow-none transition-colors duration-200',
        $href ? 'cursor-pointer outline-none hover:border-primary/40 hover:shadow-sm focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:ring-offset-background' : '',
    ];
@endphp

@if ($loading)
    <div {{ $attributes->class($card) }} aria-busy="true">
        <x-ui.skeleton class="h-2.5 w-24" />
        <x-ui.skeleton class="mt-3 h-6 w-20" />
        <x-ui.skeleton class="mt-2.5 h-2.5 w-28" />
        <span class="sr-only">Loading {{ $title }}</span>
    </div>
@else
    <{{ $href ? 'a' : 'div' }} @if ($href) href="{{ $href }}" @if ($action) title="{{ $action }}" @endif @endif
        {{ $attributes->class($card) }}>
        @if ($icon)
            <span class="{{ $chip }} absolute top-3 right-3.5 flex size-6 items-center justify-center rounded-lg sm:size-8">
                <x-ui.icon :name="$icon" class="size-3.5 sm:size-4" />
            </span>
        @endif

        <p class="pr-8 text-[11px] leading-none font-medium text-muted-foreground sm:pr-10">{{ $title }}</p>

        @if ($value === null || $value === '')
            <p class="datum mt-2.5 text-lg leading-none font-bold text-foreground">&mdash;<span class="sr-only">No data</span></p>
        @else
            <p @class(['datum mt-2.5 text-lg leading-none font-bold', $alarm ? 'text-destructive' : 'text-foreground'])>{{ $value }}</p>
        @endif

        @if ($trend)
            <p class="mt-1.5 flex items-center gap-1 text-[11px] leading-none">
                <x-ui.icon :name="$trend['up'] ? 'arrow-up-right' : 'arrow-down-right'" :stroke="3"
                           :class="$trend['up'] ? 'size-3 text-success' : 'size-3 text-destructive'" />
                <span @class(['datum font-semibold', $trend['up'] ? 'text-success' : 'text-destructive'])>{{ $trend['first'] }}</span>
                <span class="font-medium text-muted-foreground">{{ $trend['rest'] }}</span>
            </p>
        @elseif ($footer)
            <p class="mt-1.5 text-[11px] leading-none font-medium text-muted-foreground">{{ $footer }}</p>
        @endif
    </{{ $href ? 'a' : 'div' }}>
@endif
