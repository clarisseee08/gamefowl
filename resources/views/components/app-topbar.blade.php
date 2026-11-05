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
        'broodcocks' => 'Gamefowl',
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
        'pedigree' => null,  // the page has its own trail through the bird
        'schedule' => 'Schedule',
    ];

    $sectionLabel = $sectionLabels[$section] ?? Str::headline($section);
    $sectionRoute = Route::has($section.'.index') ? route($section.'.index') : null;
    $crumb = $actionLabels[$action] ?? null;

    $user = auth()->user();
    $firstName = Str::before(trim((string) $user?->full_name), ' ') ?: $user?->full_name;
    $photo = $user?->hasProfilePhoto() ? $user->profilePhotoUrl() : null;
@endphp

{{--
    THE APP HEADER (EMS, Part B §6.1), inside the content column. On the
    left: the sidebar toggle, then the search. On the right: the clock, the
    theme menu and the account menu. EMS's organisation chip, messages and
    notifications are left out on purpose: GFMS is one farm and has neither.

    52px from lg, 56px below it, where every control is a 44px target.
--}}
<header class="sticky top-0 z-30 flex h-14 w-full shrink-0 items-center justify-between gap-3 border-b border-border bg-background px-3 sm:px-5 lg:h-[52px]"
        x-on:keydown.ctrl.b.window.prevent="toggleSidebar()"
        x-on:keydown.meta.b.window.prevent="toggleSidebar()">
    <div class="flex min-w-0 flex-1 items-center gap-2 sm:gap-3">
        {{-- One toggle for every width: from lg it collapses the sidebar to
             its rail (Ctrl+B does the same), below lg it opens the sidebar as
             a sheet - the only way to the navigation on a phone. --}}
        <button type="button" x-on:click="toggleSidebar()"
                class="-ml-1 inline-flex size-11 shrink-0 items-center justify-center rounded-lg border border-border bg-card text-foreground/80 shadow-xs outline-none transition-colors hover:bg-muted hover:text-primary focus-visible:ring-[3px] focus-visible:ring-ring/50 lg:size-8"
                title="Toggle sidebar (Ctrl+B)">
            <x-ui.icon name="panel-left" class="size-4" />
            <span class="sr-only">Toggle sidebar</span>
        </button>

        <span class="hidden h-5 w-px shrink-0 bg-border sm:block" aria-hidden="true"></span>

        {{-- On a sub-page the trail is the way back to its list, so it stays;
             on a list it would only repeat the page title. --}}
        @if ($sectionRoute && $crumb)
            <nav class="d-breadcrumbs hidden min-w-0 shrink-0 py-0 text-xs md:block" aria-label="Breadcrumb">
                <ul>
                    <li><a href="{{ $sectionRoute }}" wire:navigate class="truncate text-muted-foreground hover:text-foreground">{{ $sectionLabel }}</a></li>
                    <li><span class="truncate font-medium text-foreground" aria-current="page">{{ $crumb }}</span></li>
                </ul>
            </nav>
        @endif

        {{-- The search opens the command palette; Ctrl+K is the real entry
             point and this is the affordance that tells you it exists. --}}
        <button type="button" x-on:click="$dispatch('open-command-palette')"
                class="hidden h-11 w-full min-w-0 max-w-md items-center gap-2 rounded-lg border border-border bg-card px-3 text-left text-sm text-muted-foreground shadow-xs outline-none transition-colors hover:bg-muted focus-visible:ring-[3px] focus-visible:ring-ring/50 sm:flex lg:h-8 lg:px-2.5 lg:text-xs"
                aria-label="Search (Ctrl+K)">
            <x-ui.icon name="search" class="size-4 shrink-0 lg:size-3.5" />
            <span class="min-w-0 flex-1 truncate">Search gamefowl, matings or pages</span>
            <kbd class="d-kbd d-kbd-sm datum shrink-0 text-muted-foreground">
                {{ str_contains(strtolower(request()->userAgent() ?? ''), 'mac') ? '⌘K' : 'Ctrl K' }}
            </kbd>
        </button>
    </div>

    <div class="flex shrink-0 items-center gap-1 sm:gap-2.5">
        <button type="button" x-on:click="$dispatch('open-command-palette')"
                class="inline-flex size-11 items-center justify-center rounded-lg text-muted-foreground hover:bg-muted hover:text-foreground sm:hidden"
                aria-label="Search">
            <x-ui.icon name="search" class="size-5" />
        </button>

        {{-- The farm's clock: the time and today's date, kept current on the
             page. Rendered by the server first so it never flashes empty. --}}
        <div class="hidden flex-col items-end leading-tight lg:flex"
             x-data="{ now: new Date() }"
             x-init="setInterval(() => now = new Date(), 15000)">
            <time class="datum text-sm font-semibold text-foreground"
                  x-text="now.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' })">{{ now()->format('g:i A') }}</time>
            <span class="datum text-[11px] text-muted-foreground"
                  x-text="now.toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' })">{{ now()->format('j M Y') }}</span>
        </div>

        <span class="hidden h-6 w-px bg-border lg:block" aria-hidden="true"></span>

        <x-ui.theme-menu />

        @if ($user)
            {{-- The account menu: who is signed in, their profile, and the way
                 out. Sign out is last, as in EMS. --}}
            <x-ui.dropdown-menu align="end" label="Account" trigger-variant="bare"
                                panel-class="w-80 max-w-[calc(100vw-1.5rem)] rounded-2xl p-0">
                <x-slot:trigger class="flex h-11 items-center gap-2 px-1.5 hover:bg-muted lg:h-9 lg:px-2" aria-label="Account menu for {{ $user->full_name }}">
                    <x-ui.avatar :name="$user->full_name" :src="$photo" size="header" tone="primary" />
                    <span class="hidden min-w-0 flex-col items-start text-left leading-tight lg:flex">
                        <span class="max-w-40 truncate text-xs font-medium text-foreground">{{ $user->full_name }}</span>
                        <span class="max-w-40 truncate text-[11px] text-muted-foreground">{{ $user->email }}</span>
                    </span>
                </x-slot:trigger>

                <div class="flex flex-col items-center gap-1 border-b border-border px-4 pt-6 pb-4 text-center">
                    <x-ui.avatar :name="$user->full_name" :src="$photo" size="hero" tone="primary" />
                    <p class="mt-2 text-[15px] font-semibold text-foreground">Hi, {{ $firstName }}!</p>
                    <p class="max-w-full truncate text-xs text-muted-foreground">{{ $user->email }}</p>
                    <x-ui.status-badge class="mt-1">{{ $user->role->label() }}</x-ui.status-badge>
                </div>

                <div class="p-1">
                    <x-ui.dropdown-menu.item icon="user" :href="route('profile.edit')" wire:navigate>My profile</x-ui.dropdown-menu.item>
                    <x-ui.dropdown-menu.separator />
                    <x-ui.dropdown-menu.item icon="log-out" x-on:click="document.getElementById('sign-out-form').submit()">Sign out</x-ui.dropdown-menu.item>
                </div>
            </x-ui.dropdown-menu>

            <form method="POST" action="{{ route('logout') }}" id="sign-out-form" class="hidden">
                @csrf
            </form>
        @endif
    </div>
</header>
