@php
    /*
     * Column visibility is client-side on purpose. It is a per-person viewing
     * preference, not shared state, and routing it through Livewire would put a
     * server round-trip behind a checkbox. localStorage keeps it across sessions
     * without a table, which §9 forbids.
     */
    $optionalColumns = [
        'sex' => 'Sex',
        'bloodline' => 'Bloodline',
        'class' => 'Class',
        'status' => 'Status',
        'age' => 'Age',
    ];

    // The active filter set, rendered as individually removable chips.
    $chips = collect([
        ['key' => 'search', 'label' => 'Search', 'value' => $search],
        ['key' => 'status', 'label' => 'Status', 'value' => $status],
        ['key' => 'class', 'label' => 'Class', 'value' => $class],
        ['key' => 'sex', 'label' => 'Sex', 'value' => $sex],
        ['key' => 'bloodline', 'label' => 'Bloodline', 'value' => $bloodline],
        // 'farm' is the default view, not a filter someone applied, so it is
        // deliberately not chipped - a chip you cannot meaningfully remove is
        // noise. The two non-default choices are.
        ['key' => 'ownership', 'label' => 'Ownership', 'value' => match ($ownership) {
            'outside' => 'Outside birds only',
            'all' => 'Farm and outside birds',
            default => '',
        }],
    ])->filter(fn ($c) => $c['value'] !== '' && $c['value'] !== null);
@endphp

<div
    x-data="{
        cols: JSON.parse(localStorage.getItem('gfms-bc-cols') || 'null') ?? @js(array_fill_keys(array_keys($optionalColumns), true)),
        colsOpen: false,
        filtersOpen: @js($chips->isEmpty()),
        save() { localStorage.setItem('gfms-bc-cols', JSON.stringify(this.cols)); },
    }"
