{{--
    Farm-wide health record list.

    Opens with the anchor block: the number is the paginator's own total, so it
    costs nothing and always agrees with the rows beneath it. Record type is a
    segmented strip over the existing $recordType filter; the rest of the
    filters stay in the filter bar.

    ONE COPY OF EVERY ROW. Below `md` the table's rows, cells and body drop to
    display:block, so a phone gets a stacked row - the bird first, the record
    and its dates beneath in muted type - and never a sideways-scrolling table.

    NO overflow wrapper around the table, and that is load-bearing: <main> is
    the scroll container, and an overflow-x box between the two would pin the
    sticky header to the wrapper instead of to the page. The columns are sized
    to fit the narrowest desktop content width instead. border-separate is
    there for the same reason - in the collapsed border model a sticky <th>
    leaves its bottom rule behind when it moves.
--}}
@php
    $isFiltered = $this->search !== '' || $this->recordType !== '' || $this->broodcockId !== '' || $this->dateFrom !== '' || $this->dateTo !== '';
    $total = $this->rows->total();

    $typeSegments = [['key' => '', 'label' => 'All types']];
    foreach ($this->recordTypes as $type) {
        $typeSegments[] = ['key' => $type->value, 'label' => $type->label()];
    }

    $th = 'sticky top-0 z-10 h-12 border-b border-border bg-background px-4 text-left align-middle text-[13px] font-semibold text-muted-foreground';
    $td = 'md:table-cell md:border-b md:border-border md:px-4 md:py-3.5 md:align-top';
