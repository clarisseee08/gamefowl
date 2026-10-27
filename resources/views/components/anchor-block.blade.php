@props([
    /** Sentence case, the section the screen belongs to. */
    'eyebrow' => null,
    /** The page's one h1. */
    'title',
    /** The real number this screen is about. Never invented; null hides it. */
    'figure' => null,
    /** What the number counts, in a few words: "birds on the farm". */
    'figureLabel' => null,
])

{{--
    THE ANCHOR BLOCK - how every main console screen opens.

    One eyebrow, one title, one real number and the screen's main action.
    Nothing boxed: the block is type on paper with a hairline beneath, so the
    first thing the eye lands on is the number the screen exists to move.

    Actions go in the `actions` slot (a btn-primary first). Anything that
    qualifies the number - "3 due this week" - goes in the default slot.
--}}
<header {{ $attributes->merge(['class' => 'border-b border-border pb-6 sm:pb-8']) }}>
    <div class="flex flex-wrap items-end justify-between gap-x-10 gap-y-6">
        <div class="min-w-0">
            @if ($eyebrow)
                <p class="sb-line sb-eyebrow" style="--line: 0ms">{{ $eyebrow }}</p>
            @endif
            <h1 class="sb-line mt-2 font-display text-[1.9rem] font-semibold leading-[1.08] tracking-[-0.025em] text-foreground sm:text-[2.3rem]" style="--line: 60ms">
                {{ $title }}
            </h1>
            @if ($figure !== null)
                <p class="sb-line mt-4 flex items-baseline gap-3" style="--line: 120ms">
                    <span class="datum font-display text-[2.6rem] font-semibold leading-none tracking-[-0.03em] text-foreground sm:text-[3.2rem]">{{ $figure }}</span>
                    @if ($figureLabel)
                        <span class="text-[15px] text-muted-foreground">{{ $figureLabel }}</span>
                    @endif
                </p>
            @endif
            @if (trim($slot) !== '')
                <div class="sb-line mt-3 text-[15px] text-muted-foreground" style="--line: 160ms">{{ $slot }}</div>
            @endif
        </div>

        @isset($actions)
            <div class="sb-line flex flex-wrap items-center gap-3" style="--line: 180ms">
                {{ $actions }}
            </div>
        @endisset
    </div>
</header>
