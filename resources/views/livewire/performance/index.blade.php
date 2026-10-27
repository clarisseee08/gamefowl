@php
    $records = $this->records;
    $total = $records->total();
    $filtered = $this->hasActiveFilters();

    // The event-type filter as a segmented strip: four kinds of event is a
    // short, fixed set, and seeing all of them before choosing beats a select
    // that hides three. No counts - each would be another aggregate query, and
    // a strip that reads "Derby" makes no claim it cannot back.
    $eventSegments = [['key' => '', 'label' => 'All events']];
    foreach ($this->eventTypeOptions() as $option) {
        $eventSegments[] = ['key' => $option->value, 'label' => $option->label()];
    }

    // Table columns. `sort` is the key sort() accepts, or null for a plain
    // header; `cell` hides the less load-bearing columns where there is no
    // room, so the table never has to scroll sideways.
    $columns = [
        ['label' => 'Bird', 'sort' => null, 'align' => 'left', 'cell' => ''],
        ['label' => 'Date', 'sort' => 'event_date', 'align' => 'left', 'cell' => ''],
        ['label' => 'Type of event', 'sort' => 'event_type', 'align' => 'left', 'cell' => ''],
        ['label' => 'Result', 'sort' => 'result', 'align' => 'left', 'cell' => ''],
        ['label' => 'Weight', 'sort' => 'weight', 'align' => 'right', 'cell' => ''],
        ['label' => 'Duration', 'sort' => null, 'align' => 'right', 'cell' => 'hidden xl:table-cell'],
        ['label' => 'Rating', 'sort' => 'rating', 'align' => 'left', 'cell' => 'hidden xl:table-cell'],
        ['label' => 'Recorded by', 'sort' => null, 'align' => 'left', 'cell' => 'hidden 2xl:table-cell'],
    ];

    $loadingTargets = 'search,eventType,result,from,to,sort,gotoPage,nextPage,previousPage';
@endphp

