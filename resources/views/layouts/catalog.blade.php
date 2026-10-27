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
    THE PUBLIC SHELL — the board.

    The public site is one dark design: warm-black ground, tiles a tone
    lighter, white type, comb red for the one action. It does not follow the
    light/dark toggle, because it is a single composition built around the
    birds' own colours - black plumage, gold hackle, red comb - and a light
    version would be a different site. The toggle stays where it is a working
    preference: the console and the sign-in screen.

    THE MEASURE IS WIDE AND THE MARGINS ARE TIGHT on purpose. The board is
    tiles fitted edge to edge with 8px gaps; generous outer margins would turn
    it back into a column floating in the middle of the screen, which is what
    made the previous public pages read as thin. Capped at 1680px so a tile is
    never absurd on a very wide monitor, and the pages that are prose (About,
    the legal pages) hold their own reading measure inside it.
--}}
<body class="flex min-h-full flex-col bg-board text-board-ink">
    @php
        $user = auth()->user();
        $farm = config('gfms.farm');

        $nav = [
            ['label' => 'Our stock', 'url' => route('catalog.index'), 'on' => request()->routeIs('catalog.*')],
            ['label' => 'About', 'url' => route('public.about'), 'on' => request()->routeIs('public.about')],
            ['label' => 'Visit us', 'url' => route('public.visit'), 'on' => request()->routeIs('public.visit')],
        ];
    @endphp

    {{-- A PLAIN BAR: the wordmark, three links, one action. No border under
         it and no shadow - it is separated from the board by being a step
         darker, the way every surface on this page is separated. --}}
    <header class="sticky top-0 z-30 bg-board">
        <div class="mx-auto w-full max-w-[1680px] px-3 sm:px-4">
            <div class="flex h-16 items-center gap-6">
                <a href="{{ route('home') }}" wire:navigate
                   class="inline-flex min-h-11 min-w-0 items-center gap-3 font-display text-[17px] font-semibold tracking-[-0.015em] text-board-ink">
                    <x-brand-mark :size="34" class="shrink-0 rounded-full" />
                    <span class="truncate">{{ $farm['name'] }}</span>
                </a>

                <nav class="hidden items-center gap-1 md:flex" aria-label="Main">
                    @foreach ($nav as $link)
                        <a href="{{ $link['url'] }}" wire:navigate
                           @if ($link['on']) aria-current="page" @endif
                           @class([
                               'board-nav-link',
                               'board-nav-link-on' => $link['on'],
                           ])>{{ $link['label'] }}</a>
                    @endforeach
                </nav>

                <span class="ml-auto"></span>

                @if ($user?->isInternal())
                    {{-- The way back to the console, shown only to staff. A
                         visitor never sees a door they cannot open. --}}
                    <a href="{{ route('dashboard') }}" wire:navigate class="board-btn-quiet shrink-0">Console</a>
                @endif

                {{-- This shell serves people who are not signed in at all, so it
                     cannot assume there is a session to end. The sign-in link is
                     deliberately the quiet one: this is a shop window, and the
                     farm's own staff are the only people it is for. --}}
                @auth
                    <form method="POST" action="{{ route('logout') }}" class="shrink-0">
                        @csrf
                        <button type="submit" class="board-btn-quiet">Sign out</button>
                    </form>
                @else
                    <a href="{{ route('login') }}" wire:navigate class="board-btn-quiet shrink-0">Sign in</a>
                @endauth
            </div>

            {{-- ON A PHONE THE LINKS GET THEIR OWN ROW rather than a hamburger.
                 Three short words fit across a 390px screen, and hiding three
                 links behind a button to save 44px of height costs every
                 visitor a tap to find out what the site has. --}}
            <nav class="-mt-1 flex gap-1 overflow-x-auto pb-2 md:hidden" aria-label="Main">
                @foreach ($nav as $link)
                    <a href="{{ $link['url'] }}" wire:navigate
                       @if ($link['on']) aria-current="page" @endif
                       @class([
                           'board-nav-link shrink-0',
                           'board-nav-link-on' => $link['on'],
                       ])>{{ $link['label'] }}</a>
                @endforeach
            </nav>
        </div>
    </header>

    <main class="mx-auto w-full max-w-[1680px] flex-1 px-3 pb-3 sm:px-4 sm:pb-4">
        @if (session('success'))
            <div class="mb-2 rounded-[var(--radius-tile)] bg-tile px-5 py-4 text-[15px] text-board-ink" role="status">
                {{ session('success') }}
            </div>
        @endif

        {{ $slot }}
    </main>

    {{--
        THE FOOTER IS THE LAST ROW OF THE BOARD, not a sitemap stapled under it.

        The farm's name returns at full width as the close. Under it, four
        tiles: where to find the farm, how to reach it, the pages, and the
        legal pages - Privacy and Terms are real pages on this site.

        EVERY CONTACT ROW IS CONDITIONAL. The farm's address, phone, email and
        hours come from the farm_settings row the owner edits at /settings, and
        a tile that says "Phone" with nothing after it looks broken in a way an
        absent one does not.
    --}}
    <footer class="mx-auto mt-2 w-full max-w-[1680px] px-3 pb-3 sm:px-4 sm:pb-4">
        <div class="board-grid">
            @if ($farm['address'] || $farm['hours'])
                <div class="board-tile col-span-12 bg-tile p-5 sm:col-span-6 sm:p-6 lg:col-span-3">
                    <h2 class="text-[15px] font-semibold text-board-ink">Find the farm</h2>
                    @if ($farm['address'])
                        <p class="mt-3 max-w-[30ch] text-[15px] leading-relaxed text-board-muted">{{ $farm['address'] }}</p>
                    @endif
                    @if ($farm['hours'])
                        <p class="mt-2 max-w-[30ch] text-[15px] leading-relaxed text-board-muted">{{ $farm['hours'] }}</p>
                    @endif
                </div>
            @endif

            @if ($farm['phone'] || $farm['email'])
                <div class="board-tile col-span-12 bg-tile p-5 sm:col-span-6 sm:p-6 lg:col-span-3">
                    <h2 class="text-[15px] font-semibold text-board-ink">Get in touch</h2>
                    @if ($farm['phone'])
                        <p class="datum mt-3 text-[15px] text-board-muted">{{ $farm['phone'] }}</p>
                    @endif
                    @if ($farm['email'])
                        <p class="mt-2 break-words text-[15px]">
                            <a href="mailto:{{ $farm['email'] }}" class="board-link">{{ $farm['email'] }}</a>
                        </p>
                    @endif
                </div>
            @endif

            <div class="board-tile col-span-12 bg-tile p-5 sm:col-span-6 sm:p-6 lg:col-span-3">
                <h2 class="text-[15px] font-semibold text-board-ink">The farm</h2>
                <ul class="mt-3 space-y-2 text-[15px]">
                    @foreach ($nav as $link)
                        <li><a href="{{ $link['url'] }}" wire:navigate class="board-link">{{ $link['label'] }}</a></li>
                    @endforeach
                    @guest
                        <li><a href="{{ route('login') }}" wire:navigate class="board-link">Staff sign in</a></li>
                    @endguest
                </ul>
            </div>

            <div class="board-tile col-span-12 flex flex-col justify-between gap-6 bg-tile p-5 sm:col-span-6 sm:p-6 lg:col-span-3">
                <ul class="space-y-2 text-[15px]">
                    <li><a href="{{ route('legal.privacy') }}" wire:navigate class="board-link">Privacy notice</a></li>
                    <li><a href="{{ route('legal.terms') }}" wire:navigate class="board-link">Terms of use</a></li>
                </ul>
                <p class="text-[13px] text-board-muted">
                    &copy; <span class="datum">{{ now()->year }}</span> {{ $farm['name'] }}
                </p>
            </div>

            {{-- The name, built large, as the last thing on the page: it closes
                 the board rather than opening the footer. Sized to FIT on one
                 line - a first version let the tile crop it, and "SSGuad Game
                 Fa" reads as a layout bug, not a design. --}}
            <div class="board-tile col-span-12 overflow-hidden bg-tile px-5 pb-4 pt-7 sm:px-6">
                <p class="board-footer-name" aria-hidden="true">{{ $farm['name'] }}</p>
            </div>
        </div>
    </footer>
</body>
</html>
