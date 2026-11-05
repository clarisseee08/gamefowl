@props([
    /** The bird, with primaryPhoto eager-loaded by the list. */
    'bird',
])

{{--
    A BIRD'S PHOTO ON HOVER of one name (an EMS hover card): wrap the name in
    it. For a whole row, give the <tr> the same x-data and mouse handlers and
    put <x-bird-hover-card> in the bird's cell instead.

    Only on a device that can hover, after a short pause so sweeping down a
    list does not flash every bird.
--}}
<span x-data="{ preview: false, timer: null }"
      x-on:mouseenter="if (matchMedia('(hover: hover)').matches) { timer = setTimeout(() => preview = true, 350) }"
      x-on:mouseleave="clearTimeout(timer); preview = false"
      {{ $attributes->class('relative inline-flex') }}>
    {{ $slot }}
    <x-bird-hover-card :bird="$bird" />
</span>
