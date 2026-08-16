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
<link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
<link rel="alternate icon" href="{{ asset('favicon.ico') }}" sizes="32x32">
<link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">
<meta name="theme-color" content="{{ config('gfms-brand.brand_deep') }}">
