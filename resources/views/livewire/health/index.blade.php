{{--
    Farm-wide health record list.

    Console surface: 15px body, 7:1 ink, 44px targets. The table collapses to
    stacked cards below `sm:` using table-display utilities rather than a
    duplicated card markup block, so there is exactly one copy of every row.

    Every date, dose and band number is `.datum` - mono + tabular-nums - so the
    date column reads as a column and not as a ragged list.
--}}
<div>
    @if ($statusMessage)
        <div class="mb-8 flex items-start justify-between gap-4 rounded-[4px] border border-border bg-success-bg px-4 py-3" role="status">
            <p class="text-[15px] leading-snug text-success">{{ $statusMessage }}</p>
            <button type="button" wire:click="dismissStatus"
                    class="-my-2 shrink-0 text-[13px] font-medium text-success underline underline-offset-2">
                Dismiss
            </button>
        </div>
    @endif

    <div class="mb-8 border-b border-border pb-6 sm:flex sm:items-end sm:justify-between sm:gap-8">
        <div>
            <h1 class="page-title-marked text-[32px] font-semibold leading-[1.15] tracking-[-0.02em] text-foreground">Health Records</h1>
            <p class="mt-2 max-w-[65ch] text-[15px] leading-relaxed text-muted-foreground">
                Vaccinations, medications, dewormings, treatments and check-ups for every bird.
            </p>
        </div>

        <div class="mt-5 flex flex-wrap gap-3 sm:mt-0 sm:shrink-0">
            @can('viewSchedule', \App\Models\HealthRecord::class)
                <a href="{{ route('health.schedule') }}" wire:navigate class="btn-secondary">
                    Vaccination Schedule
                </a>
            @endcan

            @can('create', \App\Models\HealthRecord::class)
                <a href="{{ route('health.create') }}" wire:navigate class="btn-primary">
                    Add Health Record
                </a>
            @endcan
        </div>
    </div>

    {{-- Filters. Every one is optional; leaving a box blank means "all".
         One row: search takes the space that is left, every other control
         shrinks to its own width, and the count only appears once a filter is
         actually narrowing the list. --}}
    <x-filter-bar :active="$this->search !== '' || $this->recordType !== '' || $this->broodcockId !== '' || $this->dateFrom !== '' || $this->dateTo !== ''"
                  clear="clearFilters"
                  :summary="'Showing '.number_format($this->rows->total()).' '.Str::plural('record', $this->rows->total()).'.'">
        <x-slot:search>
            <label for="filter-search" class="sr-only">Search</label>
            <input id="filter-search"
                   type="search"
                   wire:model.live.debounce.400ms="search"
                   class="input"
                   placeholder="Bird name, band number, product or condition">
        </x-slot:search>

        <x-filter-select id="filter-type" label="Record Type" wire:model.live="recordType">
            <option value="">All types</option>
            @foreach ($this->recordTypes as $type)
                <option value="{{ $type->value }}">{{ $type->label() }}</option>
            @endforeach
        </x-filter-select>

        <x-filter-select id="filter-bird" label="Bird" wire:model.live="broodcockId">
            <option value="">All birds</option>
            @foreach ($this->birds as $bird)
                <option value="{{ $bird->id }}">{{ $bird->displayName() }}</option>
            @endforeach
        </x-filter-select>

        {{-- The two dates are one control in two halves, so they stay together
             on a wrap and read as a range rather than as two unrelated fields. --}}
        <div class="flex min-h-11 w-full items-center gap-2 sm:w-auto sm:shrink-0">
            <label for="filter-from" class="label shrink-0">From</label>
            <input id="filter-from" type="date" wire:model.live="dateFrom" class="input datum w-full sm:w-auto">
        </div>

        <div class="flex min-h-11 w-full items-center gap-2 sm:w-auto sm:shrink-0">
            <label for="filter-to" class="label shrink-0">To</label>
            <input id="filter-to" type="date" wire:model.live="dateTo" class="input datum w-full sm:w-auto">
        </div>

        {{-- The search is debounced by 400ms, so a keystroke and its result are
             most of a second apart. Without this the bar looks unresponsive in
             between, and a keeper types the query again. --}}
        <p class="text-[13px] text-muted-foreground" wire:loading wire:target="search,recordType,broodcockId,dateFrom,dateTo">
            Searching&hellip;
        </p>
    </x-filter-bar>

    <div class="card overflow-hidden">
        @if ($this->rows->isEmpty())
            {{-- Empty states say what to do next, never just "No results". --}}
            <div class="px-6 py-16 text-center">
                <p class="text-[18px] font-medium text-foreground">No health records found.</p>
                <p class="mx-auto mt-2 max-w-[52ch] text-[15px] leading-relaxed text-muted-foreground">
                    @if ($this->search !== '' || $this->recordType !== '' || $this->broodcockId !== '' || $this->dateFrom !== '' || $this->dateTo !== '')
                        No record matches your filters. Try clearing them to see every record.
                    @else
                        Nothing has been recorded yet. Click &ldquo;Add Health Record&rdquo; to log the
                        first vaccination, medication or check-up.
                    @endif
                </p>
                <div class="mt-6 flex flex-wrap justify-center gap-3">
                    <button type="button" wire:click="clearFilters" class="btn-secondary">Clear filters</button>
                    @can('create', \App\Models\HealthRecord::class)
                        <a href="{{ route('health.create') }}" wire:navigate class="btn-primary">Add Health Record</a>
                    @endcan
                </div>
            </div>
        @else
            {{-- The card clips, so a table wider than the page scrolls inside its
                 own container rather than losing its last column. --}}
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead class="hidden bg-muted sm:table-header-group">
                        <tr class="border-b border-border">
                            <th scope="col" class="px-4 py-2.5 text-[11px] font-medium uppercase tracking-[0.06em] text-muted-foreground">Bird</th>
                            <th scope="col" class="px-4 py-2.5 text-[11px] font-medium uppercase tracking-[0.06em] text-muted-foreground">Record Type</th>
                            <th scope="col" class="px-4 py-2.5 text-[11px] font-medium uppercase tracking-[0.06em] text-muted-foreground">Product</th>
                            <th scope="col" class="px-4 py-2.5 text-[11px] font-medium uppercase tracking-[0.06em] text-muted-foreground">Check-up Date</th>
                            <th scope="col" class="px-4 py-2.5 text-[11px] font-medium uppercase tracking-[0.06em] text-muted-foreground">Next Due Date</th>
                            <th scope="col" class="px-4 py-2.5 text-[11px] font-medium uppercase tracking-[0.06em] text-muted-foreground">Status</th>
                            <th scope="col" class="px-4 py-2.5 text-right text-[11px] font-medium uppercase tracking-[0.06em] text-muted-foreground">Actions</th>
                        </tr>
                    </thead>

                    <tbody class="block divide-y divide-border sm:table-row-group">
                        @foreach ($this->rows as $record)
                            @php
                                $state = $record->scheduleState();
                                // The five status washes are the only colour besides a band,
                                // and the word itself carries the state - never hue alone.
                                $stateClasses = match ($state) {
                                    'Overdue' => 'badge-alert',
                                    'Due soon' => 'badge-warn',
                                    'Scheduled' => 'badge-info',
                                    default => 'badge-neutral',
                                };
                            @endphp

                            <tr wire:key="record-{{ $record->id }}" class="block p-4 sm:table-row sm:p-0 sm:align-top sm:hover:bg-muted">
                                <td class="block sm:table-cell sm:px-4 sm:py-3">
                                    <span class="mb-1 block text-[11px] font-medium uppercase tracking-[0.06em] text-muted-foreground sm:hidden">Bird</span>
                                    <p class="text-[15px] font-medium leading-snug text-foreground">{{ $record->broodcock->name }}</p>
                                    {{-- The band tag is the bird's real identifier: the anodised
                                         ring it wears. Its bloodline is spelled out beside it so
                                         the encoding never depends on colour alone. --}}
                                    <div class="mt-1.5 flex flex-wrap items-center gap-x-2 gap-y-1 sm:flex-nowrap">
                                        <x-band-tag :bloodline="$record->broodcock->bloodline"
                                                    :band="$record->broodcock->band_number"
                                                    size="xs" />
                                        @if ($record->broodcock->bloodline)
                                            <span class="text-[12px] text-muted-foreground sm:whitespace-nowrap">{{ $record->broodcock->bloodline }}</span>
                                        @endif
                                    </div>
                                </td>

                                <td class="mt-3 block sm:mt-0 sm:table-cell sm:px-4 sm:py-3">
                                    <span class="mb-1 block text-[11px] font-medium uppercase tracking-[0.06em] text-muted-foreground sm:hidden">Record Type</span>
                                    <span class="badge {{ $record->record_type->badgeClasses() }}">
                                        {{ $record->record_type->label() }}
                                    </span>
                                </td>

                                <td class="mt-3 block sm:mt-0 sm:table-cell sm:px-4 sm:py-3">
                                    <span class="mb-1 block text-[11px] font-medium uppercase tracking-[0.06em] text-muted-foreground sm:hidden">Product</span>
                                    <p class="text-[15px] leading-snug text-foreground">{{ $record->product_name ?: '—' }}</p>
                                    @if ($record->dosage)
                                        <p class="mt-0.5 text-[12px] text-muted-foreground">Dosage: <span class="datum">{{ $record->dosage }}</span></p>
                                    @endif
                                    @if ($record->condition)
                                        <p class="mt-0.5 text-[12px] text-muted-foreground">Condition: {{ $record->condition }}</p>
                                    @endif
                                    {{-- Internal remarks. Customers are promised health STATUS,
                                         never the farm's private notes - the Policy decides. --}}
                                    @can('viewRemarks', $record)
                                        @if ($record->remarks)
                                            <p class="mt-1.5 max-w-[40ch] border-l-2 border-border pl-2 text-[12px] leading-snug text-muted-foreground"><span class="font-medium">Remarks:</span> {{ $record->remarks }}</p>
                                        @endif
                                    @endcan
                                </td>

                                <td class="mt-3 block sm:mt-0 sm:table-cell sm:px-4 sm:py-3 sm:whitespace-nowrap">
                                    <span class="mb-1 block text-[11px] font-medium uppercase tracking-[0.06em] text-muted-foreground sm:hidden">Check-up Date</span>
                                    <span class="datum text-[15px] text-foreground">{{ $record->checkup_date->format('d M Y') }}</span>
                                </td>

                                <td class="mt-3 block sm:mt-0 sm:table-cell sm:px-4 sm:py-3 sm:whitespace-nowrap">
                                    <span class="mb-1 block text-[11px] font-medium uppercase tracking-[0.06em] text-muted-foreground sm:hidden">Next Due Date</span>
                                    @if ($record->next_due_date)
                                        <span class="datum text-[15px] text-foreground">{{ $record->next_due_date->format('d M Y') }}</span>
                                    @else
                                        <span class="text-[15px] text-muted-foreground">None</span>
                                    @endif
                                </td>

                                <td class="mt-3 block sm:mt-0 sm:table-cell sm:px-4 sm:py-3 sm:whitespace-nowrap">
                                    <span class="mb-1 block text-[11px] font-medium uppercase tracking-[0.06em] text-muted-foreground sm:hidden">Status</span>
                                    <span class="badge {{ $stateClasses }}">{{ $state }}</span>
                                </td>

                                <td class="mt-4 block border-t border-border pt-3 sm:mt-0 sm:table-cell sm:border-0 sm:px-4 sm:py-3 sm:text-right sm:whitespace-nowrap">
                                    <div class="flex gap-2 sm:justify-end">
                                        @can('update', $record)
                                            <a href="{{ route('health.edit', $record) }}" wire:navigate class="btn-secondary">Edit</a>
                                        @endcan
                                        {{-- Deliberately NOT the filled .btn-danger, following the
                                             rule set on the broodcock detail page: a solid crimson
                                             control sits within a shade of the crimson BAND colour,
                                             and repeated down fifteen rows it out-shouts the band
                                             tags that colour is reserved for. The filled variant is
                                             kept for the confirmation dialog, where destroying the
                                             record IS the primary action. --}}
                                        @can('delete', $record)
                                            <button type="button" wire:click="confirmDelete({{ $record->id }})"
                                                    class="btn-secondary text-destructive hover:border-destructive">
                                                Delete
                                            </button>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if ($this->rows->hasPages())
                <div class="border-t border-border bg-muted px-4 py-2.5">
                    {{ $this->rows->links() }}
                </div>
            @endif
        @endif
    </div>

    {{-- Destructive actions always confirm, and the dialog names the exact
         record so nobody deletes the wrong bird's vaccination. --}}
    @if ($this->recordPendingDeletion)
        @php $pending = $this->recordPendingDeletion; @endphp

        <div class="fixed inset-0 z-50 flex items-end justify-center bg-foreground/50 p-4 sm:items-center"
             x-data x-trap.noscroll="true" @keydown.escape.window="$el.querySelector('.btn-secondary')?.click()" role="dialog"
             aria-modal="true"
             aria-labelledby="delete-dialog-title">
            {{-- No shadow: the dialog separates by ground and a strong rule,
                 which is how every other surface in this system separates. --}}
            <div class="w-full max-w-lg rounded-[4px] border border-border bg-card p-6">
                <h2 id="delete-dialog-title" class="text-[22px] font-semibold tracking-[-0.01em] text-foreground">Delete this health record?</h2>

                <p class="mt-3 text-[15px] leading-relaxed text-muted-foreground">
                    You are about to delete the
                    <strong class="font-medium text-foreground">{{ $pending->record_type->label() }}</strong> record for
                    <strong class="font-medium text-foreground">{{ $pending->broodcock->name }}</strong>
                    (Band Number: <span class="datum text-foreground">{{ $pending->broodcock->displayBand() }}</span>)
                    dated <strong class="datum font-medium text-foreground">{{ $pending->checkup_date->format('d M Y') }}</strong>.
                </p>

                <p class="mt-2 text-[15px] leading-relaxed text-muted-foreground">
                    The record is kept in the farm's history and can be restored by the owner,
                    but it will no longer appear in lists or reports.
                </p>

                <div class="mt-6 flex flex-col-reverse gap-3 border-t border-border pt-5 sm:flex-row sm:justify-end">
                    <button type="button" wire:click="cancelDelete" class="btn-secondary">
                        No, keep it
                    </button>
                    <button type="button" wire:click="delete" class="btn-danger">
                        Yes, delete this record
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
