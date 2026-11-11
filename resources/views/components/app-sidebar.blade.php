@php
    use Illuminate\Support\Str;

    $user = auth()->user();

    /*
     * Grouped navigation, built server-side from the user's role so the sidebar
     * never advertises a screen the Policy would reject. Groups are the
     * §3.5 structure: what you MANAGE, what you ANALYSE, what you ADMINISTER.
     *
     * Icons are inline path data rather than an icon font or a package: one
     * fewer dependency, and they inherit currentColor so the active state needs
     * no second rule.
     */
    $groups = [
        'Manage' => [
            ['label' => 'Dashboard',   'route' => 'dashboard',         'internal' => true,
             'icon' => 'M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25V6ZM13.5 6A2.25 2.25 0 0 1 15.75 3.75H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25a2.25 2.25 0 0 1-2.25-2.25V6ZM3.75 15.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18v-2.25ZM13.5 15.75a2.25 2.25 0 0 1 2.25-2.25H18a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 18 20.25h-2.25A2.25 2.25 0 0 1 13.5 18v-2.25Z'],
            ['label' => 'Gamefowl',    'route' => 'broodcocks.index',  'internal' => true,
             'icon' => 'M7.5 20.5v-5.2a5.2 5.2 0 0 1 5.2-5.2M9.4 8.6c.4-1.3 1.5-1.4 2-.3.4-1.3 1.6-1.4 2.1-.3.4-1.1 1.5-1.1 1.9.1M12.7 10.1a4.6 4.6 0 0 1 4.2 2.6l3.4 1-3.4 1.2M14.6 16.4a1.5 1.5 0 0 1-1.4 1.9M14.9 12.2h.01'],
            ['label' => 'Catalogue',   'route' => 'catalog.index',     'internal' => false,
             'icon' => 'M2.25 15.75l5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909M18 10.5h.008v.008H18V10.5Zm2.25 6.75V6.75A2.25 2.25 0 0 0 18 4.5H6a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 6 19.5h12a2.25 2.25 0 0 0 2.25-2.25Z'],
            ['label' => 'Health',      'route' => 'health.index',      'internal' => true,
             'icon' => 'M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z'],
            ['label' => 'Breeding',    'route' => 'breeding.index',    'internal' => true,
             'icon' => 'M8.5 8.5a4.5 4.5 0 1 0 0 9 4.5 4.5 0 0 0 0-9ZM15.5 8.5a4.5 4.5 0 1 0 0 9 4.5 4.5 0 0 0 0-9Z'],
            ['label' => 'Performance', 'route' => 'performance.index', 'internal' => true,
             'icon' => 'M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z'],
            // Not a plus sign: that reads as "add" in a nav rail, which is the
            // last thing this particular section should invite. A trend line.
            ['label' => 'Mortality',   'route' => 'mortality.index',   'internal' => true,
             'icon' => 'M2.25 6 9 12.75l4.286-4.286a11.948 11.948 0 0 1 4.306 6.43l.776 2.898m0 0 3.182-5.511m-3.182 5.51-5.511-3.181'],
        ],
        'Analyse' => [
            ['label' => 'Reports',     'route' => 'reports.index',     'internal' => true,
             'icon' => 'M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z'],
        ],
        'Admin' => [
            ['label' => 'Users',       'route' => 'users.index',       'owner' => true,
             'icon' => 'M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z'],
            // A building, not a cog. What is behind this is the farm - its
            // name, its number, where to find it - and not the application's
            // preferences, which is what a cog promises and would send the
            // owner here looking for a theme switch.
            // An archive box: archived records are kept, not thrown away.
            ['label' => 'Archive',      'route' => 'archive.index',    'owner' => true,
             'icon' => 'm20.25 7.5-.625 10.632a2.25 2.25 0 0 1-2.247 2.118H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z'],
            ['label' => 'Farm Profile', 'route' => 'settings.edit',    'owner' => true,
             'icon' => 'M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21m-3.75 3.75h.008v.008h-.008v-.008Zm0 3h.008v.008h-.008v-.008Zm0 3h.008v.008h-.008v-.008Z'],
        ],
    ];

    $visible = collect($groups)->map(fn (array $items) => collect($items)->filter(function (array $item) use ($user) {
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
    }))->filter(fn ($items) => $items->isNotEmpty());

    $isActive = fn (string $route) => request()->routeIs(Str::before($route, '.').'.*')
        || request()->routeIs($route);
@endphp

{{--
    The console sidebar. Full viewport height, pinned left, its own scroll.

    The top bar deliberately does NOT span above this - it sits inside the
    content column to the right. A full-width bar over a sidebar reads as an
    intranet; the sidebar running floor-to-ceiling is what reads as an app.

    Surface is --muted, one step back from the --card content region. That
    contrast step is what makes the content read as foreground rather than as
    another panel.
--}}
<aside
    class="flex h-full shrink-0 flex-col border-r border-border bg-background transition-[width] duration-200 ease-linear"
    :class="collapsed ? 'w-14' : 'w-60 lg:w-[17.5rem]'"
    aria-label="Main navigation"
