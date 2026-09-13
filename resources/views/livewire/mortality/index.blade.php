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

    <div class="mb-8 border-b border-border pb-6 sm:flex sm:items-end sm:justify-between sm:gap-8">
        <div>
            <h1 class="page-title-marked text-[32px] font-semibold leading-[1.15] tracking-[-0.02em] text-foreground">Mortality Records</h1>
            <p class="mt-2 max-w-[65ch] text-[15px] leading-relaxed text-muted-foreground">
                Birds that have died, with the cause and how the bird was disposed of.
                This information is for farm staff only.
            </p>
        </div>

        @if ($this->canCreate)
            <a href="{{ route('mortality.create') }}" wire:navigate class="btn-primary mt-5 w-full shrink-0 sm:mt-0 sm:w-auto">
                Record a Death
            </a>
        @endif
    </div>

    {{-- A Livewire update does not re-render the layout, so the confirmation
         of a delete has to be shown from inside the component. --}}
    @if ($status)
        <div class="mb-6 rounded-[4px] bg-success-bg px-4 py-3 text-[15px] text-foreground" role="status">
            {{ $status }}
        </div>
    @endif

    {{-- ---------------------------------------------------------------
         Summary. All three numbers are computed from the rows on request -
         none of them is stored, so none of them can contradict the register.
    ---------------------------------------------------------------- --}}
    {{-- Sober on purpose. A death record is routine data entry on a farm, so
         the register is ink on paper; the alert wash is kept back for the one
         genuinely destructive action on this screen. --}}
    <div class="mb-6 grid grid-cols-1 gap-px overflow-hidden rounded-[4px] border border-border bg-border sm:grid-cols-3">
        <div class="bg-card p-5">
            <p class="text-[11px] font-medium uppercase tracking-[0.06em] text-muted-foreground">Deaths This Month</p>
            <p class="datum mt-2 text-[32px] font-semibold leading-[1.1] tracking-[-0.02em] text-foreground">{{ number_format($this->summary['this_month']) }}</p>
            <p class="help datum">{{ now()->format('F Y') }}</p>
        </div>

        <div class="bg-card p-5">
            <p class="text-[11px] font-medium uppercase tracking-[0.06em] text-muted-foreground">Deaths This Year</p>
            <p class="datum mt-2 text-[32px] font-semibold leading-[1.1] tracking-[-0.02em] text-foreground">{{ number_format($this->summary['this_year']) }}</p>
            <p class="help">1 January to 31 December {{ now()->year }}</p>
        </div>

        <div class="bg-card p-5">
            <p class="text-[11px] font-medium uppercase tracking-[0.06em] text-muted-foreground">Deaths Recorded in Total</p>
            <p class="datum mt-2 text-[32px] font-semibold leading-[1.1] tracking-[-0.02em] text-foreground">{{ number_format($this->summary['total']) }}</p>
            <p class="help">Since the farm started keeping records here</p>
        </div>
    </div>

    @if ($this->causeBreakdown->isNotEmpty())
        <div class="card mb-6 p-5">
            <h2 class="text-[11px] font-medium uppercase tracking-[0.06em] text-muted-foreground">
                Causes of Death
                <span class="text-[12px] font-normal normal-case tracking-normal text-muted-foreground">
                    ({{ $this->hasFilters() ? 'for the records you are filtering' : 'all records' }})
                </span>
            </h2>

            @php $breakdownTotal = $this->causeBreakdown->sum('total'); @endphp

            {{-- A printed bar, not a chart widget: ink on a sunk track. The one
                 interactive colour is reserved for things you can actually
                 click, so it is not spent here. --}}
            <ul class="mt-4 space-y-3.5">
                @foreach ($this->causeBreakdown as $line)
                    @php $share = $breakdownTotal > 0 ? round($line['total'] / $breakdownTotal * 100) : 0; @endphp
                    <li>
                        <div class="flex items-baseline justify-between gap-4 text-[15px]">
                            <span class="font-medium text-foreground">{{ $line['cause'] }}</span>
                            <span class="shrink-0 text-muted-foreground">
                                <span class="datum">{{ $line['total'] }}</span> {{ Str::plural('bird', $line['total']) }} (<span class="datum">{{ $share }}%</span>)
                            </span>
                        </div>
                        <div class="mt-1.5 h-1.5 w-full overflow-hidden rounded-[1px] bg-muted">
                            <div class="h-1.5 bg-muted-foreground" style="width: {{ $share }}%"></div>
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
                <x-form-select id="filter-cause" label="Cause of Death" wire:model.live="cause">
                    <option value="">All causes</option>
                    @foreach ($this->causeOptions as $option)
                        <option value="{{ $option }}">{{ $option }}</option>
                    @endforeach
                </x-form-select>
            </div>

            <div class="flex items-end pt-1">
                <button type="button"
                        wire:click="clearFilters"
                        @disabled(! $this->hasFilters())
                        class="btn-secondary w-full">
                    Clear filters
                </button>
            </div>
        </div>
    </div>

    {{-- ---------------------------------------------------------------
         The register
    ---------------------------------------------------------------- --}}
    @if ($this->rows->isEmpty())
        <div class="card px-6 py-12 text-center">
            <p class="text-[18px] font-medium text-foreground">
                @if ($this->hasFilters())
                    No deaths match these filters.
                @else
                    No deaths have been recorded yet.
                @endif
            </p>
            <p class="mx-auto mt-2 max-w-[52ch] text-[15px] leading-relaxed text-muted-foreground">
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
                    <button type="button" wire:click="clearFilters" class="btn-secondary">Clear filters</button>
                @elseif ($this->canCreate)
                    <a href="{{ route('mortality.create') }}" wire:navigate class="btn-primary">Record a Death</a>
                @endif
            </div>
        </div>
    @else
        {{-- Mobile: one card per record. A seven-column table is unreadable on
             a phone, and phones are what the farm staff actually carry. --}}
        <div class="space-y-2.5 sm:hidden">
            @foreach ($this->rows as $record)
                <div class="card p-4" wire:key="card-{{ $record->id }}">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="truncate text-[15px] font-medium text-foreground">{{ $record->broodcock?->name ?? 'Unknown bird' }}</p>
                            <p class="text-[13px] text-muted-foreground">Band: <span class="datum text-foreground">{{ $record->broodcock?->displayBand() ?? '-' }}</span></p>
                        </div>
                        <p class="datum shrink-0 text-[15px] font-medium text-foreground">
                            {{ $record->date_of_death->format('d M Y') }}
                        </p>
                    </div>

                    <dl class="mt-3 space-y-1.5 border-t border-border pt-3 text-[15px]">
                        <div class="flex gap-2">
                            <dt class="shrink-0 text-muted-foreground">Cause:</dt>
                            <dd class="font-medium text-foreground">{{ $record->cause_of_death }}</dd>
                        </div>
                        <div class="flex gap-2">
                            <dt class="shrink-0 text-muted-foreground">Disposal:</dt>
                            <dd class="text-foreground">{{ $record->disposal_method ?: 'Not recorded' }}</dd>
                        </div>
                        <div class="flex gap-2">
                            <dt class="shrink-0 text-muted-foreground">Age at death:</dt>
                            <dd class="datum text-foreground">{{ $ageLabel($record->ageAtDeathInMonths()) }}</dd>
                        </div>
                        <div class="flex gap-2">
                            <dt class="shrink-0 text-muted-foreground">Recorded by:</dt>
                            <dd class="text-foreground">{{ $record->recordedBy?->full_name ?? 'Not recorded' }}</dd>
                        </div>
                    </dl>

                    @if ($this->canDelete)
                        <button type="button"
                                wire:click="confirmDelete({{ $record->id }})"
                                class="btn-secondary mt-4 w-full text-destructive">
                            Delete Record
                        </button>
                    @endif
                </div>
            @endforeach
        </div>

        {{-- Desktop --}}
        <div class="card hidden overflow-hidden sm:block">
            <div class="overflow-x-auto">
                {{-- .table-hairline resolves to `.table-hairline tbody tr + tr`,
                     so it belongs on the table, not the tbody. --}}
                <table class="table-hairline min-w-full text-[15px]">
                    <thead class="border-b border-border bg-muted text-left text-[11px] font-medium uppercase tracking-[0.06em] text-muted-foreground">
                        <tr>
                            <th scope="col" class="px-4 py-2">Bird</th>
                            <th scope="col" class="px-4 py-2">Date of Death</th>
                            <th scope="col" class="px-4 py-2">Cause of Death</th>
                            <th scope="col" class="px-4 py-2">Disposal Method</th>
                            <th scope="col" class="px-4 py-2">Age at Death</th>
                            <th scope="col" class="px-4 py-2">Recorded By</th>
                            @if ($this->canDelete)
                                <th scope="col" class="px-4 py-2 text-right">Action</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody class="bg-card">
                        @foreach ($this->rows as $record)
                            <tr wire:key="row-{{ $record->id }}" class="group row-hover">
                                <td class="px-4 py-3">
                                    <p class="font-medium text-foreground">{{ $record->broodcock?->name ?? 'Unknown bird' }}</p>
                                    {{-- "Not yet banded" is a real state, never a blank cell -
                                         the band tag says so in words. bloodline is now in the
                                         eager-load select list, so this no longer trips
                                         shouldBeStrict(). --}}
                                    <div class="mt-1">
                                        @if ($record->broodcock)
                                            <x-band-tag :bloodline="$record->broodcock->bloodline"
                                                        :band="$record->broodcock->band_number" size="xs" />
                                        @else
                                            <span class="datum text-[13px] text-muted-foreground">-</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="datum whitespace-nowrap px-4 py-3 text-foreground">
                                    {{ $record->date_of_death->format('d M Y') }}
                                </td>
                                <td class="px-4 py-3 text-foreground">{{ $record->cause_of_death }}</td>
                                <td class="px-4 py-3 text-muted-foreground">{{ $record->disposal_method ?: 'Not recorded' }}</td>
                                <td class="datum whitespace-nowrap px-4 py-3 text-muted-foreground">
                                    {{ $ageLabel($record->ageAtDeathInMonths()) }}
                                </td>
                                <td class="px-4 py-3 text-muted-foreground">{{ $record->recordedBy?->full_name ?? 'Not recorded' }}</td>
                                @if ($this->canDelete)
                                    <td class="px-4 py-3 text-right">
                                        <button type="button"
                                                wire:click="confirmDelete({{ $record->id }})"
                                                class="-mr-2 rounded-[4px] px-2 text-[15px] font-medium text-destructive hover:bg-destructive-bg">
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

        <div class="mt-5 border-t border-border pt-4">
            {{ $this->rows->links() }}
        </div>
    @endif

    {{-- ---------------------------------------------------------------
         Delete confirmation. Destructive, so it names the bird and says
         exactly what will happen to it - never "Are you sure?".
    ---------------------------------------------------------------- --}}
    @if ($this->confirmingRecord)
        <div class="fixed inset-0 z-50 flex items-end justify-center bg-foreground/40 p-4 sm:items-center"
             x-data x-trap.noscroll="true" @keydown.escape.window="$el.querySelector('.btn-secondary')?.click()" role="dialog"
             aria-modal="true"
             aria-labelledby="delete-mortality-title"
             wire:keydown.escape="cancelDelete">
            {{-- The one place on this screen that carries the alert wash: an
                 undo of a recorded death, which really is serious. --}}
            <div class="card w-full max-w-lg p-6">
                <h2 id="delete-mortality-title" class="text-[22px] font-semibold leading-[1.2] tracking-[-0.01em] text-foreground">
                    Delete the death record for
                    {{ $this->confirmingRecord->broodcock?->displayName() ?? 'this bird' }}?
                </h2>

                <div class="mt-3 space-y-2 text-[15px] leading-relaxed text-muted-foreground">
                    <p>
                        The death recorded on
                        <strong class="datum font-medium text-foreground">{{ $this->confirmingRecord->date_of_death->format('d M Y') }}</strong>
                        ({{ $this->confirmingRecord->cause_of_death }}) will be removed from the register.
                    </p>
                    <p class="rounded-[4px] bg-destructive-bg px-3 py-2.5 text-foreground">
                        {{ $this->confirmingRecord->broodcock?->name ?? 'The bird' }}
                        will be put back on the <strong class="font-medium">active</strong> list, as if the death had never
                        been recorded. Do this only if the death was entered by mistake.
                    </p>
                </div>

                <div class="mt-6 flex flex-col-reverse gap-3 border-t border-border pt-5 sm:flex-row sm:justify-end">
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
