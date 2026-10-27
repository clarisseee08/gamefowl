{{--
    The mortality register.

    Plain on purpose. A death record is routine data entry on a farm, so the
    register is ink on paper; the alert wash is kept back for the one
    genuinely destructive action on this screen.

    Every list here is paginated and every relation shown in a row is
    eager-loaded in the component - Supabase is a network hop away, so an N+1
    would be felt, not measured.
--}}
<div>
    @php
        /**
         * Age at death, in words. Defined once here rather than repeated in
         * the phone row and the table row.
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

        $summary = $this->summary;
        $rows = $this->rows;
        $filtering = $this->hasFilters();
        $canDelete = $this->canDelete;
        $loadingTargets = 'from,to,cause';

        $figure = number_format($summary['this_year']);
        $figureLabel = 'recorded in '.now()->year;
    @endphp

    <x-anchor-block eyebrow="Manage" title="Mortality Records" :figure="$figure" :figure-label="$figureLabel">
        <span class="datum text-foreground">{{ number_format($summary['this_month']) }}</span> this month
        &middot; <span class="datum text-foreground">{{ number_format($summary['total']) }}</span> in total.
        For farm staff only.

        <x-slot:actions>
            @if ($this->canCreate)
                <a href="{{ route('mortality.create') }}" wire:navigate class="btn-primary w-full sm:w-auto">
                    Record a Death
                </a>
            @endif
        </x-slot:actions>
    </x-anchor-block>

    {{-- A Livewire update does not re-render the layout, so the confirmation
         of a delete is shown from inside the component. --}}
    @if ($status)
        <div class="mt-6 rounded-[8px] bg-success-bg px-4 py-3 text-[15px] text-foreground" role="status">
            {{ $status }}
        </div>
    @endif

    {{-- ---------------------------------------------------------------
         Causes. A printed bar, not a chart widget: ink on a sunk track.
         Colour means bloodline here, so none is spent on this.
    ---------------------------------------------------------------- --}}
    @if ($this->causeBreakdown->isNotEmpty())
        @php $breakdownTotal = $this->causeBreakdown->sum('total'); @endphp

        <section class="mt-8 max-w-[44rem]" aria-labelledby="causes-heading">
            <h2 id="causes-heading" class="font-display text-[19px] font-semibold tracking-[-0.015em] text-foreground">
                Causes of death
                <span class="ml-1 font-sans text-[14px] font-normal tracking-normal text-muted-foreground">
                    {{ $filtering ? 'for the records you are filtering' : 'all records' }}
                </span>
            </h2>

            <ul class="mt-4 space-y-3.5 border-t border-border pt-4">
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
        </section>
    @endif

    {{-- ---------------------------------------------------------------
         Filters
    ---------------------------------------------------------------- --}}
    <x-filter-bar class="mt-8" :active="$filtering" clear="clearFilters">
        <div class="flex min-h-11 w-full items-center gap-2 sm:w-auto sm:shrink-0">
            <label for="filter-from" class="label shrink-0">Died on or after</label>
            <input id="filter-from" type="date" wire:model.live="from" class="input datum w-full sm:w-auto">
        </div>

        <div class="flex min-h-11 w-full items-center gap-2 sm:w-auto sm:shrink-0">
            <label for="filter-to" class="label shrink-0">Died on or before</label>
            <input id="filter-to" type="date" wire:model.live="to" class="input datum w-full sm:w-auto">
        </div>

        <x-filter-select id="filter-cause" label="Cause" wire:model.live="cause">
            <option value="">All causes</option>
            @foreach ($this->causeOptions as $option)
                <option value="{{ $option }}">{{ $option }}</option>
            @endforeach
        </x-filter-select>

        <x-slot:loading>
            <span wire:loading wire:target="{{ $loadingTargets }}">Loading&hellip;</span>
        </x-slot:loading>
    </x-filter-bar>

    {{-- ---------------------------------------------------------------
         The register
    ---------------------------------------------------------------- --}}
    @if ($rows->isEmpty())
        @if ($filtering)
            <x-empty-state title="No deaths match these filters."
                           description="Try widening the dates, or choose “All causes”."
                           icon="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z">
                <button type="button" wire:click="clearFilters" class="btn-secondary">Clear filters</button>
            </x-empty-state>
        @else
            <x-empty-state title="No deaths have been recorded yet."
                           :description="$this->canCreate ? 'When a bird dies, record it here so the farm keeps a complete history for that bird.' : null"
                           icon="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z">
                @if ($this->canCreate)
                    <a href="{{ route('mortality.create') }}" wire:navigate class="btn-primary">Record a Death</a>
                @endif
            </x-empty-state>
        @endif
    @else
        {{-- Phones: one block per record - the bird and the date first, the
             cause under it. Phones are what the farm staff actually carry. --}}
        <ul class="divide-y divide-border border-y border-border md:hidden"
            wire:loading.remove wire:target="{{ $loadingTargets }}">
            @foreach ($rows as $record)
                <li class="py-4" wire:key="card-{{ $record->id }}">
                    <div class="flex items-start justify-between gap-3">
                        <p class="min-w-0 truncate text-[16px] font-medium text-foreground">{{ $record->broodcock?->name ?? 'Unknown bird' }}</p>
                        <p class="datum shrink-0 pt-0.5 text-[14px] text-muted-foreground">{{ $record->date_of_death->format('d M Y') }}</p>
                    </div>

                    <p class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-[14px] text-muted-foreground">
                        @if ($record->broodcock)
                            <span class="inline-flex items-center gap-1.5">
                                <x-band-dot :bloodline="$record->broodcock->bloodline" />
                                @if ($record->broodcock->band_number)
                                    <span class="datum">{{ $record->broodcock->band_number }}</span>
                                @else
                                    Not yet banded
                                @endif
                            </span>
                        @endif
                        <span class="datum">{{ $ageLabel($record->ageAtDeathInMonths()) }}</span>
                    </p>

                    <dl class="mt-2 space-y-1 text-[15px]">
                        <div class="flex gap-2">
                            <dt class="shrink-0 text-muted-foreground">Cause:</dt>
                            <dd class="text-foreground">{{ $record->cause_of_death }}</dd>
                        </div>
                        <div class="flex gap-2">
                            <dt class="shrink-0 text-muted-foreground">Disposal:</dt>
                            <dd class="text-foreground">{{ $record->disposal_method ?: 'Not recorded' }}</dd>
                        </div>
                        <div class="flex gap-2">
                            <dt class="shrink-0 text-muted-foreground">Recorded by:</dt>
                            <dd class="text-foreground">{{ $record->recordedBy?->full_name ?? 'Not recorded' }}</dd>
                        </div>
                    </dl>

                    @if ($canDelete)
                        <button type="button"
                                wire:click="confirmDelete({{ $record->id }})"
                                class="btn-quiet mt-3 -ml-3 text-destructive">
                            Delete Record
                        </button>
                    @endif
                </li>
            @endforeach
        </ul>

        <div class="space-y-5 border-y border-border py-5 md:hidden" aria-hidden="true"
             wire:loading.block wire:target="{{ $loadingTargets }}">
            @for ($i = 0; $i < 4; $i++)
                <div>
                    <div class="skeleton h-3 {{ ['w-3/4', 'w-2/3', 'w-4/5', 'w-1/2'][$i] }}"></div>
                    <div class="skeleton mt-3 h-3 w-2/5"></div>
                </div>
            @endfor
        </div>

        {{-- Wider screens: a ledger with a header that stays in view. No
             overflow wrapper - a scrolling ancestor would stop it sticking. --}}
        <table class="hidden w-full border-separate border-spacing-0 text-[15px] md:table">
            <thead class="sticky top-0 z-10 bg-background">
                <tr>
                    <x-sort-control field="date_of_death" label="Date of Death"
                                    :current="$sortBy" :direction="$sortDirection"
                                    cell="border-b border-border pl-0" wire:click="sort('date_of_death')" />
                    <th scope="col" class="border-b border-border px-4 py-3 text-left text-[13px] font-semibold text-muted-foreground">Bird</th>
                    <x-sort-control field="cause_of_death" label="Cause of Death"
                                    :current="$sortBy" :direction="$sortDirection"
                                    cell="border-b border-border" wire:click="sort('cause_of_death')" />
                    <th scope="col" class="hidden border-b border-border px-4 py-3 text-left text-[13px] font-semibold text-muted-foreground lg:table-cell">Disposal method</th>
                    <th scope="col" class="border-b border-border px-4 py-3 text-left text-[13px] font-semibold text-muted-foreground">Age at death</th>
                    <th scope="col" class="hidden border-b border-border px-4 py-3 text-left text-[13px] font-semibold text-muted-foreground lg:table-cell">Recorded by</th>
                    @if ($canDelete)
                        <th scope="col" class="border-b border-border py-3 pl-4"><span class="sr-only">Actions</span></th>
                    @endif
                </tr>
            </thead>

            <tbody wire:loading.remove wire:target="{{ $loadingTargets }}">
                @foreach ($rows as $record)
                    <tr wire:key="row-{{ $record->id }}" class="row-hover align-top">
                        <td class="datum whitespace-nowrap border-b border-border py-3 pr-4 text-foreground">
                            {{ $record->date_of_death->format('d M Y') }}
                        </td>
                        <td class="border-b border-border px-4 py-3">
                            <p class="font-medium text-foreground">{{ $record->broodcock?->name ?? 'Unknown bird' }}</p>
                            <p class="mt-0.5 inline-flex items-center gap-1.5 text-[13px] text-muted-foreground">
                                @if ($record->broodcock)
                                    <x-band-dot :bloodline="$record->broodcock->bloodline" />
                                    @if ($record->broodcock->band_number)
                                        <span class="datum">{{ $record->broodcock->band_number }}</span>
                                    @else
                                        Not yet banded
                                    @endif
                                @else
                                    Band not recorded
                                @endif
                            </p>
                        </td>
                        <td class="border-b border-border px-4 py-3 text-foreground">{{ $record->cause_of_death }}</td>
                        <td class="hidden border-b border-border px-4 py-3 text-muted-foreground lg:table-cell">{{ $record->disposal_method ?: 'Not recorded' }}</td>
                        <td class="datum whitespace-nowrap border-b border-border px-4 py-3 text-muted-foreground">
                            {{ $ageLabel($record->ageAtDeathInMonths()) }}
                        </td>
                        <td class="hidden border-b border-border px-4 py-3 text-muted-foreground lg:table-cell">{{ $record->recordedBy?->full_name ?? 'Not recorded' }}</td>
                        @if ($canDelete)
                            <td class="border-b border-border py-1.5 pl-4 text-right">
                                <button type="button"
                                        wire:click="confirmDelete({{ $record->id }})"
                                        class="btn-quiet -mr-3 text-destructive">
                                    Delete<span class="sr-only"> the death record for {{ $record->broodcock?->name ?? 'this bird' }}</span>
                                </button>
                            </td>
                        @endif
                    </tr>
                @endforeach
            </tbody>

            <x-table-skeleton :cols="$canDelete ? 7 : 6" wire:loading wire:target="{{ $loadingTargets }}" />
        </table>

        <div class="mt-6">
            {{ $rows->links() }}
        </div>
    @endif

    {{-- ---------------------------------------------------------------
         Delete confirmation. Names the bird and the date, and says exactly
         what will happen - never "Are you sure?".
    ---------------------------------------------------------------- --}}
    @if ($this->confirmingRecord)
        @php $confirming = $this->confirmingRecord; @endphp
        <div class="dialog-backdrop fixed inset-0 z-50 flex items-center justify-center bg-foreground/60 p-4"
             x-data x-trap.noscroll="true"
             x-on:keydown.escape.window="$wire.cancelDelete()"
             role="dialog" aria-modal="true" aria-labelledby="delete-mortality-title">
            <div class="dialog-panel elev-3 w-full max-w-md rounded-[12px] bg-card p-6">
                <h2 id="delete-mortality-title" class="font-display text-[22px] font-semibold leading-[1.2] tracking-[-0.015em] text-foreground">
                    Delete the death record for
                    {{ $confirming->broodcock?->displayName() ?? 'this bird' }}?
                </h2>

                <div class="mt-3 space-y-3 text-[15px] leading-relaxed text-muted-foreground">
                    <p>
                        The death recorded on
                        <strong class="datum font-semibold text-foreground">{{ $confirming->date_of_death->format('d M Y') }}</strong>
                        ({{ $confirming->cause_of_death }}) will be removed from the register.
                    </p>
                    <p class="rounded-[8px] bg-destructive-bg px-3 py-2.5 text-foreground">
                        {{ $confirming->broodcock?->name ?? 'The bird' }}
                        will be put back on the <strong class="font-semibold">active</strong> list, as if the death had never
                        been recorded. Do this only if the death was entered by mistake.
                    </p>
                </div>

                <div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                    <button type="button" wire:click="cancelDelete" class="btn-secondary">
                        No, Keep the Record
                    </button>
                    <button type="button" wire:click="delete" class="btn-danger" wire:loading.attr="disabled" wire:target="delete">
                        <span class="btn-spinner" wire:loading wire:target="delete"></span>
                        Yes, Delete This Record
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
