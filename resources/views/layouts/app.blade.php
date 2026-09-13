<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <x-partials.head-meta :title="$title ?? 'Dashboard'" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
{{--
    THE CONSOLE SHELL.

    The single most important line in this file is `overflow-hidden` on the body
    together with `overflow-y-auto` on the content region: the main region is the
    ONLY scroll container in the console. The body never scrolls.

    That one change is most of the difference between a website and an
    application. Previously this was a 1120px column centred in the viewport,
    which on a 1920px monitor left 400px of dead margin on each side - and dead
    margin is the thing that reads as "web page" no matter how the contents are
    styled.

    The sidebar runs floor to ceiling and the top bar sits INSIDE the content
    column to its right. A top bar spanning the full width above a sidebar reads
    as an intranet.
--}}
<body class="h-full overflow-hidden bg-background">
    {{-- 3px of brand across the very top of the viewport. Enough to read as
         branded on every console screen; small enough that it never competes
         with content the way a saturated colour bar would. --}}
    <div class="brand-rail" aria-hidden="true"></div>

    <div
        x-data="{
            collapsed: localStorage.getItem('gfms-sidebar') === '1',
            mobileNav: false,
        }"
        class="flex h-[calc(100vh-3px)] w-full overflow-hidden"
    >
        {{-- Desktop sidebar. Hidden below 1024px, where it becomes the sheet. --}}
        <div class="hidden lg:flex">
            <x-app-sidebar />
        </div>

        {{-- Off-canvas sheet + scrim for narrow viewports. --}}
        <div x-show="mobileNav" x-cloak class="fixed inset-0 z-50 lg:hidden" role="dialog" aria-modal="true">
            <div x-show="mobileNav"
                 x-transition:enter="transition-opacity ease-out duration-200"
                 x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                 x-transition:leave="transition-opacity ease-in duration-150"
                 x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                 @click="mobileNav = false"
                 class="absolute inset-0 bg-foreground/40"></div>

            <div x-show="mobileNav"
                 x-transition:enter="transition ease-out duration-260"
                 x-transition:enter-start="-translate-x-full" x-transition:enter-end="translate-x-0"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="translate-x-0" x-transition:leave-end="-translate-x-full"
                 @keydown.escape.window="mobileNav = false"
                 x-trap.noscroll="mobileNav"
                 class="absolute inset-y-0 left-0 flex">
                {{-- The sheet always shows labels, whatever the desktop rail is
                     set to: a 56px icon rail on a phone is unusable. --}}
                <div x-data="{ collapsed: false }" class="flex">
                    <x-app-sidebar />
                </div>
            </div>
        </div>

        {{-- Content column. min-w-0 is load-bearing: without it a wide table
             forces the flex item past the viewport instead of scrolling inside
             its own container. --}}
        <div class="flex min-w-0 flex-1 flex-col overflow-hidden">
            <x-app-topbar />

            {{-- THE ONLY SCROLL CONTAINER. --}}
            <main class="scroll-slim min-h-0 flex-1 overflow-y-auto">
                <div class="px-4 py-6 sm:px-6 lg:px-8">
                    {{-- Flash messages. Both say what actually happened, never
                         "Operation completed". --}}
                    @if (session('success'))
                        <div class="mb-5 rounded-[var(--radius-md)] bg-success-bg px-4 py-3 text-[15px] text-foreground" role="status">
                            {{ session('success') }}
                        </div>
                    @endif

                    @if (session('error'))
                        <div class="mb-5 rounded-[var(--radius-md)] bg-destructive-bg px-4 py-3 text-[15px] text-foreground" role="alert">
                            {{ session('error') }}
                        </div>
                    @endif

                    {{ $slot }}
                </div>
            </main>
        </div>

        <x-command-palette />
    </div>

    {{-- Livewire auto-injects its scripts (and Alpine, which it bundles), so
         no @livewireScripts directive is needed here. --}}
</body>
</html>
