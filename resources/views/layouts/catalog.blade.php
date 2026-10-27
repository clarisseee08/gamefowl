<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <x-partials.head-meta :title="$title ?? 'Catalogue'" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
{{--
    THE PUBLIC SHELL — the stud book.

    Warm paper and near-black ink, one red from the logo, and whitespace doing
    the separating. It does not follow the console's light/dark toggle: the
    public site is one design, and the toggle stays where it is a working
    preference - the console and the sign-in screen.

    .studbook re-points the console's semantic tokens at paper and ink (see
    app.css), which is what lets the catalogue, a bird's page and its family
    tree share one markup between this shell and the console.
--}}
<body class="flex min-h-full flex-col bg-background text-foreground">
    @php
        $user = auth()->user();
        $farm = config('gfms.farm');

        $nav = [
            ['label' => 'Our stock', 'url' => route('catalog.index'), 'on' => request()->routeIs('catalog.*')],
            ['label' => 'About', 'url' => route('public.about'), 'on' => request()->routeIs('public.about')],
            ['label' => 'Visit us', 'url' => route('public.visit'), 'on' => request()->routeIs('public.visit')],
        ];
    @endphp

    {{-- Thin, light, sticky, with one hairline under it. The logo, the farm's
         name and three links on the left; Sign in on the right. --}}
    <header class="sticky top-0 z-30 border-b border-border bg-background">
        <div class="sb-wrap flex h-16 items-center gap-2 sm:gap-6">
            <a href="{{ route('home') }}" wire:navigate
               class="inline-flex min-h-11 min-w-0 items-center gap-2.5 font-display text-[16px] font-semibold tracking-[-0.015em] text-foreground">
                <x-brand-mark :size="32" class="shrink-0 rounded-full" />
                <span class="truncate">{{ $farm['name'] }}</span>
            </a>

            <nav class="hidden items-center md:flex" aria-label="Main">
                @foreach ($nav as $link)
                    <a href="{{ $link['url'] }}" wire:navigate class="sb-nav-link"
                       @if ($link['on']) aria-current="page" @endif>{{ $link['label'] }}</a>
                @endforeach
            </nav>

            <span class="ml-auto"></span>

            @if ($user?->isInternal())
                {{-- The way back to the console, shown only to staff. A visitor
                     never sees a door they cannot open. --}}
                <a href="{{ route('dashboard') }}" wire:navigate class="sb-nav-link shrink-0">Console</a>
            @endif

            {{-- This shell serves people who are not signed in at all, so it
                 cannot assume there is a session to end. --}}
            @auth
                <form method="POST" action="{{ route('logout') }}" class="shrink-0">
                    @csrf
                    <button type="submit" class="btn-secondary min-h-11 px-4 text-[14px]">Sign out</button>
                </form>
            @else
                <a href="{{ route('login') }}" wire:navigate class="btn-secondary min-h-11 shrink-0 px-4 text-[14px]">Sign in</a>
            @endauth
        </div>

        {{-- On a phone the three links get their own row rather than a
             hamburger: three short words fit across 390px, and hiding them
             behind a button costs every visitor a tap to learn what the site
             has. The row scrolls inside itself if it ever has to. --}}
        <nav class="sb-wrap -mt-2 flex overflow-x-auto pb-1 md:hidden" aria-label="Main">
            @foreach ($nav as $link)
                <a href="{{ $link['url'] }}" wire:navigate class="sb-nav-link shrink-0 first:-ml-2.5"
                   @if ($link['on']) aria-current="page" @endif>{{ $link['label'] }}</a>
            @endforeach
        </nav>
    </header>

    <main class="flex-1">
        @if (session('success'))
            <div class="sb-wrap pt-6">
                <div class="rounded-lg bg-success-bg px-4 py-3 text-[15px] text-foreground" role="status">
                    {{ session('success') }}
                </div>
            </div>
        @endif

        {{ $slot }}
    </main>

    {{-- ONE SIMPLE ROW: the farm and where it is, the pages, the legal pages,
         the copyright. No wordmark and no boxes - a hairline above it is the
         only edge. Wraps to stacked lines on a phone. --}}
    <footer class="border-t border-border">
        <div class="sb-wrap flex flex-col gap-6 py-10 lg:flex-row lg:items-center lg:justify-between lg:gap-10">
            <div class="flex items-center gap-3">
                <x-brand-mark :size="28" class="shrink-0 rounded-full" />
                <p class="text-[14px] leading-snug">
                    <span class="font-semibold text-foreground">{{ $farm['name'] }}</span>
                    @if ($farm['address'])
                        <span class="block text-muted-foreground sm:inline"><span class="hidden sm:inline" aria-hidden="true"> &middot; </span>{{ $farm['address'] }}</span>
                    @endif
                </p>
            </div>

            <nav class="flex flex-wrap gap-x-5 text-[14px]" aria-label="Footer">
                @foreach ($nav as $link)
                    <a href="{{ $link['url'] }}" wire:navigate class="inline-flex min-h-11 items-center text-muted-foreground hover:text-foreground">{{ $link['label'] }}</a>
                @endforeach
                <a href="{{ route('legal.privacy') }}" wire:navigate class="inline-flex min-h-11 items-center text-muted-foreground hover:text-foreground">Privacy</a>
                <a href="{{ route('legal.terms') }}" wire:navigate class="inline-flex min-h-11 items-center text-muted-foreground hover:text-foreground">Terms</a>
                @guest
                    <a href="{{ route('login') }}" wire:navigate class="inline-flex min-h-11 items-center text-muted-foreground hover:text-foreground">Staff sign in</a>
                @endguest
            </nav>

            <p class="text-[13px] text-muted-foreground">&copy; <span class="datum">{{ now()->year }}</span> {{ $farm['name'] }}</p>
        </div>
    </footer>
</body>
</html>
