{{--
    One bird's health history, newest first.

    Embedded in the bird page's Health tab:

        <livewire:health.broodcock-health-history :broodcock="$broodcock" />

    An EMS card: a compact header with the add action, then a timeline of
    rows between hairlines. The date leads each row, because a history is
    read down its dates, and every date is `.datum` so the column reads down.
--}}
@php
    $stateBadge = fn (string $state): string => match ($state) {
        'Overdue' => 'd-badge-error',
        'Due soon' => 'd-badge-warning',
        'Scheduled' => 'd-badge-info',
        default => 'd-badge-neutral',
    };
@endphp
<div class="rounded-md border border-border bg-card p-5">
    <div class="flex flex-wrap items-center justify-between gap-x-6 gap-y-3">
        <div class="min-w-0">
            <h2 class="text-sm font-semibold text-foreground">Health History</h2>
            <p class="mt-0.5 max-w-[65ch] text-[13px] leading-snug text-muted-foreground lg:text-xs">
                Vaccinations, medications and check-ups for {{ $broodcock->name }}, newest first.
            </p>
        </div>

        @can('create', \App\Models\HealthRecord::class)
            <a href="{{ route('health.create', ['broodcock' => $broodcock->id]) }}" x-on:click.prevent="$dispatch('ems-dialog', 'bird-health-form')" class="d-btn d-btn-primary shrink-0">
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

        <div class="mt-4 flex flex-wrap items-center gap-x-3 gap-y-1.5 rounded-md bg-muted/60 px-3 py-2.5"
             role="{{ $followUpState === 'Overdue' ? 'alert' : 'status' }}">
            <span class="d-badge d-badge-soft {{ $stateBadge($followUpState) }}">{{ $followUpTitle }}</span>
            <p class="text-[15px] lg:text-xs leading-snug text-foreground">
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
                <a href="{{ route('health.create', ['broodcock' => $broodcock->id]) }}" x-on:click.prevent="$dispatch('ems-dialog', 'bird-health-form')" class="d-btn d-btn-primary">
                    Add Health Record
                </a>
            @endif
        </x-empty-state>
    @else
        <ol class="mt-4 divide-y divide-border border-t border-border"
            wire:loading.class="opacity-60" wire:target="perPage,gotoPage,nextPage,previousPage">
            @foreach ($this->rows as $record)
                @php $state = $record->scheduleState(); @endphp

                <li wire:key="history-{{ $record->id }}" class="relative py-4 sm:grid sm:grid-cols-[8rem_minmax(0,1fr)_auto] sm:gap-4">
                    {{-- When --}}
                    <div>
                        <p class="datum text-[15px] lg:text-xs font-semibold leading-snug text-foreground">{{ $record->checkup_date->format('d M Y') }}</p>
                        <p class="mt-0.5 text-[13px] lg:text-[11px] text-muted-foreground">{{ $record->checkup_date->diffForHumans() }}</p>
                    </div>

                    {{-- What was done --}}
                    <div class="mt-2 min-w-0 sm:mt-0">
                        <div class="flex flex-wrap items-center gap-x-2 gap-y-1">
                            <span class="d-badge d-badge-soft {{ $record->record_type->badgeClasses() }}">{{ $record->record_type->label() }}</span>
                            <span class="text-[15px] lg:text-xs leading-snug text-foreground">{{ $record->product_name ?: 'Not recorded' }}</span>
                        </div>

                        @if ($record->dosage || $record->condition)
                            <p class="mt-1.5 flex flex-wrap gap-x-5 gap-y-0.5 text-[13px] lg:text-xs text-muted-foreground">
                                @if ($record->dosage)
                                    <span>Dosage: <span class="datum text-foreground">{{ $record->dosage }}</span></span>
                                @endif
                                @if ($record->condition)
                                    <span>Condition: <span class="text-foreground">{{ $record->condition }}</span></span>
                                @endif
                            </p>
                        @endif

                        <p class="mt-1.5 flex flex-wrap items-center gap-x-2 gap-y-1 text-[13px] lg:text-xs text-muted-foreground">
                            Next due:
                            @if ($record->next_due_date)
                                <span class="datum text-foreground">{{ $record->next_due_date->format('d M Y') }}</span>
                                <span class="d-badge d-badge-soft {{ $stateBadge($state) }}">{{ $state }}</span>
                            @else
                                <span>None</span>
                            @endif
                        </p>

                        {{-- Internal remarks are for farm staff. Customers are
                             promised health status, not the farm's notes. --}}
                        @can('viewRemarks', $record)
                            @if ($record->remarks)
                                <p class="mt-2 max-w-[65ch] border-l-2 border-border pl-3 text-[13px] lg:text-xs leading-snug text-muted-foreground"><span class="font-medium">Remarks:</span> {{ $record->remarks }}</p>
                            @endif
                        @endcan
                    </div>

                    @can('update', $record)
                        <div class="absolute top-3 right-0 sm:static sm:-mt-1.5 sm:self-start">
                            <x-ui.row-action icon="pencil" :label="'Edit '.$record->record_type->label().' on '.$record->checkup_date->format('d M Y')"
                                             :href="route('health.edit', $record)" wire:click.prevent="openForm({{ $record->id }})" x-on:click="$dispatch('ems-dialog', { id: 'bird-health-form', wait: true })" />
                        </div>
                    @endcan
                </li>
            @endforeach
        </ol>

        @if ($this->rows->total() > 0)
            <x-ui.table-pagination :paginator="$this->rows" id="historyPerPage" class="mt-4" />
        @endif
    @endif

    @can('create', \App\Models\HealthRecord::class)
        <x-ui.form-dialog id="bird-health-form" model="showForm" size="lg" :wrap="false"
                          :title="$formRecordId ? 'Edit Health Record' : 'Add Health Record'"
                          :description="'For '.$broodcock->displayName().'.'">
            @if ($formRecordId)
                {{-- An edit, or a form for one bird: filled by the server when opened. --}}
                @if ($showForm)
                    <livewire:health.form :record="$formRecordId ? \App\Models\HealthRecord::find($formRecordId) : null"
                                          :for-broodcock="$broodcock->id"
                                          :return-url="route('broodcocks.show', ['broodcock' => $broodcock, 'tab' => 'health'])"
                                          :in-dialog="true" :key="'bird-health-form-'.($formRecordId ?? 'new')" />
                @else
                    <x-ui.form-skeleton :fields="5" />
                @endif
            @else
                {{-- A new record: built with the page, so Add opens at once with no request. --}}
                <livewire:health.form :record="$formRecordId ? \App\Models\HealthRecord::find($formRecordId) : null"
                                      :for-broodcock="$broodcock->id"
                                      :return-url="route('broodcocks.show', ['broodcock' => $broodcock, 'tab' => 'health'])"
                                      :in-dialog="true" :key="'bird-health-form-new-'.$formNonce" />
            @endif
        </x-ui.form-dialog>
    @endcan
</div>
