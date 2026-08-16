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
            ['label' => 'Broodcocks',  'route' => 'broodcocks.index',  'internal' => true,
             'icon' => 'M15.75 8.25a3.75 3.75 0 1 0-7.5 0 3.75 3.75 0 0 0 7.5 0ZM4.5 20.25a7.5 7.5 0 0 1 15 0'],
            ['label' => 'Catalogue',   'route' => 'catalog.index',     'internal' => false,
             'icon' => 'M2.25 15.75l5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909M18 10.5h.008v.008H18V10.5Zm2.25 6.75V6.75A2.25 2.25 0 0 0 18 4.5H6a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 6 19.5h12a2.25 2.25 0 0 0 2.25-2.25Z'],
            ['label' => 'Health',      'route' => 'health.index',      'internal' => true,
             'icon' => 'M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z'],
            ['label' => 'Breeding',    'route' => 'breeding.index',    'internal' => true,
             'icon' => 'M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18Zm0-13.5a4.5 4.5 0 1 0 0 9 4.5 4.5 0 0 0 0-9Z'],
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
            ['label' => 'Pens',        'route' => 'pens.index',        'internal' => true,
             'icon' => 'M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15'],
            ['label' => 'Users',       'route' => 'users.index',       'owner' => true,
             'icon' => 'M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z'],
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
    class="flex h-full shrink-0 flex-col bg-brand-deep transition-[width] duration-200"
    :class="collapsed ? 'w-14' : 'w-60'"
    aria-label="Main navigation"
>
    {{-- Pinned top: workspace identity. --}}
    {{-- Collapsed rail shows the mark alone; expanded shows the lockup. That is
         the lockup's defined minimum in practice: below the rail width the
         wordmark drops and the mark stands by itself. --}}
    <div class="flex h-14 shrink-0 items-center gap-2 px-3"
         style="border-bottom: 1px solid var(--color-brand-deeper)">
        <button type="button"
                @click="collapsed = ! collapsed; localStorage.setItem('gfms-sidebar', collapsed ? '1' : '0')"
                class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-brand-foreground ring-1 ring-brand-deeper hover:opacity-90"
                :aria-label="collapsed ? 'Expand sidebar' : 'Collapse sidebar'"
                :aria-expanded="collapsed ? 'false' : 'true'">
            {{-- The badge is dark green and gold; the rail is dark red. Placed
                 straight onto it, a 30px logo reads as a dark blob. It gets its
                 own light ground, which is what a coloured badge normally needs
                 on a dark surface - and it keeps the artwork untouched. --}}
            <x-brand-mark :size="30" class="rounded-full" />
        </button>
        <div x-show="! collapsed" x-cloak class="min-w-0 flex-1">
            <p class="truncate text-[14px] font-semibold leading-tight text-brand-foreground">
                {{ config('gfms.system.short') }}
            </p>
            <p class="truncate text-[11px] leading-tight text-brand-muted-fg">
                {{ config('gfms.farm.name') }}
            </p>
        </div>
    </div>

    {{-- The nav scrolls between the two pinned blocks, so a long list never
         pushes the account menu off the bottom of the viewport. --}}
    <nav class="min-h-0 flex-1 overflow-y-auto px-2 py-2">
        @foreach ($visible as $groupName => $items)
            <p class="nav-section" x-show="! collapsed" x-cloak>{{ $groupName }}</p>
            @if (! $loop->first)
                <div class="my-2" style="border-top: 1px solid var(--color-brand-deeper)" x-show="collapsed" x-cloak></div>
            @endif

            @foreach ($items as $item)
                <a href="{{ route($item['route']) }}"
                   @class(['nav-item mb-0.5', 'nav-item-active' => $isActive($item['route'])])
                   :class="collapsed ? 'justify-center px-0' : ''"
                   title="{{ $item['label'] }}"
                   @if ($isActive($item['route'])) aria-current="page" @endif>
                    <svg class="h-[18px] w-[18px] shrink-0" fill="none" stroke="currentColor"
                         stroke-width="1.6" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="{{ $item['icon'] }}"/>
                    </svg>
                    <span x-show="! collapsed" x-cloak class="truncate">{{ $item['label'] }}</span>
                </a>
            @endforeach
        @endforeach
    </nav>

    {{-- Pinned bottom: who you are and how to leave. --}}
    <div class="shrink-0 p-2" style="border-top: 1px solid var(--color-brand-deeper)">
        {{-- The whole block is the link to your own profile: a name you can
             click is a more obvious affordance than a separate icon beside it. --}}
        <a href="{{ route('profile.edit') }}"
           @class(['flex items-center gap-2.5 rounded-[var(--radius-sm)] px-2 py-1.5 transition-colors hover:bg-brand-deeper',
                   'bg-brand-deeper' => request()->routeIs('profile.*')])>
            @if ($user?->hasProfilePhoto() && $user->profilePhotoUrl())
                <img src="{{ $user->profilePhotoUrl() }}" alt=""
                     class="h-8 w-8 shrink-0 rounded-full object-cover"
                     style="box-shadow: inset 0 0 0 1px var(--color-brand-deeper)">
            @else
                <x-icon.user class="h-8 w-8 shrink-0" tone="onDark" />
            @endif
            <div x-show="! collapsed" x-cloak class="min-w-0 flex-1">
                <p class="truncate text-[13px] font-medium leading-tight text-brand-foreground">{{ $user?->full_name }}</p>
                <p class="truncate text-[11px] leading-tight text-brand-muted-fg">{{ $user?->role->label() }}</p>
            </div>
        </a>

        <form method="POST" action="{{ route('logout') }}" x-show="! collapsed" x-cloak class="mt-1">
                @csrf
                <button type="submit"
                        class="inline-flex h-9 w-9 items-center justify-center rounded-[var(--radius-sm)] text-brand-muted-fg hover:bg-brand-deeper hover:text-brand-foreground"
                        aria-label="Sign out" title="Sign out">
                    <svg class="h-[18px] w-[18px]" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15M12 9l-3 3m0 0 3 3m-3-3h12.75"/>
                    </svg>
            </button>
        </form>
    </div>
</aside>
