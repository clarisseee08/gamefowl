<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Sign in' }} &middot; {{ config('gfms.farm.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full bg-canvas">
    {{-- Sign-in is the one screen in this system that earns Apple's marketing
         spacing rather than Operate density: there is exactly one task, nobody
         is scanning, and the farm's name should land before the form does.

         No card and no logo tile. On a single-purpose screen a bordered box
         around the only content is chrome for its own sake, and "GF" in a
         rounded square was a placeholder standing in for an identity the farm
         does not have yet. The name set properly is the identity. --}}
    <div class="flex min-h-full flex-col justify-center px-6 py-16">
        <div class="mx-auto w-full max-w-[26rem]">
            <header class="text-center">
                <h1 class="text-[40px] font-semibold leading-[1.1] tracking-[-0.022em] text-ink">
                    {{ config('gfms.farm.name') }}
                </h1>
                <p class="mt-3 text-[21px] font-normal leading-snug text-ink-48">
                    Broodcock farm records
                </p>
            </header>

            <div class="mt-12">
                {{ $slot }}
            </div>
        </div>
    </div>
</body>
</html>
