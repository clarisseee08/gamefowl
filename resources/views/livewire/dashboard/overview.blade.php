@php
    $flock = $this->flock;
    $breeding = $this->breedingOverall;
    $trend = $this->breedingTrend;
    $user = auth()->user();
    // Scale bars against the best month so a farm with modest rates still gets
    // a readable chart rather than four flat stubs.
    $peak = max(1, max(array_map(fn ($m) => (int) ($m['fertility'] ?? 0), $trend)));

    /*
     * The fertility sparkline is the only one on this screen because it is the
     * only figure with a time series behind it that already exists -
     * breedingTrend() is one query and already drawn as the chart below. The
     * other figures carry a supporting proportion instead of a fake trend.
     */
    $fertilitySeries = array_map(fn ($m) => $m['fertility'], $trend);

    $scheduled = $this->overdueCount + $this->upcomingVaccinations->count();
    $compliance = $scheduled > 0
        ? round((($scheduled - $this->overdueCount) / $scheduled) * 100, 1)
        : null;

    $greeting = 'Good '.(now()->hour < 12 ? 'morning' : (now()->hour < 18 ? 'afternoon' : 'evening'))
        .', '.Str::before($user->full_name, ' ');

    $onFarmShare = $flock['total'] > 0 ? round($flock['on_farm'] / $flock['total'] * 100) : null;
    $breedingShare = $flock['on_farm'] > 0 ? round($flock['breeding'] / $flock['on_farm'] * 100) : null;
    $hasOverdue = $this->overdueCount > 0;

    $headingClass = 'font-display text-[19px] font-semibold tracking-[-0.015em] text-foreground';
    $labelClass = 'text-[13px] font-semibold text-muted-foreground';
    $figureClass = 'datum mt-2 font-display text-[2rem] font-semibold leading-none tracking-[-0.025em]';
@endphp

