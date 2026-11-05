@php
    $flock = $this->flock;
    $breeding = $this->breedingOverall;
    $trend = $this->breedingTrend;
    $user = auth()->user();

    // Scale bars against the best month so a farm with modest rates still gets
    // a readable chart rather than flat stubs.
    $peak = max(1, max(array_map(fn ($m) => (int) ($m['fertility'] ?? 0), $trend)));

    $overdue = $this->overdueVaccinations;
    $upcoming = $this->upcomingVaccinations;
    $scheduled = $this->overdueCount + $upcoming->count();
    $compliance = $scheduled > 0
        ? round((($scheduled - $this->overdueCount) / $scheduled) * 100, 1)
        : null;

    $greeting = 'Good '.(now()->hour < 12 ? 'morning' : (now()->hour < 18 ? 'afternoon' : 'evening'))
        .', '.Str::before($user->full_name, ' ');

    $onFarmShare = $flock['total'] > 0 ? round($flock['on_farm'] / $flock['total'] * 100) : null;
    $breedingShare = $flock['on_farm'] > 0 ? round($flock['breeding'] / $flock['on_farm'] * 100) : null;
    $hasOverdue = $this->overdueCount > 0;

    $statusRows = $this->byStatus;
    $statusMax = max(1, (int) $statusRows->max('total'));
    $bloodlines = $this->byBloodline;
    $withoutPedigree = $this->birdsWithoutPedigree;
    $pedigreeShare = $flock['total'] > 0 ? round(($flock['total'] - $withoutPedigree) / $flock['total'] * 100) : null;

    $row = 'flex min-h-11 items-center justify-between gap-3 px-5 py-2.5 lg:min-h-0';
@endphp

