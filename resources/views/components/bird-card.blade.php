@props([
    'bird',
    /** h2 on the catalogue, where it sits under the page's h1; h3 under a section h2. */
    'heading' => 'h3',
    /** Class and sex on the bloodline line - the catalogue shows them, the front page does not. */
    'details' => false,
    /** Stagger for the entrance, in ms. */
    'delay' => 0,
])

@php
    /* A name is shown beside the band only when it is a real name - "Spark" -
       rather than the band digits again, which most names here are. */
    $realName = preg_match('/\p{L}/u', (string) $bird->name) ? $bird->name : null;
    $title = $bird->band_number ?: $bird->name;
    $alt = ($realName ?? 'Broodcock '.$title).', '.($bird->bloodline ?: 'bloodline not recorded');
@endphp

{{--
    THE BIRD CARD - the same object everywhere a bird is listed.

    The photograph IS the card: 4:5, 12px corners, a gentle zoom on hover. No
    background, no border, no shadow under it. Below: the band in the display
    face, the age in tabular figures on the right, and the bloodline as a dot
    and a word. A bird looks the same on the front page, in the catalogue and
    anywhere else it is listed, which is the point of it being one component.
--}}
<a href="{{ route('broodcocks.show', $bird) }}" wire:navigate
   {{ $attributes->merge(['class' => 'group block']) }}
   data-reveal style="--reveal-delay: {{ $delay }}ms">
    <div class="sb-photo aspect-[4/5]">
        @if ($bird->primaryPhoto)
            <img src="{{ route('photos.show', ['photo' => $bird->primaryPhoto, 'size' => 'thumb']) }}"
                 alt="{{ $alt }}" width="400" height="500" loading="lazy" decoding="async">
        @else
            <span class="flex h-full flex-col items-center justify-center gap-2 text-[14px] text-muted-foreground">
                <x-band-dot :bloodline="$bird->bloodline" class="h-3 w-3" />
                No photo yet
            </span>
        @endif
    </div>

    <div class="mt-3.5 flex items-baseline justify-between gap-3">
        <{{ $heading }} class="min-w-0 truncate font-display text-[17px] font-semibold tracking-[-0.015em] text-foreground group-hover:underline group-hover:decoration-1 group-hover:underline-offset-4">
            {{ $title }}@if ($realName && $bird->band_number)<span class="font-sans text-[15px] font-medium text-muted-foreground"> &nbsp;{{ $realName }}</span>@endif
        </{{ $heading }}>
        @if ($bird->ageLabel())
            <span class="datum shrink-0 text-[14px] text-muted-foreground">{{ $bird->ageLabel() }}</span>
        @endif
    </div>

    <p class="mt-1 flex flex-wrap items-center gap-x-2 text-[14px] text-muted-foreground">
        <span class="inline-flex items-center gap-2">
            <x-band-dot :bloodline="$bird->bloodline" />
            {{ $bird->bloodline ?: 'Bloodline not recorded' }}
        </span>
        @if ($details)
            <span><span aria-hidden="true">&middot;</span> {{ $bird->class->label() }}</span>
            <span><span aria-hidden="true">&middot;</span> {{ $bird->sex->farmTerm() }}</span>
        @endif
    </p>

    {{-- Only the positive case is marked: "Not for sale" is the normal state
         of almost every bird here. --}}
    @if ($details && $bird->for_sale)
        <p class="mt-1.5 text-[14px] font-semibold text-primary">For sale</p>
    @endif
    @if ($details && ! $bird->band_number)
        <p class="mt-1.5 text-[13px] text-muted-foreground">Not yet banded</p>
    @endif
</a>
