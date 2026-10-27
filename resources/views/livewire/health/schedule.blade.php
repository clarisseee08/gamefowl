{{--
    Vaccination schedule - thesis objective (c).

    Two lists, in the order the farm works them: what is already late, then
    what is coming up. Both are derived from next_due_date at read time, so
    this screen can never disagree with the records behind it.

    Inside each list the rows are GROUPED BY DUE DATE. The keeper's question
    is "what has to be done on which day", so the date is stated once, on the
    left, with how late or how soon it is, and the birds for that day sit
    beside it. Both paginators already return rows ordered by next_due_date,
    so the grouping is a pass over the current page and costs no query.

    State is never carried by colour alone: overdue groups say "days late",
    upcoming groups say "in N days", and both sections are titled in words.
--}}
@php
    $overdueGroups = $this->overdue->getCollection()->groupBy(fn ($record) => $record->next_due_date->toDateString());
    $upcomingGroups = $this->dueSoon->getCollection()->groupBy(fn ($record) => $record->next_due_date->toDateString());
    $calendarIcon = 'M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5';
@endphp
<div>
    <x-anchor-block eyebrow="Manage" title="Vaccination Schedule"
                    :figure="number_format($this->overdueCount)"
                    :figure-label="Str::plural('follow-up', $this->overdueCount).' overdue'">
        <span class="datum text-foreground">{{ number_format($this->dueSoonCount) }}</span>
        {{ $this->dueSoonCount === 1 ? 'follow-up is' : 'follow-ups are' }} due in the next
        <span class="datum">{{ $this->warningDays() }}</span> days.

        <x-slot:actions>
            <a href="{{ route('health.index') }}" wire:navigate class="btn-secondary">All Health Records</a>
        </x-slot:actions>
    </x-anchor-block>

    {{-- ---------------------------------------------------------------- --}}
    {{-- Overdue - most overdue first                                     --}}
    {{-- ---------------------------------------------------------------- --}}
    <section class="mt-10" aria-labelledby="overdue-heading" data-reveal>
        <div class="flex flex-wrap items-baseline gap-x-3 gap-y-1">
            <h2 id="overdue-heading" class="font-display text-[19px] font-semibold tracking-[-0.015em] text-foreground">Overdue</h2>
            <span class="badge badge-alert datum">{{ number_format($this->overdueCount) }}</span>
            <p class="w-full text-[15px] text-muted-foreground sm:w-auto">Most overdue first, because the bird waiting longest is at most risk.</p>
        </div>

        @if ($this->overdue->isEmpty())
            <x-empty-state class="mt-4" title="Nothing is overdue."
                           description="Every follow-up date on file is still in the future. Nothing to do here today."
                           :icon="$calendarIcon" />
        @else
            <ol class="mt-4 border-t border-border">
                @foreach ($overdueGroups as $date => $records)
                    @php
                        $dueOn = $records->first()->next_due_date;
                        $daysLate = abs((int) $records->first()->daysUntilDue());
                    @endphp
                    <li wire:key="overdue-day-{{ $date }}" class="grid gap-x-8 gap-y-2 border-b border-border py-4 md:grid-cols-[13rem_1fr]">
                        <div class="flex flex-wrap items-center gap-2 md:block md:pt-2.5">
                            <p class="datum text-[15px] font-medium text-foreground">{{ $dueOn->format('d M Y') }}</p>
                            {{-- One flex child, not two: `.badge` carries a gap, and a
                                 bare text node beside the datum span would pick it up
                                 on top of the word space. --}}
                            <span class="badge badge-alert md:mt-1.5">
                                <span class="whitespace-nowrap"><span class="datum">{{ $daysLate }}</span> {{ Str::plural('day', $daysLate) }} late</span>
                            </span>
                        </div>

                        <ul class="divide-y divide-border">
                            @foreach ($records as $record)
                                <li wire:key="overdue-{{ $record->id }}" class="py-2 md:grid md:grid-cols-[minmax(0,15rem)_minmax(0,1fr)_auto] md:items-center md:gap-x-6">
                                    <a href="{{ route('broodcocks.show', $record->broodcock) }}" wire:navigate
                                       class="group inline-flex min-h-11 items-center gap-2.5">
                                        <x-band-dot :bloodline="$record->broodcock->bloodline" />
                                        <span class="min-w-0">
                                            <span class="datum block text-[15px] font-medium text-foreground group-hover:underline">{{ $record->broodcock->displayBand() }}</span>
                                            <span class="block text-[13px] leading-snug text-muted-foreground">{{ $record->broodcock->name }}@if ($record->broodcock->bloodline) &middot; {{ $record->broodcock->bloodline }}@endif</span>
                                        </span>
                                    </a>

                                    <div class="mt-1 flex flex-wrap items-center gap-x-2 gap-y-1 md:mt-0">
                                        <span class="badge {{ $record->record_type->badgeClasses() }}">{{ $record->record_type->label() }}</span>
                                        <span class="text-[15px] text-foreground">{{ $record->product_name ?: 'Not recorded' }}</span>
                                    </div>

                                    @can('create', \App\Models\HealthRecord::class)
                                        <a href="{{ route('health.create', ['broodcock' => $record->broodcock_id]) }}"
                                           wire:navigate
                                           class="btn-quiet -ml-3 mt-1 md:ml-0 md:mt-0">
                                            Record Follow-up<span class="sr-only"> for {{ $record->broodcock->name }}</span>
                                        </a>
                                    @endcan
                                </li>
                            @endforeach
                        </ul>
                    </li>
                @endforeach
            </ol>

            @if ($this->overdue->hasPages())
                <div class="mt-6">
                    {{ $this->overdue->links() }}
                </div>
            @endif
        @endif
    </section>

    {{-- ---------------------------------------------------------------- --}}
    {{-- Due soon - soonest first                                         --}}
    {{-- ---------------------------------------------------------------- --}}
    <section class="mt-14" aria-labelledby="upcoming-heading" data-reveal>
        <div class="flex flex-wrap items-baseline gap-x-3 gap-y-1">
            <h2 id="upcoming-heading" class="font-display text-[19px] font-semibold tracking-[-0.015em] text-foreground">Due in the next {{ $this->warningDays() }} days</h2>
            <span class="badge badge-warn datum">{{ number_format($this->dueSoonCount) }}</span>
            <p class="w-full text-[15px] text-muted-foreground sm:w-auto">Soonest first, the order they will be done in.</p>
        </div>

        @if ($this->dueSoon->isEmpty())
            <x-empty-state class="mt-4" title="Nothing is due in the next {{ $this->warningDays() }} days."
                           description="When you record a vaccination or deworming, fill in its “Next Due Date” and the follow-up will appear here."
                           :icon="$calendarIcon" />
        @else
            <ol class="mt-4 border-t border-border">
                @foreach ($upcomingGroups as $date => $records)
                    @php
                        $dueOn = $records->first()->next_due_date;
                        $daysLeft = (int) $records->first()->daysUntilDue();
                    @endphp
                    <li wire:key="upcoming-day-{{ $date }}" class="grid gap-x-8 gap-y-2 border-b border-border py-4 md:grid-cols-[13rem_1fr]">
                        <div class="flex flex-wrap items-center gap-2 md:block md:pt-2.5">
                            <p class="datum text-[15px] font-medium text-foreground">{{ $dueOn->format('d M Y') }}</p>
                            <span class="badge badge-warn md:mt-1.5">
                                @if ($daysLeft === 0)
                                    Today
                                @else
                                    <span class="whitespace-nowrap">In <span class="datum">{{ $daysLeft }}</span> {{ Str::plural('day', $daysLeft) }}</span>
                                @endif
                            </span>
                        </div>

                        <ul class="divide-y divide-border">
                            @foreach ($records as $record)
                                <li wire:key="upcoming-{{ $record->id }}" class="py-2 md:grid md:grid-cols-[minmax(0,15rem)_minmax(0,1fr)_auto] md:items-center md:gap-x-6">
                                    <a href="{{ route('broodcocks.show', $record->broodcock) }}" wire:navigate
                                       class="group inline-flex min-h-11 items-center gap-2.5">
                                        <x-band-dot :bloodline="$record->broodcock->bloodline" />
                                        <span class="min-w-0">
                                            <span class="datum block text-[15px] font-medium text-foreground group-hover:underline">{{ $record->broodcock->displayBand() }}</span>
                                            <span class="block text-[13px] leading-snug text-muted-foreground">{{ $record->broodcock->name }}@if ($record->broodcock->bloodline) &middot; {{ $record->broodcock->bloodline }}@endif</span>
                                        </span>
                                    </a>

                                    <div class="mt-1 flex flex-wrap items-center gap-x-2 gap-y-1 md:mt-0">
                                        <span class="badge {{ $record->record_type->badgeClasses() }}">{{ $record->record_type->label() }}</span>
                                        <span class="text-[15px] text-foreground">{{ $record->product_name ?: 'Not recorded' }}</span>
                                    </div>

                                    @can('create', \App\Models\HealthRecord::class)
                                        <a href="{{ route('health.create', ['broodcock' => $record->broodcock_id]) }}"
                                           wire:navigate
                                           class="btn-quiet -ml-3 mt-1 md:ml-0 md:mt-0">
                                            Record Follow-up<span class="sr-only"> for {{ $record->broodcock->name }}</span>
                                        </a>
                                    @endcan
                                </li>
                            @endforeach
                        </ul>
                    </li>
                @endforeach
            </ol>

            @if ($this->dueSoon->hasPages())
                <div class="mt-6">
                    {{ $this->dueSoon->links() }}
                </div>
            @endif
        @endif
    </section>
</div>
