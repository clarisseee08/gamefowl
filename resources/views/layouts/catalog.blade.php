<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <x-partials.head-meta :title="$title ?? 'Catalogue'" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
{{--
    THE PUBLIC SHELL (EMS) — the stud book.

    The forest palette on paper, the EMS parts at a public site's scale, and
    whitespace doing the separating. It does not follow the console's light/dark toggle: the
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

    {{-- THE HEADER (EMS): sticky, translucent paper over a hairline.

         The farm and its town on the left; the three pages as one segmented
         control in the middle, the current page raised out of the track;
         on the right a way to ring the farm, the theme, and Sign in - the
         one primary control, so it is the first thing a returning member of
         staff finds. --}}
    @php
        $place = filled($farm['address'] ?? null)
            ? implode(', ', array_slice(array_map('trim', explode(',', $farm['address'])), -2))
            : null;
        $headerTel = filled($farm['phone'] ?? null) ? preg_replace('/[^0-9+]/', '', $farm['phone']) : null;
        $pillTrack = 'flex items-center gap-0.5 rounded-full border border-border bg-muted/70 p-1';
        $pill = 'inline-flex items-center justify-center rounded-full px-4 text-sm font-medium transition-[color,background-color,box-shadow] duration-150 outline-none focus-visible:ring-[3px] focus-visible:ring-ring/50';
    @endphp
    <header class="sticky top-0 z-30 border-b border-border bg-background/85 backdrop-blur-md supports-[backdrop-filter]:bg-background/70">
        <div class="sb-wrap flex h-16 items-center gap-3 lg:grid lg:grid-cols-[1fr_auto_1fr] lg:gap-6">
            <a href="{{ route('home') }}" wire:navigate
               class="inline-flex min-h-11 min-w-0 items-center gap-2.5 justify-self-start">
                <x-brand-mark :size="36" class="shrink-0 rounded-full ring-1 ring-border" />
                <span class="min-w-0 leading-tight">
                    <span class="block truncate text-[15px] font-bold text-foreground">{{ $farm['name'] }}</span>
                    @if ($place)
                        <span class="hidden truncate text-xs text-muted-foreground sm:block">{{ $place }}</span>
                    @endif
                </span>
            </a>

            <nav class="hidden md:block lg:justify-self-center" aria-label="Main">
                <div class="{{ $pillTrack }}">
                    @foreach ($nav as $link)
                        <a href="{{ $link['url'] }}" wire:navigate
                           @class([
                               $pill, 'h-8',
                               'bg-background text-foreground shadow-sm' => $link['on'],
                               'text-muted-foreground hover:text-foreground' => ! $link['on'],
                           ])
                           @if ($link['on']) aria-current="page" @endif>{{ $link['label'] }}</a>
                    @endforeach
                </div>
            </nav>

            <div class="ml-auto flex shrink-0 items-center gap-1.5 lg:ml-0 lg:justify-self-end sm:gap-2">
                @if ($headerTel)
                    <a href="tel:{{ $headerTel }}" aria-label="Ring the farm"
                       class="hidden h-9 items-center gap-2 rounded-md px-2.5 text-sm font-medium text-muted-foreground transition-colors hover:bg-muted hover:text-foreground xl:inline-flex">
                        <x-ui.icon name="phone" class="size-4" /><span class="datum">{{ $farm['phone'] }}</span>
                    </a>
                @endif

                <x-ui.theme-menu />

                @if ($user?->isInternal())
                    {{-- The way back to the console, shown only to staff. A
                         visitor never sees a door they cannot open. --}}
                    <x-ui.button variant="ghost" :href="route('dashboard')" wire:navigate class="hidden sm:inline-flex">
                        <x-ui.icon name="layout-grid" /> Console
                    </x-ui.button>
                @endif

                {{-- This shell serves people who are not signed in at all, so
                     it cannot assume there is a session to end. --}}
                @auth
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <x-ui.button type="submit" variant="outline"><x-ui.icon name="log-out" /> Sign out</x-ui.button>
                    </form>
                @else
                    <x-ui.button :href="route('login')" wire:navigate>
                        <x-ui.icon name="log-in" /> Sign in
                    </x-ui.button>
                @endauth
            </div>
        </div>

        {{-- On a phone the three links get their own row rather than a
             hamburger: three short words fit across 390px, and hiding them
             costs every visitor a tap to learn what the site has. Same
             segmented control, stretched, at 44px. --}}
        <nav class="sb-wrap pb-2.5 md:hidden" aria-label="Main">
            <div class="{{ $pillTrack }}">
                @foreach ($nav as $link)
                    <a href="{{ $link['url'] }}" wire:navigate
                       @class([
                           $pill, 'h-10 flex-1 px-2',
                           'bg-background text-foreground shadow-sm' => $link['on'],
                           'text-muted-foreground' => ! $link['on'],
                       ])
                       @if ($link['on']) aria-current="page" @endif>{{ $link['label'] }}</a>
                @endforeach
            </div>
        </nav>
    </header>

    <main class="flex-1">
        @if (session('success'))
            <div class="sb-wrap pt-6">
                <div class="d-alert d-alert-soft d-alert-success text-[15px] text-base-content" role="status">
                    {{ session('success') }}
                </div>
            </div>
        @endif

        {{ $slot }}
    </main>

    {{-- THE FOOTER - the forest green, the one other dark block.

         A call to visit first (left off the visiting page, which is that
         call), then the farm, its pages, how to reach it and the legal pages
         in four columns, and a closing line with the way back to the top.
         Ink is --sb-dark-ink (12.7:1) and --sb-dark-muted (8.2:1). --}}
    @php
        $telHref = filled($farm['phone'] ?? null) ? preg_replace('/[^0-9+]/', '', $farm['phone']) : null;
    @endphp
    <footer class="mt-16 bg-[var(--sb-dark)] text-[var(--sb-dark-ink)]">
        @unless (request()->routeIs('public.visit', 'home'))
            <div class="sb-wrap pt-12 lg:pt-14">
                <div class="flex flex-col gap-5 rounded-xl border border-[var(--sb-dark-line)] bg-white/[0.04] p-6 sm:flex-row sm:items-center sm:justify-between lg:p-8">
                    <div>
                        <p class="text-xl font-bold lg:text-2xl">Come and see the birds</p>
                        <p class="mt-1.5 max-w-[52ch] text-[15px] text-[var(--sb-dark-muted)]">Visits are by arrangement. Ring or write first, and the farm will settle a time with you.</p>
                    </div>
                    <div class="flex shrink-0 flex-wrap gap-3">
                        @if ($telHref)
                            <a href="tel:{{ $telHref }}" class="d-btn border-0 bg-[var(--sb-dark-ink)] text-[var(--sb-dark)] hover:bg-white"><x-ui.icon name="phone" class="size-4" /> Ring the farm</a>
                        @endif
                        <a href="{{ route('public.visit') }}" wire:navigate class="d-btn border-[var(--sb-dark-line)] bg-transparent text-[var(--sb-dark-ink)] hover:border-[var(--sb-dark-muted)] hover:bg-white/5">Plan a visit</a>
                    </div>
                </div>
            </div>
        @endunless

        <div class="sb-wrap grid gap-10 py-12 sm:grid-cols-2 lg:grid-cols-[1.5fr_1fr_1.5fr_1fr] lg:py-14">
            <div>
                <a href="{{ route('home') }}" wire:navigate class="inline-flex items-center gap-3">
                    <x-brand-mark :size="40" class="shrink-0 rounded-full ring-2 ring-[var(--sb-dark-line)]" />
                    <span class="text-base font-bold">{{ $farm['name'] }}</span>
                </a>
                <p class="mt-4 max-w-xs text-sm leading-relaxed text-[var(--sb-dark-muted)]">
                    Broodcocks and broodhens with their pedigree, health and performance on record.
                </p>
            </div>

            <nav aria-label="Footer">
                <p class="text-xs font-semibold tracking-wider uppercase">Explore</p>
                <ul class="mt-3 space-y-0.5 text-sm">
                    @foreach ($nav as $link)
                        <li><a href="{{ $link['url'] }}" wire:navigate class="inline-flex min-h-9 items-center text-[var(--sb-dark-muted)] transition-colors hover:text-[var(--sb-dark-ink)]">{{ $link['label'] }}</a></li>
                    @endforeach
                    @guest
                        <li><a href="{{ route('login') }}" wire:navigate class="inline-flex min-h-9 items-center text-[var(--sb-dark-muted)] transition-colors hover:text-[var(--sb-dark-ink)]">Staff sign in</a></li>
                    @endguest
                </ul>
            </nav>

            <div>
                <p class="text-xs font-semibold tracking-wider uppercase">Contact</p>
                <ul class="mt-3 space-y-3 text-sm text-[var(--sb-dark-muted)]">
                    @if ($farm['address'] ?? null)
                        <li class="flex items-start gap-2.5"><x-ui.icon name="map-pin" class="mt-0.5 size-4 shrink-0" /><span>{{ $farm['address'] }}</span></li>
                    @endif
                    @if ($telHref)
                        <li class="flex items-start gap-2.5"><x-ui.icon name="phone" class="mt-0.5 size-4 shrink-0" /><a href="tel:{{ $telHref }}" class="datum transition-colors hover:text-[var(--sb-dark-ink)]">{{ $farm['phone'] }}</a></li>
                    @endif
                    @if ($farm['email'] ?? null)
                        <li class="flex items-start gap-2.5"><x-ui.icon name="mail" class="mt-0.5 size-4 shrink-0" /><a href="mailto:{{ $farm['email'] }}" class="break-words transition-colors hover:text-[var(--sb-dark-ink)]">{{ $farm['email'] }}</a></li>
                    @endif
                    @if ($farm['hours'] ?? null)
                        <li class="flex items-start gap-2.5"><x-ui.icon name="clock" class="mt-0.5 size-4 shrink-0" /><span>{{ $farm['hours'] }}</span></li>
                    @endif
                </ul>
            </div>

            <div>
                <p class="text-xs font-semibold tracking-wider uppercase">Legal</p>
                <ul class="mt-3 space-y-0.5 text-sm">
                    <li><a href="{{ route('legal.privacy') }}" wire:navigate class="inline-flex min-h-9 items-center text-[var(--sb-dark-muted)] transition-colors hover:text-[var(--sb-dark-ink)]">Privacy</a></li>
                    <li><a href="{{ route('legal.terms') }}" wire:navigate class="inline-flex min-h-9 items-center text-[var(--sb-dark-muted)] transition-colors hover:text-[var(--sb-dark-ink)]">Terms</a></li>
                </ul>
            </div>
        </div>

        <div class="border-t border-[var(--sb-dark-line)]">
            <div class="sb-wrap flex flex-wrap items-center justify-between gap-3 py-5 text-[13px] text-[var(--sb-dark-muted)]">
                <p>&copy; <span class="datum">{{ now()->year }}</span> {{ $farm['name'] }}. All rights reserved.</p>
                <button type="button" x-data x-on:click="window.scrollTo({ top: 0, behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth' })"
                        class="inline-flex min-h-9 items-center gap-1.5 transition-colors hover:text-[var(--sb-dark-ink)]">
                    <x-ui.icon name="chevron-up" class="size-4" /> Back to top
                </button>
            </div>
        </div>
    </footer>
    {{-- Explicit, not auto-injected: Livewire only injects its bundle (and
         the Alpine inside it) on a page that renders a component, and the
         plain pages in this shell - About, Visit, the legal pages, sign-in -
         render none. Their slideshows and menus need Alpine all the same. --}}
    @livewireScripts
</body>
</html>
