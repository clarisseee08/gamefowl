<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Dashboard' }} &middot; {{ config('gfms.farm.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full">
    @php
        $user = auth()->user();
        // Built server-side from the user's role so the navigation never
        // advertises a screen the Policy would reject.
        $nav = collect([
            ['label' => 'Dashboard',   'route' => 'dashboard',         'internal' => true],
            ['label' => 'Catalogue',   'route' => 'catalog.index',     'internal' => false],
            ['label' => 'Broodcocks',  'route' => 'broodcocks.index',  'internal' => true],
            ['label' => 'Health',      'route' => 'health.index',      'internal' => true],
            ['label' => 'Breeding',    'route' => 'breeding.index',    'internal' => true],
            ['label' => 'Performance', 'route' => 'performance.index', 'internal' => true],
            ['label' => 'Mortality',   'route' => 'mortality.index',   'internal' => true],
            ['label' => 'Pens',        'route' => 'pens.index',        'internal' => true],
            ['label' => 'Reports',     'route' => 'reports.index',     'internal' => true],
            ['label' => 'Users',       'route' => 'users.index',       'owner' => true],
        ])->filter(function ($item) use ($user) {
            if (! Route::has($item['route'])) {
                return false;
            }
            if (($item['owner'] ?? false) && ! $user?->isOwner()) {
                return false;
            }
            if (($item['internal'] ?? false) && ! $user?->isInternal()) {
                return false;
            }

            return true;
        });

        $isActive = fn (string $route) => request()->routeIs(Str::before($route, '.') . '.*')
            || request()->routeIs($route);
    @endphp

    <div x-data="{ mobileOpen: false }" class="min-h-full">
        {{-- Global bar. Frosted rather than solid: depth comes from the content
             scrolling beneath it, never from a shadow. --}}
        <header class="frosted sticky top-0 z-40 border-b border-border">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex h-12 items-center justify-between gap-6">
                    {{-- inline-flex + min-h-11 so the wordmark is a full 44px tap
                         target, not a 26px text run. It is the home link on a phone. --}}
                    <a href="{{ route('dashboard') }}"
                       class="inline-flex min-h-11 shrink-0 items-center text-[17px] font-semibold tracking-[-0.01em] text-foreground">
                        {{ config('gfms.farm.name') }}
                    </a>

                    {{-- Desktop nav, set below body size on purpose: the bar is a
                         standing index you navigate by position, not something you
                         read, so it should not compete with the record on the page. --}}
                    <nav class="hidden flex-1 items-center gap-1 lg:flex" aria-label="Main">
                        @foreach ($nav as $item)
                            <a href="{{ route($item['route']) }}"
                               @class([
                                   'rounded-full px-3 py-1.5 text-[12px] transition-colors',
                                   'bg-primary-50 font-medium text-primary' => $isActive($item['route']),
                                   'text-muted-foreground hover:text-foreground' => ! $isActive($item['route']),
                               ])
                               @if ($isActive($item['route'])) aria-current="page" @endif>
                                {{ $item['label'] }}
                            </a>
                        @endforeach
                    </nav>

                    <div class="flex items-center gap-3">
                        <div class="hidden text-right sm:block">
                            <p class="text-[12px] font-medium leading-tight text-foreground">{{ $user?->full_name }}</p>
                            <p class="text-[12px] leading-tight text-muted-foreground">{{ $user?->role->label() }}</p>
                        </div>

                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="inline-flex min-h-11 items-center px-1 text-[12px] text-primary hover:underline">
                                Sign out
                            </button>
                        </form>

                        <button type="button"
                                @click="mobileOpen = ! mobileOpen"
                                :aria-expanded="mobileOpen ? 'true' : 'false'"
                                class="-mr-1 inline-flex h-11 w-11 items-center justify-center rounded-full text-muted-foreground hover:bg-muted lg:hidden"
                                aria-label="Toggle navigation">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.75"
                                 viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                      x-show="! mobileOpen" d="M3.75 6.75h16.5M3.75 12h16.5M3.75 17.25h16.5"/>
                                <path stroke-linecap="round" stroke-linejoin="round"
                                      x-show="mobileOpen" x-cloak d="M6 18 18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>

            {{-- Mobile nav. The one authored motion moment in the shell: a
                 height-and-opacity reveal on an exponential ease-out. --}}
            <div x-show="mobileOpen"
                 x-cloak
                 x-collapse.duration.220ms
                 class="border-t border-border lg:hidden">
                <nav class="space-y-0.5 px-4 py-2" aria-label="Main">
                    @foreach ($nav as $item)
                        <a href="{{ route($item['route']) }}"
                           @class([
                               'block rounded-[8px] px-3 py-2.5 text-[17px]',
                               'bg-primary-50 font-medium text-primary' => $isActive($item['route']),
                               'text-foreground hover:bg-muted' => ! $isActive($item['route']),
                           ])>
                            {{ $item['label'] }}
                        </a>
                    @endforeach
                </nav>
            </div>
        </header>

        <main class="mx-auto max-w-[1120px] px-6 py-14 sm:px-6 lg:px-8">
            {{-- Flash messages. Both say what actually happened, never
                 "Operation completed". --}}
            @if (session('success'))
                <div class="mb-6 rounded-[18px] bg-success-bg px-4 py-3 text-[17px] text-success" role="status">
                    {{ session('success') }}
                </div>
            @endif

            @if (session('error'))
                <div class="mb-6 rounded-[18px] bg-destructive-bg px-4 py-3 text-[17px] text-destructive" role="alert">
                    {{ session('error') }}
                </div>
            @endif

            {{ $slot }}
        </main>
    </div>

    {{-- Livewire auto-injects its scripts (and Alpine, which it bundles), so
         no @livewireScripts directive is needed here. --}}
</body>
</html>
