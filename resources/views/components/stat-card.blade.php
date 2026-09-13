@props([
    'label',
    /** The figure itself. Always rendered with .datum. */
    'value',
    /** A short line under the figure, e.g. "of 26 on the farm". Optional. */
    'caption' => null,
    /** Inline SVG path data for a leading icon. Optional. */
    'icon' => null,
    /** Where this figure leads, if anywhere. Optional. */
    'href' => null,
])

{{--
    A SINGLE FIGURE, stated plainly.

    Structure from D:/ems/employee-management-system's stat-card and stat-tile:
    a small uppercase label, the figure at display size, and an optional caption
    under it.

    THE FIGURE IS ALWAYS .datum. Every number in this application is monospaced
    with tabular figures, so a row of these aligns on the digit rather than
    drifting - which is the single clearest amateur-to-professional tell here.
    A stat card is the most visible place that can go wrong.

    NO COLOUR, and no coloured icon chip. EMS tints its stat tiles by category;
    that is exactly the move this system forbids, because colour means
    bloodline. The label, the rule and the weight carry the hierarchy instead.

    NO COUNT-UP ANIMATION. The motion whitelist bans number roll-ups outright:
    a figure that animates is unreadable for the duration, and a dashboard of
    them turns arriving at the page into a wait.
--}}
<{{ $href ? 'a' : 'div' }}
    @if ($href) href="{{ $href }}" wire:navigate @endif
    {{ $attributes->merge(['class' => 'card p-5'.($href ? ' card-interactive' : '')]) }}>

    <div class="flex items-start justify-between gap-3">
        <p class="text-[13px] font-medium uppercase tracking-[0.06em] text-muted-foreground">{{ $label }}</p>

        @if ($icon)
            <svg class="h-4 w-4 shrink-0 text-muted-foreground" fill="none" stroke="currentColor"
                 stroke-width="1.6" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="{{ $icon }}"/>
            </svg>
        @endif
    </div>

    <p class="datum mt-3 text-[32px] font-semibold leading-none text-foreground">{{ $value }}</p>

    @if ($caption)
        <p class="mt-2 text-[14px] leading-snug text-muted-foreground">{{ $caption }}</p>
    @endif

    {{ $slot }}
</{{ $href ? 'a' : 'div' }}>
