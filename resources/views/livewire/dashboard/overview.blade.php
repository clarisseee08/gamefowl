@php
    $flock = $this->flock;
    $breeding = $this->breedingOverall;
    $trend = $this->breedingTrend;
    // Scale bars against the best month so a farm with modest rates still gets
    // a readable chart rather than four flat stubs.
    $peak = max(1, max(array_map(fn ($m) => (int) ($m['fertility'] ?? 0), $trend)));
@endphp

<div class="space-y-8">
    {{-- Headline counts --}}
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        @foreach ([
            ['Total Broodcocks', number_format($flock['total']), 'Every bird ever recorded'],
            ['On the Farm', number_format($flock['on_farm']), 'Excludes sold and deceased'],
            ['Currently Breeding', number_format($flock['breeding']), 'Status set to breeding'],
            ['Overdue Vaccinations', number_format($this->overdueCount), 'Needs attention'],
        ] as $i => [$label, $value, $hint])
            <div class="card p-5 {{ $i === 3 && $this->overdueCount > 0 ? 'ring-2 ring-rose-300' : '' }}">
                <p class="text-xs font-medium uppercase tracking-wide text-gray-500">{{ $label }}</p>
                <p class="mt-1 text-3xl font-bold {{ $i === 3 && $this->overdueCount > 0 ? 'text-rose-700' : 'text-gray-900' }}">
                    {{ $value }}
                </p>
                <p class="mt-1 text-xs text-gray-500">{{ $hint }}</p>
            </div>
        @endforeach
    </div>

    {{-- Breakdowns --}}
    <div class="grid gap-6 lg:grid-cols-3">
        @foreach ([
            ['By Status', $this->byStatus, 'status'],
            ['By Class', $this->byClass, 'class'],
        ] as [$heading, $rows, $field])
            <div class="card p-5">
                <h2 class="text-base font-semibold text-gray-900">{{ $heading }}</h2>
                @if ($rows->isEmpty())
                    <p class="mt-3 text-sm text-gray-500">No birds recorded yet.</p>
                @else
                    <ul class="mt-4 space-y-2">
                        @foreach ($rows as $row)
                            @php $enum = $row->{$field}; @endphp
                            <li class="flex items-center justify-between gap-3">
                                <span class="badge {{ $enum->badgeClasses() }}">{{ $enum->label() }}</span>
                                <span class="text-sm font-semibold text-gray-900">{{ number_format($row->total) }}</span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        @endforeach

        <div class="card p-5">
            <h2 class="text-base font-semibold text-gray-900">By Bloodline</h2>
            @if ($this->byBloodline->isEmpty())
                <p class="mt-3 text-sm text-gray-500">No bloodlines recorded yet.</p>
            @else
                <ul class="mt-4 space-y-2">
                    @foreach ($this->byBloodline as $row)
                        <li class="flex items-center justify-between gap-3">
                            <span class="truncate text-sm text-gray-700">{{ $row->bloodline }}</span>
                            <span class="text-sm font-semibold text-gray-900">{{ number_format($row->total) }}</span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>

    {{-- Breeding trend. Hand-drawn with CSS rather than a charting library -
         the thesis describes a PHP/HTML/CSS system and adding a JS chart
         dependency would contradict it. --}}
    <div class="card p-5">
        <div class="sm:flex sm:items-start sm:justify-between">
            <div>
                <h2 class="text-base font-semibold text-gray-900">Breeding Success Trend</h2>
                <p class="mt-1 text-sm text-gray-600">
                    Fertility rate per month, worked out from total eggs — not by averaging
                    each mating's percentage, which would let a 2-egg mating count as much
                    as a 200-egg one.
                </p>
            </div>
            <select wire:model.live="trendMonths" class="input mt-3 sm:mt-0 sm:w-auto" aria-label="Trend period">
                <option value="6">Last 6 months</option>
                <option value="12">Last 12 months</option>
                <option value="24">Last 24 months</option>
            </select>
        </div>

        <div class="mt-5 grid gap-4 sm:grid-cols-3">
            @foreach ([
                ['Matings Recorded', number_format($breeding['matings'])],
                ['Overall Fertility', $breeding['fertility'] !== null ? $breeding['fertility'].'%' : 'No data'],
                ['Overall Hatch Rate', $breeding['hatch'] !== null ? $breeding['hatch'].'%' : 'No data'],
            ] as [$label, $value])
                <div class="rounded-lg bg-gray-50 p-4">
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-500">{{ $label }}</p>
                    <p class="mt-1 text-2xl font-bold text-gray-900">{{ $value }}</p>
                </div>
            @endforeach
        </div>

        <div class="mt-6 overflow-x-auto">
            <div class="flex min-w-max items-end gap-2" style="height: 9rem;">
                @foreach ($trend as $month)
                    <div class="flex w-14 flex-col items-center justify-end gap-1" style="height: 100%;">
                        @if ($month['fertility'] !== null)
                            <span class="text-[10px] font-semibold text-gray-700">{{ $month['fertility'] }}%</span>
                            <div class="w-full rounded-t bg-brand-500"
                                 style="height: {{ max(2, (int) round(($month['fertility'] / $peak) * 100)) }}%"
                                 title="{{ $month['label'] }}: {{ $month['fertility'] }}% fertility from {{ $month['eggs_set'] }} eggs"></div>
                        @else
                            <span class="text-[10px] text-gray-400">—</span>
                            <div class="w-full rounded-t border border-dashed border-gray-300" style="height: 2%"></div>
                        @endif
                        <span class="text-[10px] text-gray-500">{{ Str::before($month['label'], ' ') }}</span>
                    </div>
                @endforeach
            </div>
            <p class="mt-2 text-xs text-gray-500">
                A dash means no matings were recorded that month — which is different from a 0% result.
            </p>
        </div>
    </div>

    {{-- Attention lists --}}
    <div class="grid gap-6 lg:grid-cols-2">
        <div class="card p-5">
            <div class="flex items-center justify-between">
                <h2 class="text-base font-semibold text-gray-900">Vaccinations Needing Attention</h2>
                @if (Route::has('health.schedule'))
                    <a href="{{ route('health.schedule') }}" class="text-sm font-medium text-brand-700 hover:text-brand-800">
                        Full schedule
                    </a>
                @endif
            </div>

            @if ($this->overdueVaccinations->isEmpty() && $this->upcomingVaccinations->isEmpty())
                <p class="mt-4 rounded-lg bg-brand-50 p-4 text-sm text-brand-800">
                    Nothing is overdue and nothing is due in the next
                    {{ config('gfms.vaccination_warning_days') }} days. The flock is up to date.
                </p>
            @else
                <ul class="mt-4 divide-y divide-gray-200">
                    @foreach ($this->overdueVaccinations as $record)
                        <li class="flex items-center justify-between gap-3 py-2.5">
                            <div class="min-w-0">
                                <p class="truncate text-sm font-medium text-gray-900">
                                    {{ $record->broodcock?->name ?? 'Unknown bird' }}
                                </p>
                                <p class="truncate text-xs text-gray-500">
                                    {{ $record->record_type->label() }}
                                    @if ($record->product_name) &middot; {{ $record->product_name }} @endif
                                </p>
                            </div>
                            <span class="badge shrink-0 bg-rose-100 text-rose-800 ring-rose-600/20">
                                {{ abs((int) $record->daysUntilDue()) }} days overdue
                            </span>
                        </li>
                    @endforeach

                    @foreach ($this->upcomingVaccinations as $record)
                        <li class="flex items-center justify-between gap-3 py-2.5">
                            <div class="min-w-0">
                                <p class="truncate text-sm font-medium text-gray-900">
                                    {{ $record->broodcock?->name ?? 'Unknown bird' }}
                                </p>
                                <p class="truncate text-xs text-gray-500">
                                    {{ $record->record_type->label() }}
                                    @if ($record->product_name) &middot; {{ $record->product_name }} @endif
                                </p>
                            </div>
                            <span class="badge shrink-0 bg-amber-100 text-amber-800 ring-amber-600/20">
                                due in {{ (int) $record->daysUntilDue() }} days
                            </span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>

        <div class="card p-5">
            <div class="flex items-center justify-between">
                <h2 class="text-base font-semibold text-gray-900">Recent Mortality</h2>
                <span class="text-sm text-gray-500">{{ $this->deathsThisYear }} this year</span>
            </div>

            @if ($this->recentMortality->isEmpty())
                <p class="mt-4 rounded-lg bg-brand-50 p-4 text-sm text-brand-800">
                    No deaths have been recorded.
                </p>
            @else
                <ul class="mt-4 divide-y divide-gray-200">
                    @foreach ($this->recentMortality as $record)
                        <li class="flex items-center justify-between gap-3 py-2.5">
                            <div class="min-w-0">
                                <p class="truncate text-sm font-medium text-gray-900">
                                    {{ $record->broodcock?->name ?? 'Unknown bird' }}
                                </p>
                                <p class="truncate text-xs text-gray-500">{{ $record->cause_of_death }}</p>
                            </div>
                            <span class="shrink-0 text-xs text-gray-500">
                                {{ $record->date_of_death->format('j M Y') }}
                            </span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>

    @if ($this->birdsWithoutPedigree > 0)
        <div class="card p-5">
            <h2 class="text-base font-semibold text-gray-900">Pedigree Completeness</h2>
            <p class="mt-2 text-sm text-gray-600">
                <strong>{{ number_format($this->birdsWithoutPedigree) }}</strong>
                {{ Str::plural('bird', $this->birdsWithoutPedigree) }} have no sire or dam recorded, so they
                cannot appear in a family tree. Recording parents is what turns the bloodline
                field from a label into real traceability.
            </p>
        </div>
    @endif
</div>
