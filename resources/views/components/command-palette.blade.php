@php
    use Illuminate\Support\Str;

    $user = auth()->user();

    /*
     * Route targets are built server-side and filtered by role, so the palette
     * can never offer a jump the Policy would then reject. This list is the
     * static half; records come from CommandSearchController as you type.
     */
    $targets = collect([
        ['label' => 'Dashboard', 'hint' => 'Go to', 'route' => 'dashboard', 'internal' => true],
        ['label' => 'Gamefowl', 'hint' => 'Go to', 'route' => 'broodcocks.index', 'internal' => true],
        ['label' => 'Catalogue', 'hint' => 'Go to', 'route' => 'catalog.index'],
        ['label' => 'Health records', 'hint' => 'Go to', 'route' => 'health.index', 'internal' => true],
        ['label' => 'Vaccination schedule', 'hint' => 'Go to', 'route' => 'health.schedule', 'internal' => true],
        ['label' => 'Breeding records', 'hint' => 'Go to', 'route' => 'breeding.index', 'internal' => true],
        ['label' => 'Performance records', 'hint' => 'Go to', 'route' => 'performance.index', 'internal' => true],
        ['label' => 'Mortality register', 'hint' => 'Go to', 'route' => 'mortality.index', 'internal' => true],
        ['label' => 'Reports', 'hint' => 'Go to', 'route' => 'reports.index', 'internal' => true],
        ['label' => 'Users', 'hint' => 'Go to', 'route' => 'users.index', 'owner' => true],
        ['label' => 'Add a broodcock', 'hint' => 'Create', 'route' => 'broodcocks.create', 'internal' => true],
        ['label' => 'Add a health record', 'hint' => 'Create', 'route' => 'health.create', 'internal' => true],
        ['label' => 'Add a breeding record', 'hint' => 'Create', 'route' => 'breeding.create', 'internal' => true],
        ['label' => 'Add a performance record', 'hint' => 'Create', 'route' => 'performance.create', 'internal' => true],
    ])->filter(function (array $item) use ($user) {
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
    })->map(fn (array $i) => [
        'label' => $i['label'],
        'hint' => $i['hint'],
        'url' => route($i['route']),
    ])->values();
@endphp

{{--
    Command palette.

    The clearest single signal that this is a product rather than a set of
    pages, and the cheapest to add: no schema change, no new query on a normal
    page load - the route list is rendered once with the shell.

    Opens on Cmd/Ctrl+K anywhere, and on the topbar button via an event so the
    trigger does not need to know about this component's internals.
--}}
<div
    x-data="{
        open: false,
        query: '',
        active: 0,
        targets: @js($targets),
        searchUrl: @js(route('search')),
        records: [],
        searching: false,
        timer: null,
        request: null,
        get results() {
            const q = this.query.trim().toLowerCase();
            if (! q) return this.targets.slice(0, 8);
            const pages = this.targets.filter(t => t.label.toLowerCase().includes(q)).slice(0, 5);
            return [...pages, ...this.records];
        },
        show() {
            this.open = true;
            this.query = '';
            this.records = [];
            this.active = 0;
            this.$nextTick(() => this.$refs.input?.focus());
        },
        {{-- Records are asked for 200ms after typing stops, two characters
             or more; a newer query cancels the one still in flight, and an
             answer is used only if the box still says what was asked. --}}
        search() {
            this.active = 0;
            clearTimeout(this.timer);
            const q = this.query.trim();
            if (q.length < 2) { this.request?.abort(); this.records = []; this.searching = false; return; }
            this.searching = true;
            this.timer = setTimeout(async () => {
                this.request?.abort();
                this.request = new AbortController();
                try {
                    const response = await fetch(this.searchUrl + '?q=' + encodeURIComponent(q), { headers: { Accept: 'application/json' }, signal: this.request.signal });
                    const data = await response.json();
                    if (this.query.trim() === q) { this.records = data.results ?? []; this.searching = false; }
                } catch (error) {
                    if (error.name !== 'AbortError') { this.searching = false; }
                }
            }, 200);
        },
        move(delta) {
            const n = this.results.length;
            if (! n) return;
            this.active = (this.active + delta + n) % n;
        },
        go() {
            // Click the anchor rather than assigning window.location. The anchor
            // carries wire:navigate, so Enter and a mouse click take the same
            // client-side path instead of one of them reloading the document.
            //
            // $root, NOT $el. Alpine resolves $el to the element the expression
            // is running on, and this method is invoked from @keydown.enter on
            // the <input> - so $el is the input, and the search for results
            // inside it silently finds nothing. $root is the x-data element.
            //
            // No double quotes in this selector either: x-data is an HTML
            // attribute, so the first double quote inside it ends the attribute and
            // Alpine receives truncated JavaScript. [role=option] is the unquoted form.
            this.$root.querySelectorAll('[role=option]')[this.active]?.click();
        },
    }"
    x-on:keydown.window.prevent.cmd.k="show()"
    x-on:keydown.window.prevent.ctrl.k="show()"
    x-on:open-command-palette.window="show()"
    x-on:keydown.escape.window="open = false"
