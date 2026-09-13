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
    THE PUBLIC SHELL — the second surface.

    Full-bleed like the console, but deliberately NOT the console. A dense
    application sidebar is wrong for a photo-led browse: it spends 240px of a
    grid that wants to be as wide as possible, and it presents a customer with
    navigation for screens they cannot open.

    So: a slim top bar, and the whole width given to the stock. The page scrolls
    normally here rather than being trapped in a pane, because browsing is a
    reading behaviour and a customer expects the page to move.

    THE FOOTER IS NEW. Until it existed the catalogue simply stopped at the last
    row of birds, which reads as an unfinished page rather than the bottom of
    one. Everything in it comes from config('gfms.farm.*'), and every row is
    conditional - see the note there.
--}}
<body class="flex min-h-full flex-col bg-background">
    <div class="brand-rail" aria-hidden="true"></div>
    @php
        $user = auth()->user();
        $farm = config('gfms.farm');
    @endphp

    <header class="sticky top-0 z-30 border-b border-border bg-card">
        <div class="flex h-14 items-center gap-4 px-4 sm:px-6 lg:px-8">
            <a href="{{ route('home') }}" wire:navigate
               class="inline-flex min-h-11 shrink-0 items-center gap-2.5 text-[15px] font-semibold text-foreground">
                <x-brand-mark :size="30" />
                <span class="truncate">{{ $farm['name'] }}</span>
            </a>

            {{-- Two destinations, which is the whole of the public site: the
                 stock, and arranging to come and see it. Hidden on the
                 narrowest screens, where the footer carries the same links and
                 a cramped row of them would crowd the farm's own name. --}}
            <nav class="ml-6 hidden items-center gap-5 sm:flex" aria-label="Main">
                <a href="{{ route('catalog.index') }}" wire:navigate
                   class="text-[15px] text-muted-foreground hover:text-foreground">Our stock</a>
                <a href="{{ route('home') }}#visit"
                   class="text-[15px] text-muted-foreground hover:text-foreground">Visit us</a>
            </nav>

            <span class="ml-auto"></span>

            @if ($user?->isInternal())
                {{-- The way back to the console, shown only to staff. A visitor
                     never sees a door they cannot open. --}}
                <a href="{{ route('dashboard') }}" wire:navigate
                   class="btn-secondary h-11 min-h-0 px-3 text-[14px]">
                    Console
                </a>
            @endif

            {{-- This shell serves people who are not signed in at all, so it
                 cannot assume there is a session to end. A bare "Sign out" in
                 front of a visitor who never signed in is a dead control that
                 also implies they have an account they do not have.

                 The sign-in link is deliberately quiet: this is a shop window,
                 and the farm's own staff are the only people it is for. --}}
            <x-theme-toggle />

            @auth
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="btn-quiet h-11 min-h-0 px-3 text-[14px]">Sign out</button>
                </form>
            @else
                <a href="{{ route('login') }}" wire:navigate class="btn-quiet h-11 min-h-0 px-3 text-[14px]">Sign in</a>
            @endauth
        </div>
    </header>

    <main class="flex-1 px-4 py-6 sm:px-6 lg:px-8">
        @if (session('success'))
            <div class="mb-5 rounded-[var(--radius-md)] bg-success-bg px-4 py-3 text-[15px] text-success" role="status">
                {{ session('success') }}
            </div>
        @endif

        {{ $slot }}
    </main>

    {{--
        EVERY ROW HERE IS CONDITIONAL, and that is the rule rather than caution.

        The application genuinely does not know the farm's address, phone,
        email or hours - all four default to an empty string and are supplied
        through the environment. A footer that renders "Phone" with nothing
        after it looks broken in a way that an absent row does not, so a value
        the farm has not given produces no markup at all.
    --}}
    <footer class="mt-12 border-t border-border bg-card">
        <div class="px-4 py-10 sm:px-6 lg:px-8">
            <div class="grid gap-8 sm:grid-cols-2 lg:grid-cols-3">

                <div>
                    <div class="flex items-center gap-2.5">
                        <x-brand-mark :size="26" />
                        <span class="text-[15px] font-semibold text-foreground">{{ $farm['name'] }}</span>
                    </div>
                    <p class="mt-3 max-w-[42ch] text-[15px] leading-relaxed text-muted-foreground">
                        Gamefowl bred and recorded on the farm, with the pedigree of every
                        bird kept from hatch.
                    </p>
                </div>

                @if ($farm['address'] || $farm['phone'] || $farm['email'] || $farm['hours'])
                    <div>
                        <h2 class="text-[13px] font-semibold uppercase tracking-[0.07em] text-muted-foreground">
                            Find us
                        </h2>
                        <dl class="mt-4 space-y-3 text-[15px]">
                            @if ($farm['address'])
                                <div>
                                    <dt class="text-muted-foreground">Address</dt>
                                    <dd class="mt-0.5 max-w-[38ch] text-foreground">{{ $farm['address'] }}</dd>
                                </div>
                            @endif
                            @if ($farm['phone'])
                                <div>
                                    <dt class="text-muted-foreground">Phone</dt>
                                    {{-- A phone number is registry data: monospaced so the
                                         digits group the way a written one does. --}}
                                    <dd class="datum mt-0.5 text-foreground">{{ $farm['phone'] }}</dd>
                                </div>
                            @endif
                            @if ($farm['email'])
                                <div>
                                    <dt class="text-muted-foreground">Email</dt>
                                    <dd class="mt-0.5 text-foreground">
                                        <a href="mailto:{{ $farm['email'] }}" class="hover:underline">{{ $farm['email'] }}</a>
                                    </dd>
                                </div>
                            @endif
                            @if ($farm['hours'])
                                <div>
                                    <dt class="text-muted-foreground">Visiting hours</dt>
                                    <dd class="mt-0.5 max-w-[38ch] text-foreground">{{ $farm['hours'] }}</dd>
                                </div>
                            @endif
                        </dl>
                    </div>
                @endif

                <div>
                    <h2 class="text-[13px] font-semibold uppercase tracking-[0.07em] text-muted-foreground">
                        Browse
                    </h2>
                    <ul class="mt-4 space-y-2.5 text-[15px]">
                        <li>
                            <a href="{{ route('catalog.index') }}" wire:navigate
                               class="text-foreground hover:underline">Our stock</a>
                        </li>
                        <li>
                            <a href="{{ route('home') }}#visit"
                               class="text-foreground hover:underline">Visit us</a>
                        </li>
                        @guest
                            <li>
                                <a href="{{ route('login') }}" wire:navigate
                                   class="text-muted-foreground hover:underline">Staff sign in</a>
                            </li>
                        @endguest
                    </ul>
                </div>
            </div>

            <div class="mt-10 border-t border-border pt-6">
                <p class="text-[14px] text-muted-foreground">
                    &copy; <span class="datum">{{ now()->year }}</span> {{ $farm['name'] }}.
                </p>
            </div>
        </div>
    </footer>
</body>
</html>
