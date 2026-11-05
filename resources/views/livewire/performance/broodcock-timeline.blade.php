@php
    // Every figure is derived, never stored, and the win rate is computed over
    // CONTEST events only - conditioning sessions and weigh-ins are excluded so
    // they cannot dilute the statistic.
    $summary = $this->summary;

    $typeSegments = [['key' => '', 'label' => 'All events']];
    foreach ($this->eventTypeOptions() as $option) {
        $typeSegments[] = ['key' => $option->value, 'label' => $option->label()];
    }

    // Six figures, each one registry data. Ink, not status colour: colour in
    // this system means bloodline, and the badges on each entry already say
    // which bouts were won.
    $figures = [
        ['Contests', number_format($summary->totalContests), 'of '.number_format($summary->totalEvents).' '.Str::plural('event', $summary->totalEvents)],
        ['Wins', number_format($summary->wins), null],
        ['Losses', number_format($summary->losses), null],
        ['Draws', number_format($summary->draws), null],
    ];
@endphp

<div class="rounded-md border border-border bg-card p-5">
    @if ($statusMessage !== '')
        <div class="d-alert d-alert-soft d-alert-success mb-6 flex items-start justify-between gap-4 text-base-content" role="status">
            <p class="text-[15px] lg:text-xs leading-snug text-foreground">{{ $statusMessage }}</p>
            <button type="button" wire:click="dismissStatus"
                    class="d-link d-link-hover -my-2 inline-flex min-h-11 shrink-0 items-center px-1 text-[14px] lg:text-xs font-medium text-foreground underline-offset-4">
                Dismiss
            </button>
        </div>
    @endif

    <div class="flex flex-wrap items-center justify-between gap-x-6 gap-y-3">
        <div class="min-w-0">
            <h2 class="text-sm font-semibold text-foreground">Performance History</h2>
            <p class="mt-0.5 max-w-[65ch] text-[13px] leading-snug text-muted-foreground lg:text-xs">
                Everything recorded for {{ $broodcock->displayName() }}, newest first.
            </p>
        </div>

        @can('create', App\Models\PerformanceRecord::class)
            <a href="{{ route('performance.create', ['broodcock' => $broodcock->id]) }}" x-on:click.prevent="$dispatch('ems-dialog', 'bird-performance-form')" class="d-btn d-btn-primary shrink-0">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                </svg>
                Add Performance Record
            </a>
        @endcan
    </div>

    {{-- The summary: six figures as EMS tiles. --}}
    <dl class="mt-5 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-6">
        @foreach ($figures as [$label, $value, $note])
            <div class="rounded-md bg-muted/60 px-3 py-2.5">
                <dt class="text-[13px] lg:text-[11px] font-semibold text-muted-foreground">{{ $label }}</dt>
                <dd class="datum mt-1 text-lg font-bold leading-tight text-foreground">{{ $value }}</dd>
                @if ($note)
                    <p class="datum mt-1.5 text-[13px] lg:text-[11px] text-muted-foreground">{{ $note }}</p>
                @endif
            </div>
        @endforeach

        <div class="rounded-md bg-muted/60 px-3 py-2.5">
            {{-- "No contests yet" is prose, not a figure: it drops to the body
                 size and out of mono rather than pretending to be a number. --}}
            <dt class="text-[13px] lg:text-[11px] font-semibold text-muted-foreground">Win Rate</dt>
            <dd @class([
                'mt-1 text-foreground',
                'datum text-lg font-bold leading-tight' => $summary->winRate !== null,
                'text-[15px] lg:text-xs text-muted-foreground' => $summary->winRate === null,
            ])>{{ $summary->winRateLabel() }}</dd>
            <p class="mt-1.5 text-[13px] lg:text-[11px] text-muted-foreground">Contests only (<span class="datum">{{ $summary->recordLabel() }}</span>)</p>
        </div>

        <div class="rounded-md bg-muted/60 px-3 py-2.5">
            <dt class="text-[13px] lg:text-[11px] font-semibold text-muted-foreground">Average Rating</dt>
            <dd @class([
                'mt-1 text-foreground',
                'datum text-lg font-bold leading-tight' => $summary->averageRating !== null,
                'text-[15px] lg:text-xs text-muted-foreground' => $summary->averageRating === null,
            ])>{{ $summary->averageRatingLabel() }}</dd>
        </div>
    </dl>

    {{-- Filter, as a segmented strip: four kinds of event, all in view. --}}
    @if ($this->totalEvents > 0)
        <div class="mt-5 flex flex-wrap items-center justify-between gap-x-6 gap-y-3">
            <div class="max-w-full overflow-x-auto" data-scroll-x>
                <x-segmented :segments="$typeSegments" :active="$eventType" model="eventType" label="Show events of type" />
            </div>

            <p class="text-[14px] lg:text-xs text-muted-foreground">
                <span class="datum">{{ number_format($this->events->total()) }}</span>
                {{ Str::plural('event', $this->events->total()) }} shown.
            </p>
        </div>
    @endif

    {{-- The timeline itself. --}}
    @if ($this->events->isEmpty())
        @if ($eventType !== '')
            <x-empty-state class="mt-6" title="Nothing of that kind recorded yet"
                           description="{{ $broodcock->displayName() }} has other events on file. Choose “All events” to see them."
                           icon="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z">
                <button type="button" wire:click="$set('eventType', '')" class="d-btn d-btn-outline">Show all events</button>
            </x-empty-state>
        @else
            <x-empty-state class="mt-6" title="No performance recorded yet"
                           description="Nothing has been recorded for {{ $broodcock->displayName() }} so far. Add a sparring session, derby, conditioning session or weigh-in and it will appear here."
                           icon="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z">
                @can('create', App\Models\PerformanceRecord::class)
                    <a href="{{ route('performance.create', ['broodcock' => $broodcock->id]) }}" wire:navigate class="d-btn d-btn-primary">
                        Add the first record
                    </a>
                @endcan
            </x-empty-state>
        @endif
    @else
        <ol class="mt-4 divide-y divide-border border-t border-border"
            wire:loading.class="opacity-60" wire:target="eventType,gotoPage,nextPage,previousPage">
            @foreach ($this->events as $event)
                <li wire:key="timeline-{{ $event->id }}" class="relative py-4 sm:grid sm:grid-cols-[8rem_minmax(0,1fr)_auto] sm:gap-4">
                    {{-- When. The date leads, because a timeline is read down
                         its dates. --}}
                    <div>
                        <p class="datum text-[15px] lg:text-xs font-semibold leading-snug text-foreground">
                            {{ $event->event_date->format('d M Y') }}
                        </p>
                        <p class="mt-0.5 text-[13px] lg:text-[11px] text-muted-foreground">
                            {{ $event->event_date->diffForHumans() }}
                        </p>
                    </div>

                    {{-- What happened. --}}
                    <div class="mt-2 min-w-0 sm:mt-0">
                        <div class="flex flex-wrap items-center gap-1.5">
                            <span class="d-badge d-badge-soft {{ $event->event_type->badgeClasses() }}">{{ $event->event_type->label() }}</span>
                            <span class="d-badge d-badge-soft {{ $event->result->badgeClasses() }}">{{ $event->result->label() }}</span>
                        </div>

                        <dl class="mt-2 flex flex-wrap items-center gap-x-6 gap-y-1 text-[15px] lg:text-xs">
                            <div class="flex items-center gap-1.5">
                                <dt class="text-[13px] lg:text-xs text-muted-foreground">Weight</dt>
                                <dd @class([
                                    'datum text-foreground' => $event->weight !== null,
                                    'text-muted-foreground' => $event->weight === null,
                                ])>{{ $event->weight !== null ? $event->weight.' kg' : 'Not weighed' }}</dd>
                            </div>

                            <div class="flex items-center gap-1.5">
                                <dt class="text-[13px] lg:text-xs text-muted-foreground">Duration</dt>
                                <dd @class([
                                    'datum text-foreground' => $event->durationLabel() !== null,
                                    'text-muted-foreground' => $event->durationLabel() === null,
                                ])>{{ $event->durationLabel() ?? 'Not recorded' }}</dd>
                            </div>

                            <div class="flex items-center gap-1.5">
                                <dt class="text-[13px] lg:text-xs text-muted-foreground">Rating</dt>
                                <dd class="flex items-center">
                                    @if ($event->rating === null)
                                        <span class="text-muted-foreground">Not rated</span>
                                    @else
                                        {{-- Ink stars, not amber: fill against outline,
                                             which also survives a monochrome print. --}}
                                        <span class="inline-flex items-center gap-0.5" role="img"
                                              aria-label="{{ $event->rating }} out of 5 stars">
                                            @for ($star = 1; $star <= 5; $star++)
                                                <svg class="h-4 w-4 lg:h-3.5 lg:w-3.5 {{ $star <= $event->rating ? 'text-foreground' : 'text-muted-foreground' }}"
                                                     viewBox="0 0 20 20" aria-hidden="true"
                                                     fill="{{ $star <= $event->rating ? 'currentColor' : 'none' }}"
                                                     stroke="currentColor"
                                                     stroke-width="{{ $star <= $event->rating ? '0' : '1.25' }}">
                                                    <path stroke-linejoin="round" d="M10 1.5l2.6 5.27 5.82.85-4.21 4.1.99 5.79L10 14.78l-5.2 2.73.99-5.79-4.21-4.1 5.82-.85L10 1.5z"/>
                                                </svg>
                                            @endfor
                                        </span>
                                    @endif
                                </dd>
                            </div>
                        </dl>

                        {{-- Internal remarks. Gated on the policy, not on a CSS
                             class: a customer's page never renders them. --}}
                        @if ($event->remarks !== null && $event->remarks !== '' && auth()->user()?->can('viewRemarks', $event))
                            <div class="mt-2 border-l-2 border-border pl-3">
                                <p class="text-[13px] lg:text-[11px] font-semibold text-muted-foreground">Remarks (staff only)</p>
                                <p class="mt-1 max-w-[65ch] whitespace-pre-line text-[14px] lg:text-xs leading-relaxed text-foreground">{{ $event->remarks }}</p>
                            </div>
                        @endif

                        <p class="mt-2 text-[13px] text-muted-foreground lg:text-[11px]">
                            Recorded by {{ $event->recordedBy?->full_name ?? 'a former staff member' }}
                        </p>
                    </div>

                    {{-- Actions, top right of the entry, as on every list. --}}
                    @canany(['update', 'delete'], $event)
                        <div class="absolute top-3 right-0 flex items-center gap-1 sm:static sm:-mt-1.5 sm:self-start">
                            @can('update', $event)
                                <x-ui.row-action icon="pencil" :label="'Edit '.$event->event_type->label().' on '.$event->event_date->format('d M Y')"
                                                 :href="route('performance.edit', $event)" wire:click.prevent="openForm({{ $event->id }})" x-on:click="$dispatch('ems-dialog', { id: 'bird-performance-form', wait: true })" />
                            @endcan
                            @can('delete', $event)
                                <x-ui.row-action icon="trash-2" :destructive="true" :label="'Delete '.$event->event_type->label().' on '.$event->event_date->format('d M Y')"
                                                 wire:click="confirmDelete({{ $event->id }})" />
                            @endcan
                        </div>
                    @endcanany
                </li>
            @endforeach
        </ol>

        @if ($this->events->total() > 0)
            <x-ui.table-pagination :paginator="$this->events" id="timelinePerPage" class="mt-4" />
        @endif
    @endif

    {{-- Delete confirmation, naming the exact record. --}}
    @if ($this->recordPendingDeletion !== null)
        @php($pending = $this->recordPendingDeletion)
        <div class="d-modal d-modal-open"
             x-data x-trap.noscroll="true" @keydown.escape.window="$el.querySelector('.d-btn-outline')?.click()"
             role="dialog" aria-modal="true" aria-labelledby="timeline-delete-title" aria-describedby="timeline-delete-body">
            <div class="d-modal-box elev-3 max-w-md">
                <h2 id="timeline-delete-title" class="font-display text-[22px] lg:text-base font-semibold leading-[1.2] tracking-[-0.015em] text-foreground">
                    Delete the {{ $pending->event_type->label() }} record for {{ $broodcock->displayName() }}?
                </h2>

                <div id="timeline-delete-body">
                    <p class="mt-3 text-[15px] lg:text-xs leading-relaxed text-muted-foreground">
                        The record dated <strong class="datum font-medium text-foreground">{{ $pending->event_date->format('d M Y') }}</strong>
                        will disappear from this timeline and stop counting towards the win rate.
                    </p>
                    <p class="mt-2 text-[15px] lg:text-xs leading-relaxed text-muted-foreground">
                        The farm owner can restore it later if this was a mistake.
                    </p>
                </div>

                <div class="d-modal-action flex-col-reverse gap-3 sm:flex-row">
                    <button type="button" wire:click="cancelDelete" class="d-btn d-btn-outline">Cancel</button>
                    <button type="button" wire:click="delete" class="d-btn d-btn-error" wire:loading.attr="disabled" wire:target="delete">
                        <span class="d-loading d-loading-spinner d-loading-sm" wire:loading wire:target="delete"></span>
                        Yes, delete this record
                    </button>
                </div>
            </div>
        </div>
    @endif

    @can('create', App\Models\PerformanceRecord::class)
        <x-ui.form-dialog id="bird-performance-form" model="showForm" size="lg" :wrap="false"
                          :title="$formRecordId ? 'Edit Performance Record' : 'Add Performance Record'"
                          :description="'For '.$broodcock->displayName().'.'">
            @if ($formRecordId)
                {{-- An edit, or a form for one bird: filled by the server when opened. --}}
                @if ($showForm)
                    <livewire:performance.form :record="$formRecordId ? \App\Models\PerformanceRecord::find($formRecordId) : null"
                                               :broodcock="$broodcock"
                                               :return-url="route('broodcocks.show', ['broodcock' => $broodcock, 'tab' => 'performance'])"
                                               :in-dialog="true" :key="'bird-performance-form-'.($formRecordId ?? 'new')" />
                @else
                    <x-ui.form-skeleton :fields="6" />
                @endif
            @else
                {{-- A new record: built with the page, so Add opens at once with no request. --}}
                <livewire:performance.form :record="$formRecordId ? \App\Models\PerformanceRecord::find($formRecordId) : null"
                                           :broodcock="$broodcock"
                                           :return-url="route('broodcocks.show', ['broodcock' => $broodcock, 'tab' => 'performance'])"
                                           :in-dialog="true" :key="'bird-performance-form-new-'.$formNonce" />
            @endif
        </x-ui.form-dialog>
    @endcan
</div>
