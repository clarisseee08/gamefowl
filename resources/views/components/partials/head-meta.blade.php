@props(['title' => null])

{{--
    Shared document head. One partial rather than three copies, because the
    thing this exists to prevent is exactly the drift that comes from editing
    two of the three layouts and forgetting the catalogue.

    Every name here reads from config/gfms.system. Nothing is written literally.
--}}
@php
    $system = config('gfms.system.name');
    $short = config('gfms.system.short');
    $farm = config('gfms.farm.name');
    $description = "{$system} — breeding, health, performance and pedigree records for {$farm}.";
@endphp

<title>{{ $title ? $title.' · ' : '' }}{{ $short }}</title>

{{-- Lexend for display, Public Sans for text - the client's chosen pair,
     loaded exactly as specified: Google Fonts with preconnect, here because
     every layout and the error pages include this partial. Only 400-700 are
     requested and the stylesheet never asks for 800 or 900, which the browser
     would otherwise fake-bold. FontPayloadTest holds both of those lines. --}}
<link rel="preconnect" href="https://fonts.googleapis.com" />
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
<link href="https://fonts.googleapis.com/css2?family=Lexend:wght@400;500;600;700&family=Public+Sans:ital,wght@0,400;0,500;0,600;0,700;1,400&display=swap" rel="stylesheet" />

<meta name="description" content="{{ $description }}">
<meta name="application-name" content="{{ $system }}">

<meta property="og:type" content="website">
<meta property="og:site_name" content="{{ $system }}">
<meta property="og:title" content="{{ $title ? $title.' · '.$short : $system }}">
<meta property="og:description" content="{{ $description }}">

<meta name="twitter:card" content="summary">
<meta name="twitter:title" content="{{ $title ? $title.' · '.$short : $system }}">
<meta name="twitter:description" content="{{ $description }}">

{{-- Favicon set. The SVG is the mark itself, so it stays sharp at any density;
     the ICO is the fallback for browsers that still want one. --}}
<link rel="icon" href="{{ asset('favicon.ico') }}" sizes="32x32">
<link rel="icon" href="{{ asset('images/brand/logo-192.png') }}" type="image/png" sizes="192x192">
<link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">
<meta property="og:image" content="{{ asset('images/brand/logo-512.png') }}">
<meta name="theme-color" content="{{ config('gfms-brand.brand_deep') }}">

{{--
    THEME, APPLIED BEFORE THE FIRST PAINT.

    This is inline and synchronous on purpose. Any deferred script - a module,
    a bundle, Alpine - runs after the browser has already painted, so a visitor
    who chose dark would watch the page flash white and then correct itself on
    every single navigation. That flash is the entire reason this is not in
    app.js.

    It writes nothing when no choice has been stored, which leaves the CSS
    media query in charge: a visitor who has never touched the toggle follows
    their operating system, and the first click is what pins it.

    try/catch because localStorage throws outright in a private window and in
    some embedded webviews, and a theme preference is not worth a blank page.
--}}
<script>
    (function () {
        try {
            var choice = localStorage.getItem('gfms-theme');
            if (choice === 'dark' || choice === 'light') {
                document.documentElement.setAttribute('data-theme', choice);
            }
        } catch (e) {}
    })();
</script>
