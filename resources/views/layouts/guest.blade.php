<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Sign in' }} &middot; {{ config('gfms.farm.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full bg-parchment">
    {{-- Sign-in is the one screen in this system that earns generous spacing
         rather than Console density: there is exactly one task, nobody is
         scanning, and the farm's name should land before the form does.

         Still no logo tile - "GF" in a rounded square was a placeholder standing
         in for an identity the farm does not have. The identity this system
         actually has is the band, so the band colours carry it: six anodised
         stripes, the same six a bird wears on its leg and the same six used on
         every screen behind this one. It is the one ornament on the page and it
         is the product's own subject rather than decoration. --}}
    <div class="flex min-h-full flex-col justify-center px-6 py-16">
        <div class="mx-auto w-full max-w-[26rem]">
            <div class="overflow-hidden rounded-[4px] border border-hairline bg-canvas">
                <div class="flex h-1.5" aria-hidden="true">
                    @foreach (config('gfms-brand.bands') as $hex)
                        <span class="flex-1" style="background-color: {{ $hex }}"></span>
                    @endforeach
                </div>

                <div class="px-7 py-9 sm:px-9">
                    <header class="text-center">
                        <h1 class="text-[32px] font-semibold leading-[1.12] tracking-[-0.02em] text-ink">
                            {{ config('gfms.farm.name') }}
                        </h1>
                        <p class="mt-2 text-[15px] leading-snug text-ink-80">
                            Broodcock farm records
                        </p>
                    </header>

                    <div class="mt-8 border-t border-hairline pt-8">
                        {{ $slot }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
