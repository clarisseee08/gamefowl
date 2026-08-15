@php $summary = $this->summary; @endphp

<div>
    <div class="mb-6 sm:flex sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-gray-900">Breeding Records</h1>
            <p class="mt-1 text-sm text-gray-600">
                Matings, egg counts and hatch results. Fertility and hatch rates are worked
                out automatically from the egg numbers.
            </p>
        </div>

        @can('create', App\Models\BreedingRecord::class)
            <a href="{{ route('breeding.create') }}" class="btn-primary mt-4 w-full sm:mt-0 sm:w-auto">
                Record a Mating
            </a>
        @endcan
    </div>

    {{-- Summary across everything matching the current filters --}}
    <div class="mb-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        @foreach ([
            ['Matings', number_format($summary['matings'])],
            ['Eggs Set', number_format($summary['eggs_set'])],
            ['Fertility Rate', $summary['fertility'] !== null ? $summary['fertility'].'%' : 'No data'],
            ['Hatch Rate', $summary['hatch'] !== null ? $summary['hatch'].'%' : 'No data'],
        ] as [$label, $value])
            <div class="card p-4">
                <p class="text-xs font-medium uppercase tracking-wide text-gray-500">{{ $label }}</p>
                <p class="mt-1 text-2xl font-bold text-gray-900">{{ $value }}</p>
            </div>
        @endforeach
    </div>

    {{-- Filters --}}
    <div class="card mb-6 p-4">
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
            <div class="mt-4 border-t border-gray-200 pt-4 text-right">
                <button type="button" wire:click="clearFilters" class="btn-secondary">Clear filters</button>
            </div>
        @endif
    </div>

    @if ($this->records->isEmpty())
        <div class="card p-12 text-center">
            <h3 class="text-base font-semibold text-gray-900">
                {{ $this->hasActiveFilters() ? 'No matings match your filters' : 'No breeding records yet' }}
            </h3>
            <p class="mt-1 text-sm text-gray-600">
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
                    <a href="{{ route('breeding.create') }}" class="btn-primary mt-6">Record your first mating</a>
                @endcan
            @endif
        </div>
    @else
        <div class="card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            @foreach (['Mating Date', 'Sire', 'Dam', 'Eggs Set', 'Fertile', 'Hatched', 'Fertility', 'Hatch Rate', ''] as $heading)
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-600">
                                    {{ $heading }}
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white">
                        @foreach ($this->records as $record)
                            <tr class="hover:bg-gray-50">
                                <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-900">
                                    {{ $record->mating_date->format('j M Y') }}
                                </td>
                                <td class="px-4 py-3 text-sm">
                                    <a href="{{ route('broodcocks.show', $record->sire_id) }}" class="text-brand-700 hover:text-brand-800">
                                        {{ $record->sire->name }}
                                    </a>
                                    <span class="block text-xs text-gray-500">{{ $record->sire->band_number ?? 'No band' }}</span>
                                </td>
                                <td class="px-4 py-3 text-sm">
                                    <a href="{{ route('broodcocks.show', $record->dam_id) }}" class="text-brand-700 hover:text-brand-800">
                                        {{ $record->dam->name }}
                                    </a>
                                    <span class="block text-xs text-gray-500">{{ $record->dam->band_number ?? 'No band' }}</span>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-600">{{ $record->eggs_set }}</td>
                                <td class="px-4 py-3 text-sm text-gray-600">{{ $record->eggs_fertile }}</td>
                                <td class="px-4 py-3 text-sm text-gray-600">{{ $record->eggs_hatched }}</td>
                                <td class="px-4 py-3 text-sm font-medium text-gray-900">
                                    {{ $record->fertilityRate() !== null ? $record->fertilityRate().'%' : '—' }}
                                </td>
                                <td class="px-4 py-3 text-sm font-medium text-gray-900">
                                    {{ $record->hatchRate() !== null ? $record->hatchRate().'%' : '—' }}
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-right text-sm">
                                    @if ($record->hasUnregisteredOffspring())
                                        <span class="badge bg-amber-100 text-amber-800 ring-amber-600/20">
                                            {{ $record->unregisteredOffspring() }} to register
                                        </span>
                                    @endif
                                    <a href="{{ route('breeding.show', $record) }}" class="ml-2 font-medium text-brand-700 hover:text-brand-800">
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
