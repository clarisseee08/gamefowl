<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <x-partials.head-meta :title="$title ?? 'Sign in'" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
{{--
    THE SIGN-IN SCREENS - one strong photograph, and the form.

    A split: the farm at the hour the birds are let out on the left, clean,
    with nothing laid over it; the form on the right on paper, with the
    farm's mark and name above it. Sign in, forgot password, reset and confirm
    all use this layout, so they read as one place.

    WHAT LEFT. The first versions ran a strip of the six band colours down the
    left edge, with a caption explaining what they were. Six saturated hues on
    the first screen anyone sees read as decoration, and a caption explaining a
    visual is the tell that it is not carrying itself.

    The photograph is hidden below lg: a half-screen picture above a sign-in
    form pushes the fields off a phone, which this screen must never do.

    There is no sign-up link and there must not be one: the owner creates
    every account (Fortify registration is off).
--}}
<body class="h-full bg-background text-foreground">
    <div class="flex min-h-full">
        <figure class="relative hidden overflow-hidden lg:block lg:w-[48%] xl:w-[52%]">
            <img src="{{ asset('photos/hero-farm.webp') }}" alt="Gamefowl on grass beside a shed in evening light"
                 width="2000" height="1240" fetchpriority="high" decoding="async"
                 class="auth-photo absolute inset-0 h-full w-full object-cover object-[40%_50%]">
        </figure>

        <div class="relative flex flex-1 flex-col px-5 py-8 sm:px-10 lg:px-16">
            <div class="flex items-center justify-between gap-4">
                <a href="{{ route('home') }}" class="inline-flex min-h-11 items-center gap-2.5 font-display text-[16px] font-semibold tracking-[-0.015em] text-foreground rise-in">
                    <x-brand-mark :size="32" class="shrink-0 rounded-full" />
                    {{ config('gfms.farm.name') }}
                </a>
                <x-theme-toggle class="rise-in" style="--rise-delay: 120ms" />
            </div>

            <main class="flex flex-1 items-center py-12">
                <div class="w-full max-w-[26rem]">
                    {{ $slot }}
                </div>
            </main>

            <p class="text-[13px] text-muted-foreground">
                <a href="{{ route('home') }}" class="inline-flex min-h-11 items-center gap-1.5 hover:text-foreground">
                    <span aria-hidden="true">&larr;</span> Back to the farm's website
                </a>
            </p>
        </div>
    </div>
</body>
</html>
