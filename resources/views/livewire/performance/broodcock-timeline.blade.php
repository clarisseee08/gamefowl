<div>
    {{-- ------------------------------------------------------------------
         Summary. Every figure is derived, never stored, and the win rate is
         computed over CONTEST events only - conditioning sessions and
         weigh-ins are excluded so they cannot dilute the statistic.
         ------------------------------------------------------------------ --}}
    @php($summary = $this->summary)

    @if ($statusMessage !== '')
        <div class="mb-6 flex items-start justify-between gap-4 rounded-[4px] bg-success-bg px-4 py-3 text-[15px] text-success"
             role="status">
            <p>{{ $statusMessage }}</p>
            <button type="button" wire:click="dismissStatus" class="-my-3 shrink-0 font-medium underline">
                Dismiss
            </button>
        </div>
    @endif

    <div class="mb-6 flex flex-wrap items-end justify-between gap-4 border-b border-border pb-5">
        <div>
            <h2 class="text-[22px] font-semibold leading-[1.2] tracking-[-0.01em] text-foreground">Performance History</h2>
            <p class="mt-1 max-w-[65ch] text-[15px] leading-relaxed text-muted-foreground">
                Everything recorded for {{ $broodcock->displayName() }}, newest first.
            </p>

            {{-- The one place colour is spent. The bird is already fully loaded
                 by the page this timeline is embedded in, so the tag and its
                 bloodline name cost no extra query. --}}
            <div class="mt-3 flex flex-wrap items-center gap-2">
                <x-band-tag :bloodline="$broodcock->bloodline" :band="$broodcock->band_number" size="xs" />
                @if (filled($broodcock->bloodline))
                    <span class="text-[13px] text-muted-foreground">{{ $broodcock->bloodline }}</span>
                @endif
            </div>
        </div>

        @can('create', App\Models\PerformanceRecord::class)
            <a href="{{ route('performance.create', ['broodcock' => $broodcock->id]) }}" class="btn-primary shrink-0">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                </svg>
                Add Performance Record
            </a>
        @endcan
    </div>

    {{-- Six derived figures. Every one is registry data, so every one is mono
         and tabular - read down the row and the digits line up.
         Win / loss / draw carry the ok / alert / warn washes because the brief
         maps those three words to those three tokens; nothing else here is
         coloured. --}}
    <dl class="mb-8 grid grid-cols-2 gap-px overflow-hidden rounded-[4px] border border-border bg-border sm:grid-cols-3 lg:grid-cols-6">
        <div class="bg-card p-4">
            <dt class="text-[11px] font-medium uppercase tracking-[0.06em] text-muted-foreground">Contests</dt>
            <dd class="datum mt-1.5 text-[32px] font-semibold leading-[1.1] tracking-[-0.02em] text-foreground">{{ number_format($summary->totalContests) }}</dd>
            <p class="help">of {{ number_format($summary->totalEvents) }} {{ Str::plural('event', $summary->totalEvents) }}</p>
        </div>

        <div class="bg-card p-4">
            <dt class="text-[11px] font-medium uppercase tracking-[0.06em] text-muted-foreground">Wins</dt>
            <dd class="datum mt-1.5 text-[32px] font-semibold leading-[1.1] tracking-[-0.02em] text-success">{{ number_format($summary->wins) }}</dd>
        </div>

        <div class="bg-card p-4">
            <dt class="text-[11px] font-medium uppercase tracking-[0.06em] text-muted-foreground">Losses</dt>
            <dd class="datum mt-1.5 text-[32px] font-semibold leading-[1.1] tracking-[-0.02em] text-destructive">{{ number_format($summary->losses) }}</dd>
        </div>

        <div class="bg-card p-4">
            <dt class="text-[11px] font-medium uppercase tracking-[0.06em] text-muted-foreground">Draws</dt>
            <dd class="datum mt-1.5 text-[32px] font-semibold leading-[1.1] tracking-[-0.02em] text-warning">{{ number_format($summary->draws) }}</dd>
        </div>

        <div class="bg-card p-4">
            {{-- "No contests yet" is prose, not a figure: it drops to the body
                 size and out of mono rather than pretending to be a number. --}}
            <dt class="text-[11px] font-medium uppercase tracking-[0.06em] text-muted-foreground">Win Rate</dt>
            <dd @class([
                'mt-1.5 font-semibold leading-[1.1] text-foreground',
                'datum text-[32px] tracking-[-0.02em]' => $summary->winRate !== null,
                'text-[15px]' => $summary->winRate === null,
            ])>{{ $summary->winRateLabel() }}</dd>
            <p class="help">Contests only (<span class="datum">{{ $summary->recordLabel() }}</span>)</p>
        </div>

        <div class="bg-card p-4">
            <dt class="text-[11px] font-medium uppercase tracking-[0.06em] text-muted-foreground">Average Rating</dt>
            <dd @class([
                'mt-1.5 font-semibold leading-[1.1] text-foreground',
                'datum text-[32px] tracking-[-0.02em]' => $summary->averageRating !== null,
                'text-[15px]' => $summary->averageRating === null,
            ])>{{ $summary->averageRatingLabel() }}</dd>
        </div>
    </dl>

    {{-- Filter --}}
    @if ($this->totalEvents > 0)
        <div class="card mb-6 p-4 sm:flex sm:items-end sm:gap-4">
            <div class="sm:w-72">
                <label for="timeline-type" class="label">Show</label>
                <select id="timeline-type" wire:model.live="eventType" class="input mt-1">
                    <option value="">All types of event</option>
                    @foreach ($this->eventTypeOptions() as $option)
                        <option value="{{ $option->value }}">{{ $option->label() }}</option>
                    @endforeach
                </select>
            </div>

            <p class="mt-3 text-[15px] text-muted-foreground sm:mt-0 sm:pb-3">
                <span class="datum">{{ number_format($this->events->total()) }}</span>
                {{ Str::plural('event', $this->events->total()) }} shown.
            </p>
        </div>
    @endif

    {{-- ------------------------------------------------------------------
         The timeline itself.
         ------------------------------------------------------------------ --}}
    @if ($this->events->isEmpty())
        <div class="card px-6 py-12 text-center">
            <svg class="mx-auto h-8 w-8 text-muted-foreground" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>

            @if ($eventType !== '')
                <h3 class="mt-4 text-[18px] font-medium text-foreground">Nothing of that kind recorded yet</h3>
                <p class="mx-auto mt-2 max-w-[52ch] text-[15px] leading-relaxed text-muted-foreground">
                    {{ $broodcock->displayName() }} has other events on file. Choose
                    "All types of event" to see them.
                </p>
            @else
                <h3 class="mt-4 text-[18px] font-medium text-foreground">No performance recorded yet</h3>
                <p class="mx-auto mt-2 max-w-[52ch] text-[15px] leading-relaxed text-muted-foreground">
                    Nothing has been recorded for {{ $broodcock->displayName() }} so far.
                    Add a sparring session, derby, conditioning session or weigh-in and it
                    will appear here.
                </p>
                @can('create', App\Models\PerformanceRecord::class)
                    <a href="{{ route('performance.create', ['broodcock' => $broodcock->id]) }}" class="btn-primary mt-6">
                        Add the first record
                    </a>
                @endcan
            @endif
        </div>
    @else
        <ol class="relative space-y-3 sm:space-y-0">
            {{-- The vertical spine. It was drawn in bg-background, which is the
                 page's own colour, so it had been invisible; a rule in this
                 system is a hairline. Hidden on small screens, where the cards
                 already stack and a rail only steals width. --}}
            <span class="absolute left-4 top-3 hidden h-[calc(100%-1.5rem)] w-px bg-border sm:block" aria-hidden="true"></span>

            @foreach ($this->events as $event)
                <li class="relative sm:flex sm:gap-4 sm:pb-3">
                    {{-- Marker. A square node, not a second capsule - the only
                         capsule in this system is the band tag. The parchment
                         gutter behind it is what breaks the spine cleanly. --}}
                    <span class="absolute left-0 top-3.5 hidden h-8 w-8 shrink-0 items-center justify-center bg-background sm:flex"
                          aria-hidden="true">
                        <span @class([
                            'flex h-7 w-7 items-center justify-center rounded-[2px] border border-border',
                            $event->event_type->badgeClasses(),
                        ])>
                            @if ($event->result === App\Enums\PerformanceResult::Win)
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
                                </svg>
                            @elseif ($event->result === App\Enums\PerformanceResult::Loss)
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            @else
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14"/>
                                </svg>
                            @endif
                        </span>
                    </span>

                    <div class="card w-full p-4 sm:ml-12">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <p class="datum text-[18px] font-medium leading-[1.25] text-foreground">
                                    {{ $event->event_date->format('d M Y') }}
                                </p>
                                <p class="mt-0.5 text-[12px] text-muted-foreground">
                                    {{ $event->event_date->diffForHumans() }}
                                </p>
                            </div>

                            <div class="flex flex-wrap items-center gap-1.5">
                                <span class="badge {{ $event->event_type->badgeClasses() }}">
                                    {{ $event->event_type->label() }}
                                </span>
                                <span class="badge {{ $event->result->badgeClasses() }}">
                                    {{ $event->result->label() }}
                                </span>
                            </div>
                        </div>

                        <dl class="mt-3.5 grid grid-cols-2 gap-4 border-t border-border pt-3 sm:grid-cols-3">
                            <div>
                                <dt class="text-[11px] font-medium uppercase tracking-[0.06em] text-muted-foreground">Weight</dt>
                                <dd @class([
                                    'mt-1 text-[15px] text-foreground',
                                    'datum' => $event->weight !== null,
                                ])>
                                    {{ $event->weight !== null ? $event->weight.' kg' : 'Not weighed' }}
                                </dd>
                            </div>

                            <div>
                                <dt class="text-[11px] font-medium uppercase tracking-[0.06em] text-muted-foreground">Duration</dt>
                                <dd @class([
                                    'mt-1 text-[15px] text-foreground',
                                    'datum' => $event->durationLabel() !== null,
                                ])>
                                    {{ $event->durationLabel() ?? 'Not recorded' }}
                                </dd>
                            </div>

                            <div>
                                <dt class="text-[11px] font-medium uppercase tracking-[0.06em] text-muted-foreground">Rating</dt>
                                <dd class="mt-1">
                                    @if ($event->rating === null)
                                        <span class="text-[15px] text-muted-foreground">Not rated</span>
                                    @else
                                        <span class="inline-flex items-center gap-0.5" role="img"
                                              aria-label="{{ $event->rating }} out of 5 stars">
                                            @for ($star = 1; $star <= 5; $star++)
                                                <svg class="h-4 w-4 {{ $star <= $event->rating ? 'text-foreground' : 'text-muted-foreground' }}"
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

                        {{-- Internal remarks. Gated on the policy, not on a
                             CSS class: a customer's page never renders them. --}}
                        @if ($event->remarks !== null && $event->remarks !== '' && auth()->user()?->can('viewRemarks', $event))
                            <div class="mt-3.5 rounded-[4px] border border-border bg-muted px-3 py-2.5">
                                <p class="text-[11px] font-medium uppercase tracking-[0.06em] text-muted-foreground">Remarks (staff only)</p>
                                <p class="mt-1 whitespace-pre-line text-[15px] leading-relaxed text-foreground">{{ $event->remarks }}</p>
                            </div>
                        @endif

                        <div class="mt-3.5 flex flex-wrap items-center justify-between gap-3 border-t border-border pt-2">
                            <p class="text-[12px] text-muted-foreground">
                                Recorded by {{ $event->recordedBy?->full_name ?? 'a former staff member' }}
                            </p>

                            <div class="flex items-center gap-4">
                                @can('update', $event)
                                    <a href="{{ route('performance.edit', $event) }}"
                                       class="inline-flex min-h-11 items-center text-[15px] font-medium text-primary hover:underline">
                                        Edit<span class="sr-only">, {{ $event->event_type->label() }} on {{ $event->event_date->format('d M Y') }}</span>
                                    </a>
                                @endcan
                                @can('delete', $event)
                                    <button type="button" wire:click="confirmDelete({{ $event->id }})"
                                            class="text-[15px] font-medium text-destructive hover:underline">
                                        Delete<span class="sr-only">, {{ $event->event_type->label() }} on {{ $event->event_date->format('d M Y') }}</span>
                                    </button>
                                @endcan
                            </div>
                        </div>
                    </div>
                </li>
            @endforeach
        </ol>

        <div class="mt-5 border-t border-border pt-4">
            {{ $this->events->links() }}
        </div>
    @endif

    {{-- Delete confirmation, naming the exact record. --}}
    @if ($this->recordPendingDeletion !== null)
        @php($pending = $this->recordPendingDeletion)
        <div class="fixed inset-0 z-50 flex items-end justify-center bg-foreground/40 p-4 sm:items-center" x-data x-trap.noscroll="true" @keydown.escape.window="$el.querySelector('.btn-secondary')?.click()"
             role="dialog" aria-modal="true" aria-labelledby="timeline-delete-title"
             wire:keydown.escape="cancelDelete">
            <div class="card w-full max-w-lg p-6">
                <h2 id="timeline-delete-title" class="text-[22px] font-semibold leading-[1.2] tracking-[-0.01em] text-foreground">
                    Delete this performance record?
                </h2>

                <p class="mt-3 text-[15px] leading-relaxed text-muted-foreground">
                    You are about to delete the
                    <strong class="font-medium text-foreground">{{ $pending->event_type->label() }}</strong> record for
                    <strong class="font-medium text-foreground">{{ $broodcock->displayName() }}</strong>
                    dated <strong class="datum font-medium text-foreground">{{ $pending->event_date->format('d M Y') }}</strong>.
                </p>

                <p class="mt-2 text-[15px] leading-relaxed text-muted-foreground">
                    It will disappear from this timeline and stop counting towards the win rate.
                    The farm owner can restore it later if this was a mistake.
                </p>

                <div class="mt-6 flex flex-col-reverse gap-3 border-t border-border pt-5 sm:flex-row sm:justify-end">
                    <button type="button" wire:click="cancelDelete" class="btn-secondary sm:w-auto">
                        No, keep it
                    </button>
                    <button type="button" wire:click="delete" class="btn-danger sm:w-auto">
                        Yes, delete this record
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