@endphp
<div>
    <x-anchor-block eyebrow="Manage" title="Health Records"
                    :figure="number_format($total)"
                    :figure-label="$isFiltered ? 'matching your filters' : Str::plural('record', $total).' on file'">
        Vaccinations, medications, dewormings, treatments and check-ups for every bird.

        <x-slot:actions>
            @can('create', \App\Models\HealthRecord::class)
                <a href="{{ route('health.create') }}" wire:navigate class="btn-primary">
                    Add Health Record
                </a>
            @endcan

            @can('viewSchedule', \App\Models\HealthRecord::class)
                <a href="{{ route('health.schedule') }}" wire:navigate class="btn-secondary">
                    Vaccination Schedule
                </a>
            @endcan
        </x-slot:actions>
    </x-anchor-block>

    <div class="mt-8">
        <x-segmented :segments="$typeSegments" :active="$recordType" model="recordType" label="Record type" />
    </div>

    {{-- Every filter is optional; leaving a box blank means "all". The count
         only appears once a filter is actually narrowing the list. --}}
    <x-filter-bar class="mt-6" :active="$isFiltered"
                  clear="clearFilters"
                  :summary="'Showing '.number_format($total).' '.Str::plural('record', $total).'.'">
        <x-slot:search>
            <label for="filter-search" class="sr-only">Search</label>
            <input id="filter-search"
                   type="search"
                   wire:model.live.debounce.400ms="search"
                   class="input"
                   placeholder="Bird name, band number, product or condition">
        </x-slot:search>

        <x-filter-select id="filter-bird" label="Bird" wire:model.live="broodcockId">
            <option value="">All birds</option>
            @foreach ($this->birds as $bird)
                <option value="{{ $bird->id }}">{{ $bird->displayName() }}</option>
            @endforeach
        </x-filter-select>

        {{-- The two dates are one control in two halves, so they stay together
             on a wrap and read as a range rather than as two unrelated fields. --}}
        <div class="flex min-h-11 w-full items-center gap-2 sm:w-auto sm:shrink-0">
            <label for="filter-from" class="label mb-0 shrink-0">From</label>
            <input id="filter-from" type="date" wire:model.live="dateFrom" class="input datum w-full sm:w-auto">
        </div>

        <div class="flex min-h-11 w-full items-center gap-2 sm:w-auto sm:shrink-0">
            <label for="filter-to" class="label mb-0 shrink-0">To</label>
            <input id="filter-to" type="date" wire:model.live="dateTo" class="input datum w-full sm:w-auto">
        </div>

        {{-- The search is debounced and the round trip is long, so without this
             the bar looks dead between a keystroke and its result. --}}
        <x-slot:loading>
            <span wire:loading wire:target="search,recordType,broodcockId,dateFrom,dateTo">Searching&hellip;</span>
        </x-slot:loading>
    </x-filter-bar>

    @if ($this->rows->isEmpty())
        {{-- Empty states say what to do next, never just "No results". --}}
        <x-empty-state title="No health records found."
                       :description="$isFiltered
                            ? 'No record matches your filters. Clear them to see every record.'
                            : 'Nothing has been recorded yet. Log the first vaccination, medication or check-up.'"
                       icon="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z">
            @if ($isFiltered)
                <button type="button" wire:click="clearFilters" class="btn-secondary">Clear filters</button>
            @else
                @can('create', \App\Models\HealthRecord::class)
                    <a href="{{ route('health.create') }}" wire:navigate class="btn-primary">Add Health Record</a>
                @endcan
            @endif
        </x-empty-state>
    @else
        <table class="block w-full border-separate border-spacing-0 text-left md:table">
            <thead class="hidden md:table-header-group">
                <tr>
                    <th scope="col" class="{{ $th }}">Bird</th>

                    {{-- Bird and actions stay plain: the bird sorts by a
                         relationship, not a column, so it cannot be handed to
                         orderBy(). A header that looks pressable and does
                         nothing is worse than one that plainly is not. --}}
                    @foreach (['record_type' => 'Record type', 'checkup_date' => 'Check-up date', 'next_due_date' => 'Next due date'] as $column => $heading)
                        @php
                            $isSorted = $sortBy === $column;
                            $isAscending = $sortDirection === 'asc';
                        @endphp
                        <th scope="col" class="{{ $th }}"
                            aria-sort="{{ $isSorted ? ($isAscending ? 'ascending' : 'descending') : 'none' }}">
                            <button type="button" wire:click="sort('{{ $column }}')"
                                    @class(['-mx-1.5 inline-flex min-h-11 items-center gap-1 rounded-[6px] px-1.5 hover:text-foreground', 'text-foreground' => $isSorted])>
                                {{ $heading }}
                                @if ($isSorted)
                                    <svg class="h-3.5 w-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="{{ $isAscending ? 'm4.5 15.75 7.5-7.5 7.5 7.5' : 'm19.5 8.25-7.5 7.5-7.5-7.5' }}"/>
                                    </svg>
                                    <span class="sr-only">sorted {{ $isAscending ? 'ascending' : 'descending' }}</span>
                                @endif
                            </button>
                        </th>
                    @endforeach

                    <th scope="col" class="{{ $th }} text-right"><span class="sr-only">Actions</span></th>
                </tr>
            </thead>

            <tbody class="block md:table-row-group" wire:loading.remove wire:target="search,recordType,broodcockId,dateFrom,dateTo">
                @foreach ($this->rows as $record)
                    @php
                        $bird = $record->broodcock;
                        $state = $record->scheduleState();
                        // The status washes are the only colour besides a band, and
                        // the word itself carries the state - never hue alone.
                        $stateClasses = match ($state) {
                            'Overdue' => 'badge-alert',
                            'Due soon' => 'badge-warn',
                            'Scheduled' => 'badge-info',
                            default => 'badge-neutral',
                        };
                    @endphp

                    <tr wire:key="record-{{ $record->id }}" class="block border-b border-border py-4 transition-colors hover:bg-muted/60 md:table-row md:border-0 md:py-0">
                        {{-- Bird: the band is the identifier a keeper scans for, the
                             dot its bloodline, and the bloodline is written beside
                             the name so colour is never the only channel. --}}
                        <td class="block {{ $td }}">
                            <a href="{{ route('broodcocks.show', $bird) }}" wire:navigate
                               class="group -my-1 inline-flex min-h-11 items-center gap-2.5">
                                <x-band-dot :bloodline="$bird->bloodline" />
                                <span class="min-w-0">
                                    <span class="datum block text-[15px] font-medium text-foreground group-hover:underline">{{ $bird->displayBand() }}</span>
                                    <span class="block text-[13px] leading-snug text-muted-foreground">{{ $bird->name }}@if ($bird->bloodline) &middot; {{ $bird->bloodline }}@endif</span>
                                </span>
                            </a>
                        </td>

                        <td class="mt-2 block md:mt-0 {{ $td }}">
                            <div class="flex flex-wrap items-center gap-x-2 gap-y-1">
                                <span class="badge {{ $record->record_type->badgeClasses() }}">{{ $record->record_type->label() }}</span>
                                <span class="text-[15px] leading-snug text-foreground">{{ $record->product_name ?: 'Not recorded' }}</span>
                            </div>
                            @if ($record->dosage)
                                <p class="mt-1 text-[13px] text-muted-foreground">Dosage: <span class="datum">{{ $record->dosage }}</span></p>
                            @endif
                            @if ($record->condition)
                                <p class="mt-0.5 text-[13px] text-muted-foreground">Condition: {{ $record->condition }}</p>
                            @endif
                            {{-- Internal remarks. Customers are promised health STATUS,
                                 never the farm's private notes - the Policy decides. --}}
                            @can('viewRemarks', $record)
                                @if ($record->remarks)
                                    <p class="mt-1.5 max-w-[46ch] border-l border-border pl-2 text-[13px] leading-snug text-muted-foreground"><span class="font-medium">Remarks:</span> {{ $record->remarks }}</p>
                                @endif
                            @endcan
                        </td>

                        <td class="mt-3 mr-6 inline-block align-top md:mt-0 md:mr-0 md:whitespace-nowrap {{ $td }}">
                            <span class="block text-[13px] text-muted-foreground md:hidden">Check-up date</span>
                            <span class="datum text-[15px] text-foreground">{{ $record->checkup_date->format('d M Y') }}</span>
                        </td>

                        <td class="mt-3 inline-block align-top md:mt-0 md:whitespace-nowrap {{ $td }}">
                            <span class="block text-[13px] text-muted-foreground md:hidden">Next due date</span>
                            @if ($record->next_due_date)
                                <span class="datum text-[15px] text-foreground">{{ $record->next_due_date->format('d M Y') }}</span>
                                <span class="badge {{ $stateClasses }} ml-1.5 md:ml-0 md:mt-1.5 md:flex md:w-fit">{{ $state }}</span>
                            @else
                                <span class="text-[15px] text-muted-foreground">None</span>
                            @endif
                        </td>

                        <td class="mt-3 block md:mt-0 md:table-cell md:border-b md:border-border md:px-4 md:py-2 md:text-right md:align-top md:whitespace-nowrap">
                            <div class="-ml-3 flex gap-1 md:ml-0 md:justify-end">
                                @can('update', $record)
                                    <a href="{{ route('health.edit', $record) }}" wire:navigate class="btn-quiet">
                                        Edit<span class="sr-only">, {{ $record->record_type->label() }} for {{ $bird->name }} on {{ $record->checkup_date->format('d M Y') }}</span>
                                    </a>
                                @endcan
                                {{-- Quiet, not the filled .btn-danger: a solid crimson
                                     control repeated down every row out-shouts the band
                                     colours. The filled variant is kept for the dialog,
                                     where destroying the record IS the primary action. --}}
                                @can('delete', $record)
                                    <button type="button" wire:click="confirmDelete({{ $record->id }})"
                                            class="btn-quiet text-destructive hover:bg-destructive-bg">
                                        Delete<span class="sr-only">, {{ $record->record_type->label() }} for {{ $bird->name }} on {{ $record->checkup_date->format('d M Y') }}</span>
                                    </button>
                                @endcan
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>

            <x-table-skeleton :cols="5" wire:loading wire:target="search,recordType,broodcockId,dateFrom,dateTo" />
        </table>

        @if ($this->rows->hasPages())
            <div class="mt-6">
                {{ $this->rows->links() }}
            </div>
        @endif
    @endif

    {{-- Destructive actions always confirm, and the dialog names the exact
         bird and date so nobody deletes the wrong bird's vaccination. --}}
    @if ($this->recordPendingDeletion)
        @php
            $pending = $this->recordPendingDeletion;
            $pendingBird = $pending->broodcock->band_number ?: $pending->broodcock->name;
        @endphp

        <div class="dialog-backdrop fixed inset-0 z-50 flex items-center justify-center bg-foreground/60 p-4"
             x-data x-trap.noscroll="true" x-on:keydown.escape.window="$wire.cancelDelete()"
             role="dialog" aria-modal="true" aria-labelledby="delete-dialog-title">
            <div class="dialog-panel elev-3 w-full max-w-md rounded-[12px] bg-card p-6">
                <h2 id="delete-dialog-title" class="font-display text-[22px] font-semibold leading-snug tracking-[-0.015em] text-foreground">
                    Delete the {{ Str::lower($pending->record_type->label()) }} record for
                    <span class="datum">{{ $pendingBird }}</span> on
                    <span class="datum">{{ $pending->checkup_date->format('j M Y') }}</span>?
                </h2>

                <p class="mt-3 text-[15px] leading-relaxed text-muted-foreground">
                    <strong class="font-medium text-foreground">{{ $pending->broodcock->name }}</strong>
                    (Band Number: <span class="datum text-foreground">{{ $pending->broodcock->displayBand() }}</span>),
                    {{ $pending->record_type->label() }}@if ($pending->product_name) &middot; {{ $pending->product_name }}@endif.
                    The record is kept in the farm's history and can be restored by the owner,
                    but it will no longer appear in lists or reports.
                </p>

                <div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                    <button type="button" wire:click="cancelDelete" class="btn-secondary">
                        Cancel
                    </button>
                    <button type="button" wire:click="delete" class="btn-danger" wire:loading.attr="disabled" wire:target="delete">
                        <span class="btn-spinner" wire:loading wire:target="delete"></span>
                        Yes, delete this record
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
