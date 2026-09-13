@php
    $flock = $this->flock;
    $breeding = $this->breedingOverall;
    $trend = $this->breedingTrend;
    // Scale bars against the best month so a farm with modest rates still gets
    // a readable chart rather than four flat stubs.
    $peak = max(1, max(array_map(fn ($m) => (int) ($m['fertility'] ?? 0), $trend)));
@endphp

<div class="space-y-12">
    @php
        /*
         * Only the fertility tile gets a sparkline, because it is the only figure
         * on this screen with a time series behind it that already exists -
         * breedingTrend() is one query and is already computed for the chart
         * below. The other three would each need a new aggregate to draw a
         * twelve-point history, which is a schema-adjacent change for decoration.
         * So they carry a supporting proportion instead of a fake trend.
         */
        $fertilitySeries = array_map(fn ($m) => $m['fertility'], $trend);

        $scheduled = $this->overdueCount + $this->upcomingVaccinations->count();
        $compliance = $scheduled > 0
            ? round((($scheduled - $this->overdueCount) / $scheduled) * 100, 1)
            : null;
    @endphp

    {{-- Headline counts --}}
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ([
            ['Total Broodcocks', number_format($flock['total']), 'Every bird ever recorded', null],
            ['On the Farm', number_format($flock['on_farm']), 'Excludes sold and deceased',
                $flock['total'] > 0 ? round($flock['on_farm'] / $flock['total'] * 100) : null],
            ['Currently Breeding', number_format($flock['breeding']), 'Status set to breeding',
                $flock['on_farm'] > 0 ? round($flock['breeding'] / $flock['on_farm'] * 100) : null],
            ['Overdue Vaccinations', number_format($this->overdueCount), 'Needs attention', null],
        ] as $i => [$label, $value, $hint, $share])
            @php $isAlert = $i === 3 && $this->overdueCount > 0; @endphp
            <div @class(['card p-5', 'border-destructive/30' => $isAlert])>
                <p class="text-[11px] font-semibold uppercase tracking-[0.07em] text-muted-foreground">{{ $label }}</p>
                <p @class([
                    'datum mt-1.5 text-[34px] font-semibold leading-[1.05]',
                    'text-destructive' => $isAlert,
                    'text-foreground' => ! $isAlert,
                ])>{{ $value }}</p>

                @if ($share !== null)
                    <div class="meter mt-3">
                        <span class="bg-primary" style="width: {{ $share }}%"></span>
                    </div>
                    <p class="mt-2 text-[12px] text-muted-foreground">
                        <span class="datum">{{ $share }}%</span> {{ $hint }}
                    </p>
                @else
                    <p class="mt-2 text-[12px] text-muted-foreground">{{ $hint }}</p>
                @endif
            </div>
        @endforeach
    </div>

    {{-- Two tiles that carry a shape rather than a number alone. --}}
    <div class="grid gap-4 lg:grid-cols-2">
        <div class="card p-5">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-[11px] font-semibold uppercase tracking-[0.07em] text-muted-foreground">Fertility trend</p>
                    <p class="datum mt-1.5 text-[28px] font-semibold leading-none text-foreground">
                        {{ $breeding['fertility'] !== null ? $breeding['fertility'].'%' : 'No data' }}
                    </p>
                    <p class="mt-2 text-[12px] text-muted-foreground">
                        Last <span class="datum">{{ count($trend) }}</span> months
                    </p>
                </div>
                <x-sparkline :points="$fertilitySeries" tone="primary"
                             label="Monthly fertility rate over the last {{ count($trend) }} months" />
            </div>
        </div>

        <div class="card p-5">
            <p class="text-[11px] font-semibold uppercase tracking-[0.07em] text-muted-foreground">Vaccination compliance</p>
            <p class="datum mt-1.5 text-[28px] font-semibold leading-none {{ $compliance !== null && $compliance < 80 ? 'text-warning' : 'text-foreground' }}">
                {{ $compliance !== null ? $compliance.'%' : 'No data' }}
            </p>
            @if ($compliance !== null)
                <div class="meter mt-3">
                    <span class="{{ $compliance < 80 ? 'bg-warning' : 'bg-success' }}" style="width: {{ $compliance }}%"></span>
                </div>
                <p class="mt-2 text-[12px] text-muted-foreground">
                    <span class="datum">{{ $scheduled - $this->overdueCount }}</span> of
                    <span class="datum">{{ $scheduled }}</span> scheduled follow-ups are not overdue
                </p>
            @else
                <p class="mt-2 text-[12px] text-muted-foreground">Nothing is scheduled in the window.</p>
            @endif
        </div>
    </div>

    {{-- Breakdowns --}}
    <div class="grid gap-6 lg:grid-cols-3">
        @foreach ([
            ['By Status', $this->byStatus, 'status'],
            ['By Class', $this->byClass, 'class'],
        ] as [$heading, $rows, $field])
            <div class="card p-7">
                <h2 class="text-[21px] font-semibold tracking-[-0.01em] leading-[1.25] text-foreground">{{ $heading }}</h2>
                @if ($rows->isEmpty())
                    <p class="mt-3 text-sm text-muted-foreground">No birds recorded yet.</p>
                @else
                    <ul class="mt-4 space-y-2">
                        @foreach ($rows as $row)
                            @php $enum = $row->{$field}; @endphp
                            <li class="flex items-center justify-between gap-3 border-b border-border py-1.5 last:border-0">
                                <span class="badge {{ $enum->badgeClasses() }}">{{ $enum->label() }}</span>
                                <span class="datum text-[15px] font-medium text-foreground">{{ number_format($row->total) }}</span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        @endforeach

        <div class="card p-7">
            <h2 class="text-[21px] font-semibold tracking-[-0.01em] leading-[1.25] text-foreground">By Bloodline</h2>
            @if ($this->byBloodline->isEmpty())
                <p class="mt-3 text-sm text-muted-foreground">No bloodlines recorded yet.</p>
            @else
                {{-- The one card on this screen whose subject IS bloodline was the one
                     card not using the band colours. A keeper reads their flock as
                     "mostly Sweater" - the swatch makes that legible without counting. --}}
                <ul class="mt-4 space-y-2">
                    @foreach ($this->byBloodline as $row)
                        <li class="flex items-center justify-between gap-3 border-b border-border py-1.5 last:border-0">
                            <span class="flex min-w-0 items-center gap-2.5">
                                <span class="h-3.5 w-3.5 shrink-0 rounded-[3px]"
                                      style="background-color: {{ \App\Support\BandTag::hex($row->bloodline) }}"
                                      aria-hidden="true"></span>
                                <span class="truncate text-[15px] text-foreground">{{ $row->bloodline }}</span>
                            </span>
                            <span class="datum text-[15px] font-medium text-foreground">{{ number_format($row->total) }}</span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>

    {{-- Breeding trend. Hand-drawn with CSS rather than a charting library -
         the thesis describes a PHP/HTML/CSS system and adding a JS chart
         dependency would contradict it. --}}
    <div class="card p-7">
        <div class="sm:flex sm:items-start sm:justify-between">
            <div>
                <h2 class="text-[21px] font-semibold tracking-[-0.01em] leading-[1.25] text-foreground">Breeding Success Trend</h2>
                <p class="mt-3 text-[17px] leading-relaxed text-muted-foreground">
                    Fertility rate per month, worked out from total eggs — not by averaging
                    each mating's percentage, which would let a 2-egg mating count as much
                    as a 200-egg one.
                </p>
            </div>
            {{-- The heading beside it already says what the control does, so
                 the label stays in the accessibility tree and off the page -
                 exactly what the aria-label it replaces was doing. --}}
            <div class="mt-3 sm:mt-0 sm:w-48">
                <x-form-select id="trendMonths" label="Trend period" label-hidden wire:model.live="trendMonths">
                    <option value="6">Last 6 months</option>
                    <option value="12">Last 12 months</option>
                    <option value="24">Last 24 months</option>
                </x-form-select>
            </div>
        </div>

        <div class="mt-5 grid gap-4 sm:grid-cols-3">
            @foreach ([
                ['Matings Recorded', number_format($breeding['matings'])],
                ['Overall Fertility', $breeding['fertility'] !== null ? $breeding['fertility'].'%' : 'No data'],
                ['Overall Hatch Rate', $breeding['hatch'] !== null ? $breeding['hatch'].'%' : 'No data'],
            ] as [$label, $value])
                <div class="rounded-[4px] border border-border bg-muted p-4">
                    <p class="text-[11px] font-medium uppercase tracking-[0.06em] text-muted-foreground">{{ $label }}</p>
                    <p class="datum mt-1 text-[34px] font-semibold leading-[1.12] text-foreground">{{ $value }}</p>
                </div>
            @endforeach
        </div>

        <div class="mt-6 overflow-x-auto">
            @php
                // Bar heights are computed in PIXELS, not percentages. A
                // percentage height on a flex child does not resolve against a
                // flex-basis parent, so the bars silently collapsed to nothing
                // while the labels still rendered - the chart looked "empty"
                // rather than broken, which is the worst kind of bug.
                $track = 132;
            @endphp
            <div class="flex min-w-max items-end gap-3">
                @foreach ($trend as $month)
                    @php
                        $barPx = $month['fertility'] !== null
                            ? max(3, (int) round(($month['fertility'] / $peak) * $track))
                            : 0;
                    @endphp
                    <div class="flex w-16 flex-col items-center gap-2">
                        <span class="datum text-[12px] font-medium {{ $month['fertility'] !== null ? 'text-muted-foreground' : 'text-muted-foreground' }}">
                            {{ $month['fertility'] !== null ? $month['fertility'].'%' : '—' }}
                        </span>

                        <div class="flex w-full items-end justify-center" style="height: {{ $track }}px">
                            @if ($month['fertility'] !== null)
                                <div class="w-full rounded-t-[4px] bg-primary"
                                     style="height: {{ $barPx }}px"
                                     title="{{ $month['label'] }}: {{ $month['fertility'] }}% fertility from {{ $month['eggs_set'] }} eggs"></div>
                            @else
                                <div class="h-px w-full bg-border"
                                     title="{{ $month['label'] }}: no matings recorded"></div>
                            @endif
                        </div>

                        <span class="datum text-[12px] text-muted-foreground">{{ Str::before($month['label'], ' ') }}</span>
                    </div>
                @endforeach
            </div>
            <p class="mt-2 text-xs text-muted-foreground">
                A dash means no matings were recorded that month — which is different from a 0% result.
            </p>
        </div>
    </div>

    {{-- Attention lists --}}
    <div class="grid gap-6 lg:grid-cols-2">
        <div class="card p-7">
            <div class="flex items-center justify-between">
                <h2 class="text-[21px] font-semibold tracking-[-0.01em] leading-[1.25] text-foreground">Vaccinations Needing Attention</h2>
                @if (Route::has('health.schedule'))
                    <a href="{{ route('health.schedule') }}" wire:navigate class="inline-flex min-h-11 items-center text-sm font-medium text-primary hover:underline">
                        Full schedule
                    </a>
                @endif
            </div>

            @if ($this->overdueVaccinations->isEmpty() && $this->upcomingVaccinations->isEmpty())
                <p class="mt-4 rounded-lg bg-success-bg p-4 text-sm text-success">
                    Nothing is overdue and nothing is due in the next
                    {{ config('gfms.vaccination_warning_days') }} days. The flock is up to date.
                </p>
            @else
                <ul class="mt-4 divide-y divide-border">
                    {{-- Overdue rows carry a severity stripe. Without it, "141 days
                         overdue" and "due in 29 days" differ only by the wash on a pill,
                         and on a phone in daylight that difference disappears - which is
                         exactly when this list is read. --}}
                    @foreach ($this->overdueVaccinations as $record)
                        <li class="flex items-center justify-between gap-3 border-l-2 border-destructive py-2.5 pl-3">
                            <div class="min-w-0">
                                <p class="truncate text-[15px] font-medium text-foreground">
                                    {{ $record->broodcock?->name ?? 'Unknown bird' }}
                                </p>
                                <p class="truncate text-[12px] text-muted-foreground">
                                    {{ $record->record_type->label() }}
                                    @if ($record->product_name) &middot; {{ $record->product_name }} @endif
                                </p>
                            </div>
                            <span class="badge datum shrink-0 bg-destructive-bg text-destructive ring-destructive/20">
                                {{ abs((int) $record->daysUntilDue()) }} days overdue
                            </span>
                        </li>
                    @endforeach

                    @foreach ($this->upcomingVaccinations as $record)
                        <li class="flex items-center justify-between gap-3 py-2.5">
                            <div class="min-w-0">
                                <p class="truncate text-sm font-medium text-foreground">
                                    {{ $record->broodcock?->name ?? 'Unknown bird' }}
                                </p>
                                <p class="truncate text-xs text-muted-foreground">
                                    {{ $record->record_type->label() }}
                                    @if ($record->product_name) &middot; {{ $record->product_name }} @endif
                                </p>
                            </div>
                            <span class="badge shrink-0 bg-warning-bg text-warning ring-warning/20">
                                due in {{ (int) $record->daysUntilDue() }} days
                            </span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>

        <div class="card p-7">
            <div class="flex items-center justify-between">
                <h2 class="text-[21px] font-semibold tracking-[-0.01em] leading-[1.25] text-foreground">Recent Mortality</h2>
                <span class="text-sm text-muted-foreground">{{ $this->deathsThisYear }} this year</span>
            </div>

            @if ($this->recentMortality->isEmpty())
                <p class="mt-4 rounded-lg bg-success-bg p-4 text-sm text-success">
                    No deaths have been recorded.
                </p>
            @else
                <ul class="mt-4 divide-y divide-border">
                    @foreach ($this->recentMortality as $record)
                        <li class="flex items-center justify-between gap-3 py-2.5">
                            <div class="min-w-0">
                                <p class="truncate text-sm font-medium text-foreground">
                                    {{ $record->broodcock?->name ?? 'Unknown bird' }}
                                </p>
                                <p class="truncate text-xs text-muted-foreground">{{ $record->cause_of_death }}</p>
                            </div>
                            <span class="shrink-0 text-xs text-muted-foreground">
                                {{ $record->date_of_death->format('j M Y') }}
                            </span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>

    @if ($this->birdsWithoutPedigree > 0)
        <div class="card p-7">
            <h2 class="text-[21px] font-semibold tracking-[-0.01em] leading-[1.25] text-foreground">Pedigree Completeness</h2>
            <p class="mt-2 text-sm text-muted-foreground">
                <strong>{{ number_format($this->birdsWithoutPedigree) }}</strong>
                {{ Str::plural('bird', $this->birdsWithoutPedigree) }} have no sire or dam recorded, so they
                cannot appear in a family tree. Recording parents is what turns the bloodline
                field from a label into real traceability.
            </p>
        </div>
    @endif
</div>
