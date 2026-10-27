@props([
    /** A short kicker in the accent - sentence case, never caps. */
    'eyebrow' => null,
    'title',
    /** One or two lines under the title. */
    'lead' => null,
])

{{--
    THE PUBLIC PAGE HEADER - the opening of every page after the front page.

    Eyebrow, title, one lead paragraph, and an optional row of actions. No
    box and no banner: the page opens on type, at the same left edge the rest
    of the page holds. The title is the page's one h1.
--}}
<header {{ $attributes->merge(['class' => 'max-w-[62ch]']) }}>
    @if ($eyebrow)
        <p class="sb-eyebrow sb-line" style="--line: 0ms">{{ $eyebrow }}</p>
    @endif

    <h1 class="sb-line mt-3 font-display text-[2.2rem] font-semibold leading-[1.08] tracking-[-0.03em] text-foreground sm:text-[2.8rem] lg:text-[3.1rem]"
        style="--line: 60ms">{{ $title }}</h1>

    @if ($lead)
        <p class="sb-lead sb-line mt-5" style="--line: 140ms">{{ $lead }}</p>
    @endif

    @if (isset($actions))
        <div class="sb-line mt-8" style="--line: 220ms">
            <div class="flex flex-wrap gap-3">{{ $actions }}</div>
        </div>
    @endif
</header>
