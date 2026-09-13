@props([
    'photo' => null,
    'alt' => '',
    'placeholder' => 'No photo',
    // Ask for the small copy by default. Every use of this component is a grid
    // cell or a table row; the one place that wants full resolution is the
    // lightbox on a bird's own page, which passes full="true".
    'full' => false,
])

{{--
    Renders a broodcock photo thumbnail.

    The bucket is private, so photos are served through an authorized controller
    route rather than linked directly. That route is resolved defensively: this
    component is used across several screens, and a missing photo must degrade
    to a placeholder rather than throwing a RouteNotFoundException in the
    middle of a table.
--}}
<div {{ $attributes->merge(['class' => 'overflow-hidden bg-background']) }}>
    @if ($photo && Route::has('photos.show'))
        {{-- ?size=thumb serves the ~400px copy written at upload time, falling
             back to the original for photos that predate thumbnails. Without
             it a twelve-card catalogue page pulled twelve 4 MB originals out of
             Tokyo, through four php-fpm workers, to fill boxes 300px wide.

             decoding="async" keeps a slow decode off the main thread, so a long
             table still scrolls while its images resolve. --}}
        <img src="{{ route('photos.show', $full ? $photo : ['photo' => $photo, 'size' => 'thumb']) }}"
             alt="{{ $alt }}"
             loading="lazy"
             decoding="async"
             class="h-full w-full object-cover">
    @elseif ($photo && method_exists($photo, 'url') && $photo->url())
        <img src="{{ $photo->url() }}" alt="{{ $alt }}" loading="lazy" class="h-full w-full object-cover">
    @else
        <div class="flex h-full w-full items-center justify-center px-1 text-center text-[10px] leading-tight text-muted-foreground">
            {{ $placeholder }}
        </div>
    @endif
</div>
