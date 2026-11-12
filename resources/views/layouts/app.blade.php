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
    <div
        x-data="{
            collapsed: localStorage.getItem('gfms-sidebar') === '1',
            mobileNav: false,
            toggleSidebar() {
                if (! matchMedia('(min-width: 1024px)').matches) {
                    this.mobileNav = true;
                    return;
                }
                this.collapsed = ! this.collapsed;
                localStorage.setItem('gfms-sidebar', this.collapsed ? '1' : '0');
            },
        }"
        class="flex h-full w-full overflow-hidden"
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

            {{-- transition-transform at --dur-slow, not the bare `transition`
                 shorthand at 260ms. 260 was never on the scale - the tokens are
                 100 / 150 / 200 / 300 - and 300ms is the step whose own comment
                 reads "modals, drawers". The only property changing here is the
                 translate; the shorthand was also animating colour, shadow and
                 filter on a panel that changes none of them.

                 The exit stays faster than the entrance, which is the one piece
                 of asymmetry that makes a drawer feel answered rather than
                 sluggish. --}}
            <div x-show="mobileNav"
                 x-transition:enter="transition-transform ease-out duration-300"
                 x-transition:enter-start="-translate-x-full" x-transition:enter-end="translate-x-0"
                 x-transition:leave="transition-transform ease-in duration-150"
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
                {{-- data-page-content: what app.js hides, the moment a link is
                     pressed, while the next page's skeleton stands in for it. --}}
                <div data-page-content class="px-4 py-6 sm:px-6 lg:px-8">
                    {{ $slot }}
                </div>
            </main>
        </div>

        {{-- The shape of each kind of page, for the skeleton above. Templates
             render nothing until app.js copies one into <main>. --}}
        @foreach (['list', 'detail', 'form', 'dashboard'] as $skeletonKind)
            <template data-page-skeleton-template="{{ $skeletonKind }}">
                <x-ui.page-skeleton :kind="$skeletonKind" />
            </template>
        @endforeach

        <x-command-palette />

        {{-- The one confirm dialog on the page, opened by an `ems-confirm` event. --}}
        <x-ui.confirm-dialog />
    </div>

    {{--
        TOASTS. Every save, delete and upload says what happened - top right,
        four seconds, with a close button (EMS, Part B §8.1).

        Two sources feed it: the session flash a redirect carries (written
        into the page, so it is in the HTML a test reads), and a Livewire
        `toast` event for saves that do not redirect:
        $this->dispatch('toast', message: 'Saved.', tone: 'ok').
    --}}
    <x-ui.toaster />

    {{-- Livewire auto-injects its scripts (and Alpine, which it bundles), so
         no @livewireScripts directive is needed here. --}}
</body>
</html>
