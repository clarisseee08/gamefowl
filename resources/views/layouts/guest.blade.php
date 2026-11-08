<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <x-partials.head-meta :title="$title ?? 'Sign in'" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
{{--
    THE SIGN-IN SCREENS (EMS) - the farm in pictures, and the form.

    A split: on the left a slideshow of the farm under a green wash, with the
    farm's name and its one-line promise over the foot of it; on the right
    the form on a card, on paper, with the farm's mark above it. Sign in,
    forgot password, reset and confirm all use this layout, so they read as
    one place.

    The pictures are hidden below lg: a half-screen picture above a sign-in
    form pushes the fields off a phone, which this screen must never do.

    There is no sign-up link and there must not be one: the owner creates
    every account (Fortify registration is off).
--}}
@php
    /* All of the farm's photographs, in this slideshow's own random order,
       opening on one no public page opens on (App\Support\FarmPhotos). */
    $authSlides = \App\Support\FarmPhotos::slides('sign-in');
@endphp
<body class="h-full bg-background text-foreground">
    <div class="flex min-h-full">
        <div class="relative hidden p-3 lg:block lg:w-[48%] xl:w-[52%]">
            <div class="auth-photo sticky top-3 h-[calc(100svh-1.5rem)] overflow-hidden rounded-2xl bg-muted">
                <div class="absolute inset-0">
                    <x-ui.slideshow :slides="$authSlides" :interval="6000" :controls="false" priority label="The farm" class="h-full" />
                </div>

                {{-- A wash in the forest green, deepest at the foot, so the
                     words over it hold 7:1 whatever photograph is under them. --}}
                <div class="pointer-events-none absolute inset-0 bg-gradient-to-t from-(--sb-dark) via-(--sb-dark)/55 to-transparent" aria-hidden="true"></div>

                <div class="pointer-events-none absolute inset-x-0 bottom-0 p-10 text-(--sb-dark-ink) xl:p-12">
                    <p class="text-sm font-medium text-(--sb-dark-muted)">{{ config('gfms.farm.name') }}</p>
                    <p class="mt-2 max-w-[22ch] text-3xl leading-tight font-bold tracking-tight xl:text-4xl">Broodcocks with their pedigree on record.</p>
                    <p class="mt-3 max-w-[44ch] text-[15px] leading-relaxed text-(--sb-dark-muted)">Every bird banded, every parent written down, every treatment and weigh-in kept.</p>
                </div>
            </div>
        </div>

        <div class="relative flex flex-1 flex-col px-5 py-6 sm:px-10 lg:px-14">
            <div class="flex items-center justify-between gap-4">
                <a href="{{ route('home') }}" class="rise-in inline-flex min-h-11 items-center gap-2.5 text-[15px] font-bold text-foreground">
                    <x-brand-mark :size="34" class="shrink-0 rounded-full ring-1 ring-border" />
                    {{ config('gfms.farm.name') }}
                </a>
                <x-ui.theme-menu class="rise-in" style="--rise-delay: 120ms" />
            </div>

            <main class="flex flex-1 items-center justify-center py-10">
                <div class="w-full max-w-[25rem] rounded-xl border border-border bg-card p-6 shadow-sm sm:p-8">
                    {{ $slot }}
                </div>
            </main>

            <div class="flex flex-wrap items-center justify-between gap-2 text-[13px] text-muted-foreground">
                <a href="{{ route('home') }}" class="inline-flex min-h-11 items-center gap-1.5 hover:text-foreground">
                    <x-ui.icon name="chevron-left" class="size-4" /> Back to the farm's website
                </a>
                <p>&copy; <span class="datum">{{ now()->year }}</span> {{ config('gfms.farm.name') }}</p>
            </div>
        </div>
    </div>

    {{-- Explicit, not auto-injected: Livewire only injects its bundle (and
         the Alpine inside it) on a page that renders a component, and the
         plain pages in this shell - About, Visit, the legal pages, sign-in -
         render none. Their slideshows and menus need Alpine all the same. --}}
    @livewireScripts
</body>
</html>
