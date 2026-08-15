{{--
    The mortality register.

    Every list on this screen is paginated and every relation shown in a row is
    eager-loaded in the component - Supabase is a network hop away, so an N+1
    here would be felt, not measured.
--}}
<div>
    @php
        /**
         * Age at death, in words. Defined once here rather than repeated in the
         * mobile card and the desktop row.
         */
        $ageLabel = function (?int $months): string {
            if ($months === null) {
                return 'Hatch date not known';
            }

            $years = intdiv($months, 12);
            $remainder = $months % 12;

            if ($years === 0) {
                return $months === 1 ? '1 month' : "{$months} months";
            }

            $label = $years === 1 ? '1 year' : "{$years} years";

            return $remainder === 0 ? $label : "{$label} {$remainder} mos";
        };
    @endphp

    <div class="mb-10 sm:flex sm:items-end sm:justify-between">
        <div>
            <h1 class="text-[34px] font-semibold tracking-[-0.022em] leading-[1.12] text-ink">Mortality Records</h1>
            <p class="mt-3 text-[17px] leading-relaxed text-ink-48">
                Birds that have died, with the cause and how the bird was disposed of.
                This information is for farm staff only.
            </p>
        </div>

        @if ($this->canCreate)
            <a href="{{ route('mortality.create') }}" wire:navigate class="btn-primary mt-4 sm:mt-0">
                Record a Death
            </a>
        @endif
    </div>

    {{-- A Livewire update does not re-render the layout, so the confirmation
         of a delete has to be shown from inside the component. --}}
    @if ($status)
        <div class="mb-6 rounded-lg bg-ok-wash p-4 text-sm text-ok ring-1 ring-ok/20" role="status">
            {{ $status }}
        </div>
    @endif

    {{-- ---------------------------------------------------------------
         Summary. All three numbers are computed from the rows on request -
         none of them is stored, so none of them can contradict the register.
    ---------------------------------------------------------------- --}}
    <div class="mb-10 grid grid-cols-1 gap-4 sm:grid-cols-3">
        <div class="card p-7">
            <p class="text-sm font-medium text-ink-80">Deaths This Month</p>
            <p class="mt-2 text-[40px] font-semibold tracking-[-0.022em] leading-[1.08] text-ink">{{ number_format($this->summary['this_month']) }}</p>
            <p class="help">{{ now()->format('F Y') }}</p>
        </div>

        <div class="card p-7">
            <p class="text-sm font-medium text-ink-80">Deaths This Year</p>
            <p class="mt-2 text-[40px] font-semibold tracking-[-0.022em] leading-[1.08] text-ink">{{ number_format($this->summary['this_year']) }}</p>
            <p class="help">1 January to 31 December {{ now()->year }}</p>
        </div>

        <div class="card p-7">
            <p class="text-sm font-medium text-ink-80">Deaths Recorded in Total</p>
            <p class="mt-2 text-[40px] font-semibold tracking-[-0.022em] leading-[1.08] text-ink">{{ number_format($this->summary['total']) }}</p>
            <p class="help">Since the farm started keeping records here</p>
        </div>
    </div>

    @if ($this->causeBreakdown->isNotEmpty())
        <div class="card mb-6 p-5">
            <h2 class="text-sm font-semibold text-ink">
                Causes of Death
                <span class="font-normal text-ink-48">
                    ({{ $this->hasFilters() ? 'for the records you are filtering' : 'all records' }})
                </span>
            </h2>

            @php $breakdownTotal = $this->causeBreakdown->sum('total'); @endphp

            <ul class="mt-4 space-y-3">
                @foreach ($this->causeBreakdown as $line)
                    @php $share = $breakdownTotal > 0 ? round($line['total'] / $breakdownTotal * 100) : 0; @endphp
                    <li>
                        <div class="flex items-baseline justify-between gap-4 text-sm">
                            <span class="font-medium text-ink">{{ $line['cause'] }}</span>
                            <span class="shrink-0 text-ink-80">
                                {{ $line['total'] }} {{ Str::plural('bird', $line['total']) }} ({{ $share }}%)
                            </span>
                        </div>
                        <div class="mt-1 h-2 w-full overflow-hidden rounded-full bg-parchment">
                            <div class="h-2 rounded-full bg-action" style="width: {{ $share }}%"></div>
                        </div>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- ---------------------------------------------------------------
         Filters
    ---------------------------------------------------------------- --}}
    <div class="card mb-6 p-5">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div>
                <label for="filter-from" class="label">Died On or After</label>
                <input id="filter-from" type="date" wire:model.live="from" class="input mt-1">
            </div>

            <div>
                <label for="filter-to" class="label">Died On or Before</label>
                <input id="filter-to" type="date" wire:model.live="to" class="input mt-1">
            </div>

            <div>
                <label for="filter-cause" class="label">Cause of Death</label>
                <select id="filter-cause" wire:model.live="cause" class="input mt-1">
                    <option value="">All causes</option>
                    @foreach ($this->causeOptions as $option)
                        <option value="{{ $option }}">{{ $option }}</option>
                    @endforeach
                </select>
            </div>

            <div class="flex items-end">
                <button type="button"
                        wire:click="clearFilters"
                        @disabled(! $this->hasFilters())
                        class="btn-secondary w-full">
                    Clear Filters
                </button>
            </div>
        </div>
    </div>

    {{-- ---------------------------------------------------------------
         The register
    ---------------------------------------------------------------- --}}
    @if ($this->rows->isEmpty())
        <div class="card p-10 text-center">
            <p class="text-[21px] font-semibold tracking-[-0.01em] leading-[1.25] text-ink">
                @if ($this->hasFilters())
                    No deaths match these filters.
                @else
                    No deaths have been recorded yet.
                @endif
            </p>
            <p class="mx-auto mt-2 max-w-md text-sm text-ink-80">
                @if ($this->hasFilters())
                    Try widening the dates, or choose "All causes".
                @elseif ($this->canCreate)
                    That is good news. If a bird does die, click "Record a Death" so the
                    farm has a complete history for that bird.
                @else
                    That is good news.
                @endif
            </p>

            <div class="mt-6">
                @if ($this->hasFilters())
                    <button type="button" wire:click="clearFilters" class="btn-secondary">Clear Filters</button>
                @elseif ($this->canCreate)
                    <a href="{{ route('mortality.create') }}" wire:navigate class="btn-primary">Record a Death</a>
                @endif
            </div>
        </div>
    @else
        {{-- Mobile: one card per record. A seven-column table is unreadable on
             a phone, and phones are what the farm staff actually carry. --}}
        <div class="space-y-4 sm:hidden">
            @foreach ($this->rows as $record)
                <div class="card p-6" wire:key="card-{{ $record->id }}">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="font-semibold text-ink">{{ $record->broodcock?->name ?? 'Unknown bird' }}</p>
                            <p class="text-sm text-ink-80">Band: {{ $record->broodcock?->displayBand() ?? '-' }}</p>
                        </div>
                        <span class="badge bg-alert-wash text-alert ring-alert/20">
                            {{ $record->date_of_death->format('d M Y') }}
                        </span>
                    </div>

                    <dl class="mt-3 space-y-1 text-sm">
                        <div class="flex gap-2">
                            <dt class="text-ink-48">Cause:</dt>
                            <dd class="font-medium text-ink">{{ $record->cause_of_death }}</dd>
                        </div>
                        <div class="flex gap-2">
                            <dt class="text-ink-48">Disposal:</dt>
                            <dd class="text-ink">{{ $record->disposal_method ?: 'Not recorded' }}</dd>
                        </div>
                        <div class="flex gap-2">
                            <dt class="text-ink-48">Age at death:</dt>
                            <dd class="text-ink">{{ $ageLabel($record->ageAtDeathInMonths()) }}</dd>
                        </div>
                        <div class="flex gap-2">
                            <dt class="text-ink-48">Recorded by:</dt>
                            <dd class="text-ink">{{ $record->recordedBy?->full_name ?? 'Not recorded' }}</dd>
                        </div>
                    </dl>

                    @if ($this->canDelete)
                        <button type="button"
                                wire:click="confirmDelete({{ $record->id }})"
                                class="btn-secondary mt-4 w-full text-alert">
                            Delete Record
                        </button>
                    @endif
                </div>
            @endforeach
        </div>

        {{-- Desktop --}}
        <div class="card hidden overflow-hidden sm:block">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-divider text-sm">
                    <thead class="bg-pearl text-left text-[12px] font-medium uppercase tracking-[0.06em] text-ink-80">
                        <tr>
                            <th scope="col" class="px-6 py-4">Bird</th>
                            <th scope="col" class="px-6 py-4">Date of Death</th>
                            <th scope="col" class="px-6 py-4">Cause of Death</th>
                            <th scope="col" class="px-6 py-4">Disposal Method</th>
                            <th scope="col" class="px-6 py-4">Age at Death</th>
                            <th scope="col" class="px-6 py-4">Recorded By</th>
                            @if ($this->canDelete)
                                <th scope="col" class="px-6 py-4 text-right">Action</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-divider bg-white">
                        @foreach ($this->rows as $record)
                            <tr wire:key="row-{{ $record->id }}" class="hover:bg-pearl">
                                <td class="px-6 py-4">
                                    <p class="font-medium text-ink">{{ $record->broodcock?->name ?? 'Unknown bird' }}</p>
                                    <p class="text-xs text-ink-48">{{ $record->broodcock?->displayBand() ?? '-' }}</p>
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-ink">
                                    {{ $record->date_of_death->format('d M Y') }}
                                </td>
                                <td class="px-6 py-4 text-ink">{{ $record->cause_of_death }}</td>
                                <td class="px-6 py-4 text-ink-80">{{ $record->disposal_method ?: 'Not recorded' }}</td>
                                <td class="whitespace-nowrap px-6 py-4 text-ink-80">
                                    {{ $ageLabel($record->ageAtDeathInMonths()) }}
                                </td>
                                <td class="px-6 py-4 text-ink-80">{{ $record->recordedBy?->full_name ?? 'Not recorded' }}</td>
                                @if ($this->canDelete)
                                    <td class="px-6 py-4 text-right">
                                        <button type="button"
                                                wire:click="confirmDelete({{ $record->id }})"
                                                class="rounded-lg px-3 py-2 text-sm font-semibold text-alert hover:bg-alert-wash">
                                            Delete
                                        </button>
                                    </td>
                                @endif
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-6">
            {{ $this->rows->links() }}
        </div>
    @endif

    {{-- ---------------------------------------------------------------
         Delete confirmation. Destructive, so it names the bird and says
         exactly what will happen to it - never "Are you sure?".
    ---------------------------------------------------------------- --}}
    @if ($this->confirmingRecord)
        <div class="fixed inset-0 z-50 flex items-end justify-center bg-ink/40 p-4 sm:items-center"
             role="dialog"
             aria-modal="true"
             aria-labelledby="delete-mortality-title"
             wire:keydown.escape="cancelDelete">
            <div class="w-full max-w-lg rounded-xl bg-white p-6 shadow-xl">
                <h2 id="delete-mortality-title" class="text-lg font-semibold text-ink">
                    Delete the death record for
                    {{ $this->confirmingRecord->broodcock?->displayName() ?? 'this bird' }}?
                </h2>

                <div class="mt-3 space-y-2 text-sm text-ink-80">
                    <p>
                        The death recorded on
                        <strong>{{ $this->confirmingRecord->date_of_death->format('d M Y') }}</strong>
                        ({{ $this->confirmingRecord->cause_of_death }}) will be removed from the register.
                    </p>
                    <p>
                        {{ $this->confirmingRecord->broodcock?->name ?? 'The bird' }}
                        will be put back on the <strong>active</strong> list, as if the death had never
                        been recorded. Do this only if the death was entered by mistake.
                    </p>
                </div>

                <div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                    <button type="button" wire:click="cancelDelete" class="btn-secondary">
                        No, Keep the Record
                    </button>
                    <button type="button" wire:click="delete" class="btn-danger">
                        Yes, Delete This Record
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
