{{--
    One bird's health history, newest first.

    Embedded in the bird page's Health tab:

        <livewire:health.broodcock-health-history :broodcock="$broodcock" />

    It sits beside the performance timeline on that tab, so it is built the
    same way: a section heading, then a timeline of rows between hairlines.
    No card, no table chrome - the date leads each row, because a history is
    read down its dates, and every date is `.datum` so the column reads down.
--}}
@php
    $stateBadge = fn (string $state): string => match ($state) {
        'Overdue' => 'badge-alert',
        'Due soon' => 'badge-warn',
        'Scheduled' => 'badge-info',
        default => 'badge-neutral',
    };
@endphp
<div>
    <div class="flex flex-wrap items-end justify-between gap-x-8 gap-y-5">
        <div class="min-w-0">
            <h2 class="sb-h2">Health History</h2>
            <p class="mt-2 max-w-[65ch] text-[15px] leading-relaxed text-muted-foreground">
                Vaccinations, medications and check-ups for {{ $broodcock->name }}, newest first.
            </p>
        </div>

        @can('create', \App\Models\HealthRecord::class)
            <a href="{{ route('health.create', ['broodcock' => $broodcock->id]) }}" wire:navigate class="btn-primary shrink-0">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                </svg>
                Add Health Record
            </a>
        @endcan
    </div>

    {{-- The one thing the keeper needs at a glance: what is this bird waiting
         on? One line between hairlines; the badge says the state in words, so
         the wash is never the only channel. Overdue is assertive, the rest
         are polite. --}}
    @if ($this->nextFollowUp)
        @php
            $followUp = $this->nextFollowUp;
            $followUpState = $followUp->scheduleState();
            $followUpTitle = match ($followUpState) {
                'Overdue' => 'Follow-up overdue',
                'Due soon' => 'Follow-up due soon',
                default => 'Follow-up scheduled',
            };
        @endphp

        <div class="mt-6 flex flex-wrap items-center gap-x-3 gap-y-1.5 border-y border-border py-3"
             role="{{ $followUpState === 'Overdue' ? 'alert' : 'status' }}">
            <span class="badge {{ $stateBadge($followUpState) }}">{{ $followUpTitle }}</span>
            <p class="text-[15px] leading-snug text-foreground">
                {{ $followUp->record_type->label() }}@if ($followUp->product_name) ({{ $followUp->product_name }})@endif
                on <span class="datum">{{ $followUp->next_due_date->format('d M Y') }}</span>.
            </p>
        </div>
    @endif

    @if ($this->rows->isEmpty())
        @php
            $canAddRecord = auth()->user()?->can('create', \App\Models\HealthRecord::class) ?? false;
        @endphp
        <x-empty-state class="mt-6" title="No health records for this bird yet."
                       :description="$canAddRecord
                            ? 'Log this bird’s first vaccination, deworming or check-up.'
                            : 'Nothing has been recorded for '.$broodcock->name.' so far.'"
                       icon="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z">
            @if ($canAddRecord)
                <a href="{{ route('health.create', ['broodcock' => $broodcock->id]) }}" wire:navigate class="btn-primary">
                    Add Health Record
                </a>
            @endif
        </x-empty-state>
    @else
        <ol @class(['divide-y divide-border border-b border-border', 'mt-6 border-t' => ! $this->nextFollowUp])
            wire:loading.class="opacity-60" wire:target="gotoPage,nextPage,previousPage">
            @foreach ($this->rows as $record)
                @php $state = $record->scheduleState(); @endphp

                <li wire:key="history-{{ $record->id }}" class="py-4 sm:grid sm:grid-cols-[9rem_minmax(0,1fr)_auto] sm:gap-6">
                    {{-- When --}}
                    <div>
                        <p class="datum text-[16px] font-medium leading-snug text-foreground">{{ $record->checkup_date->format('d M Y') }}</p>
                        <p class="mt-0.5 text-[13px] text-muted-foreground">{{ $record->checkup_date->diffForHumans() }}</p>
                    </div>

                    {{-- What was done --}}
                    <div class="mt-2 min-w-0 sm:mt-0">
                        <div class="flex flex-wrap items-center gap-x-2 gap-y-1">
                            <span class="badge {{ $record->record_type->badgeClasses() }}">{{ $record->record_type->label() }}</span>
                            <span class="text-[15px] leading-snug text-foreground">{{ $record->product_name ?: 'Not recorded' }}</span>
                        </div>

                        @if ($record->dosage || $record->condition)
                            <p class="mt-1.5 flex flex-wrap gap-x-5 gap-y-0.5 text-[13px] text-muted-foreground">
                                @if ($record->dosage)
                                    <span>Dosage: <span class="datum text-foreground">{{ $record->dosage }}</span></span>
                                @endif
                                @if ($record->condition)
                                    <span>Condition: <span class="text-foreground">{{ $record->condition }}</span></span>
                                @endif
                            </p>
                        @endif

                        <p class="mt-1.5 flex flex-wrap items-center gap-x-2 gap-y-1 text-[13px] text-muted-foreground">
                            Next due:
                            @if ($record->next_due_date)
                                <span class="datum text-[15px] text-foreground">{{ $record->next_due_date->format('d M Y') }}</span>
                                <span class="badge {{ $stateBadge($state) }}">{{ $state }}</span>
                            @else
                                <span>None</span>
                            @endif
                        </p>

                        {{-- Internal remarks are for farm staff. Customers are
                             promised health status, not the farm's notes. --}}
                        @can('viewRemarks', $record)
                            @if ($record->remarks)
                                <p class="mt-2 max-w-[65ch] border-l-2 border-border pl-3 text-[13px] leading-snug text-muted-foreground"><span class="font-medium">Remarks:</span> {{ $record->remarks }}</p>
                            @endif
                        @endcan
                    </div>

                    @can('update', $record)
                        <div class="-ml-3 mt-1 sm:-mr-3 sm:-mt-2.5 sm:ml-0">
                            <a href="{{ route('health.edit', $record) }}" wire:navigate class="btn-quiet">
                                Edit<span class="sr-only">, {{ $record->record_type->label() }} on {{ $record->checkup_date->format('d M Y') }}</span>
                            </a>
                        </div>
                    @endcan
                </li>
            @endforeach
        </ol>

        @if ($this->rows->hasPages())
            <div class="mt-6">
                {{ $this->rows->links() }}
            </div>
        @endif
    @endif
</div>
