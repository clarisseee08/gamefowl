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
     * what was counted, because the same number means "on the farm" in the
     * default view and "matching" once a filter narrows it.
     */
    $narrowed = $search !== '' || $status !== '' || $class !== '' || $sex !== '' || $bloodline !== '';
    $figureLabel = $narrowed
        ? Str::plural('bird', $total).' '.($total === 1 ? 'matches' : 'match').' these filters'
        : match ($ownership) {
            'outside' => Str::plural('outside bird', $total),
            'all' => Str::plural('bird', $total).', farm and outside',
            default => Str::plural('bird', $total).' on the farm',
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

    // Every control that re-queries the list. One string so the skeleton,
    // the "Searching" line and the result rows can never disagree about it.
    $filterTargets = 'search,status,class,sex,bloodline,ownership,sort,gotoPage,nextPage,previousPage';
@endphp

<div
    x-data="{
        cols: (() => { try { return JSON.parse(localStorage.getItem('gfms-bc-cols') || 'null'); } catch (e) { return null; } })() ?? @js(array_fill_keys(array_keys($optionalColumns), true)),
        colsOpen: false,
        save() { try { localStorage.setItem('gfms-bc-cols', JSON.stringify(this.cols)); } catch (e) {} },
    }"
>
    <x-anchor-block eyebrow="Manage" title="Broodcocks" :figure="number_format($total)" :figure-label="$figureLabel">
        @can('create', App\Models\Broodcock::class)
            <x-slot:actions>
                <a href="{{ route('broodcocks.create') }}" wire:navigate class="btn-primary">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                    </svg>
                    Add broodcock
                </a>
            </x-slot:actions>
        @endcan
    </x-anchor-block>

    {{-- SEARCH AND FILTERS, ONE ROW. The search takes the space that is left;
         every select shrinks to its own width and the row wraps where it runs
         out of room. Each field name sits inside its control's border (see
         x-filter-select), so the row is one control high, not two. --}}
    <div class="mt-6 flex flex-wrap items-center gap-3" role="search" aria-label="Find a broodcock">
        <div class="relative min-w-0 flex-1 basis-64">
            <label for="search" class="sr-only">Search</label>
            <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/>
            </svg>
            <input
                id="search"
                type="search"
                wire:model.live.debounce.300ms="search"
                placeholder="Name, band number or bloodline"
                class="input pl-9"
            >
        </div>

        <x-filter-select id="status" label="Status" wire:model.live="status">
            <option value="">All statuses</option>
            @foreach ($this->statusOptions() as $option)
                <option value="{{ $option->value }}">{{ $option->label() }}</option>
            @endforeach
        </x-filter-select>

        <x-filter-select id="class" label="Class" wire:model.live="class">
            <option value="">All classes</option>
            @foreach ($this->classOptions() as $option)
                <option value="{{ $option->value }}">{{ $option->label() }}</option>
            @endforeach
        </x-filter-select>

        <x-filter-select id="sex" label="Sex" wire:model.live="sex">
            <option value="">Male and female</option>
            @foreach ($this->sexOptions() as $option)
                <option value="{{ $option->value }}">{{ $option->farmTerm() }} ({{ $option->label() }})</option>
            @endforeach
        </x-filter-select>

        <x-filter-select id="bloodline" label="Bloodline" wire:model.live="bloodline">
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
        <x-filter-select id="ownership" label="Ownership" wire:model.live="ownership">
            <option value="farm">This farm's birds</option>
            <option value="outside">Outside birds only</option>
            <option value="all">Both</option>
        </x-filter-select>

        {{-- Column visibility. Which columns matter depends on what you came
             here to do, and a laptop has room for fewer than a monitor. --}}
        <div class="relative hidden sm:block">
            <button type="button" @click="colsOpen = ! colsOpen"
                    class="btn-secondary"
                    :aria-expanded="colsOpen ? 'true' : 'false'"
                    aria-controls="bc-columns">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 4.5v15m6-15v15M4.5 19.5h15a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5h-15A1.5 1.5 0 0 0 3 6v12a1.5 1.5 0 0 0 1.5 1.5Z"/>
                </svg>
                Columns
            </button>

            <div id="bc-columns" x-show="colsOpen" x-cloak @click.outside="colsOpen = false" @keydown.escape.window="colsOpen = false"
                 class="popover enter-pop absolute right-0 z-30 mt-2 w-56 p-2">
                <p class="px-2 pb-1.5 pt-1 text-[13px] font-semibold text-muted-foreground">Show columns</p>
                @foreach ($optionalColumns as $key => $label)
                    <label class="flex min-h-11 cursor-pointer items-center gap-2.5 rounded-[var(--radius-sm)] px-2 text-[15px] text-foreground hover:bg-muted">
                        <input type="checkbox" class="h-4 w-4 accent-primary"
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
         most of a second apart. --}}
    <div class="mt-3 flex min-h-11 flex-wrap items-center gap-x-4 gap-y-1 text-[14px] text-muted-foreground" aria-live="polite">
        <span wire:loading.remove wire:target="{{ $filterTargets }}">
            @if ($this->hasActiveFilters())
                Showing <span class="datum text-foreground">{{ number_format($total) }}</span> {{ Str::plural('bird', $total) }} matching your filters.
            @elseif ($birds->lastPage() > 1)
                Page <span class="datum">{{ $birds->currentPage() }}</span> of <span class="datum">{{ $birds->lastPage() }}</span>.
            @endif
        </span>
        <span wire:loading wire:target="{{ $filterTargets }}">Searching&hellip;</span>

        @if ($this->hasActiveFilters())
            <button type="button" wire:click="clearFilters" class="btn-quiet">Clear filters</button>
        @endif
    </div>

    {{-- RESULTS --}}
    @if ($birds->isEmpty())
        @if ($this->hasActiveFilters())
            <x-empty-state class="mt-4"
                           title="No birds match your filters"
                           description="Try removing a filter or searching for something else."
                           icon="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z">
                <button type="button" wire:click="clearFilters" class="btn-secondary">Clear filters</button>
            </x-empty-state>
        @else
            <x-empty-state class="mt-4"
                           title="No broodcocks recorded yet"
                           description="Start by adding your first bird. You will be able to record its health, breeding and performance afterwards."
                           icon="M12 4.5v15m7.5-7.5h-15">
                @can('create', App\Models\Broodcock::class)
                    <a href="{{ route('broodcocks.create') }}" wire:navigate class="btn-primary">Add your first broodcock</a>
                @endcan
            </x-empty-state>
        @endif
    @else
        {{-- PHONES: stacked rows. Farm staff are mostly on phones, outdoors; a
             table that scrolls sideways hides the column they came for. Each
             row is one tap target: band and name, then bloodline, sex and age. --}}
        <ul class="divide-y divide-border border-y border-border sm:hidden"
            wire:loading.remove wire:target="{{ $filterTargets }}">
            @foreach ($birds as $bird)
                <li wire:key="bc-m-{{ $bird->id }}">
                    <a href="{{ route('broodcocks.show', $bird) }}" wire:navigate
                       class="flex min-h-11 items-center gap-3 py-3 hover:bg-muted/60">
                        <span class="block w-11 shrink-0 overflow-hidden rounded-[6px] bg-muted aspect-[4/5]">
                            @if ($bird->primaryPhoto)
                                <img src="{{ route('photos.show', ['photo' => $bird->primaryPhoto, 'size' => 'thumb']) }}" alt=""
                                     width="44" height="55" loading="lazy" decoding="async" class="h-full w-full object-cover">
                            @else
                                <span class="flex h-full w-full items-center justify-center"><x-band-dot :bloodline="$bird->bloodline" /></span>
                            @endif
                        </span>

                        <span class="min-w-0 flex-1">
                            <span class="flex items-baseline gap-2">
                                @if ($bird->band_number)
                                    <span class="datum font-display text-[17px] font-semibold text-foreground">{{ $bird->band_number }}</span>
                                    <span class="truncate text-[15px] text-muted-foreground">{{ $bird->name }}</span>
                                @else
                                    <span class="truncate font-display text-[17px] font-semibold text-foreground">{{ $bird->name }}</span>
                                    <span class="shrink-0 text-[14px] text-muted-foreground">Not yet banded</span>
                                @endif
                            </span>
                            <span class="mt-0.5 flex items-center gap-1.5 truncate text-[14px] text-muted-foreground">
                                <x-band-dot :bloodline="$bird->bloodline" />
                                <span class="truncate">{{ $bird->bloodline ?? 'Bloodline not recorded' }} &middot; {{ $bird->sex->label() }} &middot; <span class="datum">{{ $bird->ageLabel() ?? 'Age unknown' }}</span></span>
                            </span>
                        </span>

                        <span class="flex shrink-0 flex-col items-end gap-1">
                            <span class="badge {{ $bird->status->badgeClasses() }}">{{ $bird->status->label() }}</span>
                            @if ($bird->is_external && $ownership !== 'outside')
                                <span class="badge badge-neutral">Outside</span>
                            @endif
                        </span>
                    </a>
                </li>
            @endforeach
        </ul>

        {{-- Phone loading: the shape of the answer, replacing the old one. --}}
        <div class="sm:hidden" wire:loading.block wire:target="{{ $filterTargets }}" aria-hidden="true">
            @for ($i = 0; $i < 5; $i++)
                <div class="flex items-center gap-3 border-b border-border py-3">
                    <div class="skeleton aspect-[4/5] w-11 shrink-0"></div>
                    <div class="flex-1 space-y-2">
                        <div class="skeleton h-3 {{ ['w-1/2', 'w-2/3', 'w-2/5'][$i % 3] }}"></div>
                        <div class="skeleton h-3 {{ ['w-3/4', 'w-1/2', 'w-3/5'][$i % 3] }}"></div>
                    </div>
                </div>
            @endfor
        </div>

        {{-- TABLETS AND UP: the table. No overflow wrapper - the content region
             is the scroll container, so `sticky top-0` on the head pins to it
             and the column names stay put through a long list. An overflow
             wrapper would become the sticky container and quietly break that;
             secondary columns drop at narrower widths instead. --}}
        <table class="hidden w-full border-b border-border sm:table">
            <thead class="sticky top-0 z-10 bg-background">
                <tr class="border-b border-border">
                    <th scope="col" class="w-12 py-0 pl-0 pr-1 text-left">
                        <label class="flex h-11 w-11 cursor-pointer items-center justify-center">
                            <input type="checkbox" class="h-4 w-4 accent-primary"
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
                                'whitespace-nowrap px-3 py-0 text-[13px] font-semibold text-muted-foreground',
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
                    <th scope="col" class="px-3 py-0"><span class="sr-only">Actions</span></th>
                </tr>
            </thead>

            <tbody class="divide-y divide-border" wire:loading.remove wire:target="{{ $filterTargets }}">
                @foreach ($birds as $bird)
                    {{-- .row-hover tints the row and reveals its actions together;
                         focus-within means keyboard users get them too. --}}
                    <tr wire:key="bc-{{ $bird->id }}"
                        @class(['group row-hover', 'is-selected' => in_array($bird->id, $selected, true)])>
                        <td class="w-12 py-1 pl-0 pr-1">
                            <label class="flex h-11 w-11 cursor-pointer items-center justify-center">
                                <input type="checkbox" value="{{ $bird->id }}" wire:model.live="selected"
                                       class="h-4 w-4 accent-primary"
                                       aria-label="Select {{ $bird->name }}">
                            </label>
                        </td>
                        <td class="w-14 py-1.5 pr-2">
                            {{-- The thumbnail copy only, never the original: a page
                                 of rows must not pull full-size files out of Tokyo. --}}
                            <a href="{{ route('broodcocks.show', $bird) }}" wire:navigate tabindex="-1" aria-hidden="true"
                               class="block w-10 overflow-hidden rounded-[6px] bg-muted aspect-[4/5]">
                                @if ($bird->primaryPhoto)
                                    <img src="{{ route('photos.show', ['photo' => $bird->primaryPhoto, 'size' => 'thumb']) }}" alt=""
                                         width="40" height="50" loading="lazy" decoding="async" class="h-full w-full object-cover">
                                @else
                                    <span class="flex h-full w-full items-center justify-center"><x-band-dot :bloodline="$bird->bloodline" /></span>
                                @endif
                            </a>
                        </td>
                        <td class="whitespace-nowrap px-3 py-1.5">
                            @if ($bird->band_number)
                                <a href="{{ route('broodcocks.show', $bird) }}" wire:navigate
                                   class="datum font-display text-[16px] font-semibold text-foreground hover:underline hover:underline-offset-4">{{ $bird->band_number }}</a>
                            @else
                                <span class="text-[14px] text-muted-foreground">Not yet banded</span>
                            @endif
                        </td>
                        <td class="px-3 py-1.5 text-[15px] text-foreground">
                            <a href="{{ route('broodcocks.show', $bird) }}" wire:navigate class="hover:underline hover:underline-offset-4">
                                {{ $bird->name }}
                            </a>
                            {{-- Only when the list can contain both. Badging every row
                                 in the default view would label the ordinary case. --}}
                            @if ($bird->is_external && $ownership !== 'outside')
                                <span class="badge badge-neutral ml-1.5" title="Belongs to another farm; recorded so the pedigree stays complete">Outside</span>
                            @endif
                        </td>
                        <td x-show="cols.bloodline" class="whitespace-nowrap px-3 py-1.5 text-[15px] text-foreground">
                            <span class="inline-flex items-center gap-2">
                                <x-band-dot :bloodline="$bird->bloodline" />
                                @if ($bird->bloodline)
                                    {{ $bird->bloodline }}
                                @else
                                    <span class="text-muted-foreground">Not recorded</span>
                                @endif
                            </span>
                        </td>
                        <td x-show="cols.sex" class="hidden whitespace-nowrap px-3 py-1.5 text-[15px] text-muted-foreground md:table-cell">{{ $bird->sex->label() }}</td>
                        <td x-show="cols.age" class="whitespace-nowrap px-3 py-1.5 text-right text-[15px]">
                            @if ($bird->ageLabel())
                                <span class="datum text-foreground">{{ $bird->ageLabel() }}</span>
                            @else
                                <span class="text-muted-foreground">Unknown</span>
                            @endif
                        </td>
                        <td x-show="cols.class" class="hidden whitespace-nowrap px-3 py-1.5 lg:table-cell">
                            <span class="badge {{ $bird->class->badgeClasses() }}">{{ $bird->class->label() }}</span>
                        </td>
                        <td x-show="cols.status" class="whitespace-nowrap px-3 py-1.5">
                            <span class="badge {{ $bird->status->badgeClasses() }}">{{ $bird->status->label() }}</span>
                        </td>
                        <td class="whitespace-nowrap px-3 py-1 text-right">
                            <span class="row-actions inline-flex items-center gap-1">
                                <a href="{{ route('broodcocks.show', $bird) }}" wire:navigate class="btn-quiet px-3 text-[14px]">
                                    View<span class="sr-only">, {{ $bird->name }}</span>
                                </a>
                                @can('update', $bird)
                                    <a href="{{ route('broodcocks.edit', $bird) }}" wire:navigate class="btn-quiet px-3 text-[14px]">
                                        Edit<span class="sr-only">, {{ $bird->name }}</span>
                                    </a>
                                @endcan
                            </span>
                        </td>
                    </tr>
                @endforeach
            </tbody>

            <x-table-skeleton :cols="10" wire:loading wire:target="{{ $filterTargets }}" />
        </table>

        <div class="mt-6">
            {{ $birds->links() }}
        </div>
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
                <span class="pr-2 text-[15px] text-foreground">
                    <span class="datum font-semibold">{{ count($selected) }}</span>
                    {{ Str::plural('bird', count($selected)) }} selected
                </span>

                @if ($this->canDeleteSelection)
                    <button type="button" wire:click="confirmDeleteSelected" class="btn-secondary text-destructive">
                        Delete selected
                    </button>
                @endif

                <button type="button" wire:click="clearSelection"
                        class="inline-flex h-11 w-11 items-center justify-center rounded-full text-muted-foreground hover:bg-muted hover:text-foreground"
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
        <div class="dialog-backdrop fixed inset-0 z-50 flex items-center justify-center bg-foreground/60 p-4"
             x-data x-trap.noscroll="true" x-on:keydown.escape.window="$wire.cancelDeleteSelected()"
             role="dialog" aria-modal="true" aria-labelledby="bulk-delete-title" aria-describedby="bulk-delete-body">
            <div class="dialog-panel elev-3 w-full max-w-md rounded-[12px] bg-card p-6">
                <h2 id="bulk-delete-title" class="font-display text-[22px] font-semibold tracking-[-0.015em] text-foreground">
                    Delete <span class="datum">{{ count($selected) }}</span> {{ Str::plural('bird', count($selected)) }}?
                </h2>
                <p id="bulk-delete-body" class="mt-3 text-[15px] leading-relaxed text-muted-foreground">
                    This removes <strong class="font-semibold text-foreground">{{ implode(', ', $named) }}@if ($more > 0) and <span class="datum">{{ $more }}</span> more @endif</strong>
                    from the active records. Their health, breeding and performance history is kept and the
                    deletion is recorded in the activity log. Birds your role cannot delete are left as they are.
                </p>
                <div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                    <button type="button" wire:click="cancelDeleteSelected" class="btn-secondary">Cancel</button>
                    <button type="button" wire:click="deleteSelected" wire:loading.attr="disabled" wire:target="deleteSelected" class="btn-danger">
                        <span class="btn-spinner" wire:loading wire:target="deleteSelected"></span>
                        Yes, delete {{ count($selected) === 1 ? 'this bird' : 'these birds' }}
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