>
    {{-- Page header. Sits in the content column, left-aligned in the canvas -
         never centred, and never inside a max-width wrapper. --}}
    <div class="mb-5 flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="page-title-marked text-[26px] font-semibold leading-[1.2] text-foreground">Broodcocks</h1>
            <p class="mt-1 text-[14px] text-muted-foreground">
                All birds recorded on the farm. Use the search and filters to narrow the list.
            </p>
        </div>

        <div class="flex items-center gap-2">
            {{-- Filters collapse by default. Seven select boxes cost ~290px above
                 the first row of data, and now that the active set is expressed as
                 chips there is nothing to read in the panel when it is closed. --}}
            <button type="button" @click="filtersOpen = ! filtersOpen"
                    class="btn-secondary h-11 min-h-0 px-3 text-[14px]"
                    :aria-expanded="filtersOpen ? 'true' : 'false'">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 3c2.755 0 5.455.232 8.083.678.533.09.917.556.917 1.096v1.044a2.25 2.25 0 0 1-.659 1.591l-5.432 5.432a2.25 2.25 0 0 0-.659 1.591v2.927a2.25 2.25 0 0 1-1.244 2.013L9.75 21v-6.568a2.25 2.25 0 0 0-.659-1.591L3.659 7.409A2.25 2.25 0 0 1 3 5.818V4.774c0-.54.384-1.006.917-1.096A48.32 48.32 0 0 1 12 3Z"/>
                </svg>
                Filters
            </button>

            {{-- Column visibility. Twelve columns is too many for a laptop, and
                 which six matter depends entirely on what you came here to do. --}}
            <div class="relative">
                <button type="button" @click="colsOpen = ! colsOpen"
                        class="btn-secondary h-11 min-h-0 px-3 text-[14px]"
                        :aria-expanded="colsOpen ? 'true' : 'false'">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"/>
                    </svg>
                    Columns
                </button>

                <div x-show="colsOpen" x-cloak @click.outside="colsOpen = false"
                     class="popover enter-pop absolute right-0 z-30 mt-2 w-56 p-2">
                    <p class="px-2 pb-1.5 pt-1 text-[11px] font-semibold uppercase tracking-[0.07em] text-muted-foreground">
                        Show columns
                    </p>
                    @foreach ($optionalColumns as $key => $label)
                        <label class="flex min-h-11 cursor-pointer items-center gap-2.5 rounded-[var(--radius-sm)] px-2 text-[14px] text-foreground hover:bg-muted">
                            <input type="checkbox" class="h-4 w-4 accent-primary"
                                   x-model="cols['{{ $key }}']" @change="save()">
                            {{ $label }}
                        </label>
                    @endforeach
                </div>
            </div>

            @can('create', App\Models\Broodcock::class)
                <a href="{{ route('broodcocks.create') }}" wire:navigate class="btn-primary h-11 min-h-0 px-3.5 text-[14px]">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                    </svg>
                    Add Broodcock
                </a>
            @endcan
        </div>
    </div>

    {{-- Active filters as removable chips. Previously the only way to know a
         filter was on was to re-read seven select boxes. --}}
    @if ($chips->isNotEmpty())
        <div class="mb-4 flex flex-wrap items-center gap-2">
            @foreach ($chips as $chip)
                <span class="inline-flex items-center gap-1.5 rounded-full border border-border bg-card py-1 pl-2.5 pr-1 text-[13px] text-foreground">
                    <span class="text-muted-foreground">{{ $chip['label'] }}:</span>
                    <span class="datum">{{ $chip['value'] }}</span>
                    <button type="button" wire:click="clearFilter('{{ $chip['key'] }}')"
                            class="inline-flex h-6 w-6 items-center justify-center rounded-full text-muted-foreground hover:bg-muted hover:text-foreground"
                            aria-label="Remove {{ $chip['label'] }} filter">
                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </span>
            @endforeach
            <button type="button" wire:click="clearFilters" class="btn-quiet h-8 min-h-0 px-2 text-[13px]">
                Clear all
            </button>
        </div>
    @endif

    {{-- Search and filters. One row: search takes the space that is left, the
         six selects shrink to their own width and wrap when the row runs out
         of room. Each field name sits inside its control's border rather than
         stacked above it - see x-filter-select. --}}
    <x-filter-bar x-show="filtersOpen" x-cloak x-collapse
                  :active="$this->hasActiveFilters()"
                  clear="clearFilters"
                  :summary="'Showing '.number_format($this->broodcocks->total()).' '.Str::plural('bird', $this->broodcocks->total()).' matching your filters.'">
        <x-slot:search>
            <label for="search" class="sr-only">Search</label>
            <input
                id="search"
                type="search"
                wire:model.live.debounce.300ms="search"
                placeholder="Name, band number or bloodline"
                class="input"
            >
        </x-slot:search>

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

        {{-- Ownership.

             Defaults to the farm's own birds. Outside parents are created
             automatically by the breeding form - a borrowed hen has to be a
             real row or the pedigree loses the whole branch above her - so
             this list would otherwise fill up with birds the farm does not
             own and cannot act on. They stay one selection away rather than
             hidden, because a keeper still needs to correct their details. --}}
        <x-filter-select id="ownership" label="Ownership" wire:model.live="ownership">
            <option value="farm">This farm's birds</option>
            <option value="outside">Outside birds only</option>
            <option value="all">Both</option>
        </x-filter-select>
    </x-filter-bar>

    {{-- Results --}}
    @if ($this->broodcocks->isEmpty())
        <div class="card p-12 text-center">
            <svg class="mx-auto h-12 w-12 text-muted-foreground" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25"/>
            </svg>

            @if ($this->hasActiveFilters())
                <h3 class="mt-4 text-[21px] font-semibold tracking-[-0.01em] leading-[1.25] text-foreground">No birds match your filters</h3>
                <p class="mt-3 text-[17px] leading-relaxed text-muted-foreground">Try removing a filter or searching for something else.</p>
                <button type="button" wire:click="clearFilters" class="btn-secondary mt-6">Clear filters</button>
            @else
                <h3 class="mt-4 text-[21px] font-semibold tracking-[-0.01em] leading-[1.25] text-foreground">No broodcocks recorded yet</h3>
                <p class="mt-3 text-[17px] leading-relaxed text-muted-foreground">
                    Start by adding your first bird. You will be able to record its health,
                    breeding and performance afterwards.
                </p>
                @can('create', App\Models\Broodcock::class)
                    <a href="{{ route('broodcocks.create') }}" wire:navigate class="btn-primary mt-6">Add your first broodcock</a>
                @endcan
            @endif
        </div>
    @else
        {{-- Mobile: cards. Farm staff are mostly on phones. --}}
        <div class="space-y-3 sm:hidden">
            @foreach ($this->broodcocks as $bird)
                <a href="{{ route('broodcocks.show', $bird) }}" wire:navigate class="card flex gap-4 p-4">
                    <x-photo-thumb :photo="$bird->primaryPhoto" :alt="$bird->name"
                                   class="h-16 w-16 shrink-0 rounded-lg" />
                    <div class="min-w-0 flex-1">
                        <p class="truncate font-semibold text-foreground">{{ $bird->name }}</p>
                        <p class="truncate text-sm text-muted-foreground">{{ $bird->displayBand() }}</p>
                        <div class="mt-2 flex flex-wrap gap-1">
                            <span class="badge {{ $bird->status->badgeClasses() }}">{{ $bird->status->label() }}</span>
                            <span class="badge {{ $bird->class->badgeClasses() }}">{{ $bird->class->label() }}</span>
                        </div>
                    </div>
                </a>
            @endforeach
        </div>

        {{-- Desktop: table --}}
        <div class="card hidden overflow-hidden sm:block">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-border">
                    {{-- The Photo column is gone. It spent the leftmost and most valuable
                         column in the table on the word "None" for nearly every bird,
                         because a working farm photographs very few of them. The band tag
                         takes that position instead: it is the identifier a keeper
                         actually scans for, and it carries the bloodline as colour so a
                         thirty-row table can be read by bloodline without reading it. --}}
                    {{-- Sticky header. The content region is the scroll container, so a
                         plain `sticky top-0` here pins to it rather than to the viewport
                         and the column names stay put through a long list. --}}
                    <thead class="sticky top-0 z-10 bg-muted">
                        <tr class="border-b border-border">
                            <th scope="col" class="w-10 px-4 py-2.5">
                                @php $pageIds = $this->broodcocks->pluck('id')->all(); @endphp
                                <input type="checkbox" class="h-4 w-4 accent-primary"
                                       wire:click="toggleSelectPage"
                                       @checked($pageIds !== [] && ! array_diff($pageIds, $selected))
                                       aria-label="Select all birds on this page">
                            </th>
                            @foreach ([
                                'band_number' => 'Band Number',
                                'name' => 'Name',
                                'sex' => 'Sex',
                                'bloodline' => 'Bloodline',
                                'class' => 'Class',
                                'status' => 'Status',
                                'date_hatched' => 'Age',
                            ] as $column => $heading)
                                @php
                                    // Maps the sort key to the client-side visibility key so a
                                    // hidden column hides its header too.
                                    $visKey = ['sex' => 'sex', 'bloodline' => 'bloodline',
                                               'class' => 'class', 'status' => 'status', 'date_hatched' => 'age'][$column] ?? null;
                                @endphp
                                <th scope="col"
                                    @if ($visKey) x-show="cols.{{ $visKey }}" @endif
                                    class="px-4 py-2.5 text-left text-[11px] font-medium uppercase tracking-[0.06em] text-muted-foreground">
                                    @if (in_array($column, ['band_number','name','bloodline','class','status','date_hatched'], true))
                                        {{-- `uppercase` is repeated here on purpose. Tailwind's
                                             preflight sets `button { text-transform: none }`, so a
                                             sort button silently drops the transform from its own
                                             <th> - which is why Sex (the one unsortable column)
                                             was the only header rendering in caps. --}}
                                        <button type="button" wire:click="sort('{{ $column }}')" class="inline-flex items-center gap-1 uppercase tracking-[0.06em] hover:text-foreground">
                                            {{ $heading }}
                                            @if ($sortBy === $column)
                                                <span aria-hidden="true">{{ $sortDirection === 'asc' ? '▲' : '▼' }}</span>
                                                <span class="sr-only">sorted {{ $sortDirection === 'asc' ? 'ascending' : 'descending' }}</span>
                                            @endif
                                        </button>
                                    @else
                                        {{ $heading }}
                                    @endif
                                </th>
                            @endforeach
                            <th scope="col" class="px-4 py-2.5 text-right text-[11px] font-medium uppercase tracking-[0.06em] text-muted-foreground">
                                <span class="sr-only">Actions</span>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border bg-card">
                        @foreach ($this->broodcocks as $bird)
                            {{-- `group` + .row-hover: the row tints and reveals its actions
                                 together. focus-within is included so the actions appear for
                                 keyboard users too, not just on a mouse hover. --}}
                            <tr wire:key="bc-{{ $bird->id }}"
                                @class(['group row-hover', 'is-selected' => in_array($bird->id, $selected, true)])>
                                <td class="w-10 px-4 py-2.5">
                                    <input type="checkbox" value="{{ $bird->id }}" wire:model.live="selected"
                                           class="h-4 w-4 accent-primary"
                                           aria-label="Select {{ $bird->name }}">
                                </td>
                                <td class="whitespace-nowrap px-4 py-2.5">
                                    <x-band-tag :bloodline="$bird->bloodline" :band="$bird->band_number" size="xs" />
                                </td>
                                <td class="whitespace-nowrap px-4 py-2.5 text-[14px] font-medium text-foreground">
                                    <a href="{{ route('broodcocks.show', $bird) }}" wire:navigate class="hover:text-primary hover:underline">
                                        {{ $bird->name }}
                                    </a>
                                    {{-- Only when the list can contain both. Badging every
                                         row in the default view would label the ordinary
                                         case, which is the opposite of what a badge is for. --}}
                                    @if ($bird->is_external && $ownership !== 'outside')
                                        <span class="badge badge-neutral ml-1.5" title="Belongs to another farm; recorded so the pedigree stays complete">Outside</span>
                                    @endif
                                </td>
                                <td x-show="cols.sex" class="whitespace-nowrap px-4 py-2.5 text-[14px] text-muted-foreground">{{ $bird->sex->label() }}</td>
                                <td x-show="cols.bloodline" class="whitespace-nowrap px-4 py-2.5 text-[14px] text-muted-foreground">{{ $bird->bloodline ?? 'Not recorded' }}</td>
                                <td x-show="cols.class" class="whitespace-nowrap px-4 py-2.5">
                                    <span class="badge {{ $bird->class->badgeClasses() }}">{{ $bird->class->label() }}</span>
                                </td>
                                <td x-show="cols.status" class="whitespace-nowrap px-4 py-2.5">
                                    <span class="badge {{ $bird->status->badgeClasses() }}">{{ $bird->status->label() }}</span>
                                </td>
                                <td x-show="cols.age" class="datum whitespace-nowrap px-4 py-2.5 text-[13px] text-muted-foreground">
                                    {{ $bird->ageLabel() ?? 'Unknown' }}
                                </td>
                                <td class="whitespace-nowrap px-4 py-2.5 text-right">
                                    <span class="row-actions inline-flex items-center gap-1">
                                        <a href="{{ route('broodcocks.show', $bird) }}" wire:navigate
                                           class="btn-quiet h-8 min-h-0 px-2 text-[13px]">
                                            View<span class="sr-only">, {{ $bird->name }}</span>
                                        </a>
                                        @can('update', $bird)
                                            <a href="{{ route('broodcocks.edit', $bird) }}" wire:navigate
                                               class="btn-quiet h-8 min-h-0 px-2 text-[13px]">
                                                Edit<span class="sr-only">, {{ $bird->name }}</span>
                                            </a>
                                        @endcan
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-4">
            {{ $this->broodcocks->links() }}
        </div>
    @endif

    {{--
        Floating selection bar.

        Fixed to the bottom of the content region rather than pushed into the
        page flow, so selecting a row never reflows the table you are reading.
        It only appears when there is a selection, and it only offers actions the
        Policy already permits - the count is server-side, so it survives
        pagination and a filter change.
    --}}
    @if (count($selected) > 0)
        <div class="fixed inset-x-0 bottom-0 z-40 flex justify-center px-4 pb-5 lg:pl-64"
             role="status" aria-live="polite">
            <div class="elev-3 enter-pop flex items-center gap-3 rounded-full border border-border bg-card py-2 pl-4 pr-2">
                <span class="text-[14px] text-foreground">
                    <span class="datum font-medium">{{ count($selected) }}</span>
                    {{ Str::plural('bird', count($selected)) }} selected
                </span>

                @if ($this->canDeleteSelection)
                    <button type="button"
                            wire:click="deleteSelected"
                            wire:confirm="Remove {{ count($selected) }} {{ Str::plural('bird', count($selected)) }} from the active records? Their health, breeding and performance history is kept."
                            class="btn-secondary h-9 min-h-0 px-3 text-[13px] text-destructive hover:border-destructive">
                        Delete selected
                    </button>
                @endif

                <button type="button" wire:click="clearSelection"
                        class="inline-flex h-9 w-9 items-center justify-center rounded-full text-muted-foreground hover:bg-muted hover:text-foreground"
                        aria-label="Clear selection">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </div>
    @endif
</div>