>
    {{-- Pinned top: workspace identity. --}}
    {{-- Collapsed rail shows the mark alone; expanded shows the lockup. That is
         the lockup's defined minimum in practice: below the rail width the
         wordmark drops and the mark stands by itself. --}}
    {{--
        Brand block.

        The logo and wordmark are a LINK HOME, not the collapse control. Those
        were the same button before, which meant the most obvious thing on the
        rail did the least expected thing - clicking a logo goes home in every
        product anyone has used.

        Collapse is the toggle at the left of the header (and Ctrl+B), where
        EMS puts it; who is signed in, and Sign out, are the header's account
        menu.
    --}}
    <div class="flex h-14 shrink-0 items-center gap-2 border-b border-border px-3 lg:h-[52px]"
         style="border-bottom: 1px solid var(--color-border)"
         :class="collapsed ? 'justify-center px-2' : ''">
        <a href="{{ route('dashboard') }}" wire:navigate.hover
           class="flex min-w-0 flex-1 items-center gap-2.5 rounded-[var(--radius-sm)] p-1 hover:bg-background"
           :class="collapsed ? 'flex-none justify-center' : ''"
           title="{{ config('gfms.system.name') }}">
            {{-- The badge is dark green and gold; the rail is dark red. Placed
                 straight onto it, a 30px logo reads as a dark blob. It gets its
                 own light ground, which is what a coloured badge normally needs
                 on a dark surface - and it keeps the artwork untouched. --}}
            <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-card ring-1 ring-border">
                <x-brand-mark :size="30" class="rounded-full" />
            </span>

            <span x-show="! collapsed" x-cloak class="min-w-0">
                <span class="block truncate text-[15px] font-semibold leading-tight text-foreground lg:text-[13px]">
                    {{ config('gfms.farm.name') }}
                </span>
            </span>
            <span class="sr-only">Go to dashboard</span>
        </a>

    </div>


    {{-- The nav scrolls between the two pinned blocks, so a long list never
         pushes the account menu off the bottom of the viewport. --}}
    {{-- d-menu with its active pair pointed back at the card ground and ink:
         daisyUI's default active fill is the neutral (solid ink), which on a
         --muted rail reads as a selected button rather than "you are here". --}}
    <nav class="scroll-slim min-h-0 flex-1 overflow-y-auto px-2 pt-1 pb-2 lg:px-3">
        <ul class="flex w-full flex-col gap-1">
            @foreach ($visible as $groupName => $items)
                <li class="mt-4 mb-1 flex h-5 items-center border-b border-muted-foreground/10 px-4 pb-2 text-[11px] font-medium tracking-widest text-muted-foreground uppercase" x-show="! collapsed" x-cloak>{{ $groupName }}</li>
                @if (! $loop->first)
                    <li class="mx-0 my-2 h-px bg-border opacity-100" x-show="collapsed" x-cloak aria-hidden="true"></li>
                @endif

                @foreach ($items as $item)
                    <li>
                        {{-- .hover: the next screen starts loading after 60ms over the
                             link, so on the slow free server it is often already here
                             when the click lands. --}}
                        <a href="{{ route($item['route']) }}" wire:navigate.hover
                           @class([
                               'relative flex min-h-11 w-full items-center gap-3 overflow-hidden rounded-lg border px-4 text-sm font-medium outline-none transition-[transform,box-shadow,border-color,background-color] duration-200 ease-out focus-visible:ring-2 focus-visible:ring-ring/50 lg:h-8 lg:min-h-0 lg:text-[12.5px]',
                               'border-primary/20 bg-linear-to-r from-primary to-primary/80 text-primary-foreground shadow-[0_4px_8px_rgba(0,0,0,0.15),0_2px_4px_rgba(0,0,0,0.1),inset_0_1px_0_rgba(255,255,255,0.2)] hover:shadow-[0_6px_12px_rgba(0,0,0,0.2),0_3px_6px_rgba(0,0,0,0.15),inset_0_1px_0_rgba(255,255,255,0.3)]' => $isActive($item['route']),
                               'border-transparent text-muted-foreground hover:bg-muted hover:text-foreground' => ! $isActive($item['route']),
                           ])
                           :class="collapsed ? 'justify-center px-0' : ''"
                           title="{{ $item['label'] }}"
                           @if ($isActive($item['route'])) aria-current="page" @endif>
                            <svg class="size-[18px] shrink-0 lg:size-4" fill="none" stroke="currentColor"
                                 stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="{{ $item['icon'] }}"/>
                            </svg>
                            <span x-show="! collapsed" x-cloak class="truncate">{{ $item['label'] }}</span>
                        </a>
                    </li>
                @endforeach
            @endforeach
        </ul>
    </nav>
</aside>
