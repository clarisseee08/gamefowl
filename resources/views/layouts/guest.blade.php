@php
    $bands = config('gfms-brand.bands');

    /*
     * THE STRIP, re-sequenced and unevenly weighted.
     *
     * config/gfms-brand.php lists the bands ember, amber, jade, cobalt, plum,
     * rose - which is spectral order. Six equal segments in spectral order is a
     * flag, and a flag is the one thing this must not read as. So the order is
     * rewritten to put a wide hue gap between every pair of neighbours, and the
     * weights are uneven, because a strip of real anodised rings is not divided
     * into sixths.
     *
     * Names, not hexes: the colours still come from the config, so the band a
     * visitor meets here is the same one a bird wears in the catalogue.
     */
    $strip = [
        ['cobalt', 19],
        ['amber', 11],
        ['plum', 24],
        ['jade', 15],
        ['rose', 9],
        ['ember', 22],
    ];
@endphp
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
    THE AUTH SCREEN — a leg band, a brand panel and a form.

    This is the first screen anyone sees, including a panel on defense day, and
    it was doing the least work of any screen in the app. The split-panel layout
    fixed the worst of that; what it did not fix is that roughly sixty per cent
    of both panels was empty, and that the single best thing on the page - the
    strip of band colours - was six pixels tall in a bottom corner.

    The band is now the page's bound edge, floor to ceiling. The void is filled
    by scale rather than by inventing content to put in it.

    The dark panel is chrome, and brand_deep is the only place that green is
    allowed. The form sits on the light side, so no control on it ever inherits
    brand colour - the separation that keeps "branded" and "urgent" from reading
    as the same thing. (An earlier version of this comment described the panel as
    "comb red" and the controls as "peacock". Both of those palettes were
    retired; the panel is brand_deep #243619 and the controls are the deep-red
    primary scale.)

    Below lg the panel collapses to a header strip rather than stacking a
    half-screen of green above the form, which would push the fields off a phone.
--}}
<body class="h-full bg-background">
    <div class="brand-rail" aria-hidden="true"></div>

    <div class="flex min-h-[calc(100%-3px)]">

        {{-- ---------------------------------------------------------------
             THE LEG BAND.

             aria-hidden, because it is the same information the caption in the
             panel states in words, and a screen reader being read six colour
             names would learn nothing from it.

             THE DRAINED STATE IS SERVER-RENDERED, and the first sensible
             attempt at this was not. It was an Alpine x-data on this wrapper
             with :class binding the drained state - which did nothing at all,
             silently, because Alpine ships inside Livewire's bundle and this
             layout has no Livewire component on it, so window.Alpine is
             undefined here. Anything reaching for x-* on a guest page is
             writing dead attributes.

             So the failure renders from PHP, and five lines of vanilla JS in
             app.js take it off again on the first keystroke. Without any JS at
             all the band simply stays drained, which is still true.
        --------------------------------------------------------------- --}}
        <div @class(['leg-band', 'leg-band-drained' => $errors->any()]) aria-hidden="true">
            @foreach ($strip as [$name, $weight])
                <span class="leg-band-seg"
                      style="flex: {{ $weight }} 0 0; background-color: {{ $bands[$name] }}; --band-delay: {{ $loop->index * 60 }}ms"></span>
            @endforeach
        </div>

        <div class="flex flex-1 flex-col lg:flex-row">

            {{-- Brand panel. Centred rather than justify-between: pinning the
                 caption to the floor is what opened a four-hundred-pixel hole
                 in the middle of it, and the band now carries the full height
                 that the hole was pretending to. --}}
            <div class="flex shrink-0 flex-col justify-center bg-brand-deep px-6 py-6 lg:w-[42%] lg:px-14 lg:py-14">
                <x-brand-lockup size="md" tone="onDark" class="rise-in lg:hidden" />

                <div class="hidden lg:block">
                    <span class="rise-in inline-flex h-28 w-28 items-center justify-center rounded-full bg-brand-foreground"
                          style="--rise-delay: 240ms">
                        <x-brand-mark :size="96" class="rounded-full" />
                    </span>

                    <h1 class="rise-in mt-8 text-[34px] font-semibold leading-[1.15] text-brand-foreground"
                        style="--rise-delay: 320ms">
                        {{ config('gfms.system.name') }}
                    </h1>

                    <p class="rise-in mt-3 max-w-[36ch] text-[16px] leading-relaxed text-brand-muted-fg"
                       style="--rise-delay: 380ms">
                        Breeding, health, performance and pedigree records for
                        {{ config('gfms.farm.name') }}.
                    </p>

                    {{-- The caption stays, and now points at something worth
                         pointing at. Colour is never the only channel in this
                         system: the sentence is what makes the edge of the page
                         mean something to someone who cannot separate the six
                         hues, or who is reading this printed in grey. --}}
                    <p class="rise-in mt-10 max-w-[34ch] text-[13px] leading-relaxed text-brand-muted-fg"
                       style="--rise-delay: 440ms">
                        Every bird carries its bloodline as a band colour.
                        Those are the six, down the edge of this page.
                    </p>
                </div>
            </div>

            {{-- Form panel.

                 THE THEME TOGGLE BELONGS HERE, and until now it was on every
                 shell except this one - so the one screen a keeper meets before
                 they have an account was the one screen where they could not
                 turn the lights down. It could not be here before: the control
                 was Alpine-driven, and Alpine is not loaded on a guest page. --}}
            <div class="relative flex flex-1 items-center justify-center px-6 py-10 lg:px-16">
                <x-theme-toggle class="absolute right-4 top-4 rise-in" style="--rise-delay: 200ms" />

                <div class="w-full max-w-[26rem]">
                    {{ $slot }}
                </div>
            </div>
        </div>
    </div>
</body>
</html>
