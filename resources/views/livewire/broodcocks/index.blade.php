@php
    /*
     * Column visibility is client-side on purpose. It is a per-person viewing
     * preference, not shared state, and routing it through Livewire would put a
     * server round-trip behind a checkbox. localStorage keeps it across sessions
     * without a table, which §9 forbids.
     */
    $optionalColumns = [
        'bloodline' => 'Bloodline',
        'sex' => 'Sex',
        'age' => 'Age',
        'class' => 'Class',
        'status' => 'Status',
    ];

    // Property access, so #[Computed] memoizes: the view reads the paginator
    // a dozen times and must not re-run the query for each.
    $birds = $this->broodcocks;
    $total = $birds->total();

    /*
     * THE FIGURE is the paginator's own total - the number of birds this view
     * is actually showing across all its pages. No extra query. Its label says
     * what was counted, because the same number means "on record" in the
     * default view and "matching" once a filter narrows it. "On record", not
     * "on the farm": the default view lists every status, sold and deceased
     * included, so it is the dashboard's on-farm count plus the birds that left.
     */
    $narrowed = $search !== '' || $status !== '' || $class !== '' || $sex !== '' || $bloodline !== '';
    $figureLabel = $narrowed
        ? Str::plural('bird', $total).' '.($total === 1 ? 'matches' : 'match').' these filters'
        : match ($ownership) {
            'outside' => Str::plural('outside bird', $total),
            'all' => Str::plural('bird', $total).', farm and outside',
            default => Str::plural('bird', $total).' on record',
        };

    /*
     * The table's columns, in order. `sort` is the key Index::sort() accepts
     * (null = not sortable), `vis` the client-side visibility key, `cell` the
     * breakpoint classes that drop a secondary column on a narrow tablet.
     */
    $columns = [
        ['label' => 'Band number', 'sort' => 'band_number', 'vis' => null,        'align' => 'left',  'cell' => ''],
        ['label' => 'Name',        'sort' => 'name',        'vis' => null,        'align' => 'left',  'cell' => ''],
        ['label' => 'Bloodline',   'sort' => 'bloodline',   'vis' => 'bloodline', 'align' => 'left',  'cell' => ''],
        ['label' => 'Sex',         'sort' => null,          'vis' => 'sex',       'align' => 'left',  'cell' => 'hidden md:table-cell'],
        ['label' => 'Age',         'sort' => 'date_hatched','vis' => 'age',       'align' => 'right', 'cell' => ''],
        ['label' => 'Class',       'sort' => 'class',       'vis' => 'class',     'align' => 'left',  'cell' => 'hidden lg:table-cell'],
        ['label' => 'Status',      'sort' => 'status',      'vis' => 'status',    'align' => 'left',  'cell' => ''],
    ];

    $pageIds = $birds->pluck('id')->all();
    $allOnPageSelected = $pageIds !== [] && ! array_diff($pageIds, $selected);

    // The filters, then every control that re-queries the list. Strings, so
    // the skeleton, the "Searching" line and the rows cannot disagree.
    $searchTargets = 'search,status,class,sex,bloodline,ownership';
    $filterTargets = $searchTargets.',sort,perPage,gotoPage,nextPage,previousPage';
@endphp

<div
    x-data="{
        cols: (() => { try { return JSON.parse(localStorage.getItem('gfms-bc-cols') || 'null'); } catch (e) { return null; } })() ?? @js(array_fill_keys(array_keys($optionalColumns), true)),
        colsOpen: false,
        view: $persist(gfmsDefaultView()).as('gfms-view-gamefowl'),
        save() { try { localStorage.setItem('gfms-bc-cols', JSON.stringify(this.cols)); } catch (e) {} },
    }"
