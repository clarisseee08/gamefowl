@props([
    /** The bird on this card, or null for an ancestor the farm has not recorded. */
    'bird' => null,
    /** This Bird · Sire · Dam · Grandsire · Granddam */
    'role',
    /** The bird the tree is about: its card is marked. */
    'root' => false,
])

{{--
    ONE CARD IN THE FAMILY TREE: the bird's photograph, its role, its band
    and name, and its bloodline as the coloured edge along the top. The
    thumbnail copy only - a tree of full-size files would be slow. A photo is
    shown to a visitor only for a bird the catalogue would list; the photo
    policy refuses the rest, and a broken image is worse than none.
--}}
@php
    $canSeePhoto = $bird?->primaryPhoto
        && ((auth()->user()?->isInternal() ?? false) || $bird->isPubliclyVisible());
    $band = $bird?->band_number ?: $bird?->name;
    $name = ($bird?->band_number && preg_match('/\p{L}/u', (string) $bird->name)) ? $bird->name : null;
@endphp

@if ($bird)
    <a href="{{ route('broodcocks.show', $bird) }}" wire:navigate
       @class([
           'ft-node group flex w-36 flex-col overflow-hidden rounded-md border bg-card text-left shadow-xs outline-none transition-colors hover:border-primary/50 focus-visible:ring-[3px] focus-visible:ring-ring/50 sm:w-40',
           'border-primary/60 ring-2 ring-primary/20' => $root,
           'border-border' => ! $root,
       ])
       style="border-top: 3px solid {{ \App\Support\BandTag::hex($bird->bloodline) }}">
        <span class="block aspect-[4/3] w-full overflow-hidden bg-muted">
            @if ($canSeePhoto)
                <img src="{{ route('photos.show', ['photo' => $bird->primaryPhoto, 'size' => 'thumb']) }}" alt=""
                     width="160" height="120" loading="lazy" decoding="async" class="h-full w-full object-cover">
            @else
                <span class="flex h-full w-full flex-col items-center justify-center gap-1.5 text-[11px] text-muted-foreground">
                    <x-band-dot :bloodline="$bird->bloodline" class="h-2.5 w-2.5" />
                    No photo yet
                </span>
            @endif
        </span>
        <span class="flex min-w-0 flex-col gap-0.5 p-2.5">
            <span class="text-[11px] font-medium text-muted-foreground">{{ $role }}</span>
            <span @class(['truncate text-sm font-semibold text-foreground lg:text-xs', 'datum' => $bird->band_number])>{{ $band }}</span>
            @if ($name)
                <span class="truncate text-xs text-muted-foreground lg:text-[11px]">{{ $name }}</span>
            @endif
            <span class="mt-0.5 flex min-w-0 items-center gap-1.5 text-[11px] text-muted-foreground">
                <x-band-dot :bloodline="$bird->bloodline" />
                <span class="truncate">{{ $bird->bloodline ?: 'Bloodline not recorded' }}@if ($bird->is_external) &middot; Outside bird @endif</span>
            </span>
        </span>
    </a>
@else
    <div class="ft-node flex w-36 flex-col items-center justify-center gap-1 rounded-md border border-dashed border-border bg-card/60 px-3 py-6 text-center sm:w-40">
        <span class="text-[11px] font-medium text-muted-foreground">{{ $role }}</span>
        <span class="text-xs text-muted-foreground">Not recorded</span>
    </div>
@endif