{{--
    THE FARM DASHBOARD.

    Opens on the anchor block - the one number the owner reads first, birds on
    the farm - then figures on paper, separated by hairlines rather than boxed
    into stat cards. What needs doing today (vaccinations, deaths) comes before
    what is merely interesting (the breeding trend, the flock breakdowns).
--}}
<div>
    <x-anchor-block eyebrow="Today on the farm" :title="$greeting"
                    :figure="number_format($flock['on_farm'])" figure-label="birds on the farm">
        Here is how {{ config('gfms.farm.name') }} is doing today.
        You are signed in as <span class="font-semibold text-foreground">{{ $user->role->label() }}</span>.

        @can('create', App\Models\Broodcock::class)
            <x-slot:actions>
                <a href="{{ route('broodcocks.create') }}" wire:navigate class="btn-primary">Add broodcock</a>
            </x-slot:actions>
        @endcan
    </x-anchor-block>

    {{-- Headline figures --}}
    <dl class="mt-10 grid gap-x-10 gap-y-8 sm:grid-cols-2 xl:grid-cols-4" aria-label="Headline figures">
        <div class="border-t border-border pt-4">
            <dt class="{{ $labelClass }}">Total broodcocks</dt>
            <dd>
                <p class="{{ $figureClass }} text-foreground">{{ number_format($flock['total']) }}</p>
                @if ($onFarmShare !== null)
                    <div class="meter mt-4"><span class="bg-foreground" style="width: {{ $onFarmShare }}%"></span></div>
                    <p class="mt-2 text-[13px] text-muted-foreground">
                        <span class="datum">{{ $onFarmShare }}%</span> still on the farm, excluding sold and deceased
                    </p>
                @else
                    <p class="mt-3 text-[13px] text-muted-foreground">Every bird ever recorded</p>
                @endif
            </dd>
        </div>

        <div class="border-t border-border pt-4">
            <dt class="{{ $labelClass }}">Currently breeding</dt>
            <dd>
                <p class="{{ $figureClass }} text-foreground">{{ number_format($flock['breeding']) }}</p>
                @if ($breedingShare !== null)
                    <div class="meter mt-4"><span class="bg-foreground" style="width: {{ $breedingShare }}%"></span></div>
                    <p class="mt-2 text-[13px] text-muted-foreground">
                        <span class="datum">{{ $breedingShare }}%</span> of birds on the farm
                    </p>
                @else
                    <p class="mt-3 text-[13px] text-muted-foreground">Status set to breeding</p>
                @endif
            </dd>
        </div>

        <div class="border-t border-border pt-4">
            <dt class="{{ $labelClass }}">Overdue vaccinations</dt>
            <dd>
                <p @class([$figureClass, 'text-destructive' => $hasOverdue, 'text-foreground' => ! $hasOverdue])>
                    {{ number_format($this->overdueCount) }}
                </p>
                <p class="mt-3 text-[13px] text-muted-foreground">{{ $hasOverdue ? 'Needs attention' : 'Nothing overdue' }}</p>
            </dd>
        </div>

        <div class="border-t border-border pt-4">
            <dt class="{{ $labelClass }}">Vaccination compliance</dt>
            <dd>
                <p class="{{ $figureClass }} text-foreground">{{ $compliance !== null ? $compliance.'%' : 'No data' }}</p>
                @if ($compliance !== null)
                    <div class="meter mt-4">
                        <span class="{{ $compliance < 80 ? 'bg-warning' : 'bg-success' }}" style="width: {{ $compliance }}%"></span>
                    </div>
                    <p class="mt-2 text-[13px] text-muted-foreground">
                        <span class="datum">{{ $scheduled - $this->overdueCount }}</span> of
                        <span class="datum">{{ $scheduled }}</span> scheduled follow-ups are not overdue
                    </p>
                @else
                    <p class="mt-3 text-[13px] text-muted-foreground">Nothing is scheduled in the window.</p>
                @endif
            </dd>
        </div>
    </dl>

    {{-- What needs doing: two columns on a desktop, one on a phone. --}}
    <div class="mt-16 grid gap-x-12 gap-y-14 lg:grid-cols-2" data-reveal>
        <section aria-labelledby="vaccinations-heading">
            <div class="flex flex-wrap items-end justify-between gap-x-4 border-b border-border pb-3">
                <h2 id="vaccinations-heading" class="{{ $headingClass }}">Vaccinations needing attention</h2>
                @if (Route::has('health.schedule'))
                    <a href="{{ route('health.schedule') }}" wire:navigate class="sb-link">
                        Full schedule <span aria-hidden="true">&rarr;</span>
                    </a>
                @endif
            </div>

            @if ($this->overdueVaccinations->isEmpty() && $this->upcomingVaccinations->isEmpty())
                <p class="flex items-start gap-3 py-5 text-[15px] text-foreground">
                    <svg class="mt-0.5 h-5 w-5 shrink-0 text-success" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/>
                    </svg>
                    <span>
                        Nothing is overdue and nothing is due in the next
                        <span class="datum">{{ config('gfms.vaccination_warning_days') }}</span> days. The flock is up to date.
                    </span>
                </p>
            @else
                <ul class="divide-y divide-border">
                    {{-- Every row here is overdue (scopeOverdue) or due soon
                         (scopeDueSoon), and each says how late or how near in
                         words. The section heading says the rest - a stripe that
                         every row carries separates nothing. --}}
                    @foreach ($this->overdueVaccinations as $record)
                        <li class="flex min-h-11 items-center justify-between gap-3 py-3">
                            <div class="min-w-0">
                                <p class="truncate text-[15px] font-semibold text-foreground">
                                    @if ($record->broodcock)
                                        <a href="{{ route('broodcocks.show', $record->broodcock) }}" wire:navigate class="hover:underline hover:underline-offset-4">{{ $record->broodcock->name }}</a>
                                    @else
                                        Unknown bird
                                    @endif
                                </p>
                                <p class="truncate text-[13px] text-muted-foreground">
                                    {{ $record->record_type->label() }}
                                    @if ($record->product_name) &middot; {{ $record->product_name }} @endif
                                </p>
                            </div>
                            <span class="badge badge-alert datum shrink-0">
                                {{ abs((int) $record->daysUntilDue()) }} days overdue
                            </span>
                        </li>
                    @endforeach

                    @foreach ($this->upcomingVaccinations as $record)
                        <li class="flex min-h-11 items-center justify-between gap-3 py-3">
                            <div class="min-w-0">
                                <p class="truncate text-[15px] font-semibold text-foreground">
                                    @if ($record->broodcock)
                                        <a href="{{ route('broodcocks.show', $record->broodcock) }}" wire:navigate class="hover:underline hover:underline-offset-4">{{ $record->broodcock->name }}</a>
                                    @else
                                        Unknown bird
                                    @endif
                                </p>
                                <p class="truncate text-[13px] text-muted-foreground">
                                    {{ $record->record_type->label() }}
                                    @if ($record->product_name) &middot; {{ $record->product_name }} @endif
                                </p>
                            </div>
                            <span class="badge badge-warn datum shrink-0">
                                due in {{ (int) $record->daysUntilDue() }} days
                            </span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>

        <section aria-labelledby="mortality-heading">
            <div class="flex flex-wrap items-end justify-between gap-x-4 border-b border-border pb-3">
                <h2 id="mortality-heading" class="{{ $headingClass }}">Recent mortality</h2>
                <span class="text-[14px] text-muted-foreground"><span class="datum">{{ $this->deathsThisYear }}</span> this year</span>
            </div>

            @if ($this->recentMortality->isEmpty())
                <p class="flex items-start gap-3 py-5 text-[15px] text-foreground">
                    <svg class="mt-0.5 h-5 w-5 shrink-0 text-success" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/>
                    </svg>
                    <span>No deaths have been recorded.</span>
                </p>
            @else
                <ul class="divide-y divide-border">
                    @foreach ($this->recentMortality as $record)
                        <li class="flex min-h-11 items-center justify-between gap-3 py-3">
                            <div class="min-w-0">
                                <p class="truncate text-[15px] font-semibold text-foreground">
                                    @if ($record->broodcock)
                                        <a href="{{ route('broodcocks.show', $record->broodcock) }}" wire:navigate class="hover:underline hover:underline-offset-4">{{ $record->broodcock->name }}</a>
                                    @else
                                        Unknown bird
                                    @endif
                                </p>
                                <p class="truncate text-[13px] text-muted-foreground">{{ $record->cause_of_death }}</p>
                            </div>
                            <span class="datum shrink-0 text-[14px] text-muted-foreground">
                                {{ $record->date_of_death->format('j M Y') }}
                            </span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>
    </div>

    {{-- Breeding trend. Hand-drawn with CSS rather than a charting library -
         the thesis describes a PHP/HTML/CSS system and adding a JS chart
         dependency would contradict it. --}}
    <section class="mt-16" aria-labelledby="breeding-heading" data-reveal>
        <div class="flex flex-wrap items-end justify-between gap-x-8 gap-y-4 border-b border-border pb-4">
            <div class="min-w-0">
                <h2 id="breeding-heading" class="{{ $headingClass }}">Breeding success trend</h2>
                <p class="mt-2 max-w-[62ch] text-[15px] leading-relaxed text-muted-foreground">
                    Fertility rate per month, worked out from total eggs, not by averaging
                    each mating's percentage, which would let a 2-egg mating count as much
                    as a 200-egg one.
                </p>
            </div>
            {{-- The heading beside it already says what the control does, so
                 the label stays in the accessibility tree and off the page. --}}
            <div class="w-full sm:w-52">
                <x-form-select id="trendMonths" label="Trend period" label-hidden wire:model.live="trendMonths">
                    <option value="6">Last 6 months</option>
                    <option value="12">Last 12 months</option>
                    <option value="24">Last 24 months</option>
                </x-form-select>
            </div>
        </div>

        <dl class="mt-8 grid gap-x-10 gap-y-8 sm:grid-cols-3">
            <div class="border-t border-border pt-4">
                <dt class="{{ $labelClass }}">Matings recorded</dt>
                <dd class="{{ $figureClass }} text-foreground">{{ number_format($breeding['matings']) }}</dd>
            </div>
            <div class="border-t border-border pt-4">
                <dt class="{{ $labelClass }}">Overall fertility</dt>
                <dd>
                    <p class="{{ $figureClass }} text-foreground">{{ $breeding['fertility'] !== null ? $breeding['fertility'].'%' : 'No data' }}</p>
                    <div class="mt-3 flex items-center gap-3">
                        <x-sparkline :points="$fertilitySeries" tone="primary"
                                     label="Monthly fertility rate over the last {{ count($trend) }} months" />
                        <span class="text-[13px] text-muted-foreground">Last <span class="datum">{{ count($trend) }}</span> months</span>
                    </div>
                </dd>
            </div>
            <div class="border-t border-border pt-4">
                <dt class="{{ $labelClass }}">Overall hatch rate</dt>
                <dd class="{{ $figureClass }} text-foreground">{{ $breeding['hatch'] !== null ? $breeding['hatch'].'%' : 'No data' }}</dd>
            </div>
        </dl>

        <div class="mt-10 overflow-x-auto pb-2">
            @php
                // Bar heights are computed in PIXELS, not percentages. A
                // percentage height on a flex child does not resolve against a
                // flex-basis parent, so the bars silently collapsed to nothing
                // while the labels still rendered.
                $track = 132;
            @endphp
            <div class="flex min-w-max items-end gap-3 border-b border-border">
                @foreach ($trend as $month)
                    @php
                        $barPx = $month['fertility'] !== null
                            ? max(3, (int) round(($month['fertility'] / $peak) * $track))
                            : 0;
                    @endphp
                    <div class="flex w-16 flex-col items-center gap-2">
                        <span class="datum text-[12px] font-medium text-muted-foreground">
                            {{ $month['fertility'] !== null ? $month['fertility'].'%' : 'No data' }}
                        </span>

                        <div class="flex w-full items-end justify-center" style="height: {{ $track }}px">
                            @if ($month['fertility'] !== null)
                                <div class="w-full rounded-t-[4px] bg-foreground"
                                     style="height: {{ $barPx }}px"
                                     title="{{ $month['label'] }}: {{ $month['fertility'] }}% fertility from {{ $month['eggs_set'] }} eggs"></div>
                            @else
                                <div class="h-px w-full bg-border"
                                     title="{{ $month['label'] }}: no matings recorded"></div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
            <div class="flex min-w-max gap-3 pt-2">
                @foreach ($trend as $month)
                    <span class="datum w-16 text-center text-[12px] text-muted-foreground">{{ Str::before($month['label'], ' ') }}</span>
                @endforeach
            </div>
            <p class="mt-3 text-[13px] text-muted-foreground">
                No data means no matings were recorded that month, which is different from a 0% result.
            </p>
        </div>
    </section>

    {{-- The flock, broken down. Three columns on a wide screen, one on a phone. --}}
    <section class="mt-16" aria-labelledby="flock-heading" data-reveal>
        <h2 id="flock-heading" class="{{ $headingClass }}">The flock</h2>

        <div class="mt-6 grid gap-x-12 gap-y-10 md:grid-cols-2 xl:grid-cols-3">
            @foreach ([
                ['By status', $this->byStatus, 'status'],
                ['By class', $this->byClass, 'class'],
            ] as [$heading, $rows, $field])
                <div>
                    <h3 class="border-b border-border pb-2 {{ $labelClass }}">{{ $heading }}</h3>
                    @if ($rows->isEmpty())
                        <p class="py-4 text-[15px] text-muted-foreground">No birds recorded yet.</p>
                    @else
                        <ul class="divide-y divide-border">
                            @foreach ($rows as $row)
                                @php $enum = $row->{$field}; @endphp
                                <li class="flex min-h-11 items-center justify-between gap-3 py-2">
                                    <span class="badge {{ $enum->badgeClasses() }}">{{ $enum->label() }}</span>
                                    <span class="datum text-[15px] font-medium text-foreground">{{ number_format($row->total) }}</span>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            @endforeach

            <div>
                <h3 class="border-b border-border pb-2 {{ $labelClass }}">By bloodline</h3>
                @if ($this->byBloodline->isEmpty())
                    <p class="py-4 text-[15px] text-muted-foreground">No bloodlines recorded yet.</p>
                @else
                    {{-- The one breakdown whose subject IS bloodline carries the
                         band colour, as a dot beside the written name. --}}
                    <ul class="divide-y divide-border">
                        @foreach ($this->byBloodline as $row)
                            <li class="flex min-h-11 items-center justify-between gap-3 py-2">
                                <span class="flex min-w-0 items-center gap-2.5">
                                    <x-band-dot :bloodline="$row->bloodline" />
                                    <span class="truncate text-[15px] text-foreground">{{ $row->bloodline }}</span>
                                </span>
                                <span class="datum text-[15px] font-medium text-foreground">{{ number_format($row->total) }}</span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>
    </section>

    @if ($this->birdsWithoutPedigree > 0)
        <section class="mt-16 border-t border-border pt-6" aria-labelledby="pedigree-heading" data-reveal>
            <h2 id="pedigree-heading" class="{{ $headingClass }}">Pedigree completeness</h2>
            <p class="mt-2 max-w-[66ch] text-[15px] leading-relaxed text-muted-foreground">
                <span class="datum font-semibold text-foreground">{{ number_format($this->birdsWithoutPedigree) }}</span>
                {{ Str::plural('bird', $this->birdsWithoutPedigree) }} have no sire or dam recorded, so they
                cannot appear in a family tree. Recording parents is what turns the bloodline
                field from a label into real traceability.
            </p>
        </section>
    @endif
</div>
