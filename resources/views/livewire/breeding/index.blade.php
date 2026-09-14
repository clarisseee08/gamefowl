@php $summary = $this->summary; @endphp

<div>
    <div class="mb-10 sm:flex sm:items-center sm:justify-between">
        <div>
            <h1 class="page-title-marked text-[34px] font-semibold tracking-[-0.022em] leading-[1.12] text-foreground">Breeding Records</h1>
            <p class="mt-3 text-[17px] leading-relaxed text-muted-foreground">
                Matings, egg counts and hatch results. Fertility and hatch rates are worked
                out automatically from the egg numbers.
            </p>
        </div>

        @can('create', App\Models\BreedingRecord::class)
            <a href="{{ route('breeding.create') }}" wire:navigate class="btn-primary mt-4 w-full sm:mt-0 sm:w-auto">
                Record a Mating
            </a>
        @endcan
    </div>

    {{-- Summary across everything matching the current filters --}}
    <div class="mb-10 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        @foreach ([
            ['Matings', number_format($summary['matings'])],
            ['Eggs Set', number_format($summary['eggs_set'])],
            ['Fertility Rate', $summary['fertility'] !== null ? $summary['fertility'].'%' : 'No data'],
            ['Hatch Rate', $summary['hatch'] !== null ? $summary['hatch'].'%' : 'No data'],
        ] as [$label, $value])
            <div class="card p-6">
                <p class="text-[12px] font-medium uppercase tracking-[0.06em] text-muted-foreground">{{ $label }}</p>
                <p class="mt-1 text-[34px] font-semibold tracking-[-0.022em] leading-[1.12] text-foreground">{{ $value }}</p>
            </div>
        @endforeach
    </div>

    {{-- Filters. One row: search takes the space that is left, the date range
         and the bloodline shrink to their own width and wrap when they run out
         of room. --}}
    <x-filter-bar :active="$this->hasActiveFilters()" clear="clearFilters">
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
        {{-- The bar's own loading slot, which it has declared since it was
             written and which no page had ever passed. The search is debounced
             and the round trip is to Tokyo, so a keystroke and its result are
             most of a second apart - without this the bar looks dead in
             between, and a keeper types the query again. --}}
        <x-slot:loading>
            <span wire:loading wire:target="search,from,to,bloodline">Searching&hellip;</span>
        </x-slot:loading>
    </x-filter-bar>

    @if ($this->records->isEmpty())
        <div class="card p-12 text-center">
            <h3 class="text-[21px] font-semibold tracking-[-0.01em] leading-[1.25] text-foreground">
                {{ $this->hasActiveFilters() ? 'No matings match your filters' : 'No breeding records yet' }}
            </h3>
            <p class="mt-3 text-[17px] leading-relaxed text-muted-foreground">
                @if ($this->hasActiveFilters())
                    Try widening the date range or clearing the filters.
                @else
                    Record a mating to start tracking fertility and hatch rates.
                @endif
            </p>
            @if ($this->hasActiveFilters())
                <button type="button" wire:click="clearFilters" class="btn-secondary mt-6">Clear filters</button>
            @else
                @can('create', App\Models\BreedingRecord::class)
                    <a href="{{ route('breeding.create') }}" wire:navigate class="btn-primary mt-6">Record your first mating</a>
                @endcan
            @endif
        </div>
    @else
        <div class="card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-border">
                    <thead class="bg-muted">
                        <tr>
                            {{-- The date and the three egg counts sort. Sire and Dam
                                 are relationships, and Fertility and Hatch Rate are
                                 computed at read time from the counts rather than
                                 stored - BreedingRecord has no rate column, which
                                 its own test asserts. Sorting by a rate would mean
                                 ordering by an expression, and the honest version of
                                 that is to sort by the counts it is derived from. --}}
                            <x-sort-control field="mating_date" label="Mating Date"
                                            :current="$sortBy" :direction="$sortDirection"
                                            cell="px-6 py-4" wire:click="sort('mating_date')" />

                            @foreach (['Sire', 'Dam'] as $heading)
                                <th scope="col" class="px-6 py-4 text-left text-[12px] font-medium uppercase tracking-[0.06em] text-muted-foreground">
                                    {{ $heading }}
                                </th>
                            @endforeach

                            @foreach ([['eggs_set', 'Eggs Set'], ['eggs_fertile', 'Fertile'], ['eggs_hatched', 'Hatched']] as [$field, $heading])
                                <x-sort-control :field="$field" :label="$heading"
                                                :current="$sortBy" :direction="$sortDirection"
                                                cell="px-6 py-4" wire:click="sort('{{ $field }}')" />
                            @endforeach

                            @foreach (['Fertility', 'Hatch Rate', ''] as $heading)
                                <th scope="col" class="px-6 py-4 text-left text-[12px] font-medium uppercase tracking-[0.06em] text-muted-foreground">
                                    {{ $heading }}
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border bg-card" wire:loading.remove wire:target="search,from,to,bloodline">
                        @foreach ($this->records as $record)
                            <tr class="group row-hover">
                                {{-- Every figure in this row is .datum: the date, the three
                                     egg counts and both rates. They were plain text, which
                                     means eight columns of digits that do not line up
                                     down the table - the one thing this design system
                                     says a registry must never look like. --}}
                                <td class="datum whitespace-nowrap px-6 py-4 text-sm text-foreground">
                                    {{ $record->mating_date->format('j M Y') }}
                                </td>

                                {{-- Each parent carries its band, the same object the
                                     catalogue and the bird page use. It was the raw
                                     band_number with 'No band' behind a null coalesce -
                                     a third phrasing of what x-band-tag already states
                                     as "Not yet banded", and the identifier a keeper
                                     actually reads was the smallest thing in the cell. --}}
                                @foreach ([[$record->sire_id, $record->sire], [$record->dam_id, $record->dam]] as [$parentId, $parent])
                                    <td class="px-6 py-4 text-sm">
                                        <a href="{{ route('broodcocks.show', $parentId) }}" wire:navigate
                                           class="group inline-block">
                                            <span class="block text-foreground group-hover:underline">{{ $parent->name }}</span>
                                            <span class="mt-1 block">
                                                <x-band-tag :bloodline="$parent->bloodline" :band="$parent->band_number" size="xs" />
                                            </span>
                                        </a>
                                    </td>
                                @endforeach

                                <td class="datum px-6 py-4 text-sm text-muted-foreground">{{ $record->eggs_set }}</td>
                                <td class="datum px-6 py-4 text-sm text-muted-foreground">{{ $record->eggs_fertile }}</td>
                                <td class="datum px-6 py-4 text-sm text-muted-foreground">{{ $record->eggs_hatched }}</td>
                                <td class="datum px-6 py-4 text-sm font-medium text-foreground">
                                    {{ $record->fertilityRate() !== null ? $record->fertilityRate().'%' : 'No data' }}
                                </td>
                                <td class="datum px-6 py-4 text-sm font-medium text-foreground">
                                    {{ $record->hatchRate() !== null ? $record->hatchRate().'%' : 'No data' }}
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-right text-sm">
                                    @if ($record->hasUnregisteredOffspring())
                                        {{-- badge-warn, not the three utilities it resolves
                                             to. The class vocabulary is a contract shared
                                             with five PHP enums; open-coding a badge here
                                             means a restyle reaches every other badge in
                                             the application except this one. --}}
                                        <span class="badge badge-warn">
                                            <span class="datum">{{ $record->unregisteredOffspring() }}</span>&nbsp;to register
                                        </span>
                                    @endif
                                    <a href="{{ route('breeding.show', $record) }}" wire:navigate class="ml-2 font-medium text-primary hover:underline">
                                        View
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>

                    <x-table-skeleton :cols="9" wire:loading wire:target="search,from,to,bloodline" />
                </table>
            </div>
        </div>

        <div class="mt-4">{{ $this->records->links() }}</div>
    @endif
</div>
