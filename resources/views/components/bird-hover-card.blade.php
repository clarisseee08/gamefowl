@props([
    /** The bird, with primaryPhoto eager-loaded by the list. */
    'bird',
])

{{--
    THE HOVER CARD ALONE: a bird's photo, name, band and bloodline, shown
    beside the element it sits in while an ancestor's Alpine `preview` is
    true. A list row owns `preview` (so resting anywhere on the row shows
    the bird); x-bird-hover owns it when only a name should trigger it.

    The thumbnail is requested only when the card opens, pulses until the
    file arrives, then fades in. Decorative: the row names the bird already.
--}}
@php
    $photo = $bird->relationLoaded('primaryPhoto') ? $bird->primaryPhoto : null;
@endphp

<span x-show="preview" x-cloak aria-hidden="true"
      x-anchor.fixed.right-start.offset.12="$el.parentElement"
      x-transition:enter="transition ease-out duration-150"
      x-transition:enter-start="scale-95 opacity-0"
      x-transition:enter-end="scale-100 opacity-100"
      x-transition:leave="transition ease-in duration-100"
      x-transition:leave-start="opacity-100"
      x-transition:leave-end="opacity-0"
      class="pointer-events-none z-40 block w-52 rounded-md border border-border bg-popover p-2 text-popover-foreground shadow-md">
    <span class="relative block aspect-[4/5] w-full overflow-hidden rounded-sm bg-muted">
        @if ($photo)
            {{-- A pulse until the file arrives, then the photo fades in. --}}
            <span x-data="{ loaded: false }" class="block h-full w-full">
                <span x-show="! loaded" class="absolute inset-0 animate-pulse bg-muted-foreground/15"></span>
                <img x-bind:src="preview ? @js(route('photos.show', ['photo' => $photo, 'size' => 'thumb'])) : null" alt=""
                     x-on:load="loaded = true"
                     width="192" height="240" decoding="async"
                     class="h-full w-full object-cover transition-opacity duration-300"
                     x-bind:class="loaded ? 'opacity-100' : 'opacity-0'">
            </span>
        @else
            <span class="flex h-full w-full flex-col items-center justify-center gap-2 text-xs text-muted-foreground">
                <x-band-dot :bloodline="$bird->bloodline" />
                No photo yet
            </span>
        @endif
    </span>
    <span class="block px-1 pt-2 pb-0.5">
        <span class="block truncate text-sm font-semibold text-foreground lg:text-xs">{{ $bird->name }}</span>
        <span class="mt-0.5 flex items-center gap-1.5 text-xs text-muted-foreground lg:text-[11px]">
            @if ($bird->band_number)
                <span class="datum">{{ $bird->band_number }}</span>
                <span aria-hidden="true">&middot;</span>
            @endif
            <span class="truncate">{{ $bird->bloodline ?: 'Bloodline not recorded' }}</span>
        </span>
    </span>
</span>