<div>
    <x-anchor-block eyebrow="Manage" title="Performance Records"
                    :figure="number_format($total)"
                    :figure-label="Str::plural('record', $total).($filtered ? ' matching your filters' : ' on file')">
        Every sparring session, derby, conditioning session and weigh-in recorded on the farm.

        @can('create', App\Models\PerformanceRecord::class)
            <x-slot:actions>
                <a href="{{ route('performance.create') }}" wire:navigate class="btn-primary">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                    </svg>
                    Add Performance Record
                </a>
            </x-slot:actions>
        @endcan
    </x-anchor-block>

    {{-- Livewire re-renders only this component, so the confirmation of a
         delete lives here rather than in the layout's session flash. --}}
    @if ($statusMessage !== '')
        <div class="mt-6 flex items-start justify-between gap-4 rounded-[8px] bg-success-bg px-4 py-3" role="status">
            <p class="text-[15px] leading-snug text-foreground">{{ $statusMessage }}</p>
            <button type="button" wire:click="dismissStatus"
                    class="-my-2 inline-flex min-h-11 shrink-0 items-center px-1 text-[14px] font-medium text-foreground underline-offset-4 hover:underline">
                Dismiss
            </button>
        </div>
    @endif

    {{-- Kind of event: the whole set visible before one is chosen. --}}
    <div class="mt-8 overflow-x-auto" data-scroll-x>
        <x-segmented :segments="$eventSegments" :active="$eventType" model="eventType" label="Type of event" />
    </div>

    {{-- Search, result and date range. --}}
    <x-filter-bar class="mt-5" :active="$filtered"
                  clear="clearFilters"
                  :summary="'Showing '.number_format($total).' '.Str::plural('record', $total).' matching your filters.'">
        <x-slot:search>
            <label for="search" class="sr-only">Search by Bird</label>
            <input id="search" type="search" wire:model.live.debounce.300ms="search"
                   placeholder="Bird name, band number, breed or bloodline" class="input">
        </x-slot:search>

        <x-filter-select id="result" label="Result" wire:model.live="result">
            <option value="">All results</option>
            @foreach ($this->resultOptions() as $option)
                <option value="{{ $option->value }}">{{ $option->label() }}</option>
            @endforeach
        </x-filter-select>

        {{-- The two dates are one control in two halves, so they stay together
             on a wrap and read as a range rather than two unrelated fields. --}}
        <div class="flex min-h-11 w-full items-center gap-2 sm:w-auto sm:shrink-0">
            <label for="from" class="label mb-0 shrink-0">From Date</label>
            <input id="from" type="date" wire:model.live="from" class="input datum w-full sm:w-auto">
        </div>

        <div class="flex min-h-11 w-full items-center gap-2 sm:w-auto sm:shrink-0">
            <label for="to" class="label mb-0 shrink-0">To Date</label>
            <input id="to" type="date" wire:model.live="to" class="input datum w-full sm:w-auto">
        </div>

        <x-slot:loading>
            <span wire:loading wire:target="search,eventType,result,from,to">Searching&hellip;</span>
        </x-slot:loading>
    </x-filter-bar>

    @if ($records->isEmpty())
        @if ($filtered)
            <x-empty-state title="No records match your filters"
                           description="Try a different date range, or clear the filters to see everything."
                           icon="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z">
                <button type="button" wire:click="clearFilters" class="btn-secondary">Clear filters</button>
            </x-empty-state>
        @else
            <x-empty-state title="No performance records yet"
                           description="Record a sparring session, derby, conditioning session or weigh-in and it will appear here and on the bird's own timeline."
                           icon="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z">
                @can('create', App\Models\PerformanceRecord::class)
                    <a href="{{ route('performance.create') }}" wire:navigate class="btn-primary">
                        Add your first performance record
                    </a>
                @endcan
            </x-empty-state>
        @endif
    @else
        {{-- Phones and small tablets: one block per record - the bird on the
             first line, what happened on the second. Farm staff are mostly on
             phones, and a nine-column table there is a sideways scrollbar. --}}
        <ul class="divide-y divide-border border-y border-border md:hidden"
            wire:loading.class="opacity-60" wire:target="{{ $loadingTargets }}">
            @foreach ($records as $record)
                <li wire:key="perf-row-{{ $record->id }}" class="py-4">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            @if ($record->broodcock !== null)
                                <a href="{{ route('broodcocks.show', $record->broodcock) }}" wire:navigate
                                   class="inline-flex min-h-11 max-w-full items-center gap-2 text-[16px] font-medium text-foreground hover:underline">
                                    <x-band-tag :bloodline="$record->broodcock->bloodline"
                                                :band="$record->broodcock->band_number" size="xs" />
                                    <span class="truncate">{{ $record->broodcock->name }}</span>
                                </a>
                            @else
                                <p class="flex min-h-11 items-center text-[16px] text-muted-foreground">Bird removed</p>
                            @endif
                        </div>
                        <p class="datum shrink-0 pt-3 text-[14px] text-muted-foreground">{{ $record->event_date->format('d M Y') }}</p>
                    </div>

                    <div class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-2 text-[14px] text-muted-foreground">
                        <span class="badge {{ $record->event_type->badgeClasses() }}">{{ $record->event_type->label() }}</span>
                        @if ($record->result->countsTowardRecord())
                            <span class="badge {{ $record->result->badgeClasses() }}">{{ $record->result->label() }}</span>
                        @endif
                        <span @class(['datum' => $record->weight !== null])>{{ $record->weight !== null ? $record->weight.' kg' : 'Not recorded' }}</span>
                        <span aria-hidden="true">&middot;</span>
                        <span @class(['datum' => $record->durationLabel() !== null])>{{ $record->durationLabel() ?? 'Not recorded' }}</span>
                        <span aria-hidden="true">&middot;</span>
                        <span class="datum">{{ $record->rating !== null ? $record->rating.' of 5' : 'Not rated' }}</span>
                    </div>

                    @canany(['update', 'delete'], $record)
                        <div class="mt-2 flex flex-wrap items-center gap-x-6">
                            @can('update', $record)
                                <a href="{{ route('performance.edit', $record) }}" wire:navigate
                                   class="inline-flex min-h-11 items-center text-[15px] font-medium text-primary hover:underline">
                                    Edit<span class="sr-only">, {{ $record->event_type->label() }} record</span>
                                </a>
                            @endcan
                            @can('delete', $record)
                                <button type="button" wire:click="confirmDelete({{ $record->id }})"
                                        class="inline-flex min-h-11 items-center text-[15px] font-medium text-destructive hover:underline">
                                    Delete<span class="sr-only">, {{ $record->event_type->label() }} record</span>
                                </button>
                            @endcan
                        </div>
                    @endcanany
                </li>
            @endforeach
        </ul>

        {{-- Wider screens: the ledger. No horizontal scroll wrapper - that would
             make its own scroll box and stop the header sticking - so the
             least load-bearing columns drop out below xl and 2xl instead. --}}
        <table class="hidden w-full md:table">
            <thead>
                <tr class="border-b border-border">
                    @foreach ($columns as $column)
                        @php
                            $isSorted = $column['sort'] !== null && $sortBy === $column['sort'];
                            $ariaSort = $isSorted ? ($sortDirection === 'asc' ? 'ascending' : 'descending') : 'none';
                        @endphp
                        <th scope="col"
                            @if ($column['sort'] !== null) aria-sort="{{ $ariaSort }}" @endif
                            @class([
                                'sticky top-0 z-10 bg-background px-3 py-2 text-[13px] font-semibold text-muted-foreground whitespace-nowrap',
                                'text-right' => $column['align'] === 'right',
                                'text-left' => $column['align'] !== 'right',
                                $column['cell'],
                            ])>
                            @if ($column['sort'] !== null)
                                <button type="button" wire:click="sort('{{ $column['sort'] }}')"
                                        @class([
                                            'inline-flex items-center gap-1 hover:text-foreground',
                                            'flex-row-reverse' => $column['align'] === 'right',
                                            'text-foreground' => $isSorted,
                                        ])>
                                    {{ $column['label'] }}
                                    @if ($isSorted)
                                        <svg class="h-3.5 w-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="{{ $sortDirection === 'asc' ? 'm4.5 15.75 7.5-7.5 7.5 7.5' : 'm19.5 8.25-7.5 7.5-7.5-7.5' }}"/>
                                        </svg>
                                        <span class="sr-only">sorted {{ $sortDirection === 'asc' ? 'ascending' : 'descending' }}</span>
                                    @endif
                                </button>
                            @else
                                {{ $column['label'] }}
                            @endif
                        </th>
                    @endforeach
                    <th scope="col" class="sticky top-0 z-10 bg-background px-3 py-2 text-right">
                        <span class="sr-only">Actions</span>
                    </th>
                </tr>
            </thead>

            <tbody class="divide-y divide-border" wire:loading.remove wire:target="{{ $loadingTargets }}">
                @foreach ($records as $record)
                    <tr wire:key="perf-tr-{{ $record->id }}" class="hover:bg-muted/60">
                        <td class="whitespace-nowrap px-3 py-2 text-[15px] font-medium text-foreground">
                            @if ($record->broodcock !== null)
                                {{-- Name only: displayName() appends the band in
                                     parentheses, which the tag already shows. --}}
                                <a href="{{ route('broodcocks.show', $record->broodcock) }}" wire:navigate
                                   class="inline-flex min-h-11 items-center gap-2 hover:underline">
                                    <x-band-tag :bloodline="$record->broodcock->bloodline"
                                                :band="$record->broodcock->band_number" size="xs" />
                                    {{ $record->broodcock->name }}
                                </a>
                            @else
                                <span class="font-normal text-muted-foreground">Bird removed</span>
                            @endif
                        </td>
                        <td class="datum whitespace-nowrap px-3 py-2 text-[15px] text-foreground">
                            {{ $record->event_date->format('d M Y') }}
                        </td>
                        <td class="whitespace-nowrap px-3 py-2">
                            <span class="badge {{ $record->event_type->badgeClasses() }}">{{ $record->event_type->label() }}</span>
                        </td>
                        <td class="whitespace-nowrap px-3 py-2">
                            <span class="badge {{ $record->result->badgeClasses() }}">{{ $record->result->label() }}</span>
                        </td>
                        <td @class([
                            'whitespace-nowrap px-3 py-2 text-right text-[15px]',
                            'datum text-foreground' => $record->weight !== null,
                            'text-muted-foreground' => $record->weight === null,
                        ])>
                            {{ $record->weight !== null ? $record->weight.' kg' : 'Not recorded' }}
                        </td>
                        <td @class([
                            'hidden whitespace-nowrap px-3 py-2 text-right text-[15px] xl:table-cell',
                            'datum text-foreground' => $record->durationLabel() !== null,
                            'text-muted-foreground' => $record->durationLabel() === null,
                        ])>
                            {{ $record->durationLabel() ?? 'Not recorded' }}
                        </td>
                        <td class="hidden whitespace-nowrap px-3 py-2 xl:table-cell">
                            @if ($record->rating === null)
                                <span class="text-[14px] text-muted-foreground">Not rated</span>
                            @else
                                {{-- Ink stars, not amber: colour in this system means
                                     bloodline, so a rating is drawn with fill and
                                     outline instead of hue. --}}
                                <span class="inline-flex items-center gap-0.5" role="img"
                                      aria-label="{{ $record->rating }} out of 5 stars">
                                    @for ($star = 1; $star <= 5; $star++)
                                        <svg class="h-4 w-4 {{ $star <= $record->rating ? 'text-foreground' : 'text-muted-foreground' }}"
                                             viewBox="0 0 20 20" aria-hidden="true"
                                             fill="{{ $star <= $record->rating ? 'currentColor' : 'none' }}"
                                             stroke="currentColor"
                                             stroke-width="{{ $star <= $record->rating ? '0' : '1.25' }}">
                                            <path stroke-linejoin="round" d="M10 1.5l2.6 5.27 5.82.85-4.21 4.1.99 5.79L10 14.78l-5.2 2.73.99-5.79-4.21-4.1 5.82-.85L10 1.5z"/>
                                        </svg>
                                    @endfor
                                </span>
                            @endif
                        </td>
                        <td class="hidden max-w-[11rem] truncate px-3 py-2 text-[15px] text-muted-foreground 2xl:table-cell"
                            title="{{ $record->recordedBy?->full_name }}">
                            {{ $record->recordedBy?->full_name ?? 'Not recorded' }}
                        </td>
                        <td class="whitespace-nowrap px-3 py-2 text-right">
                            <div class="flex items-center justify-end gap-5">
                                @can('update', $record)
                                    <a href="{{ route('performance.edit', $record) }}" wire:navigate
                                       class="inline-flex min-h-11 items-center text-[15px] font-medium text-primary hover:underline">
                                        Edit<span class="sr-only">, {{ $record->event_type->label() }} record</span>
                                    </a>
                                @endcan
                                @can('delete', $record)
                                    <button type="button" wire:click="confirmDelete({{ $record->id }})"
                                            class="inline-flex min-h-11 items-center text-[15px] font-medium text-destructive hover:underline">
                                        Delete<span class="sr-only">, {{ $record->event_type->label() }} record</span>
                                    </button>
                                @endcan
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>

            <x-table-skeleton :cols="6" wire:loading wire:target="{{ $loadingTargets }}" />
        </table>

        <div class="mt-6 border-t border-border pt-4">
            {{ $records->links() }}
        </div>
    @endif

    {{-- Delete confirmation. Names the exact record - the bird, the kind of
         event and the date - so nobody deletes the wrong one by muscle memory. --}}
    @if ($this->recordPendingDeletion !== null)
        @php($pending = $this->recordPendingDeletion)
        <div class="dialog-backdrop fixed inset-0 z-50 flex items-center justify-center bg-foreground/60 p-4"
             x-data x-trap.noscroll="true" @keydown.escape.window="$el.querySelector('.btn-secondary')?.click()"
             role="dialog" aria-modal="true" aria-labelledby="delete-dialog-title" aria-describedby="delete-dialog-body">
            <div class="dialog-panel elev-3 w-full max-w-md rounded-[12px] bg-card p-6">
                <h2 id="delete-dialog-title" class="font-display text-[22px] font-semibold leading-[1.2] tracking-[-0.015em] text-foreground">
                    Delete the {{ $pending->event_type->label() }} record for {{ $pending->broodcock?->displayName() ?? 'this bird' }}?
                </h2>

                <div id="delete-dialog-body">
                    <p class="mt-3 text-[15px] leading-relaxed text-muted-foreground">
                        The record dated <strong class="datum font-medium text-foreground">{{ $pending->event_date->format('d M Y') }}</strong>
                        will be removed from the bird's timeline and will stop counting towards its win rate.
                    </p>
                    <p class="mt-2 text-[15px] leading-relaxed text-muted-foreground">
                        The farm owner can restore it later if this was a mistake.
                    </p>
                </div>

                <div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                    <button type="button" wire:click="cancelDelete" class="btn-secondary">Cancel</button>
                    <button type="button" wire:click="delete" class="btn-danger" wire:loading.attr="disabled" wire:target="delete">
                        <span class="btn-spinner" wire:loading wire:target="delete"></span>
                        Yes, delete this record
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
