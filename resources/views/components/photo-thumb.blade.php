@props([
    'photo' => null,
    'alt' => '',
    'placeholder' => 'No photo',
])

{{--
    Renders a broodcock photo thumbnail.

    The bucket is private, so photos are served through an authorized controller
    route rather than linked directly. That route is resolved defensively: this
    component is used across several screens, and a missing photo must degrade
    to a placeholder rather than throwing a RouteNotFoundException in the
    middle of a table.
--}}
<div {{ $attributes->merge(['class' => 'overflow-hidden bg-gray-100']) }}>
    @if ($photo && Route::has('photos.show'))
        <img src="{{ route('photos.show', $photo) }}"
             alt="{{ $alt }}"
             loading="lazy"
             class="h-full w-full object-cover">
    @elseif ($photo && method_exists($photo, 'url') && $photo->url())
        <img src="{{ $photo->url() }}" alt="{{ $alt }}" loading="lazy" class="h-full w-full object-cover">
    @else
        <div class="flex h-full w-full items-center justify-center px-1 text-center text-[10px] leading-tight text-gray-400">
            {{ $placeholder }}
        </div>
    @endif
</div>
