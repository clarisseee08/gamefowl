<div>
    {{-- ------------------------------------------------------------------
         Summary. Every figure is derived, never stored, and the win rate is
         computed over CONTEST events only - conditioning sessions and
         weigh-ins are excluded so they cannot dilute the statistic.
         ------------------------------------------------------------------ --}}
    @php($summary = $this->summary)

    @if ($statusMessage !== '')
        <div class="mb-6 flex items-start justify-between gap-4 rounded-lg bg-brand-50 p-4 text-sm text-brand-800 ring-1 ring-brand-200"
             role="status">
            <p>{{ $statusMessage }}</p>
            <button type="button" wire:click="dismissStatus" class="shrink-0 font-medium underline">
                Dismiss
            </button>
        </div>
    @endif

    <div class="mb-6 flex flex-wrap items-start justify-between gap-3">
        <div>
            <h2 class="text-lg font-semibold text-gray-900">Performance History</h2>
            <p class="mt-1 text-sm text-gray-600">
                Everything recorded for {{ $broodcock->displayName() }}, newest first.
            </p>
        </div>

        @can('create', App\Models\PerformanceRecord::class)
            <a href="{{ route('performance.create', ['broodcock' => $broodcock->id]) }}" class="btn-primary">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                </svg>
                Add Performance Record
            </a>
        @endcan
    </div>

    <dl class="mb-6 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-6">
        <div class="card p-4">
            <dt class="text-xs font-medium uppercase tracking-wide text-gray-500">Contests</dt>
            <dd class="mt-1 text-2xl font-bold text-gray-900">{{ number_format($summary->totalContests) }}</dd>
            <p class="help">of {{ number_format($summary->totalEvents) }} {{ Str::plural('event', $summary->totalEvents) }}</p>
        </div>

        <div class="card p-4">
            <dt class="text-xs font-medium uppercase tracking-wide text-gray-500">Wins</dt>
            <dd class="mt-1 text-2xl font-bold text-emerald-700">{{ number_format($summary->wins) }}</dd>
        </div>

        <div class="card p-4">
            <dt class="text-xs font-medium uppercase tracking-wide text-gray-500">Losses</dt>
            <dd class="mt-1 text-2xl font-bold text-rose-700">{{ number_format($summary->losses) }}</dd>
        </div>

        <div class="card p-4">
            <dt class="text-xs font-medium uppercase tracking-wide text-gray-500">Draws</dt>
            <dd class="mt-1 text-2xl font-bold text-amber-700">{{ number_format($summary->draws) }}</dd>
        </div>

        <div class="card p-4">
            <dt class="text-xs font-medium uppercase tracking-wide text-gray-500">Win Rate</dt>
            <dd class="mt-1 text-2xl font-bold text-gray-900">{{ $summary->winRateLabel() }}</dd>
            <p class="help">Contests only ({{ $summary->recordLabel() }})</p>
        </div>

        <div class="card p-4">
            <dt class="text-xs font-medium uppercase tracking-wide text-gray-500">Average Rating</dt>
            <dd class="mt-1 text-2xl font-bold text-gray-900">{{ $summary->averageRatingLabel() }}</dd>
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

            <p class="mt-3 text-sm text-gray-600 sm:mt-0 sm:pb-2.5">
                {{ number_format($this->events->total()) }}
                {{ Str::plural('event', $this->events->total()) }} shown.
            </p>
        </div>
    @endif

    {{-- ------------------------------------------------------------------
         The timeline itself.
         ------------------------------------------------------------------ --}}
    @if ($this->events->isEmpty())
        <div class="card p-12 text-center">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>

            @if ($eventType !== '')
                <h3 class="mt-4 text-base font-semibold text-gray-900">Nothing of that kind recorded yet</h3>
                <p class="mt-1 text-sm text-gray-600">
                    {{ $broodcock->displayName() }} has other events on file. Choose
                    "All types of event" to see them.
                </p>
            @else
                <h3 class="mt-4 text-base font-semibold text-gray-900">No performance recorded yet</h3>
                <p class="mt-1 text-sm text-gray-600">
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
        <ol class="relative space-y-4 sm:space-y-0">
            {{-- The vertical spine. Hidden on small screens, where the cards
                 already stack and a rail only steals width. --}}
            <span class="absolute left-5 top-2 hidden h-[calc(100%-1rem)] w-px bg-gray-200 sm:block" aria-hidden="true"></span>

            @foreach ($this->events as $event)
                <li class="relative sm:flex sm:gap-4 sm:pb-4">
                    {{-- Marker --}}
                    <span class="absolute left-0 top-4 hidden h-10 w-10 shrink-0 items-center justify-center rounded-full bg-white ring-4 ring-gray-50 sm:flex"
                          aria-hidden="true">
                        <span @class([
                            'flex h-10 w-10 items-center justify-center rounded-full ring-1 ring-inset',
                            $event->event_type->badgeClasses(),
                        ])>
                            @if ($event->result === App\Enums\PerformanceResult::Win)
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
                                </svg>
                            @elseif ($event->result === App\Enums\PerformanceResult::Loss)
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            @else
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14"/>
                                </svg>
                            @endif
                        </span>
                    </span>

                    <div class="card w-full p-4 sm:ml-14">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <p class="text-base font-semibold text-gray-900">
                                    {{ $event->event_date->format('d M Y') }}
                                </p>
                                <p class="text-xs text-gray-500">
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

                        <dl class="mt-4 grid grid-cols-2 gap-4 sm:grid-cols-3">
                            <div>
                                <dt class="text-xs font-medium uppercase tracking-wide text-gray-500">Weight</dt>
                                <dd class="mt-0.5 text-sm text-gray-900">
                                    {{ $event->weight !== null ? $event->weight.' kg' : 'Not weighed' }}
                                </dd>
                            </div>

                            <div>
                                <dt class="text-xs font-medium uppercase tracking-wide text-gray-500">Duration</dt>
                                <dd class="mt-0.5 text-sm text-gray-900">
                                    {{ $event->durationLabel() ?? 'Not recorded' }}
                                </dd>
                            </div>

                            <div>
                                <dt class="text-xs font-medium uppercase tracking-wide text-gray-500">Rating</dt>
                                <dd class="mt-0.5">
                                    @if ($event->rating === null)
                                        <span class="text-sm text-gray-400">Not rated</span>
                                    @else
                                        <span class="inline-flex items-center gap-0.5" role="img"
                                              aria-label="{{ $event->rating }} out of 5 stars">
                                            @for ($star = 1; $star <= 5; $star++)
                                                <svg class="h-4 w-4 {{ $star <= $event->rating ? 'text-amber-400' : 'text-gray-300' }}"
                                                     fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                                                    <path d="M10 1.5l2.6 5.27 5.82.85-4.21 4.1.99 5.79L10 14.78l-5.2 2.73.99-5.79-4.21-4.1 5.82-.85L10 1.5z"/>
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
                            <div class="mt-4 rounded-lg bg-gray-50 p-3 ring-1 ring-gray-200">
                                <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Remarks (staff only)</p>
                                <p class="mt-1 whitespace-pre-line text-sm text-gray-700">{{ $event->remarks }}</p>
                            </div>
                        @endif

                        <div class="mt-4 flex flex-wrap items-center justify-between gap-3 border-t border-gray-100 pt-3">
                            <p class="text-xs text-gray-500">
                                Recorded by {{ $event->recordedBy?->full_name ?? 'a former staff member' }}
                            </p>

                            <div class="flex items-center gap-3">
                                @can('update', $event)
                                    <a href="{{ route('performance.edit', $event) }}"
                                       class="text-sm font-medium text-brand-700 hover:text-brand-800">
                                        Edit<span class="sr-only">, {{ $event->event_type->label() }} on {{ $event->event_date->format('d M Y') }}</span>
                                    </a>
                                @endcan
                                @can('delete', $event)
                                    <button type="button" wire:click="confirmDelete({{ $event->id }})"
                                            class="text-sm font-medium text-rose-700 hover:text-rose-800">
                                        Delete<span class="sr-only">, {{ $event->event_type->label() }} on {{ $event->event_date->format('d M Y') }}</span>
                                    </button>
                                @endcan
                            </div>
                        </div>
                    </div>
                </li>
            @endforeach
        </ol>

        <div class="mt-6">
            {{ $this->events->links() }}
        </div>
    @endif

    {{-- Delete confirmation, naming the exact record. --}}
    @if ($this->recordPendingDeletion !== null)
        @php($pending = $this->recordPendingDeletion)
        <div class="fixed inset-0 z-50 flex items-end justify-center bg-gray-900/50 p-4 sm:items-center"
             role="dialog" aria-modal="true" aria-labelledby="timeline-delete-title"
             wire:keydown.escape="cancelDelete">
            <div class="card w-full max-w-lg p-6">
                <h2 id="timeline-delete-title" class="text-lg font-semibold text-gray-900">
                    Delete this performance record?
                </h2>

                <p class="mt-2 text-sm text-gray-600">
                    You are about to delete the
                    <strong>{{ $pending->event_type->label() }}</strong> record for
                    <strong>{{ $broodcock->displayName() }}</strong>
                    dated <strong>{{ $pending->event_date->format('d M Y') }}</strong>.
                </p>

                <p class="mt-2 text-sm text-gray-600">
                    It will disappear from this timeline and stop counting towards the win rate.
                    The farm owner can restore it later if this was a mistake.
                </p>

                <div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
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
