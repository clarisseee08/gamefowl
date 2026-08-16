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
    THE AUTH SCREEN — a two-panel split.

    This is the first screen anyone sees, including a panel on defense day, and
    it was doing the least work of any screen in the app: a small centred card on
    a near-white page. It now carries the identity at full size.

    The brand panel is chrome, which is the only place comb red is allowed. The
    form sits on the light side, so every control on it stays peacock and no
    control ever inherits brand colour - the separation that keeps "branded" and
    "urgent" from reading as the same thing.

    Below lg the panel collapses to a header strip rather than stacking a
    half-screen of red above the form, which would push the fields off a phone.
--}}
<body class="h-full bg-background">
    <div class="brand-rail" aria-hidden="true"></div>

    <div class="flex min-h-[calc(100%-3px)] flex-col lg:flex-row">

        {{-- Brand panel. --}}
        <div class="flex shrink-0 flex-col justify-between bg-brand-deep px-6 py-6 lg:w-[42%] lg:px-12 lg:py-14">
            <x-brand-lockup size="md" tone="onDark" class="lg:hidden" />

            <div class="hidden lg:block">
                <x-icon.rooster class="h-20 w-20 text-brand-foreground" eye="var(--color-brand-deep)" />

                <h1 class="mt-8 text-[34px] font-semibold leading-[1.15] text-brand-foreground">
                    {{ config('gfms.system.name') }}
                </h1>
                <p class="mt-3 max-w-[36ch] text-[16px] leading-relaxed text-brand-muted-fg">
                    Breeding, health, performance and pedigree records for
                    {{ config('gfms.farm.name') }}.
                </p>
            </div>

            {{-- The band colours, still the product's own subject. Kept because
                 they are the one ornament that means something: these are the
                 colours a bird actually wears. --}}
            <div class="mt-8 hidden lg:block">
                <div class="flex h-1.5 w-40 overflow-hidden rounded-full" aria-hidden="true">
                    @foreach (config('gfms-brand.bands') as $hex)
                        <span class="flex-1" style="background-color: {{ $hex }}"></span>
                    @endforeach
                </div>
                <p class="mt-3 text-[12px] text-brand-muted-fg">
                    Every bird carries its bloodline as a band colour.
                </p>
            </div>
        </div>

        {{-- Form panel. --}}
        <div class="flex flex-1 items-center justify-center px-6 py-10 lg:px-16">
            <div class="w-full max-w-[26rem]">
                {{ $slot }}
            </div>
        </div>
    </div>
</body>
</html>
