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

    It is a daisyUI d-stat inside its own d-stats box, so a stat card is the
    same object as a row of stats, standing alone on the card surface. The
    d-stat-* parts are re-inked: daisyUI draws the title and description at 60%
    ink, under the console's 7:1 floor, and the value at weight 800, which is
    not loaded here.

    NO COUNT-UP ANIMATION. The motion whitelist bans number roll-ups outright:
    a figure that animates is unreadable for the duration, and a dashboard of
    them turns arriving at the page into a wait.
--}}
<{{ $href ? 'a' : 'div' }}
    @if ($href) href="{{ $href }}" wire:navigate @endif
    {{ $attributes->merge(['class' => 'd-stats grid rounded-box border border-border bg-base-100 elev-1'.($href ? ' card-interactive' : '')]) }}>

    <div class="d-stat gap-y-0 p-5">
        @if ($icon)
            <div class="d-stat-figure self-start">
                <svg class="h-4 w-4 shrink-0 text-muted-foreground" fill="none" stroke="currentColor"
                     stroke-width="1.6" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="{{ $icon }}"/>
                </svg>
            </div>
        @endif

        <p class="d-stat-title text-[13px] font-medium uppercase tracking-[0.06em] text-muted-foreground">{{ $label }}</p>

        <p class="d-stat-value datum mt-3 text-[32px] font-semibold leading-none text-base-content">{{ $value }}</p>

        @if ($caption)
            <p class="d-stat-desc mt-2 whitespace-normal text-[14px] leading-snug text-muted-foreground">{{ $caption }}</p>
        @endif

        {{ $slot }}
    </div>
</{{ $href ? 'a' : 'div' }}>