{{--
    THE OWNER'S DASHBOARD (EMS, Part B §6.2 / §6.6).

    One screen on a laptop: the page header, the four KPI tiles, then two
    columns of cards - what needs doing and how breeding is going on the
    left, the shape of the flock on the right. Every figure is one this
    component already computed; nothing new is queried.
--}}
<div class="space-y-6">
    <x-ui.page-header :title="$greeting" :figure="number_format($flock['on_farm'])" figure-label="birds on the farm">
        {{ now()->format('l, j F Y') }} &middot; {{ $user->role->label() }}

        @can('create', App\Models\Broodcock::class)
            <x-slot:actions>
                <x-ui.button :href="route('broodcocks.create')" x-on:click.prevent="$dispatch('ems-dialog', 'broodcocks-form')"><x-ui.icon name="plus" /> Add gamefowl</x-ui.button>
            </x-slot:actions>
        @endcan
    </x-ui.page-header>

    {{-- Headline figures: EMS KPI tiles. The overdue tile is the one alarm on
         the row, so it is the one that turns red and the one that drills down. --}}
    <x-ui.kpi-row role="group" aria-label="Headline figures">
        <x-ui.kpi-tile label="Birds on record" icon="clipboard-list"
                       :value="number_format($flock['total'])"
                       :caption="$onFarmShare !== null ? $onFarmShare.'% still on the farm, excluding sold and deceased' : 'Every bird ever recorded'" />

        <x-ui.kpi-tile label="Currently breeding" icon="heart"
                       :value="number_format($flock['breeding'])"
                       :caption="$breedingShare !== null ? $breedingShare.'% of birds on the farm' : 'Status set to breeding'" />

        <x-ui.kpi-tile label="Overdue vaccinations" icon="syringe"
                       :value="number_format($this->overdueCount)" :alarm="$hasOverdue"
                       :href="route('health.schedule')" action="Open the vaccination schedule" wire:navigate
                       :caption="$hasOverdue ? 'Needs attention' : 'Nothing overdue'" />

        <x-ui.kpi-tile label="Vaccination compliance" icon="shield-check"
                       :value="$compliance !== null ? $compliance.'%' : null"
                       :caption="$compliance !== null
                           ? ($scheduled - $this->overdueCount).' of '.$scheduled.' scheduled follow-ups are not overdue'
                           : 'Nothing is scheduled in the window.'" />
    </x-ui.kpi-row>

    <div class="grid gap-4 lg:grid-cols-3">
        {{-- ============ What needs doing, and how breeding is going ============ --}}
        <div class="min-w-0 space-y-4 lg:col-span-2">

            {{-- Vaccinations: overdue first; due soon one tab away. --}}
            <x-ui.card :flush="true"
                       x-data="{ tab: '{{ $overdue->isNotEmpty() || $upcoming->isEmpty() ? 'overdue' : 'soon' }}' }">
                <x-ui.card.header class="border-b border-border/60 py-4">
                    <x-ui.card.title>Vaccinations</x-ui.card.title>
                    <x-ui.card.description>Follow-ups for birds on the farm</x-ui.card.description>
                    <x-ui.card.action>
                        <x-ui.button variant="outline" :href="route('health.schedule')" wire:navigate>Open schedule</x-ui.button>
                    </x-ui.card.action>
                </x-ui.card.header>

                @if ($overdue->isEmpty() && $upcoming->isEmpty())
                    <div class="px-5 py-8 text-center">
                        <x-ui.icon name="circle-check" class="mx-auto size-5 text-success" />
                        <p class="mt-2 text-sm font-semibold text-foreground">The flock is up to date.</p>
                        <p class="mt-1 text-xs text-muted-foreground">No vaccination is overdue or due in the next {{ config('gfms.vaccination_warning_days') }} days.</p>
                    </div>
                @else
                    <div class="flex gap-5 border-b border-border/60 px-5" role="tablist" aria-label="Vaccinations">
                        @foreach (['overdue' => ['Overdue', $this->overdueCount, 'bg-destructive-bg text-destructive'], 'soon' => ['Due soon', $upcoming->count(), 'bg-warning-bg text-warning']] as $key => [$label, $count, $tone])
                            <button type="button" role="tab" x-on:click="tab = '{{ $key }}'"
                                    x-bind:aria-selected="tab === '{{ $key }}' ? 'true' : 'false'"
                                    class="relative flex min-h-11 items-center gap-2 pt-2 pb-2 text-sm font-medium outline-none focus-visible:ring-2 focus-visible:ring-ring/50 lg:min-h-0 lg:text-[12.5px]"
                                    x-bind:class="tab === '{{ $key }}' ? 'font-bold text-primary' : 'text-muted-foreground hover:text-foreground'">
                                {{ $label }}
                                <span class="{{ $tone }} datum inline-flex h-[18px] min-w-[18px] items-center justify-center rounded-full px-1 text-[11px] leading-none font-semibold">{{ $count > 99 ? '99+' : $count }}</span>
                                <span class="absolute -bottom-px left-0 h-[2px] w-full rounded-full" x-bind:class="tab === '{{ $key }}' ? 'bg-primary' : 'bg-transparent'"></span>
                            </button>
                        @endforeach
                    </div>

                    @foreach (['overdue' => $overdue, 'soon' => $upcoming] as $key => $records)
                        <ul class="divide-y divide-border/60" role="tabpanel" x-show="tab === '{{ $key }}'" @if ($key !== 'overdue') x-cloak @endif>
                            @forelse ($records as $record)
                                <li class="{{ $row }}">
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-semibold text-foreground lg:text-xs">
                                            @if ($record->broodcock)
                                                <a href="{{ route('broodcocks.show', $record->broodcock) }}" wire:navigate class="">{{ $record->broodcock->name }}</a>
                                                @if ($record->broodcock->band_number)
                                                    <span class="datum ml-1 font-normal text-muted-foreground">{{ $record->broodcock->band_number }}</span>
                                                @endif
                                            @else
                                                Unknown bird
                                            @endif
                                        </p>
                                        <p class="truncate text-[13px] text-muted-foreground lg:text-[11px]">
                                            {{ $record->record_type->label() }}@if ($record->product_name) &middot; {{ $record->product_name }}@endif
                                        </p>
                                    </div>
                                    @if ($key === 'overdue')
                                        <span class="d-badge d-badge-soft d-badge-error datum shrink-0">{{ abs((int) $record->daysUntilDue()) }} days overdue</span>
                                    @else
                                        <span class="d-badge d-badge-soft d-badge-warning datum shrink-0">
                                            {{ (int) $record->daysUntilDue() === 0 ? 'due today' : 'due in '.(int) $record->daysUntilDue().' days' }}
                                        </span>
                                    @endif
                                </li>
                            @empty
                                <li class="px-5 py-6 text-center text-xs text-muted-foreground">
                                    {{ $key === 'overdue' ? 'Nothing is overdue.' : 'Nothing is due in the next '.config('gfms.vaccination_warning_days').' days.' }}
                                </li>
                            @endforelse
                        </ul>
                    @endforeach
                @endif
            </x-ui.card>

            {{-- Breeding: the three figures for the period, then fertility per month. --}}
            <x-ui.card>
                <x-ui.card.header>
                    <x-ui.card.title>Breeding</x-ui.card.title>
                    <x-ui.card.description>Fertility per month, worked out from total eggs</x-ui.card.description>
                    <x-ui.card.action>
                        <x-ui.select id="trendMonths" label="Trend period" :label-hidden="true" wire:model.live="trendMonths"
                                    :options="['6' => 'Last 6 months', '12' => 'Last 12 months', '24' => 'Last 24 months']" wrapper-class="w-40" />
                    </x-ui.card.action>
                </x-ui.card.header>

                <x-ui.card.content class="space-y-5">
                    <dl class="grid grid-cols-3 gap-3">
                        @foreach ([
                            ['Matings', number_format($breeding['matings'])],
                            ['Fertility', $breeding['fertility'] !== null ? $breeding['fertility'].'%' : null],
                            ['Hatch rate', $breeding['hatch'] !== null ? $breeding['hatch'].'%' : null],
                        ] as [$label, $value])
                            <div class="rounded-lg bg-muted/50 px-3 py-2.5">
                                <dt class="text-[11px] font-medium text-muted-foreground">{{ $label }}</dt>
                                <dd @class(['datum mt-1 leading-none font-bold text-foreground', $value !== null ? 'text-lg' : 'text-sm'])>{{ $value ?? 'No data' }}</dd>
                            </div>
                        @endforeach
                    </dl>

                    @php $track = 96; @endphp
                    <div>
                        <div class="flex items-end gap-1.5 border-b border-border" style="height: {{ $track }}px" aria-hidden="true">
                            @foreach ($trend as $month)
                                <div class="flex h-full min-w-0 flex-1 items-end"
                                     title="{{ $month['label'] }}: {{ $month['fertility'] !== null ? $month['fertility'].'% fertility from '.$month['eggs_set'].' eggs' : 'no matings recorded' }}">
                                    @if ($month['fertility'] !== null)
                                        <div class="w-full rounded-t-[3px] bg-primary/85 transition-colors hover:bg-primary"
                                             style="height: {{ max(3, (int) round(($month['fertility'] / $peak) * $track)) }}px"></div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                        <div class="mt-1.5 flex gap-1.5" aria-hidden="true">
                            @foreach ($trend as $month)
                                <span class="datum min-w-0 flex-1 truncate text-center text-[11px] text-muted-foreground">{{ Str::before($month['label'], ' ') }}</span>
                            @endforeach
                        </div>
                        <ul class="sr-only">
                            @foreach ($trend as $month)
                                <li>{{ $month['label'] }}: {{ $month['fertility'] !== null ? $month['fertility'].'% fertility' : 'no matings recorded' }}</li>
                            @endforeach
                        </ul>
                    </div>
                </x-ui.card.content>
            </x-ui.card>
        </div>

        {{-- ============ The shape of the flock ============ --}}
        <div class="min-w-0 space-y-4">

            <x-ui.card>
                <x-ui.card.header>
                    <x-ui.card.title>Flock by status</x-ui.card.title>
                </x-ui.card.header>
                <x-ui.card.content>
                    @if ($statusRows->isEmpty())
                        <p class="text-xs text-muted-foreground">No birds recorded yet.</p>
                    @else
                        <ul class="space-y-3">
                            @foreach ($statusRows as $statusRow)
                                <li>
                                    <div class="flex items-center justify-between gap-3">
                                        <span class="d-badge d-badge-soft {{ $statusRow->status->badgeClasses() }}">{{ $statusRow->status->label() }}</span>
                                        <span class="datum text-sm font-semibold text-foreground lg:text-xs">{{ number_format($statusRow->total) }}</span>
                                    </div>
                                    <div class="mt-1.5 h-1 overflow-hidden rounded-full bg-muted" aria-hidden="true">
                                        <div class="h-full rounded-full bg-primary/70" style="width: {{ round($statusRow->total / $statusMax * 100) }}%"></div>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </x-ui.card.content>
            </x-ui.card>

            <x-ui.card>
                <x-ui.card.header>
                    <x-ui.card.title>Bloodlines</x-ui.card.title>
                </x-ui.card.header>
                <x-ui.card.content>
                    @if ($bloodlines->isEmpty())
                        <p class="text-xs text-muted-foreground">No bloodlines recorded yet.</p>
                    @else
                        <ul class="space-y-2">
                            @foreach ($bloodlines as $line)
                                <li class="flex items-center justify-between gap-3 text-sm lg:text-xs">
                                    <span class="flex min-w-0 items-center gap-2 text-foreground">
                                        <x-band-dot :bloodline="$line->bloodline" />
                                        <span class="truncate">{{ $line->bloodline }}</span>
                                    </span>
                                    <span class="datum font-semibold text-foreground">{{ number_format($line->total) }}</span>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </x-ui.card.content>
            </x-ui.card>

            <x-ui.card :flush="true">
                <x-ui.card.header class="py-4">
                    <x-ui.card.title>Recent deaths</x-ui.card.title>
                    <x-ui.card.description><span class="datum">{{ $this->deathsThisYear }}</span> this year</x-ui.card.description>
                    @if (Route::has('mortality.index'))
                        <x-ui.card.action>
                            <a href="{{ route('mortality.index') }}" wire:navigate class="text-xs font-medium text-primary">View all</a>
                        </x-ui.card.action>
                    @endif
                </x-ui.card.header>
                @if ($this->recentMortality->isEmpty())
                    <p class="border-t border-border/60 px-5 py-5 text-xs text-muted-foreground">No deaths recorded.</p>
                @else
                    <ul class="divide-y divide-border/60 border-t border-border/60">
                        @foreach ($this->recentMortality->take(3) as $record)
                            <li class="{{ $row }}">
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-semibold text-foreground lg:text-xs">
                                        @if ($record->broodcock)
                                            <a href="{{ route('broodcocks.show', $record->broodcock) }}" wire:navigate class="">{{ $record->broodcock->name }}</a>
                                        @else
                                            Unknown bird
                                        @endif
                                    </p>
                                    <p class="truncate text-[13px] text-muted-foreground lg:text-[11px]">{{ $record->cause_of_death }}</p>
                                </div>
                                <span class="datum shrink-0 text-[13px] text-muted-foreground lg:text-[11px]">{{ $record->date_of_death->format('j M Y') }}</span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </x-ui.card>

            <x-ui.card>
                <x-ui.card.header>
                    <x-ui.card.title>Pedigree</x-ui.card.title>
                    <x-ui.card.description>
                        @if ($withoutPedigree === 0)
                            Every bird has its parents recorded.
                        @else
                            <span class="datum font-semibold text-foreground">{{ number_format($withoutPedigree) }}</span>
                            {{ Str::plural('bird', $withoutPedigree) }} with no parents recorded
                        @endif
                    </x-ui.card.description>
                </x-ui.card.header>
                @if ($pedigreeShare !== null)
                    <x-ui.card.content class="space-y-1.5">
                        <x-ui.progress :value="$pedigreeShare" label="Pedigree completeness" />
                        <p class="text-[11px] text-muted-foreground"><span class="datum">{{ $pedigreeShare }}%</span> of birds have a family tree</p>
                    </x-ui.card.content>
                @endif
            </x-ui.card>
        </div>
    </div>

    @can('create', App\Models\Broodcock::class)
        {{-- Add gamefowl opens here, in a dialog over the dashboard. --}}
        <x-ui.form-dialog id="broodcocks-form" model="showForm" size="xl" :wrap="false" title="Add a Gamefowl">
            <livewire:broodcocks.form :in-dialog="true" :key="'dashboard-broodcock-form-'.$formNonce" />
        </x-ui.form-dialog>
    @endcan
</div>