>
    {{-- d-modal-open is bound to `open` rather than written statically: daisyUI
         locks the root scroll with :root:has(.d-modal-open), and :has() does not
         care that x-show has set display:none. The backdrop paints the scrim, so
         the modal's own fill is cleared. --}}
    <div x-show="open" x-cloak
         class="d-modal z-[60] bg-transparent"
         :class="{ 'd-modal-open': open }"
         role="dialog" aria-modal="true" aria-label="Command palette">
        <div x-show="open"
             x-transition:enter="transition-opacity ease-out duration-200"
             x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
             x-transition:leave="transition-opacity ease-in duration-150"
             x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
             @click="open = false"
             class="d-modal-backdrop bg-foreground/40"></div>

        <div x-show="open" x-cloak
             {{-- transition-[opacity,transform] and duration-200, not the bare
                  `transition` shorthand at 260ms. 260 is not on the scale at all -
                  the tokens are 100 / 150 / 200 / 300, and --dur-base (200ms) is
                  the one whose own comment names "dropdowns, popovers, tabs,
                  accordions". A command palette is a popover. --}}
             x-transition:enter="transition-[opacity,transform] ease-out duration-200"
             x-transition:enter-start="opacity-0 -translate-y-1"
             x-transition:enter-end="opacity-100 translate-y-0"
             x-trap.noscroll="open"
             class="d-modal-box mt-[12vh] w-[min(92vw,36rem)] max-w-none self-start overflow-hidden rounded-box border border-base-300 bg-base-100 p-0 elev-2">

            <div class="flex items-center gap-3 border-b border-border px-4">
                <svg class="h-4 w-4 shrink-0 text-muted-foreground" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/>
                </svg>
                <input x-ref="input" x-model="query" type="text"
                       @keydown.down.prevent="move(1)"
                       @keydown.up.prevent="move(-1)"
                       @keydown.enter.prevent="go()"
                       @input="search()"
                       placeholder="Search gamefowl, matings or pages…"
                       aria-label="Search"
                       class="min-h-11 w-full border-0 bg-transparent py-3 text-[16px] text-foreground placeholder:text-muted-foreground focus:outline-none">
                <kbd class="d-kbd d-kbd-sm datum hidden text-muted-foreground sm:inline-flex">Esc</kbd>
            </div>

            <ul class="d-menu max-h-[50vh] w-full flex-nowrap overflow-y-auto p-1.5 [--d-menu-active-bg:var(--color-primary-50)] [--d-menu-active-fg:var(--color-primary-700)]" role="listbox">
                <template x-for="(item, i) in results" :key="item.url + i">
                    <li>
                        <span x-show="i === 0 || results[i - 1].group !== item.group"
                              class="d-menu-title px-2.5 pt-2 pb-1 text-[11px] font-medium text-muted-foreground" x-text="item.group"></span>
                        <a :href="item.url" wire:navigate
                           @mouseenter="active = i"
                           :class="active === i ? 'd-menu-active' : 'text-foreground'"
                           class="flex min-h-11 items-center gap-3 px-2.5 text-[14px] lg:min-h-9 lg:text-xs"
                           :aria-selected="active === i"
                           role="option">
                            <span class="min-w-0 flex-1 truncate" x-text="item.label"></span>
                            <span class="shrink-0 truncate text-[12px] text-muted-foreground lg:text-[11px]" x-text="item.hint"></span>
                        </a>
                    </li>
                </template>
                <li x-show="searching" class="px-4 py-3 text-center text-[14px] text-muted-foreground lg:text-xs">
                    Searching records&hellip;
                </li>
                <li x-show="! searching && results.length === 0" class="px-4 py-6 text-center text-[14px] text-muted-foreground">
                    Nothing matches that.
                </li>
            </ul>
        </div>
    </div>
</div>
