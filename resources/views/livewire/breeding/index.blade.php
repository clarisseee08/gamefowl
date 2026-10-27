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
@endphp

<div>
    <x-anchor-block eyebrow="Manage" title="Breeding Records" :figure="$figure" :figure-label="$figureLabel">
        Matings, egg counts and hatch results. Fertility and hatch rates are worked
        out automatically from the egg numbers.

        <x-slot:actions>
            @can('create', App\Models\BreedingRecord::class)
                <a href="{{ route('breeding.create') }}" wire:navigate class="btn-primary w-full sm:w-auto">
                    Record a Mating
                </a>
            @endcan
        </x-slot:actions>
    </x-anchor-block>

    {{-- The farm-wide funnel, as type on paper. Summed in SQL across every
         matching record, not just the page on screen. --}}
    <dl class="sb-line mt-6 grid grid-cols-3 gap-4 sm:max-w-[34rem] sm:gap-8" style="--line: 220ms">
        @foreach ([
            ['Eggs set', number_format($summary['eggs_set'])],
            ['Fertility rate', $summary['fertility'] !== null ? $summary['fertility'].'%' : 'No data'],
            ['Hatch rate', $summary['hatch'] !== null ? $summary['hatch'].'%' : 'No data'],
        ] as [$label, $value])
            <div class="min-w-0">
                <dt class="text-[13px] font-semibold text-muted-foreground">{{ $label }}</dt>
                <dd class="datum mt-1 font-display text-[22px] font-semibold leading-tight tracking-[-0.015em] text-foreground sm:text-[26px]">{{ $value }}</dd>
            </div>
        @endforeach
    </dl>

    {{-- Filters. One row: search takes the space that is left, the date range
         and the bloodline shrink to their own width and wrap when they run out
         of room. --}}
    <x-filter-bar class="mt-8" :active="$filtering" clear="clearFilters">
        <x-slot:search>
            <label for="search" class="sr-only">Search by bird</label>
            <input id="search" type="search" wire:model.live.debounce.300ms="search"
                   placeholder="Sire or dam name / band" class="input">
        </x-slot:search>

        {{-- The two dates are one control in two halves, so they stay together
             on a wrap and read as a range rather than as two unrelated fields. --}}
        <div class="flex min-h-11 w-full items-center gap-2 sm:w-auto sm:shrink-0">
            <label for="from" class="label shrink-0">Mated from</label>
            <input id="from" type="date" wire:model.live="from" class="input datum w-full sm:w-auto">
        </div>

        <div class="flex min-h-11 w-full items-center gap-2 sm:w-auto sm:shrink-0">
            <label for="to" class="label shrink-0">Mated up to</label>
            <input id="to" type="date" wire:model.live="to" class="input datum w-full sm:w-auto">
        </div>

        <x-filter-select id="bloodline" label="Bloodline (sire)" wire:model.live="bloodline">
            <option value="">All bloodlines</option>
            @foreach ($this->bloodlineOptions as $option)
                <option value="{{ $option }}">{{ $option }}</option>
            @endforeach
        </x-filter-select>

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
                <button type="button" wire:click="clearFilters" class="btn-secondary">Clear filters</button>
            </x-empty-state>
        @else
            <x-empty-state title="No breeding records yet"
                           description="Record a mating to start tracking fertility and hatch rates."
                           icon="M12 3c-3.3 0-6 4.6-6 9.2A6 6 0 0 0 12 18.5a6 6 0 0 0 6-6.3C18 7.6 15.3 3 12 3Z">
                @can('create', App\Models\BreedingRecord::class)
                    <a href="{{ route('breeding.create') }}" wire:navigate class="btn-primary">Record your first mating</a>
                @endcan
            </x-empty-state>
        @endif
    @else
        {{-- ---------------------------------------------------------------
             PHONES: one block per mating. The pair on the first line, the
             date and the egg funnel on the second. A nine-column table read
             sideways on a phone is a table nobody reads.
        --------------------------------------------------------------- --}}
        <ul class="divide-y divide-border border-y border-border md:hidden"
            wire:loading.remove wire:target="{{ $loadingTargets }}">
            @foreach ($records as $record)
                <li class="py-4" wire:key="m-{{ $record->id }}">
                    <a href="{{ route('breeding.show', $record) }}" wire:navigate
                       class="flex min-h-11 items-start justify-between gap-3">
                        <span class="min-w-0 text-[16px] font-medium text-foreground">
                            {{ $record->sire->name }}
                            <span class="text-muted-foreground" aria-hidden="true">&times;</span>
                            <span class="sr-only">with</span>
                            {{ $record->dam->name }}
                        </span>
                        <span class="datum shrink-0 pt-0.5 text-[14px] text-muted-foreground">{{ $record->mating_date->format('j M Y') }}</span>
                    </a>

                    <p class="mt-1 flex flex-wrap items-center gap-x-4 gap-y-1 text-[14px] text-muted-foreground">
                        @foreach ([$record->sire, $record->dam] as $parent)
                            <span class="inline-flex items-center gap-1.5">
                                <x-band-dot :bloodline="$parent->bloodline" />
                                @if ($parent->band_number)
                                    <span class="datum">{{ $parent->band_number }}</span>
                                @else
                                    Not yet banded
                                @endif
                            </span>
                        @endforeach
                    </p>

                    <p class="mt-2 text-[14px] text-muted-foreground">
                        <span class="datum text-foreground">{{ $record->eggs_set }}</span> set
                        &middot; <span class="datum text-foreground">{{ $record->eggs_fertile }}</span> fertile
                        &middot; <span class="datum text-foreground">{{ $record->eggs_hatched }}</span> hatched
                        @if ($record->hatchRate() !== null)
                            &middot; <span class="datum text-foreground">{{ $record->hatchRate() }}%</span> hatch rate
                        @endif
                    </p>

                    @if ($record->hasUnregisteredOffspring())
                        <p class="mt-2">
                            <span class="badge badge-warn">
                                <span class="datum">{{ $record->unregisteredOffspring() }}</span>&nbsp;to register
                            </span>
                        </p>
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

        {{-- ---------------------------------------------------------------
             WIDER SCREENS: a ledger. No overflow wrapper on purpose - a
             scrolling ancestor would stop the header sticking under the
             topbar. The table is separate-bordered so the header's hairline
             travels with it when it sticks; a collapsed border stays behind.
        --------------------------------------------------------------- --}}
        <table class="hidden w-full border-separate border-spacing-0 text-[15px] md:table">
            <thead class="sticky top-0 z-10 bg-background">
                <tr>
                    {{-- The date and the three egg counts sort. Fertility and
                         hatch rate are computed at read time from the counts,
                         not stored, so there is no column to order by. --}}
                    <x-sort-control field="mating_date" label="Mating Date"
                                    :current="$sortBy" :direction="$sortDirection"
                                    cell="border-b border-border pl-0" wire:click="sort('mating_date')" />

                    <th scope="col" class="border-b border-border px-4 py-3 text-left text-[13px] font-semibold text-muted-foreground">Pair</th>

                    @foreach ([['eggs_set', 'Eggs Set'], ['eggs_fertile', 'Fertile'], ['eggs_hatched', 'Hatched']] as [$field, $heading])
                        <x-sort-control :field="$field" :label="$heading" align="right"
                                        :current="$sortBy" :direction="$sortDirection"
                                        cell="border-b border-border" wire:click="sort('{{ $field }}')" />
                    @endforeach

                    <th scope="col" class="hidden border-b border-border px-4 py-3 text-right text-[13px] font-semibold text-muted-foreground lg:table-cell">Fertility</th>
                    <th scope="col" class="border-b border-border px-4 py-3 text-right text-[13px] font-semibold text-muted-foreground">Hatch rate</th>
                    <th scope="col" class="border-b border-border py-3 pl-4"><span class="sr-only">Open</span></th>
                </tr>
            </thead>

            <tbody wire:loading.remove wire:target="{{ $loadingTargets }}">
                @foreach ($records as $record)
                    <tr class="row-hover align-top" wire:key="r-{{ $record->id }}">
                        <td class="datum whitespace-nowrap border-b border-border py-3 pr-4 text-foreground">
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
                                    <a href="{{ route('broodcocks.show', $parent) }}" wire:navigate
                                       class="group inline-flex min-h-11 items-center gap-2">
                                        <x-band-dot :bloodline="$parent->bloodline" />
                                        <span class="text-foreground group-hover:underline group-hover:underline-offset-4">{{ $parent->name }}</span>
                                        @if ($parent->band_number)
                                            <span class="datum text-[13px] text-muted-foreground">{{ $parent->band_number }}</span>
                                        @else
                                            <span class="text-[13px] text-muted-foreground">Not yet banded</span>
                                        @endif
                                    </a>
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

                        <td class="whitespace-nowrap border-b border-border py-3 pl-4 text-right">
                            <span class="inline-flex min-h-11 items-center justify-end gap-3">
                                @if ($record->hasUnregisteredOffspring())
                                    {{-- badge-warn, not the utilities it resolves to: the
                                         class vocabulary is a contract with the enums. --}}
                                    <span class="badge badge-warn">
                                        <span class="datum">{{ $record->unregisteredOffspring() }}</span>&nbsp;to register
                                    </span>
                                @endif
                                <a href="{{ route('breeding.show', $record) }}" wire:navigate
                                   class="inline-flex min-h-11 items-center font-semibold text-primary hover:underline hover:underline-offset-4">
                                    View<span class="sr-only"> {{ $record->sire->name }} &times; {{ $record->dam->name }}</span>
                                </a>
                            </span>
                        </td>
                    </tr>
                @endforeach
            </tbody>

            <x-table-skeleton :cols="8" wire:loading wire:target="{{ $loadingTargets }}" />
        </table>

        <div class="mt-6">{{ $records->links() }}</div>
    @endif
</div>
