<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Dashboard' }} &middot; {{ config('gfms.farm.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full bg-gray-50">
    @php
        $user = auth()->user();
        // Built server-side from the user's role so the navigation never
        // advertises a screen the Policy would reject.
        $nav = collect([
            ['label' => 'Dashboard',   'route' => 'dashboard',    'internal' => true],
            ['label' => 'Broodcocks',  'route' => 'broodcocks.index', 'internal' => false],
            ['label' => 'Health',      'route' => 'health.index',     'internal' => true],
            ['label' => 'Breeding',    'route' => 'breeding.index',   'internal' => true],
            ['label' => 'Performance', 'route' => 'performance.index','internal' => true],
            ['label' => 'Mortality',   'route' => 'mortality.index',  'internal' => true],
            ['label' => 'Pens',        'route' => 'pens.index',       'internal' => true],
            ['label' => 'Reports',     'route' => 'reports.index',    'internal' => true],
            ['label' => 'Users',       'route' => 'users.index',      'owner' => true],
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
    @endphp

    <div x-data="{ mobileOpen: false }" class="min-h-full">
        <nav class="bg-brand-700 text-white">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex h-16 items-center justify-between">
                    <div class="flex items-center gap-8">
                        <a href="{{ route('dashboard') }}" class="flex items-center gap-2 font-bold">
                            <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-white/15 text-sm">GF</span>
                            <span class="hidden sm:inline">{{ config('gfms.farm.name') }}</span>
                        </a>

                        <div class="hidden items-center gap-1 lg:flex">
                            @foreach ($nav as $item)
                                <a href="{{ route($item['route']) }}"
                                   @class([
                                       'rounded-lg px-3 py-2 text-sm font-medium transition',
                                       'bg-brand-800 text-white' => request()->routeIs(Str::before($item['route'], '.').'.*'),
                                       'text-brand-100 hover:bg-brand-600 hover:text-white' => ! request()->routeIs(Str::before($item['route'], '.').'.*'),
                                   ])>
                                    {{ $item['label'] }}
                                </a>
                            @endforeach
                        </div>
                    </div>

                    <div class="flex items-center gap-3">
                        <div class="hidden text-right sm:block">
                            <p class="text-sm font-medium leading-tight">{{ $user?->full_name }}</p>
                            <p class="text-xs text-brand-200">{{ $user?->role->label() }}</p>
                        </div>

                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="rounded-lg px-3 py-2 text-sm font-medium text-brand-100 hover:bg-brand-600 hover:text-white">
                                Sign out
                            </button>
                        </form>

                        <button type="button"
                                @click="mobileOpen = ! mobileOpen"
                                class="rounded-lg p-2 text-brand-100 hover:bg-brand-600 lg:hidden"
                                aria-label="Toggle navigation">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>

            <div x-show="mobileOpen" x-cloak class="border-t border-brand-600 lg:hidden">
                <div class="space-y-1 px-4 py-3">
                    @foreach ($nav as $item)
                        <a href="{{ route($item['route']) }}"
                           class="block rounded-lg px-3 py-2.5 text-base font-medium text-brand-100 hover:bg-brand-600 hover:text-white">
                            {{ $item['label'] }}
                        </a>
                    @endforeach
                </div>
            </div>
        </nav>

        <main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
            {{-- Flash messages. Success is green, problems are red - and both
                 say what actually happened, not "Operation completed". --}}
            @if (session('success'))
                <div class="mb-6 rounded-lg bg-brand-50 p-4 text-sm text-brand-800 ring-1 ring-brand-200" role="status">
                    {{ session('success') }}
                </div>
            @endif

            @if (session('error'))
                <div class="mb-6 rounded-lg bg-rose-50 p-4 text-sm text-rose-800 ring-1 ring-rose-200" role="alert">
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
