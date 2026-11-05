@props([
    /** The chosen bird, with primaryPhoto loaded - or null while none is chosen. */
    'bird' => null,
    /** What the bird is in this form: "Sire", "Dam" - optional. */
    'role' => null,
    /** The Livewire property the picker writes: while it round-trips, a skeleton stands in. */
    'target' => null,
])

{{--
    THE CHOSEN BIRD, SHOWN BACK. Sits under a form's bird picker so the
    keeper can see - by its photograph, band and bloodline - that the bird
    they picked from a list of names is the bird in their hands. Renders
    nothing until a bird is chosen.

    Two waits, two answers. While the server looks the bird up, a pulsing
    skeleton of the card holds its place (wire:loading on `target`); once
    the card arrives, the photograph fades in when its file has loaded
    rather than painting in strips.
--}}
@if ($target)
    <div wire:loading.flex wire:target="{{ $target }}" aria-busy="true"
         class="mt-3 hidden items-center gap-3 rounded-md border border-border bg-muted/40 p-2.5">
        <span class="sr-only" role="status">Loading the bird&hellip;</span>
        <x-ui.skeleton class="h-20 w-16 shrink-0 rounded-sm" />
        <span class="flex flex-1 flex-col gap-2">
            <x-ui.skeleton class="h-3 w-12" />
            <x-ui.skeleton class="h-3.5 w-40" />
            <x-ui.skeleton class="h-3 w-28" />
        </span>
    </div>
@endif

@if ($bird)
    @php
        $photo = $bird->relationLoaded('primaryPhoto') ? $bird->primaryPhoto : null;
    @endphp
    <div {{ $attributes->class('mt-3 flex items-center gap-3 rounded-md border border-border bg-muted/40 p-2.5 animate-ems-fade-in') }}
         @if ($target) wire:loading.remove wire:target="{{ $target }}" @endif
         wire:key="bird-preview-{{ $bird->id }}">
        <span class="relative block h-20 w-16 shrink-0 overflow-hidden rounded-sm bg-muted"
              style="box-shadow: inset 0 3px 0 {{ \App\Support\BandTag::hex($bird->bloodline) }}">
            @if ($photo)
                <span x-data="{ loaded: false }" x-init="loaded = $refs.photo.complete && $refs.photo.naturalWidth > 0" class="block h-full w-full">
                    <span x-show="! loaded" class="absolute inset-0 animate-pulse bg-muted-foreground/15" aria-hidden="true"></span>
                    <img x-ref="photo" x-on:load="loaded = true"
                         src="{{ route('photos.show', ['photo' => $photo, 'size' => 'thumb']) }}" alt="Photograph of {{ $bird->name }}"
                         width="64" height="80" decoding="async"
                         class="h-full w-full object-cover transition-opacity duration-300"
                         x-bind:class="loaded ? 'opacity-100' : 'opacity-0'">
                </span>
            @else
                <span class="flex h-full w-full flex-col items-center justify-center gap-1 px-1 text-center text-[11px] leading-tight text-muted-foreground">
                    <x-band-dot :bloodline="$bird->bloodline" />
                    No photo
                </span>
            @endif
        </span>
        <span class="min-w-0">
            @if ($role)
                <span class="block text-[11px] font-medium text-muted-foreground">{{ $role }}</span>
            @endif
            <span class="block truncate text-sm font-semibold text-foreground lg:text-xs">
                {{ $bird->name }}@if ($bird->band_number) <span class="datum font-normal text-muted-foreground">&middot; {{ $bird->band_number }}</span>@endif
            </span>
            <span class="mt-0.5 flex flex-wrap items-center gap-x-2 gap-y-1 text-xs text-muted-foreground lg:text-[11px]">
                <span class="inline-flex items-center gap-1.5"><x-band-dot :bloodline="$bird->bloodline" />{{ $bird->bloodline ?: 'Bloodline not recorded' }}</span>
                <span>{{ $bird->sex->farmTerm() }}</span>
                @if ($bird->is_external)
                    <span>Outside bird</span>
                @endif
            </span>
        </span>
    </div>
@endif
