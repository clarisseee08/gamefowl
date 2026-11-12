@props([
    /** Where the card's title leads - the record's own page, when it has one. */
    'href' => null,
])

{{--
    ONE RECORD AS A CARD - the cards half of a list's table / cards switch
    (EMS candidate card, in this console's terms).

    Slots, top to bottom: `media` (a photo or a mark, left of the title),
    `title`, `subtitle`, `badge` (top right - status), the default slot for
    the record's facts, then a footer strip with `meta` on the left and
    `actions` - the same row actions as the table - on the right.

    Flat, as every card here is: a border and no shadow. Hover moves the
    border and lifts it a pixel; colour stays for bloodline and status.
--}}
<article data-record-card {{ $attributes->class('group flex h-full flex-col rounded-md border border-border bg-card transition-[border-color,transform] duration-150 ease-out hover:-translate-y-px hover:border-ring/60') }}>
    <div class="flex flex-1 flex-col p-4">
        <div class="flex items-start gap-3">
            @isset($media)
                <div class="shrink-0">{{ $media }}</div>
            @endisset

            <div class="min-w-0 flex-1">
                <div class="flex items-start justify-between gap-2">
                    <div class="min-w-0">
                        <h3 class="truncate text-sm font-semibold text-foreground lg:text-xs">
                            @if ($href)
                                <a href="{{ $href }}" wire:navigate class="outline-none hover:underline focus-visible:underline">{{ $title }}</a>
                            @else
                                {{ $title }}
                            @endif
                        </h3>
                        @isset($subtitle)
                            <p class="mt-0.5 truncate text-[13px] text-muted-foreground lg:text-[11px]">{{ $subtitle }}</p>
                        @endisset
                    </div>
                    @isset($badge)
                        <div class="flex shrink-0 flex-wrap justify-end gap-1">{{ $badge }}</div>
                    @endisset
                </div>
            </div>
        </div>

        @if (trim($slot) !== '')
            <div class="mt-3 text-[13px] text-foreground lg:text-xs">{{ $slot }}</div>
        @endif
    </div>

    @if (isset($meta) || isset($actions))
        <div class="flex min-h-11 items-center justify-between gap-2 border-t border-border bg-muted/30 px-4 py-1.5 text-[13px] text-muted-foreground lg:min-h-9 lg:text-[11px]">
            <div class="min-w-0 truncate">{{ $meta ?? '' }}</div>
            @isset($actions)
                <div class="flex shrink-0 items-center gap-0.5">{{ $actions }}</div>
            @endisset
        </div>
    @endif
</article>
