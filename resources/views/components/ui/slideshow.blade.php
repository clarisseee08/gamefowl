@props([
    'slides' => [],
    'interval' => 6000,
    'label' => 'Photographs',
    'imgClass' => '',
    'controls' => true,
    'priority' => false,
])

{{--
    A SLIDESHOW (EMS carousel) - photographs crossfading in place.

    Each slide is ['src', 'alt'] plus, optionally: 'href' (the slide links
    somewhere - a bird's page), 'caption' and 'meta' (the chip in the corner),
    'dot' (a bloodline hex for the chip's band dot), 'icon' (an icon in place
    of the dot) and 'position' (an object-position for the crop).

    Entirely client-side: changing slides never talks to the server. It
    advances on its own every `interval` ms, pauses while the pointer or focus
    is on it and while the tab is hidden, and does not advance at all for
    someone who has asked for reduced motion - they still get the arrows, the
    dots and a swipe. One slide renders as a plain photograph, no controls.

    Every slide's image is in the DOM from the start (they are small WebPs),
    so a fade never waits on a download; only the first is fetched with
    priority when `priority` is set, as the hero's is.
--}}
@php
    $slides = array_values(array_filter($slides, fn ($slide) => filled($slide['src'] ?? null)));
    $count = count($slides);
@endphp

<div {{ $attributes->class('group/slides relative overflow-hidden') }}
     role="region" aria-roledescription="carousel" aria-label="{{ $label }}"
     @if ($count > 1)
         x-data="{
             at: 0,
             count: {{ $count }},
             held: false,
             timer: null,
             startX: null,
             go(n) { this.at = (n + this.count) % this.count },
             next() { this.go(this.at + 1) },
             prev() { this.go(this.at - 1) },
             play() {
                 clearInterval(this.timer);
                 if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) { return }
                 this.timer = setInterval(() => { if (! this.held && ! document.hidden) { this.next() } }, {{ (int) $interval }});
             },
             swipe(endX) {
                 if (this.startX === null) { return }
                 const moved = endX - this.startX;
                 this.startX = null;
                 if (Math.abs(moved) < 40) { return }
                 moved < 0 ? this.next() : this.prev();
                 this.play();
             },
         }"
         x-init="play()"
         x-on:mouseenter="held = true"
         x-on:mouseleave="held = false"
         x-on:focusin="held = true"
         x-on:focusout="held = false"
         x-on:keydown.arrow-right="next(); play()"
         x-on:keydown.arrow-left="prev(); play()"
         x-on:touchstart.passive="startX = $event.touches[0].clientX"
         x-on:touchend="swipe($event.changedTouches[0].clientX)"
         x-on:remove="clearInterval(timer)"
     @endif>
    @foreach ($slides as $index => $slide)
        @php
            $tag = filled($slide['href'] ?? null) ? 'a' : 'div';
        @endphp
        <{{ $tag }}
            @if ($tag === 'a') href="{{ $slide['href'] }}" wire:navigate @endif
            role="group" aria-roledescription="slide" aria-label="{{ $index + 1 }} of {{ $count }}"
            @class([
                'absolute inset-0 block transition-opacity duration-700 ease-out motion-reduce:transition-none',
                'opacity-0 pointer-events-none' => $index > 0,
            ])
            @if ($count > 1)
                x-bind:class="at === {{ $index }} ? 'opacity-100' : 'opacity-0 pointer-events-none'"
                x-bind:aria-hidden="at !== {{ $index }}"
                @if ($index > 0) aria-hidden="true" @endif
            @endif>
            <img src="{{ $slide['src'] }}" alt="{{ $slide['alt'] ?? '' }}"
                 @if ($index === 0 && $priority) fetchpriority="high" @else loading="lazy" @endif
                 decoding="async"
                 @if (filled($slide['position'] ?? null)) style="object-position: {{ $slide['position'] }}" @endif
                 class="{{ trim('h-full w-full object-cover '.$imgClass) }}">

            @if (filled($slide['caption'] ?? null))
                <span class="sb-chip">
                    @if (filled($slide['dot'] ?? null))
                        <span class="band-dot" style="background-color: {{ $slide['dot'] }}" aria-hidden="true"></span>
                    @elseif (filled($slide['icon'] ?? null))
                        <x-ui.icon :name="$slide['icon']" class="size-3.5" />
                    @endif
                    <span class="datum">{{ $slide['caption'] }}</span>
                    @if (filled($slide['meta'] ?? null))
                        <span class="font-medium text-muted-foreground">{{ $slide['meta'] }}</span>
                    @endif
                </span>
            @endif
        </{{ $tag }}>
    @endforeach

    @if ($count > 1 && $controls)
        {{-- Arrows: on a pointer device they appear with the hover or focus;
             a phone swipes, and keeps the dots. --}}
        <button type="button" x-on:click="prev(); play()" aria-label="Previous photograph"
                class="absolute top-1/2 left-3 z-10 hidden size-9 -translate-y-1/2 items-center justify-center rounded-full border border-border bg-background/85 text-foreground shadow-sm backdrop-blur-sm transition-opacity duration-150 hover:bg-background sm:inline-flex sm:opacity-0 sm:group-hover/slides:opacity-100 sm:group-focus-within/slides:opacity-100">
            <x-ui.icon name="chevron-left" class="size-4" />
        </button>
        <button type="button" x-on:click="next(); play()" aria-label="Next photograph"
                class="absolute top-1/2 right-3 z-10 hidden size-9 -translate-y-1/2 items-center justify-center rounded-full border border-border bg-background/85 text-foreground shadow-sm backdrop-blur-sm transition-opacity duration-150 hover:bg-background sm:inline-flex sm:opacity-0 sm:group-hover/slides:opacity-100 sm:group-focus-within/slides:opacity-100">
            <x-ui.icon name="chevron-right" class="size-4" />
        </button>

        {{-- The dots. Each is a 24px target around an 8px mark; the current
             one stretches into a short bar. --}}
        <div class="absolute right-3 bottom-3 z-10 flex items-center rounded-full bg-black/35 px-1.5 backdrop-blur-sm">
            @foreach ($slides as $index => $slide)
                <button type="button" x-on:click="go({{ $index }}); play()"
                        aria-label="Show photograph {{ $index + 1 }}"
                        x-bind:aria-current="at === {{ $index }} ? 'true' : 'false'"
                        class="inline-flex h-6 items-center justify-center px-1">
                    <span class="block h-1.5 rounded-full bg-white transition-all duration-300"
                          x-bind:class="at === {{ $index }} ? 'w-5 opacity-100' : 'w-1.5 opacity-60'"></span>
                </button>
            @endforeach
        </div>
    @endif
</div>
