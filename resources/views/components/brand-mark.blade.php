@props(['size' => 32])

@php
    /*
     * The farm's own badge — SSGuad Gamefarm, supplied as artwork rather than
     * drawn here. It supersedes the geometric rooster this app used before: a
     * real client logo always beats a generated stand-in, and this one already
     * carries the farm's name, its founding year and two birds.
     *
     * WHERE THE FILES LIVE:
     *   resources/brand/logo-source.png   the 1080px original, kept as the thing
     *                                     every other size is derived FROM
     *   public/images/brand/logo-{n}.png  512/192/128/64/32, generated
     *   public/favicon.ico                32px, PNG-in-ICO
     *   public/apple-touch-icon.png       180px, flattened onto the brand ground
     *
     * The source is ~2MB. Serving it into a 32px slot would push two megabytes
     * down a farm connection on every page load, so the derived sizes are
     * committed and the source is never referenced by a view.
     *
     * Picks the smallest generated file that is at least as large as the render
     * size, so a 32px sidebar icon does not download the 512px artwork.
     */
    $available = [32, 64, 128, 192, 512];
    $needed = $size * 2;   // retina
    $file = collect($available)->first(fn (int $w) => $w >= $needed) ?? 512;
@endphp

<img
    src="{{ asset("images/brand/logo-{$file}.png") }}"
    width="{{ $size }}"
    height="{{ $size }}"
    alt="{{ config('gfms.farm.name') }}"
    loading="eager"
    decoding="async"
    {{ $attributes->merge(['class' => 'shrink-0 select-none']) }}
    style="width: {{ $size }}px; height: {{ $size }}px"
>
