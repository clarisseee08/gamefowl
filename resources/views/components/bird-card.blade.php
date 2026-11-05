@props([
    /** The console's denser grid: smaller type under the photograph. */
    'compact' => false,
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
    THE BIRD CARD (EMS) - the same object everywhere a bird is listed.

    A bordered card: the photograph across the top at 4:3 (a gentle zoom on
    hover), then the band, the age in tabular figures, and the bloodline as a
    dot and a word. A bird looks the same on the front page, in the catalogue
    and anywhere else it is listed, which is the point of it being one
    component.
--}}
<a href="{{ route('broodcocks.show', $bird) }}" wire:navigate
   {{ $attributes->merge(['class' => 'group flex h-full flex-col overflow-hidden rounded-md border border-border bg-card transition-[border-color,box-shadow] duration-200 hover:border-primary/40 hover:shadow-sm focus-visible:outline-none focus-visible:ring-[3px] focus-visible:ring-ring/50']) }}
   data-reveal style="--reveal-delay: {{ $delay }}ms">
    <div class="sb-photo aspect-[4/3] rounded-none" style="box-shadow: inset 0 3px 0 {{ \App\Support\BandTag::hex($bird->bloodline) }}">
        @if ($bird->primaryPhoto)
            <img src="{{ route('photos.show', ['photo' => $bird->primaryPhoto, 'size' => 'thumb']) }}"
                 alt="{{ $alt }}" width="400" height="300" loading="lazy" decoding="async">
        @else
            <span class="flex h-full flex-col items-center justify-center gap-2 text-[13px] text-muted-foreground">
                <x-band-dot :bloodline="$bird->bloodline" class="h-2.5 w-2.5" />
                No photo yet
            </span>
        @endif
    </div>

    <div @class(['flex flex-1 flex-col', $compact ? 'p-2.5' : 'p-3.5'])>
        <div class="flex items-baseline justify-between gap-3">
            <{{ $heading }} class="min-w-0 truncate {{ $compact ? 'text-[13px]' : 'text-[15px]' }} font-semibold text-foreground">
                <span class="datum">{{ $title }}</span>@if ($realName && $bird->band_number)<span class="{{ $compact ? 'text-xs' : 'text-[13px]' }} font-medium text-muted-foreground"> &nbsp;{{ $realName }}</span>@endif
            </{{ $heading }}>
            @if ($bird->ageLabel())
                <span class="datum shrink-0 {{ $compact ? 'text-[11px]' : 'text-xs' }} text-muted-foreground">{{ $bird->ageLabel() }}</span>
            @endif
        </div>

        <p class="mt-1 flex flex-wrap items-center gap-x-2 {{ $compact ? 'text-[11px]' : 'text-[13px]' }} text-muted-foreground">
            <span class="inline-flex items-center gap-1.5">
                <x-band-dot :bloodline="$bird->bloodline" />
                {{ $bird->bloodline ?: 'Bloodline not recorded' }}
            </span>
            @if ($details)
                <span><span aria-hidden="true">&middot;</span> {{ $bird->class->label() }}</span>
                <span><span aria-hidden="true">&middot;</span> {{ $bird->sex->farmTerm() }}</span>
            @endif
        </p>

        {{-- Only the positive case is marked: "Not for sale" is the normal
             state of almost every bird here. --}}
        @if (($details && $bird->for_sale) || ($details && ! $bird->band_number))
            <div class="mt-2.5 flex flex-wrap gap-1.5">
                @if ($bird->for_sale)
                    <span class="d-badge d-badge-soft d-badge-success">For sale</span>
                @endif
                @unless ($bird->band_number)
                    <span class="d-badge d-badge-soft d-badge-neutral">Not yet banded</span>
                @endunless
            </div>
        @endif
    </div>
</a>
