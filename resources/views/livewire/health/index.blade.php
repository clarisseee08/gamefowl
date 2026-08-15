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
        <div class="mb-8 flex items-start justify-between gap-4 rounded-[4px] border border-hairline bg-ok-wash px-4 py-3" role="status">
            <p class="text-[15px] leading-snug text-ok">{{ $statusMessage }}</p>
            <button type="button" wire:click="dismissStatus"
                    class="-my-2 shrink-0 text-[13px] font-medium text-ok underline underline-offset-2">
                Dismiss
            </button>
        </div>
    @endif

    <div class="mb-8 border-b border-rule-strong pb-6 sm:flex sm:items-end sm:justify-between sm:gap-8">
        <div>
            <h1 class="text-[32px] font-semibold leading-[1.15] tracking-[-0.02em] text-ink">Health Records</h1>
            <p class="mt-2 max-w-[65ch] text-[15px] leading-relaxed text-ink-80">
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

    {{-- Filters. Every one is optional; leaving a box blank means "all". --}}
    <div class="card mb-4 overflow-hidden">
        <div class="grid gap-4 p-4 sm:grid-cols-2 sm:p-5 lg:grid-cols-5">
            <div class="lg:col-span-2">
                <label for="filter-search" class="label">Search</label>
                <input id="filter-search"
                       type="search"
                       wire:model.live.debounce.400ms="search"
                       class="input mt-1"
                       placeholder="Bird name, band number, product or condition">
            </div>

            <div>
                <label for="filter-type" class="label">Record Type</label>
                <select id="filter-type" wire:model.live="recordType" class="input mt-1">
                    <option value="">All types</option>
                    @foreach ($this->recordTypes as $type)
                        <option value="{{ $type->value }}">{{ $type->label() }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="filter-bird" class="label">Bird</label>
                <select id="filter-bird" wire:model.live="broodcockId" class="input mt-1">
                    <option value="">All birds</option>
                    @foreach ($this->birds as $bird)
                        <option value="{{ $bird->id }}">{{ $bird->displayName() }}</option>
                    @endforeach
                </select>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label for="filter-from" class="label">From</label>
                    <input id="filter-from" type="date" wire:model.live="dateFrom" class="input datum mt-1">
                </div>
                <div>
                    <label for="filter-to" class="label">To</label>
                    <input id="filter-to" type="date" wire:model.live="dateTo" class="input datum mt-1">
                </div>
            </div>
        </div>

        {{-- The result count is sunk into the card's foot so it reads as a
             consequence of the filters above it, not as a separate statement. --}}
        <div class="flex flex-wrap items-center justify-between gap-3 border-t border-hairline bg-pearl px-4 py-2.5 sm:px-5">
            <p class="text-[13px] text-ink-80" wire:loading.remove wire:target="search,recordType,broodcockId,dateFrom,dateTo">
                Showing <strong class="datum font-medium text-ink">{{ number_format($this->rows->total()) }}</strong>
                {{ Str::plural('record', $this->rows->total()) }}.
            </p>
            <p class="text-[13px] text-ink-80" wire:loading wire:target="search,recordType,broodcockId,dateFrom,dateTo">
                Searching&hellip;
            </p>

            <button type="button" wire:click="clearFilters" class="btn-quiet -my-1 px-2 text-[13px]">
                Clear Filters
            </button>
        </div>
    </div>

    <div class="card overflow-hidden">
        @if ($this->rows->isEmpty())
            {{-- Empty states say what to do next, never just "No results". --}}
            <div class="px-6 py-16 text-center">
                <p class="text-[18px] font-medium text-ink">No health records found.</p>
                <p class="mx-auto mt-2 max-w-[52ch] text-[15px] leading-relaxed text-ink-80">
                    @if ($this->search !== '' || $this->recordType !== '' || $this->broodcockId !== '' || $this->dateFrom !== '' || $this->dateTo !== '')
                        No record matches your filters. Try clearing them to see every record.
                    @else
                        Nothing has been recorded yet. Click &ldquo;Add Health Record&rdquo; to log the
                        first vaccination, medication or check-up.
                    @endif
                </p>
                <div class="mt-6 flex flex-wrap justify-center gap-3">
                    <button type="button" wire:click="clearFilters" class="btn-secondary">Clear Filters</button>
                    @can('create', \App\Models\HealthRecord::class)
                        <a href="{{ route('health.create') }}" wire:navigate class="btn-primary">Add Health Record</a>
                    @endcan
                </div>
            </div>
        @else
            <table class="w-full text-left">
                <thead class="hidden bg-pearl sm:table-header-group">
                    <tr class="border-b border-rule-strong">
                        <th scope="col" class="px-4 py-2.5 text-[11px] font-medium uppercase tracking-[0.06em] text-ink-80">Bird</th>
                        <th scope="col" class="px-4 py-2.5 text-[11px] font-medium uppercase tracking-[0.06em] text-ink-80">Record Type</th>
                        <th scope="col" class="px-4 py-2.5 text-[11px] font-medium uppercase tracking-[0.06em] text-ink-80">Product</th>
                        <th scope="col" class="px-4 py-2.5 text-[11px] font-medium uppercase tracking-[0.06em] text-ink-80">Check-up Date</th>
                        <th scope="col" class="px-4 py-2.5 text-[11px] font-medium uppercase tracking-[0.06em] text-ink-80">Next Due Date</th>
                        <th scope="col" class="px-4 py-2.5 text-[11px] font-medium uppercase tracking-[0.06em] text-ink-80">Status</th>
                        <th scope="col" class="px-4 py-2.5 text-right text-[11px] font-medium uppercase tracking-[0.06em] text-ink-80">Actions</th>
                    </tr>
                </thead>

                <tbody class="block divide-y divide-divider sm:table-row-group">
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

                        <tr wire:key="record-{{ $record->id }}" class="block p-4 sm:table-row sm:p-0 sm:align-top sm:hover:bg-pearl">
                            <td class="block sm:table-cell sm:px-4 sm:py-3">
                                <span class="mb-1 block text-[11px] font-medium uppercase tracking-[0.06em] text-ink-80 sm:hidden">Bird</span>
                                <p class="text-[15px] font-medium leading-snug text-ink">{{ $record->broodcock->name }}</p>
                                {{-- The band tag is the bird's real identifier: the anodised
                                     ring it wears. Its bloodline is spelled out beside it so
                                     the encoding never depends on colour alone. --}}
                                <div class="mt-1.5 flex flex-wrap items-center gap-x-2 gap-y-1">
                                    <x-band-tag :bloodline="$record->broodcock->bloodline"
                                                :band="$record->broodcock->band_number"
                                                size="xs" />
                                    @if ($record->broodcock->bloodline)
                                        <span class="text-[12px] text-ink-80">{{ $record->broodcock->bloodline }}</span>
                                    @endif
                                </div>
                            </td>

                            <td class="mt-3 block sm:mt-0 sm:table-cell sm:px-4 sm:py-3">
                                <span class="mb-1 block text-[11px] font-medium uppercase tracking-[0.06em] text-ink-80 sm:hidden">Record Type</span>
                                <span class="badge {{ $record->record_type->badgeClasses() }}">
                                    {{ $record->record_type->label() }}
                                </span>
                            </td>

                            <td class="mt-3 block sm:mt-0 sm:table-cell sm:px-4 sm:py-3">
                                <span class="mb-1 block text-[11px] font-medium uppercase tracking-[0.06em] text-ink-80 sm:hidden">Product</span>
                                <p class="text-[15px] leading-snug text-ink">{{ $record->product_name ?: '—' }}</p>
                                @if ($record->dosage)
                                    <p class="mt-0.5 text-[12px] text-ink-80">Dosage: <span class="datum">{{ $record->dosage }}</span></p>
                                @endif
                                @if ($record->condition)
                                    <p class="mt-0.5 text-[12px] text-ink-80">Condition: {{ $record->condition }}</p>
                                @endif
                                {{-- Internal remarks. Customers are promised health STATUS,
                                     never the farm's private notes - the Policy decides. --}}
                                @can('viewRemarks', $record)
                                    @if ($record->remarks)
                                        <p class="mt-1.5 max-w-[40ch] border-l-2 border-hairline pl-2 text-[12px] leading-snug text-ink-80"><span class="font-medium">Remarks:</span> {{ $record->remarks }}</p>
                                    @endif
                                @endcan
                            </td>

                            <td class="mt-3 block sm:mt-0 sm:table-cell sm:px-4 sm:py-3 sm:whitespace-nowrap">
                                <span class="mb-1 block text-[11px] font-medium uppercase tracking-[0.06em] text-ink-80 sm:hidden">Check-up Date</span>
                                <span class="datum text-[15px] text-ink">{{ $record->checkup_date->format('d M Y') }}</span>
                            </td>

                            <td class="mt-3 block sm:mt-0 sm:table-cell sm:px-4 sm:py-3 sm:whitespace-nowrap">
                                <span class="mb-1 block text-[11px] font-medium uppercase tracking-[0.06em] text-ink-80 sm:hidden">Next Due Date</span>
                                @if ($record->next_due_date)
                                    <span class="datum text-[15px] text-ink">{{ $record->next_due_date->format('d M Y') }}</span>
                                @else
                                    <span class="text-[15px] text-ink-80">None</span>
                                @endif
                            </td>

                            <td class="mt-3 block sm:mt-0 sm:table-cell sm:px-4 sm:py-3">
                                <span class="mb-1 block text-[11px] font-medium uppercase tracking-[0.06em] text-ink-80 sm:hidden">Status</span>
                                <span class="badge {{ $stateClasses }}">{{ $state }}</span>
                            </td>

                            <td class="mt-4 block border-t border-hairline pt-3 sm:mt-0 sm:table-cell sm:border-0 sm:px-4 sm:py-3 sm:text-right sm:whitespace-nowrap">
                                <div class="flex gap-2 sm:justify-end">
                                    @can('update', $record)
                                        <a href="{{ route('health.edit', $record) }}" wire:navigate class="btn-secondary">Edit</a>
                                    @endcan
                                    @can('delete', $record)
                                        <button type="button" wire:click="confirmDelete({{ $record->id }})" class="btn-danger">
                                            Delete
                                        </button>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="border-t border-hairline bg-pearl px-4 py-2.5">
                {{ $this->rows->links() }}
            </div>
        @endif
    </div>

    {{-- Destructive actions always confirm, and the dialog names the exact
         record so nobody deletes the wrong bird's vaccination. --}}
    @if ($this->recordPendingDeletion)
        @php $pending = $this->recordPendingDeletion; @endphp

        <div class="fixed inset-0 z-50 flex items-end justify-center bg-ink/50 p-4 sm:items-center"
             x-data x-trap.noscroll="true" @keydown.escape.window="$el.querySelector('.btn-secondary')?.click()" role="dialog"
             aria-modal="true"
             aria-labelledby="delete-dialog-title">
            {{-- No shadow: the dialog separates by ground and a strong rule,
                 which is how every other surface in this system separates. --}}
            <div class="w-full max-w-lg rounded-[4px] border border-rule-strong bg-canvas p-6">
                <h2 id="delete-dialog-title" class="text-[22px] font-semibold tracking-[-0.01em] text-ink">Delete this health record?</h2>

                <p class="mt-3 text-[15px] leading-relaxed text-ink-80">
                    You are about to delete the
                    <strong class="font-medium text-ink">{{ $pending->record_type->label() }}</strong> record for
                    <strong class="font-medium text-ink">{{ $pending->broodcock->name }}</strong>
                    (Band Number: <span class="datum text-ink">{{ $pending->broodcock->displayBand() }}</span>)
                    dated <strong class="datum font-medium text-ink">{{ $pending->checkup_date->format('d M Y') }}</strong>.
                </p>

                <p class="mt-2 text-[15px] leading-relaxed text-ink-80">
                    The record is kept in the farm's history and can be restored by the owner,
                    but it will no longer appear in lists or reports.
                </p>

                <div class="mt-6 flex flex-col-reverse gap-3 border-t border-hairline pt-5 sm:flex-row sm:justify-end">
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
