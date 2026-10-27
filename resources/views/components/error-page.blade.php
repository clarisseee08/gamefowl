@props([
    /** The HTTP status, shown as-is. It is the one thing worth quoting to a maintainer. */
    'code',
    /** What happened, in the farm's own words rather than the protocol's. */
    'heading',
    /** Browser tab title. Defaults to the heading. */
    'title' => null,
])

@php
    /*
     * AN ERROR PAGE HAS TO BE THE SAFEST VIEW IN THE APPLICATION.
     *
     * It renders when something has already gone wrong, so it touches nothing
     * that can go wrong a second time: config only - no query, no model, no
     * relationship. The session driver here is `cookie`, so even auth()->check()
     * costs nothing and cannot fail against a database that is already refusing.
     *
     * WHY THIS EXISTS AT ALL. There were no error views, so every 404, 403 and
     * 500 fell through to Laravel's default - a bare page reading "Not Found"
     * with no farm name, no navigation and no way back. Tolerable for a private
     * admin tool; not for this one. /catalog and /broodcocks/{id} are PUBLIC, so
     * a customer following a stale link or mistyping a URL was shown something
     * that looks like the whole site is broken.
     */
    $farm = config('gfms.farm.name');

    // Where "back" goes depends on who is looking, exactly as the bird page's
    // back link does: staff have a console, everyone else has the catalogue.
    $internal = auth()->check() && (auth()->user()?->isInternal() ?? false);
    $home = $internal ? route('dashboard') : route('home');
    $homeLabel = $internal ? 'Back to the dashboard' : 'Back to the farm';
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <x-partials.head-meta :title="$title ?? $heading" />
    @vite(['resources/css/app.css'])
</head>
<body class="h-full bg-background text-foreground">
<main class="flex min-h-full items-center px-5 py-16 sm:px-10 lg:px-24">
    <div class="w-full max-w-[34rem]">
        <a href="{{ $home }}" class="inline-flex min-h-11 items-center gap-2.5 font-display text-[16px] font-semibold tracking-[-0.015em] text-foreground">
            <x-brand-mark :size="32" class="rounded-full" />
            <span>{{ $farm }}</span>
        </a>

        {{-- The status code is registry data like every other number here, so it
             is monospaced. It is shown rather than hidden behind friendlier
             wording because it is the one detail worth quoting to whoever
             maintains this. --}}
        <p class="datum mt-14 text-[15px] font-semibold text-primary">Error {{ $code }}</p>

        <h1 class="mt-3 font-display text-[2.2rem] font-semibold leading-[1.08] tracking-[-0.03em] text-foreground sm:text-[2.8rem]">
            {{ $heading }}
        </h1>

        <div class="mt-5 max-w-[52ch] space-y-3 text-[17px] leading-relaxed text-muted-foreground">
            {{ $slot }}
        </div>

        <div class="mt-9 flex flex-wrap items-center gap-3">
            <a href="{{ $home }}" class="btn-primary">{{ $homeLabel }}</a>

            {{-- A second way out, when the page has one worth offering. --}}
            @isset($actions)
                {{ $actions }}
            @endisset
        </div>
    </div>
</main>
</body>
</html>
