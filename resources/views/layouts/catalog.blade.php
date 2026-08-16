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
    THE CATALOGUE SHELL — the second surface.

    Full-bleed like the console, but deliberately NOT the console. A dense
    application sidebar is wrong for a photo-led browse: it spends 240px of a
    grid that wants to be as wide as possible, and it presents a customer with
    navigation for screens they cannot open.

    So: a slim top bar, and the whole width given to the stock. The page scrolls
    normally here rather than being trapped in a pane, because browsing is a
    reading behaviour and a customer expects the page to move.
--}}
<body class="min-h-full bg-background">
    <div class="brand-rail" aria-hidden="true"></div>
    @php $user = auth()->user(); @endphp

    <header class="sticky top-0 z-30 border-b border-border bg-card">
        <div class="flex h-14 items-center gap-4 px-4 sm:px-6 lg:px-8">
            <a href="{{ route('catalog.index') }}"
               class="inline-flex min-h-11 shrink-0 items-center gap-2.5 text-[15px] font-semibold text-foreground">
<x-brand-mark :size="30" />
                <span class="truncate">{{ config('gfms.farm.name') }}</span>
            </a>

            <span class="ml-auto"></span>

            @if ($user?->isInternal())
                {{-- The way back to the console, shown only to staff. A customer
                     never sees a door they cannot open. --}}
                <a href="{{ route('dashboard') }}"
                   class="btn-secondary h-11 min-h-0 px-3 text-[14px]">
                    Console
                </a>
            @endif

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="btn-quiet h-11 min-h-0 px-3 text-[14px]">Sign out</button>
            </form>
        </div>
    </header>

    <main class="px-4 py-6 sm:px-6 lg:px-8">
        @if (session('success'))
            <div class="mb-5 rounded-[var(--radius-md)] bg-success-bg px-4 py-3 text-[15px] text-success" role="status">
                {{ session('success') }}
            </div>
        @endif

        {{ $slot }}
    </main>
</body>
</html>