>
    <x-anchor-block eyebrow="Manage" title="Gamefowl" :figure="number_format($total)" :figure-label="$figureLabel">
        @can('create', App\Models\Broodcock::class)
            <x-slot:actions>
                <a href="{{ route('broodcocks.create') }}" x-on:click.prevent="$dispatch('ems-dialog', 'broodcocks-form')" class="d-btn d-btn-primary">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                    </svg>
                    Add gamefowl
                </a>
            </x-slot:actions>
        @endcan
    </x-anchor-block>

    {{-- SEARCH AND FILTERS, ONE ROW. The search takes the space that is left;
         every select keeps its own width. It compacts before it wraps: from lg to
         102rem of screen the field names inside the selects step aside and
         Columns becomes an icon; below 76rem the search takes a line of its
         own and the filters share the next, rather than breaking the row
         wherever it happens to run out.

         Screen breakpoints, not a container query: a size container is the
         containing block for fixed children, and every select's open list
         is fixed-positioned - it would land in the corner of the row. --}}
    <div class="mt-6 flex flex-wrap items-center gap-2" role="search" aria-label="Find a broodcock">
        <div class="relative min-w-40 flex-1 basis-full min-[76rem]:basis-40">
            <label for="search" class="sr-only">Search</label>
            <svg class="pointer-events-none absolute left-3 top-1/2 z-[1] h-4 w-4 -translate-y-1/2 text-muted-foreground" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/>
            </svg>
            <input
                id="search"
                type="search"
                wire:model.live.debounce.300ms="search"
                placeholder="Name, band number or bloodline"
                class="d-input w-full pl-9"
            >
        </div>

        <x-filter-select :compact="true" id="status" label="Status" wire:model.live="status">
            <option value="">All statuses</option>
            @foreach ($this->statusOptions() as $option)
                <option value="{{ $option->value }}">{{ $option->label() }}</option>
            @endforeach
        </x-filter-select>

        <x-filter-select :compact="true" id="class" label="Class" wire:model.live="class">
            <option value="">All classes</option>
            @foreach ($this->classOptions() as $option)
                <option value="{{ $option->value }}">{{ $option->label() }}</option>
            @endforeach
        </x-filter-select>

        <x-filter-select :compact="true" id="sex" label="Sex" wire:model.live="sex">
            <option value="">Male and female</option>
            @foreach ($this->sexOptions() as $option)
                <option value="{{ $option->value }}">{{ $option->farmTerm() }} ({{ $option->label() }})</option>
            @endforeach
        </x-filter-select>

        <x-filter-select :compact="true" id="bloodline" label="Bloodline" wire:model.live="bloodline">
            <option value="">All bloodlines</option>
            @foreach ($this->bloodlineOptions as $option)
                <option value="{{ $option }}">{{ $option }}</option>
            @endforeach
        </x-filter-select>

        {{-- Ownership. Defaults to the farm's own birds. Outside parents are
             created by the breeding form - a borrowed hen has to be a real row
             or the pedigree loses the branch above her - so they stay one
             selection away rather than hidden: a keeper still needs to
             correct their details. --}}
        <x-filter-select :compact="true" id="ownership" label="Ownership" wire:model.live="ownership">
            <option value="farm">This farm's birds</option>
            <option value="outside">Outside birds only</option>
            <option value="all">Both</option>
        </x-filter-select>

        {{-- Column visibility. Which columns matter depends on what you came
             here to do, and a laptop has room for fewer than a monitor. --}}
        <x-ui.view-toggle />

        <div class="relative hidden md:block" x-show="view === 'table'">
            <button type="button" @click="colsOpen = ! colsOpen"
                    class="d-btn d-btn-outline"
                    :aria-expanded="colsOpen ? 'true' : 'false'"
                    aria-controls="bc-columns">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 4.5v15m6-15v15M4.5 19.5h15a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5h-15A1.5 1.5 0 0 0 3 6v12a1.5 1.5 0 0 0 1.5 1.5Z"/>
                </svg>
                <span class="lg:max-[102rem]:sr-only">Columns</span>
            </button>

            <div id="bc-columns" x-show="colsOpen" x-cloak @click.outside="colsOpen = false" @keydown.escape.window="colsOpen = false"
                 class="bg-base-100 rounded-box border border-base-300 elev-2 enter-pop absolute right-0 z-30 mt-2 w-56 p-2">
                <p class="px-2 pb-1.5 pt-1 text-[13px] lg:text-[11px] font-semibold text-muted-foreground">Show columns</p>
                @foreach ($optionalColumns as $key => $label)
                    <label class="flex min-h-11 cursor-pointer items-center gap-2.5 rounded-[var(--radius-sm)] px-2 text-[15px] lg:text-xs text-foreground hover:bg-muted">
                        <input type="checkbox" class="d-checkbox d-checkbox-primary"
                               x-model="cols['{{ $key }}']" @change="save()">
                        {{ $label }}
                    </label>
                @endforeach
            </div>
        </div>
    </div>

    {{-- What the filters matched, and the one way out. Only while a filter is
         on: a permanent "0 filters" line is chrome that never earns its space.
         The polite "Searching" lives here too, beside the count it is about to
         change - the round trip is to Tokyo, so a keystroke and its answer are
         most of a second apart. Where you are in the pages is in the footer
         under the table, not here. --}}
    <div @class([
            'flex flex-wrap items-center gap-x-4 gap-y-1 text-[14px] text-muted-foreground lg:text-xs',
            'mt-3 min-h-11' => $this->hasActiveFilters(),
         ]) aria-live="polite">
        @if ($this->hasActiveFilters())
            <span wire:loading.remove wire:target="{{ $searchTargets }}">
                Showing <span class="datum text-foreground">{{ number_format($total) }}</span> {{ Str::plural('bird', $total) }} matching your filters.
            </span>
        @endif
        <span wire:loading wire:target="{{ $searchTargets }}" @class(['mt-3' => ! $this->hasActiveFilters()])>Searching&hellip;</span>

        @if ($this->hasActiveFilters())
            <button type="button" wire:click="clearFilters" class="d-btn d-btn-ghost">Clear filters</button>
        @endif
    </div>

    {{-- RESULTS --}}
    @if ($birds->isEmpty())
        @if ($this->hasActiveFilters())
            <x-empty-state class="mt-4"
                           title="No birds match your filters"
                           description="Try removing a filter or searching for something else."
                           icon="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z">
                <button type="button" wire:click="clearFilters" class="d-btn d-btn-outline">Clear filters</button>
            </x-empty-state>
        @else
            <x-empty-state class="mt-4"
                           title="No broodcocks recorded yet"
                           description="Start by adding your first bird. You will be able to record its health, breeding and performance afterwards."
                           icon="M12 4.5v15m7.5-7.5h-15">
                @can('create', App\Models\Broodcock::class)
                    <a href="{{ route('broodcocks.create') }}" x-on:click.prevent="$dispatch('ems-dialog', 'broodcocks-form')" class="d-btn d-btn-primary">Add your first broodcock</a>
                @endcan
            </x-empty-state>
        @endif
    @else
        {{-- TABLETS AND UP, WHEN CHOSEN: the table. No overflow wrapper - the content region
             is the scroll container, so `sticky top-0` on the head pins to it
             and the column names stay put through a long list. An overflow
             wrapper would become the sticky container and quietly break that;
             secondary columns drop at narrower widths instead. --}}
        {{-- EMS: a wide table scrolls inside its own container; the page never does. --}}
        <div class="mt-4 hidden overflow-x-auto md:block" x-show="view === 'table'">
            <table class="d-table w-full border-b border-border">
                <thead class="sticky top-0 z-10 bg-background">
                    <tr class="border-b border-border">
                        <th scope="col" class="w-12 py-0 pl-0 pr-1 text-left">
                            <label class="flex h-11 w-11 cursor-pointer items-center justify-center">
                                <input type="checkbox" class="d-checkbox d-checkbox-primary"
                                       wire:click="toggleSelectPage"
                                       @checked($allOnPageSelected)
                                       aria-label="Select all birds on this page">
                            </label>
                        </th>
                        <th scope="col" class="w-14 py-0 pr-2"><span class="sr-only">Photo</span></th>
                        @foreach ($columns as $column)
                            @php
                                $isSorted = $column['sort'] !== null && $sortBy === $column['sort'];
                                $isAscending = $sortDirection === 'asc';
                            @endphp
                            <th scope="col"
                                @if ($column['vis']) x-show="cols.{{ $column['vis'] }}" @endif
                                @if ($column['sort']) aria-sort="{{ $isSorted ? ($isAscending ? 'ascending' : 'descending') : 'none' }}" @endif
                                @class([
                                    'whitespace-nowrap px-3 py-0 text-[13px] lg:text-[11px] font-semibold text-muted-foreground',
                                    'text-right' => $column['align'] === 'right',
                                    'text-left' => $column['align'] !== 'right',
                                    $column['cell'],
                                ])>
                                @if ($column['sort'])
                                    <button type="button" wire:click="sort('{{ $column['sort'] }}')"
                                            @class([
                                                'inline-flex min-h-11 items-center gap-1 font-semibold hover:text-foreground',
                                                'text-foreground' => $isSorted,
                                                'flex-row-reverse' => $column['align'] === 'right',
                                            ])>
                                        {{ $column['label'] }}
                                        @if ($isSorted)
                                            <svg class="h-3.5 w-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="{{ $isAscending ? 'm4.5 15.75 7.5-7.5 7.5 7.5' : 'm19.5 8.25-7.5 7.5-7.5-7.5' }}"/>
                                            </svg>
                                            <span class="sr-only">sorted {{ $isAscending ? 'ascending' : 'descending' }}</span>
                                        @endif
                                    </button>
                                @else
                                    {{ $column['label'] }}
                                @endif
                            </th>
                        @endforeach
                        <th scope="col" class="px-3 py-0 text-right text-[13px] lg:text-[11px] font-semibold text-muted-foreground">Actions</th>
                    </tr>
                </thead>

                <tbody wire:loading.remove wire:target="{{ $filterTargets }}">
                    @foreach ($birds as $bird)
                        {{-- .row-hover tints the row and reveals its actions together;
                             focus-within means keyboard users get them too. --}}
                        {{-- Resting the pointer on a row previews the bird's photo
                             beside it (an EMS hover card). Only on a device that
                             can hover, after a short pause so sweeping the
                             pointer down the list does not flash every bird. --}}
                        <tr wire:key="bc-{{ $bird->id }}"
                            x-data="{ preview: false, previewTimer: null }"
                            x-on:mouseenter="if (matchMedia('(hover: hover)').matches) { previewTimer = setTimeout(() => preview = true, 350) }"
                            x-on:mouseleave="clearTimeout(previewTimer); preview = false"
                            @class(['group row-hover', 'is-selected' => in_array($bird->id, $selected, true)])>
                            <td class="w-12 py-1 pl-0 pr-1">
                                <label class="flex h-11 w-11 cursor-pointer items-center justify-center">
                                    <input type="checkbox" value="{{ $bird->id }}" wire:model.live="selected"
                                           class="d-checkbox d-checkbox-primary"
                                           aria-label="Select {{ $bird->name }}">
                                </label>
                            </td>
                            <td class="w-14 py-1.5 pr-2">
                                {{-- The thumbnail copy only, never the original: a page
                                     of rows must not pull full-size files out of Tokyo. --}}
                                <a href="{{ route('broodcocks.show', $bird) }}" wire:navigate tabindex="-1" aria-hidden="true" x-ref="thumb"
                                   class="block w-10 overflow-hidden rounded-[6px] bg-muted aspect-[4/5]">
                                    @if ($bird->primaryPhoto)
                                        <img src="{{ route('photos.show', ['photo' => $bird->primaryPhoto, 'size' => 'thumb']) }}" alt=""
                                             width="40" height="50" loading="lazy" decoding="async" class="h-full w-full object-cover">
                                    @else
                                        <span class="flex h-full w-full items-center justify-center"><x-band-dot :bloodline="$bird->bloodline" /></span>
                                    @endif
                                </a>

                                {{-- The same thumbnail file the cell already loaded (about
                                     400px, sharp at this size), so the preview costs no
                                     new request and no query. Decorative: the row's own
                                     links already name the bird. --}}
                                <div x-show="preview" x-cloak aria-hidden="true"
                                     x-anchor.fixed.right-start.offset.12="$refs.thumb"
                                     x-transition:enter="transition ease-out duration-150"
                                     x-transition:enter-start="scale-95 opacity-0"
                                     x-transition:enter-end="scale-100 opacity-100"
                                     x-transition:leave="transition ease-in duration-100"
                                     x-transition:leave-start="opacity-100"
                                     x-transition:leave-end="opacity-0"
                                     class="pointer-events-none z-40 w-52 rounded-md border border-border bg-popover p-2 text-popover-foreground shadow-md">
                                    <div class="aspect-[4/5] w-full overflow-hidden rounded-sm bg-muted">
                                        @if ($bird->primaryPhoto)
                                            <img x-bind:src="preview ? @js(route('photos.show', ['photo' => $bird->primaryPhoto, 'size' => 'thumb'])) : null" alt=""
                                                 width="192" height="240" decoding="async" class="h-full w-full object-cover">
                                        @else
                                            <div class="flex h-full w-full flex-col items-center justify-center gap-2 text-xs text-muted-foreground">
                                                <x-band-dot :bloodline="$bird->bloodline" />
                                                No photo yet
                                            </div>
                                        @endif
                                    </div>
                                    <div class="px-1 pt-2 pb-0.5">
                                        <p class="truncate text-sm font-semibold text-foreground lg:text-xs">{{ $bird->name }}</p>
                                        <p class="mt-0.5 flex items-center gap-1.5 text-xs text-muted-foreground lg:text-[11px]">
                                            @if ($bird->band_number)
                                                <span class="datum">{{ $bird->band_number }}</span>
                                                <span aria-hidden="true">&middot;</span>
                                            @endif
                                            <span class="truncate">{{ $bird->bloodline }}</span>
                                        </p>
                                    </div>
                                </div>
                            </td>
                            <td class="whitespace-nowrap px-3 py-1.5">
                                @if ($bird->band_number)
                                    <a href="{{ route('broodcocks.show', $bird) }}" wire:navigate
                                       class="datum font-display text-[16px] lg:text-xs font-semibold text-foreground">{{ $bird->band_number }}</a>
                                @else
                                    <span class="text-[14px] lg:text-xs text-muted-foreground">Not yet banded</span>
                                @endif
                            </td>
                            <td class="px-3 py-1.5 text-[15px] lg:text-xs text-foreground">
                                <a href="{{ route('broodcocks.show', $bird) }}" wire:navigate class="">
                                    {{ $bird->name }}
                                </a>
                                {{-- Only when the list can contain both. Badging every row
                                     in the default view would label the ordinary case. --}}
                                @if ($bird->is_external && $ownership !== 'outside')
                                    <span class="d-badge d-badge-soft d-badge-neutral ml-1.5" title="Belongs to another farm; recorded so the pedigree stays complete">Outside</span>
                                @endif
                            </td>
                            <td x-show="cols.bloodline" class="whitespace-nowrap px-3 py-1.5 text-[15px] lg:text-xs text-foreground">
                                <span class="inline-flex items-center gap-2">
                                    <x-band-dot :bloodline="$bird->bloodline" />
                                    @if ($bird->bloodline)
                                        {{ $bird->bloodline }}
                                    @else
                                        <span class="text-muted-foreground">Not recorded</span>
                                    @endif
                                </span>
                            </td>
                            <td x-show="cols.sex" class="hidden whitespace-nowrap px-3 py-1.5 text-[15px] lg:text-xs text-muted-foreground md:table-cell">{{ $bird->sex->label() }}</td>
                            <td x-show="cols.age" class="whitespace-nowrap px-3 py-1.5 text-right text-[15px] lg:text-xs">
                                @if ($bird->ageLabel())
                                    <span class="datum text-foreground">{{ $bird->ageLabel() }}</span>
                                @else
                                    <span class="text-muted-foreground">Unknown</span>
                                @endif
                            </td>
                            <td x-show="cols.class" class="hidden whitespace-nowrap px-3 py-1.5 lg:table-cell">
                                <span class="d-badge d-badge-soft {{ $bird->class->badgeClasses() }}">{{ $bird->class->label() }}</span>
                            </td>
                            <td x-show="cols.status" class="whitespace-nowrap px-3 py-1.5">
                                <span class="d-badge d-badge-soft {{ $bird->status->badgeClasses() }}">{{ $bird->status->label() }}</span>
                            </td>
                            <td class="whitespace-nowrap px-3 py-1 text-right">
                                <span class="inline-flex items-center justify-end gap-0.5">
                                    <x-ui.row-action icon="eye" :label="'View '.$bird->name" :href="route('broodcocks.show', $bird)" wire:navigate />
                                    @can('update', $bird)
                                        <x-ui.row-action icon="pencil" :label="'Edit '.$bird->name" :href="route('broodcocks.edit', $bird)" wire:click.prevent="openForm({{ $bird->id }})" x-on:click="$dispatch('ems-dialog', { id: 'broodcocks-form', wait: true })" />
                                    @endcan
                                    @can('delete', $bird)
                                        <x-ui.row-action icon="trash-2" :label="'Archive '.$bird->name" :destructive="true"
                                                         x-on:click="const w = $wire; w.$set('selected', [{{ \Illuminate\Support\Js::from((string) $bird->id) }}]).then(() => w.confirmDeleteSelected())" />
                                    @endcan
                                </span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>

                <x-table-skeleton :cols="10" wire:loading wire:target="{{ $filterTargets }}" />
            </table>
        </div>

        {{-- CARDS: the same birds, the same actions. Always on a phone, where a
             table read sideways hides the column you came for; from md up only
             when the switch says so. --}}
        <div x-bind:class="view === 'cards' ? '' : 'md:hidden'" x-cloak>
            <ul class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4"
                wire:loading.remove wire:target="{{ $filterTargets }}">
                @foreach ($birds as $bird)
                    <li wire:key="bc-card-{{ $bird->id }}">
                        <x-ui.record-card :href="route('broodcocks.show', $bird)">
                            <x-slot:media>
                                <span class="block w-12 overflow-hidden rounded-[6px] bg-muted aspect-[4/5]">
                                    @if ($bird->primaryPhoto)
                                        <img src="{{ route('photos.show', ['photo' => $bird->primaryPhoto, 'size' => 'thumb']) }}" alt=""
                                             width="48" height="60" loading="lazy" decoding="async" class="h-full w-full object-cover">
                                    @else
                                        <span class="flex h-full w-full items-center justify-center"><x-band-dot :bloodline="$bird->bloodline" /></span>
                                    @endif
                                </span>
                            </x-slot:media>
                            <x-slot:title>
                                <span class="datum">{{ $bird->band_number ?? $bird->name }}</span>
                            </x-slot:title>
                            <x-slot:subtitle>{{ $bird->band_number ? $bird->name : 'Not yet banded' }}</x-slot:subtitle>
                            <x-slot:badge>
                                <span class="d-badge d-badge-soft {{ $bird->status->badgeClasses() }}">{{ $bird->status->label() }}</span>
                                @if ($bird->is_external && $ownership !== 'outside')
                                    <span class="d-badge d-badge-soft d-badge-neutral">Outside</span>
                                @endif
                            </x-slot:badge>

                            <dl class="grid grid-cols-3 gap-3">
                                <x-ui.fact label="Bloodline">
                                    <span class="inline-flex items-center gap-1.5"><x-band-dot :bloodline="$bird->bloodline" /> {{ $bird->bloodline ?? 'Not recorded' }}</span>
                                </x-ui.fact>
                                <x-ui.fact label="Sex">{{ $bird->sex->label() }}</x-ui.fact>
                                <x-ui.fact label="Age"><span class="datum">{{ $bird->ageLabel() ?? 'Unknown' }}</span></x-ui.fact>
                            </dl>

                            <x-slot:meta>
                                <span class="d-badge d-badge-soft {{ $bird->class->badgeClasses() }}">{{ $bird->class->label() }}</span>
                            </x-slot:meta>
                            <x-slot:actions>
                                <x-ui.row-action icon="eye" :label="'View '.$bird->name" :href="route('broodcocks.show', $bird)" wire:navigate />
                                @can('update', $bird)
                                    <x-ui.row-action icon="pencil" :label="'Edit '.$bird->name" :href="route('broodcocks.edit', $bird)" wire:click.prevent="openForm({{ $bird->id }})" x-on:click="$dispatch('ems-dialog', { id: 'broodcocks-form', wait: true })" />
                                @endcan
                                @can('delete', $bird)
                                    <x-ui.row-action icon="trash-2" :label="'Archive '.$bird->name" :destructive="true"
                                                     x-on:click="const w = $wire; w.$set('selected', [{{ \Illuminate\Support\Js::from((string) $bird->id) }}]).then(() => w.confirmDeleteSelected())" />
                                @endcan
                            </x-slot:actions>
                        </x-ui.record-card>
                    </li>
                @endforeach
            </ul>
            <x-ui.cards-skeleton class="mt-4" wire:loading.grid wire:target="{{ $filterTargets }}" />
        </div>

        <x-ui.table-pagination :paginator="$birds" class="mt-4" />
    @endif

    {{--
        SELECTION BAR. Sticky to the bottom of the content region rather than
        fixed to the window, so it never sits under the sidebar whatever width
        the sidebar is, and selecting a row never reflows the table above. It
        offers only what the Policy already permits; the selection is
        server-side, so it survives pagination and a filter change.
    --}}
    @if (count($selected) > 0)
        <div class="sticky bottom-4 z-30 mt-6 flex justify-center" role="status" aria-live="polite">
            <div class="elev-3 enter-pop flex items-center gap-2 rounded-full border border-border bg-card py-1.5 pl-5 pr-1.5">
                <span class="pr-2 text-[15px] lg:text-xs text-foreground">
                    <span class="datum font-semibold">{{ count($selected) }}</span>
                    {{ Str::plural('bird', count($selected)) }} selected
                </span>

                @if ($this->canDeleteSelection)
                    <button type="button" wire:click="confirmDeleteSelected" class="d-btn d-btn-outline text-destructive">
                        Archive selected
                    </button>
                @endif

                <button type="button" wire:click="clearSelection"
                        class="d-btn d-btn-ghost d-btn-circle text-muted-foreground hover:text-foreground"
                        aria-label="Clear selection" title="Clear selection">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </div>
    @endif

    {{-- Bulk delete confirmation. Names the birds and says what is kept,
         because "Are you sure?" tells a worried keeper nothing. --}}
    @if ($confirmingBulkDelete && count($selected) > 0)
        @php
            $doomed = $this->selectedBirds;
            $named = $doomed->take(5)->map(fn ($b) => $b->band_number ?: $b->name)->all();
            $more = $doomed->count() - count($named);
        @endphp
        <div class="d-modal d-modal-open"
             x-data x-trap.noscroll="true" x-on:keydown.escape.window="$wire.cancelDeleteSelected()"
             role="dialog" aria-modal="true" aria-labelledby="bulk-delete-title" aria-describedby="bulk-delete-body">
            <div class="d-modal-box elev-3 w-full max-w-md">
                <h2 id="bulk-delete-title" class="font-display text-[22px] lg:text-base font-semibold tracking-[-0.015em] text-foreground">
                    Archive <span class="datum">{{ count($selected) }}</span> {{ Str::plural('bird', count($selected)) }}?
                </h2>
                <p id="bulk-delete-body" class="mt-3 text-[15px] lg:text-xs leading-relaxed text-muted-foreground">
                    <strong class="font-semibold text-foreground">{{ implode(', ', $named) }}@if ($more > 0) and <span class="datum">{{ $more }}</span> more @endif</strong>
                    @if (count($selected) === 1)
                        leaves the gamefowl list, the catalogue and the reports. Its health, breeding and performance
                        history is kept, and the farm owner can restore it from the Archive.
                    @else
                        leave the gamefowl list, the catalogue and the reports. Their health, breeding and performance
                        history is kept, and the farm owner can restore them from the Archive. Birds your role cannot
                        archive are left as they are.
                    @endif
                </p>
                <div class="d-modal-action flex-col-reverse gap-3 sm:flex-row">
                    <button type="button" wire:click="cancelDeleteSelected" class="d-btn d-btn-outline">Cancel</button>
                    <button type="button" wire:click="deleteSelected" wire:loading.attr="disabled" wire:target="deleteSelected" class="d-btn d-btn-error">
                        <span class="d-loading d-loading-spinner d-loading-sm" wire:loading wire:target="deleteSelected"></span>
                        Yes, archive {{ count($selected) === 1 ? 'this bird' : 'these birds' }}
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- Add and edit open here, in a dialog over the list, never on a page of their own. --}}
    {{-- The new-record form is built with the page, and it authorizes on
         mount, so it is only built for someone who may create one. --}}
    @can('create', \App\Models\Broodcock::class)
        <x-ui.form-dialog id="broodcocks-form" model="showForm" size="xl" :wrap="false"
                    :title="$formRecordId ? 'Edit '.(\App\Models\Broodcock::find($formRecordId)?->name ?? 'gamefowl') : 'Add a Gamefowl'">
            @if ($formRecordId)
                {{-- An edit, or a form for one bird: filled by the server when opened. --}}
                @if ($showForm)
                    <livewire:broodcocks.form :broodcock="$formRecordId ? \App\Models\Broodcock::find($formRecordId) : null"
                                              :in-dialog="true" :key="'broodcocks-form-'.($formRecordId ?? 'new')" />
                @else
                    <x-ui.form-skeleton :fields="7" />
                @endif
            @else
                {{-- A new record: built with the page, so Add opens at once with no request. --}}
                <livewire:broodcocks.form :broodcock="$formRecordId ? \App\Models\Broodcock::find($formRecordId) : null"
                                          :in-dialog="true" :key="'broodcocks-form-new-'.$formNonce" />
            @endif
        </x-ui.form-dialog>
    @endcan
</div>
