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

    {{-- Filters --}}
    <div class="card mb-10 p-6">
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div>
                <label for="search" class="label">Search by bird</label>
                <input id="search" type="search" wire:model.live.debounce.300ms="search"
                       placeholder="Sire or dam name / band" class="input mt-1">
            </div>
            <div>
                <label for="from" class="label">Mated from</label>
                <input id="from" type="date" wire:model.live="from" class="input mt-1">
            </div>
            <div>
                <label for="to" class="label">Mated up to</label>
                <input id="to" type="date" wire:model.live="to" class="input mt-1">
            </div>
            <div>
                <label for="bloodline" class="label">Bloodline (sire)</label>
                <select id="bloodline" wire:model.live="bloodline" class="input mt-1">
                    <option value="">All bloodlines</option>
                    @foreach ($this->bloodlineOptions as $option)
                        <option value="{{ $option }}">{{ $option }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        @if ($this->hasActiveFilters())
            <div class="mt-4 border-t border-border pt-4 text-right">
                <button type="button" wire:click="clearFilters" class="btn-secondary">Clear filters</button>
            </div>
        @endif
    </div>

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
                            @foreach (['Mating Date', 'Sire', 'Dam', 'Eggs Set', 'Fertile', 'Hatched', 'Fertility', 'Hatch Rate', ''] as $heading)
                                <th scope="col" class="px-6 py-4 text-left text-[12px] font-medium uppercase tracking-[0.06em] text-muted-foreground">
                                    {{ $heading }}
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border bg-card">
                        @foreach ($this->records as $record)
                            <tr class="group row-hover">
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-foreground">
                                    {{ $record->mating_date->format('j M Y') }}
                                </td>
                                <td class="px-6 py-4 text-sm">
                                    <a href="{{ route('broodcocks.show', $record->sire_id) }}" wire:navigate class="text-primary hover:underline">
                                        {{ $record->sire->name }}
                                    </a>
                                    <span class="block text-xs text-muted-foreground">{{ $record->sire->band_number ?? 'No band' }}</span>
                                </td>
                                <td class="px-6 py-4 text-sm">
                                    <a href="{{ route('broodcocks.show', $record->dam_id) }}" wire:navigate class="text-primary hover:underline">
                                        {{ $record->dam->name }}
                                    </a>
                                    <span class="block text-xs text-muted-foreground">{{ $record->dam->band_number ?? 'No band' }}</span>
                                </td>
                                <td class="px-6 py-4 text-sm text-muted-foreground">{{ $record->eggs_set }}</td>
                                <td class="px-6 py-4 text-sm text-muted-foreground">{{ $record->eggs_fertile }}</td>
                                <td class="px-6 py-4 text-sm text-muted-foreground">{{ $record->eggs_hatched }}</td>
                                <td class="px-6 py-4 text-sm font-medium text-foreground">
                                    {{ $record->fertilityRate() !== null ? $record->fertilityRate().'%' : '—' }}
                                </td>
                                <td class="px-6 py-4 text-sm font-medium text-foreground">
                                    {{ $record->hatchRate() !== null ? $record->hatchRate().'%' : '—' }}
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-right text-sm">
                                    @if ($record->hasUnregisteredOffspring())
                                        <span class="badge bg-warning-bg text-warning ring-warning/20">
                                            {{ $record->unregisteredOffspring() }} to register
                                        </span>
                                    @endif
                                    <a href="{{ route('breeding.show', $record) }}" wire:navigate class="ml-2 font-medium text-primary hover:underline">
                                        View
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-4">{{ $this->records->links() }}</div>
    @endif
</div>
