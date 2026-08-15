{{--
    Farm-wide health record list.

    The table collapses to stacked cards below `sm:` using table-display
    utilities rather than a duplicated card markup block, so there is exactly
    one copy of every row.
--}}
<div>
    @if ($statusMessage)
        <div class="mb-6 flex items-start justify-between gap-4 rounded-lg bg-ok-wash p-4 text-sm text-ok ring-1 ring-ok/20" role="status">
            <p>{{ $statusMessage }}</p>
            <button type="button" wire:click="dismissStatus" class="shrink-0 font-semibold underline">Dismiss</button>
        </div>
    @endif

    <div class="mb-10 sm:flex sm:items-end sm:justify-between">
        <div>
            <h1 class="text-[34px] font-semibold tracking-[-0.022em] leading-[1.12] text-ink">Health Records</h1>
            <p class="mt-3 text-[17px] leading-relaxed text-ink-48">
                Vaccinations, medications, dewormings, treatments and check-ups for every bird.
            </p>
        </div>

        <div class="mt-4 flex flex-wrap gap-3 sm:mt-0">
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
    <div class="card mb-6 p-4 sm:p-6">
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
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

            <div class="grid grid-cols-2 gap-2">
                <div>
                    <label for="filter-from" class="label">From</label>
                    <input id="filter-from" type="date" wire:model.live="dateFrom" class="input mt-1">
                </div>
                <div>
                    <label for="filter-to" class="label">To</label>
                    <input id="filter-to" type="date" wire:model.live="dateTo" class="input mt-1">
                </div>
            </div>
        </div>

        <div class="mt-4 flex items-center justify-between gap-4">
            <p class="text-sm text-ink-80" wire:loading.remove wire:target="search,recordType,broodcockId,dateFrom,dateTo">
                Showing <strong>{{ number_format($this->rows->total()) }}</strong>
                {{ Str::plural('record', $this->rows->total()) }}.
            </p>
            <p class="text-sm text-ink-48" wire:loading wire:target="search,recordType,broodcockId,dateFrom,dateTo">
                Searching&hellip;
            </p>

            <button type="button" wire:click="clearFilters" class="btn-secondary">
                Clear Filters
            </button>
        </div>
    </div>

    <div class="card overflow-hidden">
        @if ($this->rows->isEmpty())
            {{-- Empty states say what to do next, never just "No results". --}}
            <div class="px-6 py-16 text-center">
                <p class="text-[21px] font-semibold tracking-[-0.01em] leading-[1.25] text-ink">No health records found.</p>
                <p class="mx-auto mt-2 max-w-md text-sm text-ink-80">
                    @if ($this->search !== '' || $this->recordType !== '' || $this->broodcockId !== '' || $this->dateFrom !== '' || $this->dateTo !== '')
                        No record matches your filters. Try clearing them to see every record.
                    @else
                        Nothing has been recorded yet. Click &ldquo;Add Health Record&rdquo; to log the
                        first vaccination, medication or check-up.
                    @endif
                </p>
                <div class="mt-6 flex justify-center gap-3">
                    <button type="button" wire:click="clearFilters" class="btn-secondary">Clear Filters</button>
                    @can('create', \App\Models\HealthRecord::class)
                        <a href="{{ route('health.create') }}" wire:navigate class="btn-primary">Add Health Record</a>
                    @endcan
                </div>
            </div>
        @else
            <table class="w-full text-left text-sm">
                <thead class="hidden bg-pearl text-xs uppercase tracking-wide text-ink-80 sm:table-header-group">
                    <tr>
                        <th scope="col" class="px-6 py-4 font-semibold">Bird</th>
                        <th scope="col" class="px-6 py-4 font-semibold">Record Type</th>
                        <th scope="col" class="px-6 py-4 font-semibold">Product</th>
                        <th scope="col" class="px-6 py-4 font-semibold">Check-up Date</th>
                        <th scope="col" class="px-6 py-4 font-semibold">Next Due Date</th>
                        <th scope="col" class="px-6 py-4 font-semibold">Status</th>
                        <th scope="col" class="px-6 py-4 text-right font-semibold">Actions</th>
                    </tr>
                </thead>

                <tbody class="block divide-y divide-divider sm:table-row-group">
                    @foreach ($this->rows as $record)
                        @php
                            $state = $record->scheduleState();
                            $stateClasses = match ($state) {
                                'Overdue' => 'bg-alert-wash text-alert ring-alert/20',
                                'Due soon' => 'bg-warn-wash text-warn ring-warn/20',
                                'Scheduled' => 'bg-info-wash text-info ring-info/20',
                                default => 'bg-parchment text-ink-80 ring-hairline',
                            };
                        @endphp

                        <tr wire:key="record-{{ $record->id }}" class="block p-4 sm:table-row sm:p-0 sm:align-top sm:hover:bg-pearl">
                            <td class="block sm:table-cell sm:px-4 sm:py-3">
                                <span class="text-[12px] font-medium uppercase tracking-[0.06em] text-ink-48 sm:hidden">Bird</span>
                                <p class="font-semibold text-ink">{{ $record->broodcock->name }}</p>
                                <p class="text-xs text-ink-48">Band Number: {{ $record->broodcock->displayBand() }}</p>
                            </td>

                            <td class="mt-2 block sm:mt-0 sm:table-cell sm:px-4 sm:py-3">
                                <span class="text-[12px] font-medium uppercase tracking-[0.06em] text-ink-48 sm:hidden">Record Type</span>
                                <span class="badge {{ $record->record_type->badgeClasses() }}">
                                    {{ $record->record_type->label() }}
                                </span>
                            </td>

                            <td class="mt-2 block sm:mt-0 sm:table-cell sm:px-4 sm:py-3">
                                <span class="text-[12px] font-medium uppercase tracking-[0.06em] text-ink-48 sm:hidden">Product</span>
                                <p class="text-ink">{{ $record->product_name ?: '—' }}</p>
                                @if ($record->dosage)
                                    <p class="text-xs text-ink-48">Dosage: {{ $record->dosage }}</p>
                                @endif
                                @if ($record->condition)
                                    <p class="text-xs text-ink-48">Condition: {{ $record->condition }}</p>
                                @endif
                                {{-- Internal remarks. Customers are promised health STATUS,
                                     never the farm's private notes - the Policy decides. --}}
                                @can('viewRemarks', $record)
                                    @if ($record->remarks)
                                        <p class="mt-1 text-xs text-ink-48"><span class="font-medium">Remarks:</span> {{ $record->remarks }}</p>
                                    @endif
                                @endcan
                            </td>

                            <td class="mt-2 block sm:mt-0 sm:table-cell sm:px-4 sm:py-3 sm:whitespace-nowrap">
                                <span class="text-[12px] font-medium uppercase tracking-[0.06em] text-ink-48 sm:hidden">Check-up Date</span>
                                <span class="text-ink">{{ $record->checkup_date->format('d M Y') }}</span>
                            </td>

                            <td class="mt-2 block sm:mt-0 sm:table-cell sm:px-4 sm:py-3 sm:whitespace-nowrap">
                                <span class="text-[12px] font-medium uppercase tracking-[0.06em] text-ink-48 sm:hidden">Next Due Date</span>
                                <span class="text-ink">
                                    {{ $record->next_due_date?->format('d M Y') ?? 'None' }}
                                </span>
                            </td>

                            <td class="mt-2 block sm:mt-0 sm:table-cell sm:px-4 sm:py-3">
                                <span class="text-[12px] font-medium uppercase tracking-[0.06em] text-ink-48 sm:hidden">Status</span>
                                <span class="badge {{ $stateClasses }}">{{ $state }}</span>
                            </td>

                            <td class="mt-3 block sm:mt-0 sm:table-cell sm:px-4 sm:py-3 sm:text-right sm:whitespace-nowrap">
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

            <div class="border-t border-hairline px-4 py-3">
                {{ $this->rows->links() }}
            </div>
        @endif
    </div>

    {{-- Destructive actions always confirm, and the dialog names the exact
         record so nobody deletes the wrong bird's vaccination. --}}
    @if ($this->recordPendingDeletion)
        @php $pending = $this->recordPendingDeletion; @endphp

        <div class="fixed inset-0 z-50 flex items-end justify-center bg-ink/40 p-4 sm:items-center"
             role="dialog"
             aria-modal="true"
             aria-labelledby="delete-dialog-title">
            <div class="w-full max-w-lg rounded-xl bg-white p-6 shadow-xl">
                <h2 id="delete-dialog-title" class="text-lg font-semibold text-ink">Delete this health record?</h2>

                <p class="mt-3 text-sm text-ink-80">
                    You are about to delete the
                    <strong>{{ $pending->record_type->label() }}</strong> record for
                    <strong>{{ $pending->broodcock->name }}</strong>
                    (Band Number: {{ $pending->broodcock->displayBand() }})
                    dated <strong>{{ $pending->checkup_date->format('d M Y') }}</strong>.
                </p>

                <p class="mt-2 text-sm text-ink-80">
                    The record is kept in the farm's history and can be restored by the owner,
                    but it will no longer appear in lists or reports.
                </p>

                <div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
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
