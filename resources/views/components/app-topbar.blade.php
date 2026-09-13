@php
    use Illuminate\Support\Str;

    /*
     * Breadcrumbs derived from the route name rather than hand-written per view.
     * Hand-written trails rot: a route gets renamed and one screen silently
     * lies about where you are.
     *
     * `broodcocks.show` -> Broodcocks > (record). The trailing segment is only
     * shown when it is not an index, because "Broodcocks > Index" is noise.
     */
    $routeName = (string) request()->route()?->getName();
    $segments = explode('.', $routeName);
    $section = $segments[0] ?? '';
    $action = $segments[1] ?? 'index';

    $sectionLabels = [
        'dashboard' => 'Dashboard',
        'broodcocks' => 'Broodcocks',
        'catalog' => 'Catalogue',
        'health' => 'Health',
        'breeding' => 'Breeding',
        'performance' => 'Performance',
        'mortality' => 'Mortality',
        'reports' => 'Reports',
        'users' => 'Users',
        'design' => 'Design System',
    ];

    $actionLabels = [
        'create' => 'New',
        'edit' => 'Edit',
        'show' => null,      // the page header already names the record
        'pedigree' => 'Family Tree',
        'schedule' => 'Schedule',
    ];

    $sectionLabel = $sectionLabels[$section] ?? Str::headline($section);
    $sectionRoute = Route::has($section.'.index') ? route($section.'.index') : null;
    $crumb = $actionLabels[$action] ?? null;
@endphp

{{--
    Top bar. 56px, inside the content column - NOT spanning above the sidebar.
    Sticky, so the breadcrumb stays put while the region beneath it scrolls.
--}}
<header class="sticky top-0 z-30 flex h-14 shrink-0 items-center gap-3 border-b border-border bg-card px-4 sm:px-6">
    {{-- Off-canvas trigger. Below 1024px the sidebar is a sheet, so this is the
         only way back to navigation. --}}
    <button type="button"
            @click="mobileNav = true"
            class="-ml-1 inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-[var(--radius-sm)] text-muted-foreground hover:bg-muted lg:hidden"
            aria-label="Open navigation">
        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5M3.75 17.25h16.5"/>
        </svg>
    </button>

    {{--
        Breadcrumbs only where they earn their place.

        On an index screen the trail was a single crumb repeating the page's own
        h1 one line below it - "Dashboard" above "Good morning, Salvador", or
        "Broodcocks" above "Broodcocks". A breadcrumb that names where you
        already obviously are is noise, and it took a whole row of the bar.

        On a SUB-page it is the way back, so it stays there.
    --}}
    <nav class="flex min-w-0 flex-1 items-center gap-2 text-[13px]" aria-label="Breadcrumb">
        @if ($sectionRoute && $crumb)
            <a href="{{ $sectionRoute }}" class="truncate text-muted-foreground hover:text-foreground">{{ $sectionLabel }}</a>
            <span class="text-muted-foreground" aria-hidden="true">/</span>
            <span class="truncate font-medium text-foreground" aria-current="page">{{ $crumb }}</span>
        @endif
    </nav>

    {{-- Command palette trigger. The keyboard shortcut is the real entry point;
         this is the affordance that tells you it exists. --}}
    <button type="button"
            @click="$dispatch('open-command-palette')"
            class="hidden h-9 items-center gap-2 rounded-[var(--radius-sm)] border border-border bg-background px-2.5 text-[13px] text-muted-foreground hover:bg-muted sm:inline-flex"
            aria-label="Open command palette">
        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/>
        </svg>
        <span>Search</span>
        <kbd class="datum ml-4 rounded-[3px] border border-border bg-card px-1.5 py-0.5 text-[11px] text-muted-foreground">
            {{ str_contains(strtolower(request()->userAgent() ?? ''), 'mac') ? '⌘K' : 'Ctrl K' }}
        </kbd>
    </button>

    <button type="button"
            @click="$dispatch('open-command-palette')"
            class="inline-flex h-11 w-11 items-center justify-center rounded-[var(--radius-sm)] text-muted-foreground hover:bg-muted sm:hidden"
            aria-label="Search">
        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/>
        </svg>
    </button>
</header>
