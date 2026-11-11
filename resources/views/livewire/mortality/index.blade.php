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
                <a href="{{ route('mortality.create') }}" x-on:click.prevent="$dispatch('ems-dialog', 'mortality-form')" class="d-btn d-btn-primary w-full sm:w-auto">
                    Record a Death
                </a>
            @endif
        </x-slot:actions>
    </x-anchor-block>

    {{-- The register's figures as EMS KPI tiles. --}}
    @php $leading = $this->causeBreakdown->first(); @endphp
    <x-ui.kpi-row class="mt-6" role="group" aria-label="Mortality figures">
        <x-ui.kpi-tile label="This year" icon="calendar" :value="number_format($summary['this_year'])"
                       :caption="'Deaths recorded in '.now()->year" />
        <x-ui.kpi-tile label="This month" icon="calendar" :value="number_format($summary['this_month'])"
                       :caption="'Deaths recorded in '.now()->format('F')" />
        <x-ui.kpi-tile label="All time" icon="clipboard-list" :value="number_format($summary['total'])"
                       caption="Every death on the register" />
        <x-ui.kpi-tile label="Leading cause" icon="triangle-alert" :value="$leading['cause'] ?? null"
                       :caption="$leading ? $leading['total'].' of '.$this->causeBreakdown->sum('total').' '.Str::plural('death', $this->causeBreakdown->sum('total')) : 'No deaths recorded'" />
    </x-ui.kpi-row>

    {{-- A Livewire update does not re-render the layout, so the confirmation
         of a delete is shown from inside the component. --}}
    @if ($status)
        <div class="d-alert d-alert-soft d-alert-success mt-6 block text-[15px] lg:text-xs text-base-content" role="status">
            {{ $status }}
        </div>
    @endif

    {{-- ---------------------------------------------------------------
         Filters
    ---------------------------------------------------------------- --}}
    <x-filter-bar class="mt-8" :active="$filtering" clear="clearFilters">
        <div class="flex min-h-11 w-full items-center gap-2 sm:w-auto sm:shrink-0">
            <label for="filter-from" class="d-fieldset-legend mb-0 shrink-0 py-0">Died on or after</label>
            <input id="filter-from" type="date" wire:model.live="from" class="d-input datum w-full sm:w-auto">
        </div>

        <div class="flex min-h-11 w-full items-center gap-2 sm:w-auto sm:shrink-0">
            <label for="filter-to" class="d-fieldset-legend mb-0 shrink-0 py-0">Died on or before</label>
            <input id="filter-to" type="date" wire:model.live="to" class="d-input datum w-full sm:w-auto">
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
                <button type="button" wire:click="clearFilters" class="d-btn d-btn-outline">Clear filters</button>
            </x-empty-state>
        @else
            <x-empty-state title="No deaths have been recorded yet."
                           :description="$this->canCreate ? 'When a bird dies, record it here so the farm keeps a complete history for that bird.' : null"
                           icon="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z">
                @if ($this->canCreate)
                    <a href="{{ route('mortality.create') }}" x-on:click.prevent="$dispatch('ems-dialog', 'mortality-form')" class="d-btn d-btn-primary">Record a Death</a>
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
                        <p class="min-w-0 truncate text-[16px] lg:text-xs font-medium text-foreground">{{ $record->broodcock?->name ?? 'Unknown bird' }}</p>
                        <p class="datum shrink-0 pt-0.5 text-[14px] lg:text-xs text-muted-foreground">{{ $record->date_of_death->format('d M Y') }}</p>
                    </div>

                    <p class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-[14px] lg:text-xs text-muted-foreground">
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

                    <dl class="mt-2 space-y-1 text-[15px] lg:text-xs">
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
                                class="d-btn d-btn-ghost mt-3 -ml-3 text-destructive">
                            Archive Record
                        </button>
                    @endif
                </li>
            @endforeach
        </ul>

        <div class="space-y-5 border-y border-border py-5 md:hidden" aria-hidden="true"
             wire:loading.block wire:target="{{ $loadingTargets }}">
            @for ($i = 0; $i < 4; $i++)
                <div>
                    <div class="d-skeleton h-3 {{ ['w-3/4', 'w-2/3', 'w-4/5', 'w-1/2'][$i] }}"></div>
                    <div class="d-skeleton mt-3 h-3 w-2/5"></div>
                </div>
            @endfor
        </div>

        {{-- Wider screens: a ledger with a header that stays in view. No
             overflow wrapper - a scrolling ancestor would stop it sticking. --}}
        <table class="d-table hidden w-full border-separate border-spacing-0 text-[15px] lg:text-xs md:table">
            <thead class="sticky top-0 z-10 bg-background">
                <tr>
                    <x-sort-control field="date_of_death" label="Date of Death"
                                    :current="$sortBy" :direction="$sortDirection"
                                    cell="border-b border-border pl-0" wire:click="sort('date_of_death')" />
                    <th scope="col" class="border-b border-border px-4 py-3 text-left text-[13px] lg:text-[11px] font-semibold text-muted-foreground">Bird</th>
                    <x-sort-control field="cause_of_death" label="Cause of Death"
                                    :current="$sortBy" :direction="$sortDirection"
                                    cell="border-b border-border" wire:click="sort('cause_of_death')" />
                    <th scope="col" class="hidden border-b border-border px-4 py-3 text-left text-[13px] lg:text-[11px] font-semibold text-muted-foreground lg:table-cell">Disposal method</th>
                    <th scope="col" class="border-b border-border px-4 py-3 text-left text-[13px] lg:text-[11px] font-semibold text-muted-foreground">Age at death</th>
                    <th scope="col" class="hidden border-b border-border px-4 py-3 text-left text-[13px] lg:text-[11px] font-semibold text-muted-foreground lg:table-cell">Recorded by</th>
                    @if ($canDelete)
                        <th scope="col" class="border-b border-border py-3 pl-4 pr-0 text-right text-[13px] lg:text-[11px] font-semibold text-muted-foreground">Actions</th>
                    @endif
                </tr>
            </thead>

            <tbody wire:loading.remove wire:target="{{ $loadingTargets }}">
                @foreach ($rows as $record)
                    <tr wire:key="row-{{ $record->id }}" class="row-hover align-top *:align-top"
                        x-data="{ preview: false, timer: null }"
                        x-on:mouseenter="if (matchMedia('(hover: hover)').matches) { timer = setTimeout(() => preview = true, 350) }"
                        x-on:mouseleave="clearTimeout(timer); preview = false">
                        <td class="datum whitespace-nowrap border-b border-border py-3 pl-0 pr-4 text-foreground">
                            {{ $record->date_of_death->format('d M Y') }}
                        </td>
                        <td class="border-b border-border px-4 py-3">
                            <p class="relative inline-flex font-medium text-foreground">
                                {{ $record->broodcock?->name ?? 'Unknown bird' }}
                                @if ($record->broodcock)
                                    <x-bird-hover-card :bird="$record->broodcock" />
                                @endif
                            </p>
                            <p class="mt-0.5 inline-flex items-center gap-1.5 text-[13px] lg:text-[11px] text-muted-foreground">
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
                            <td class="border-b border-border py-1.5 pl-4 pr-0 text-right">
                                <x-ui.row-action icon="trash-2" :label="'Archive the death record for '.($record->broodcock?->name ?? 'this bird')"
                                                 :destructive="true" wire:click="confirmDelete({{ $record->id }})" />
                            </td>
                        @endif
                    </tr>
                @endforeach
            </tbody>

            <x-table-skeleton :cols="$canDelete ? 7 : 6" wire:loading wire:target="{{ $loadingTargets }}" />
        </table>

        <x-ui.table-pagination :paginator="$rows" class="mt-4" />
    @endif

    {{-- ---------------------------------------------------------------
         Archive confirmation. Names the bird and the date, and says exactly
         what will happen - never "Are you sure?".
    ---------------------------------------------------------------- --}}
    @if ($this->confirmingRecord)
        @php $confirming = $this->confirmingRecord; @endphp
        <div class="d-modal d-modal-open"
             x-data x-trap.noscroll="true"
             x-on:keydown.escape.window="$wire.cancelDelete()"
             role="dialog" aria-modal="true" aria-labelledby="delete-mortality-title">
            <div class="d-modal-box elev-3 max-w-md">
                <h2 id="delete-mortality-title" class="font-display text-[22px] lg:text-base font-semibold leading-[1.2] tracking-[-0.015em] text-foreground">
                    Archive the death record for
                    {{ $confirming->broodcock?->displayName() ?? 'this bird' }}?
                </h2>

                <div class="mt-3 space-y-3 text-[15px] lg:text-xs leading-relaxed text-muted-foreground">
                    <p>
                        The death recorded on
                        <strong class="datum font-semibold text-foreground">{{ $confirming->date_of_death->format('d M Y') }}</strong>
                        ({{ $confirming->cause_of_death }}) leaves the register and is kept in the Archive.
                    </p>
                    <p class="d-alert d-alert-soft d-alert-error block px-3 py-2.5 text-[15px] lg:text-xs leading-relaxed text-base-content">
                        {{ $confirming->broodcock?->name ?? 'The bird' }}
                        will be put back on the <strong class="font-semibold">active</strong> list, as if the death had never
                        been recorded. Do this only if the death was entered by mistake.
                    </p>
                </div>

                <div class="d-modal-action flex-col-reverse gap-3 sm:flex-row">
                    <button type="button" wire:click="cancelDelete" class="d-btn d-btn-outline">
                        No, Keep the Record
                    </button>
                    <button type="button" wire:click="delete" class="d-btn d-btn-error" wire:loading.attr="disabled" wire:target="delete">
                        <span class="d-loading d-loading-spinner d-loading-sm" wire:loading wire:target="delete"></span>
                        Yes, Archive This Record
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- Add and edit open here, in a dialog over the list, never on a page of their own. --}}
    {{-- The new-record form is built with the page, and it authorizes on
         mount, so it is only built for someone who may create one. --}}
    @can('create', \App\Models\MortalityRecord::class)
        <x-ui.form-dialog id="mortality-form" model="showForm" size="lg" :wrap="false" :title="'Record a Death'">
            @if ($formRecordId || $formBroodcockId)
                {{-- An edit, or a form for one bird: filled by the server when opened. --}}
                @if ($showForm)
                    <livewire:mortality.form :broodcock="$formBroodcockId ? \App\Models\Broodcock::find($formBroodcockId) : null"
                                             :in-dialog="true" :key="'mortality-form-'.($formRecordId ?? 'new').'-'.($formBroodcockId ?? '')" />
                @else
                    <x-ui.form-skeleton :fields="5" />
                @endif
            @else
                {{-- A new record: built with the page, so Add opens at once with no request. --}}
                <livewire:mortality.form :broodcock="$formBroodcockId ? \App\Models\Broodcock::find($formBroodcockId) : null"
                                      :in-dialog="true" :key="'mortality-form-new-'.$formNonce" />
            @endif
        </x-ui.form-dialog>
    @endcan
</div>
