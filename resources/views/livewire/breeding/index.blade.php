@php
    // Property access, not a method call: #[Computed] only memoizes on
    // property access, and both are read several times below.
    $summary = $this->summary;
    $records = $this->records;
    $filtering = $this->hasActiveFilters();

    // The figure is the paginator's own total, so it already follows every
    // filter - search and bloodline included - and costs no extra query.
    $figure = number_format($records->total());
    $figureLabel = $filtering
        ? ($records->total() === 1 ? 'mating matches your filters' : 'matings match your filters')
        : Str::plural('mating', $records->total()).' recorded';

    $loadingTargets = 'search,from,to,bloodline';
    // The rows also wait on sorting and paging; the "Searching…" line does not.
    $tableTargets = $loadingTargets.',sort,perPage,gotoPage,nextPage,previousPage';
@endphp

<div x-data="{ view: $persist(gfmsDefaultView()).as('gfms-view-breeding') }">
    <x-anchor-block eyebrow="Manage" title="Breeding Records" :figure="$figure" :figure-label="$figureLabel">
        Matings, egg counts and hatch results. Fertility and hatch rates are worked
        out automatically from the egg numbers.

        <x-slot:actions>
            @can('create', App\Models\BreedingRecord::class)
                <a href="{{ route('breeding.create') }}" x-on:click.prevent="$dispatch('ems-dialog', 'breeding-form')" class="d-btn d-btn-primary w-full sm:w-auto">
                    Record a Mating
                </a>
            @endcan
        </x-slot:actions>
    </x-anchor-block>

    {{-- The farm-wide funnel as EMS KPI tiles. Summed in SQL across every
         matching record, not just the page on screen. --}}
    <x-ui.kpi-row class="mt-6" role="group" aria-label="Breeding figures">
        <x-ui.kpi-tile label="Eggs set" icon="clipboard-list" :value="number_format($summary['eggs_set'])"
                       caption="Across the matings listed" />
        <x-ui.kpi-tile label="Fertility rate" icon="circle-check"
                       :value="$summary['fertility'] !== null ? $summary['fertility'].'%' : null"
                       caption="Fertile eggs of eggs set" />
        <x-ui.kpi-tile label="Hatch rate" icon="arrow-up-right"
                       :value="$summary['hatch'] !== null ? $summary['hatch'].'%' : null"
                       caption="Chicks hatched of fertile eggs" />
    </x-ui.kpi-row>

    {{-- Filters. One row: search takes the space that is left, the date range
         and the bloodline shrink to their own width and wrap when they run out
         of room. --}}
    <x-filter-bar class="mt-8" :active="$filtering" clear="clearFilters">
        <x-slot:search>
            <label for="search" class="sr-only">Search by bird</label>
            <input id="search" type="search" wire:model.live.debounce.300ms="search"
                   placeholder="Sire or dam name / band" class="d-input w-full">
        </x-slot:search>

        {{-- The two dates are one control in two halves, so they stay together
             on a wrap and read as a range rather than as two unrelated fields.
             On a phone they sit side by side, each label above its field; from
             sm up the wrapper dissolves and each is an inline label + field. --}}
        <div class="grid w-full grid-cols-2 gap-3 sm:contents">
            <div class="flex min-h-11 min-w-0 flex-col gap-1 sm:w-auto sm:shrink-0 sm:flex-row sm:items-center sm:gap-2">
                <label for="from" class="d-fieldset-legend mb-0 shrink-0 py-0 lg:text-xs">Mated from</label>
                <input id="from" type="date" wire:model.live="from" class="d-input datum w-full sm:w-auto">
            </div>

            <div class="flex min-h-11 min-w-0 flex-col gap-1 sm:w-auto sm:shrink-0 sm:flex-row sm:items-center sm:gap-2">
                <label for="to" class="d-fieldset-legend mb-0 shrink-0 py-0 lg:text-xs">Mated up to</label>
                <input id="to" type="date" wire:model.live="to" class="d-input datum w-full sm:w-auto">
            </div>
        </div>

        <x-filter-select id="bloodline" label="Bloodline (sire)" wire:model.live="bloodline">
            <option value="">All bloodlines</option>
            @foreach ($this->bloodlineOptions as $option)
                <option value="{{ $option }}">{{ $option }}</option>
            @endforeach
        </x-filter-select>

        <x-ui.view-toggle />

        {{-- The search is debounced and the round trip is to Tokyo, so a
             keystroke and its result are most of a second apart - without this
             the bar looks dead in between. --}}
        <x-slot:loading>
            <span wire:loading wire:target="{{ $loadingTargets }}">Searching&hellip;</span>
        </x-slot:loading>
    </x-filter-bar>

    @if ($records->isEmpty())
        @if ($filtering)
            <x-empty-state title="No matings match your filters"
                           description="Try widening the date range or clearing the filters."
                           icon="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z">
                <button type="button" wire:click="clearFilters" class="d-btn d-btn-outline">Clear filters</button>
            </x-empty-state>
        @else
            <x-empty-state title="No breeding records yet"
                           description="Record a mating to start tracking fertility and hatch rates."
                           icon="M12 3c-3.3 0-6 4.6-6 9.2A6 6 0 0 0 12 18.5a6 6 0 0 0 6-6.3C18 7.6 15.3 3 12 3Z">
                @can('create', App\Models\BreedingRecord::class)
                    <a href="{{ route('breeding.create') }}" x-on:click.prevent="$dispatch('ems-dialog', 'breeding-form')" class="d-btn d-btn-primary">Record your first mating</a>
                @endcan
            </x-empty-state>
        @endif
    @else
        {{-- ---------------------------------------------------------------
             WIDER SCREENS, WHEN CHOSEN: a ledger. No overflow wrapper on purpose - a
             scrolling ancestor would stop the header sticking under the
             topbar. The table is separate-bordered so the header's hairline
             travels with it when it sticks; a collapsed border stays behind.
        --------------------------------------------------------------- --}}
        {{-- EMS: a wide table scrolls inside its own container; the page never does. --}}
        <div class="hidden overflow-x-auto md:block" x-show="view === 'table'">
            <table class="d-table w-full border-separate border-spacing-0 text-[15px] lg:text-xs">
                <thead class="sticky top-0 z-10 bg-background">
                    <tr>
                        {{-- The date and the three egg counts sort. Fertility and
                             hatch rate are computed at read time from the counts,
                             not stored, so there is no column to order by. --}}
                        <x-sort-control field="mating_date" label="Mating Date"
                                        :current="$sortBy" :direction="$sortDirection"
                                        cell="border-b border-border pl-0" wire:click="sort('mating_date')" />

                        <th scope="col" class="border-b border-border px-4 py-3 text-left text-[13px] lg:text-[11px] font-semibold text-muted-foreground">Pair</th>

                        @foreach ([['eggs_set', 'Eggs Set'], ['eggs_fertile', 'Fertile'], ['eggs_hatched', 'Hatched']] as [$field, $heading])
                            <x-sort-control :field="$field" :label="$heading" align="right"
                                            :current="$sortBy" :direction="$sortDirection"
                                            cell="border-b border-border" wire:click="sort('{{ $field }}')" />
                        @endforeach

                        <th scope="col" class="hidden border-b border-border px-4 py-3 text-right text-[13px] lg:text-[11px] font-semibold text-muted-foreground lg:table-cell">Fertility</th>
                        <th scope="col" class="border-b border-border px-4 py-3 text-right text-[13px] lg:text-[11px] font-semibold text-muted-foreground">Hatch rate</th>
                        <th scope="col" class="border-b border-border py-3 pl-4 pr-0 text-right text-[13px] lg:text-[11px] font-semibold text-muted-foreground">Actions</th>
                    </tr>
                </thead>

                <tbody wire:loading.remove wire:target="{{ $tableTargets }}">
                    @foreach ($records as $record)
                        <tr class="row-hover align-top *:align-top" wire:key="r-{{ $record->id }}">
                            <td class="datum whitespace-nowrap border-b border-border py-3 pl-0 pr-4 text-foreground">
                                <span class="inline-flex min-h-11 items-center">{{ $record->mating_date->format('j M Y') }}</span>
                            </td>

                            {{-- Sire × dam, each with its band dot and band number,
                                 each opening its own bird page. --}}
                            <td class="border-b border-border px-4 py-3">
                                <div class="flex flex-wrap items-center gap-x-2">
                                    @foreach ([$record->sire, $record->dam] as $parent)
                                        @if (! $loop->first)
                                            <span class="text-muted-foreground" aria-hidden="true">&times;</span>
                                            <span class="sr-only">with</span>
                                        @endif
                                        @if ($parent === null)
                                            <span class="inline-flex min-h-11 items-center text-muted-foreground">Bird removed</span>
                                        @elseif ($parent->trashed())
                                            <span class="inline-flex min-h-11 items-center gap-2 text-muted-foreground">
                                                <x-band-dot :bloodline="$parent->bloodline" />
                                                <span>{{ $parent->name }}</span>
                                                <span class="text-[13px] lg:text-[11px]">removed</span>
                                            </span>
                                        @else
                                            <x-bird-hover :bird="$parent">
                                                <a href="{{ route('broodcocks.show', $parent) }}" wire:navigate
                                                   class="group inline-flex min-h-11 items-center gap-2">
                                                    <x-band-dot :bloodline="$parent->bloodline" />
                                                    <span class="text-foreground">{{ $parent->name }}</span>
                                                    @if ($parent->band_number)
                                                        <span class="datum text-[13px] lg:text-[11px] text-muted-foreground">{{ $parent->band_number }}</span>
                                                    @else
                                                        <span class="text-[13px] lg:text-[11px] text-muted-foreground">Not yet banded</span>
                                                    @endif
                                                </a>
                                            </x-bird-hover>
                                        @endif
                                    @endforeach
                                </div>
                            </td>

                            @foreach ([$record->eggs_set, $record->eggs_fertile, $record->eggs_hatched] as $count)
                                <td class="datum border-b border-border px-4 py-3 text-right text-foreground">
                                    <span class="inline-flex min-h-11 items-center">{{ $count }}</span>
                                </td>
                            @endforeach

                            <td class="datum hidden border-b border-border px-4 py-3 text-right text-muted-foreground lg:table-cell">
                                <span class="inline-flex min-h-11 items-center">{{ $record->fertilityRate() !== null ? $record->fertilityRate().'%' : 'No data' }}</span>
                            </td>
                            <td class="datum border-b border-border px-4 py-3 text-right text-muted-foreground">
                                <span class="inline-flex min-h-11 items-center">{{ $record->hatchRate() !== null ? $record->hatchRate().'%' : 'No data' }}</span>
                            </td>

                            <td class="whitespace-nowrap border-b border-border py-3 pl-4 pr-0 text-right">
                                <span class="inline-flex min-h-11 items-center justify-end gap-3">
                                    @if ($record->hasUnregisteredOffspring())
                                        {{-- d-badge-warning, not the utilities it resolves to: the
                                             class vocabulary is a contract with the enums. --}}
                                        <span class="d-badge d-badge-soft d-badge-warning">
                                            <span class="datum">{{ $record->unregisteredOffspring() }}</span>&nbsp;to register
                                        </span>
                                    @endif
                                    <x-ui.row-action icon="eye" :label="'View '.($record->sire?->name ?? 'Bird removed').' × '.($record->dam?->name ?? 'Bird removed')"
                                                     :href="route('breeding.show', $record)" wire:navigate />
                                </span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>

                <x-table-skeleton :cols="8" wire:loading wire:target="{{ $tableTargets }}" />
            </table>
        </div>

        {{-- CARDS: the same matings, the same action. Always on a phone, where a
             nine-column table read sideways is a table nobody reads; from md up
             only when the switch says so. --}}
        <div x-bind:class="view === 'cards' ? '' : 'md:hidden'" x-cloak>
            <ul class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4"
                wire:loading.remove wire:target="{{ $tableTargets }}">
                @foreach ($records as $record)
                    @php
                        $pairName = ($record->sire?->name ?? 'Bird removed').' × '.($record->dam?->name ?? 'Bird removed');
                        // As the table says it: a parent that was archived is named, then marked.
                        $cardTitle = ($record->sire?->name ?? 'Bird removed').($record->sire?->trashed() ? ' (removed)' : '')
                            .' × '.($record->dam?->name ?? 'Bird removed').($record->dam?->trashed() ? ' (removed)' : '');
                    @endphp
                    <li wire:key="m-card-{{ $record->id }}">
                        <x-ui.record-card :href="route('breeding.show', $record)">
                            <x-slot:title>{{ $cardTitle }}</x-slot:title>
                            <x-slot:subtitle>
                                @foreach ([$record->sire, $record->dam] as $parent)
                                    @if (! $loop->first)
                                        <span aria-hidden="true">&times;</span>
                                    @endif
                                    @if ($parent === null)
                                        Bird removed
                                    @else
                                        <span class="inline-flex items-center gap-1">
                                            <x-band-dot :bloodline="$parent->bloodline" />
                                            @if ($parent->band_number)
                                                <span class="datum">{{ $parent->band_number }}</span>
                                            @else
                                                Not yet banded
                                            @endif
                                        </span>
                                    @endif
                                @endforeach
                            </x-slot:subtitle>
                            @if ($record->hasUnregisteredOffspring())
                                <x-slot:badge>
                                    <span class="d-badge d-badge-soft d-badge-warning">
                                        <span class="datum">{{ $record->unregisteredOffspring() }}</span>&nbsp;to register
                                    </span>
                                </x-slot:badge>
                            @endif

                            <dl class="grid grid-cols-3 gap-3">
                                <x-ui.fact label="Eggs Set"><span class="datum">{{ $record->eggs_set }}</span></x-ui.fact>
                                <x-ui.fact label="Fertile"><span class="datum">{{ $record->eggs_fertile }}</span></x-ui.fact>
                                <x-ui.fact label="Hatched"><span class="datum">{{ $record->eggs_hatched }}</span></x-ui.fact>
                                <x-ui.fact label="Fertility"><span class="datum">{{ $record->fertilityRate() !== null ? $record->fertilityRate().'%' : 'No data' }}</span></x-ui.fact>
                                <x-ui.fact label="Hatch rate"><span class="datum">{{ $record->hatchRate() !== null ? $record->hatchRate().'%' : 'No data' }}</span></x-ui.fact>
                            </dl>

                            <x-slot:meta>
                                <span class="datum">{{ $record->mating_date->format('j M Y') }}</span>
                            </x-slot:meta>
                            <x-slot:actions>
                                <x-ui.row-action icon="eye" :label="'View '.$pairName"
                                                 :href="route('breeding.show', $record)" wire:navigate />
                            </x-slot:actions>
                        </x-ui.record-card>
                    </li>
                @endforeach
            </ul>
            <x-ui.cards-skeleton class="mt-4" wire:loading.grid wire:target="{{ $tableTargets }}" />
        </div>

        <x-ui.table-pagination :paginator="$records" class="mt-4" />
    @endif

    {{-- Add and edit open here, over the list, never on a page of their own. --}}
    {{-- The new-record form is built with the page, and it authorizes on
         mount, so it is only built for someone who may create one. --}}
    @can('create', \App\Models\BreedingRecord::class)
        <x-ui.form-dialog id="breeding-form" model="showForm" size="xl" :wrap="false" :title="$formRecordId ? 'Edit Breeding Record' : 'Record a Mating'">
            @if ($formRecordId || $formBroodcockId)
                {{-- An edit, or a form for one bird: filled by the server when opened. --}}
                @if ($showForm)
                    <livewire:breeding.form :record="$formRecordId ? \App\Models\BreedingRecord::find($formRecordId) : null"
                                            :in-dialog="true" :key="'breeding-form-'.($formRecordId ?? 'new').'-'.($formBroodcockId ?? '')" />
                @else
                    <x-ui.form-skeleton :fields="7" />
                @endif
            @else
                {{-- A new record: built with the page, so Add opens at once with no request. --}}
                <livewire:breeding.form :record="$formRecordId ? \App\Models\BreedingRecord::find($formRecordId) : null"
                                      :in-dialog="true" :key="'breeding-form-new-'.$formNonce" />
            @endif
        </x-ui.form-dialog>
    @endcan
</div>
