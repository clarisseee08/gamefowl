<div>
    {{-- Header --}}
    <div class="mb-6 sm:flex sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-gray-900">Performance Records</h1>
            <p class="mt-1 text-sm text-gray-600">
                Every sparring session, derby, conditioning session and weigh-in recorded on the farm.
            </p>
        </div>

        @can('create', App\Models\PerformanceRecord::class)
            <a href="{{ route('performance.create') }}" class="btn-primary mt-4 w-full sm:mt-0 sm:w-auto">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                </svg>
                Add Performance Record
            </a>
        @endcan
    </div>

    {{-- Search and filters --}}
    <div class="card mb-6 p-4">
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div class="lg:col-span-2">
                <label for="search" class="label">Search by Bird</label>
                <input
                    id="search"
                    type="search"
                    wire:model.live.debounce.300ms="search"
                    placeholder="Bird name, band number, breed or bloodline"
                    class="input mt-1"
                >
            </div>

            <div>
                <label for="eventType" class="label">Type of Event</label>
                <select id="eventType" wire:model.live="eventType" class="input mt-1">
                    <option value="">All types of event</option>
                    @foreach ($this->eventTypeOptions() as $option)
                        <option value="{{ $option->value }}">{{ $option->label() }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="result" class="label">Result</label>
                <select id="result" wire:model.live="result" class="input mt-1">
                    <option value="">All results</option>
                    @foreach ($this->resultOptions() as $option)
                        <option value="{{ $option->value }}">{{ $option->label() }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="from" class="label">From Date</label>
                <input id="from" type="date" wire:model.live="from" class="input mt-1">
            </div>

            <div>
                <label for="to" class="label">To Date</label>
                <input id="to" type="date" wire:model.live="to" class="input mt-1">
            </div>
        </div>

        @if ($this->hasActiveFilters())
            <div class="mt-4 flex flex-col gap-3 border-t border-gray-200 pt-4 sm:flex-row sm:items-center sm:justify-between">
                <p class="text-sm text-gray-600">
                    Showing {{ number_format($this->records->total()) }}
                    {{ Str::plural('record', $this->records->total()) }} matching your filters.
                </p>
                <button type="button" wire:click="clearFilters" class="btn-secondary">
                    Clear filters
                </button>
            </div>
        @endif
    </div>

    {{-- Results --}}
    @if ($this->records->isEmpty())
        <div class="card p-12 text-center">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z"/>
            </svg>

            @if ($this->hasActiveFilters())
                <h3 class="mt-4 text-base font-semibold text-gray-900">No records match your filters</h3>
                <p class="mt-1 text-sm text-gray-600">
                    Try a different date range, or clear the filters to see everything.
                </p>
                <button type="button" wire:click="clearFilters" class="btn-secondary mt-6">Clear filters</button>
            @else
                <h3 class="mt-4 text-base font-semibold text-gray-900">No performance records yet</h3>
                <p class="mt-1 text-sm text-gray-600">
                    Record a sparring session, derby, conditioning session or weigh-in and it will
                    appear here and on the bird's own timeline.
                </p>
                @can('create', App\Models\PerformanceRecord::class)
                    <a href="{{ route('performance.create') }}" class="btn-primary mt-6">
                        Add your first performance record
                    </a>
                @endcan
            @endif
        </div>
    @else
        {{-- Mobile: cards. Farm staff are mostly on phones. --}}
        <div class="space-y-3 sm:hidden">
            @foreach ($this->records as $record)
                <div class="card p-4">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="truncate font-semibold text-gray-900">
                                {{ $record->broodcock?->displayName() ?? 'Bird removed' }}
                            </p>
                            <p class="text-sm text-gray-600">{{ $record->event_date->format('d M Y') }}</p>
                        </div>
                        <div class="flex shrink-0 flex-col items-end gap-1">
                            <span class="badge {{ $record->event_type->badgeClasses() }}">
                                {{ $record->event_type->label() }}
                            </span>
                            @if ($record->result->countsTowardRecord())
                                <span class="badge {{ $record->result->badgeClasses() }}">
                                    {{ $record->result->label() }}
                                </span>
                            @endif
                        </div>
                    </div>

                    <dl class="mt-3 grid grid-cols-3 gap-2 text-sm">
                        <div>
                            <dt class="text-xs text-gray-500">Weight</dt>
                            <dd class="text-gray-900">{{ $record->weight !== null ? $record->weight.' kg' : '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-gray-500">Duration</dt>
                            <dd class="text-gray-900">{{ $record->durationLabel() ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-gray-500">Rating</dt>
                            <dd>
                                @if ($record->rating === null)
                                    <span class="text-gray-400">Not rated</span>
                                @else
                                    <span class="inline-flex items-center gap-0.5" role="img"
                                          aria-label="{{ $record->rating }} out of 5 stars">
                                        @for ($star = 1; $star <= 5; $star++)
                                            <svg class="h-4 w-4 {{ $star <= $record->rating ? 'text-amber-400' : 'text-gray-300' }}"
                                                 fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                                                <path d="M10 1.5l2.6 5.27 5.82.85-4.21 4.1.99 5.79L10 14.78l-5.2 2.73.99-5.79-4.21-4.1 5.82-.85L10 1.5z"/>
                                            </svg>
                                        @endfor
                                    </span>
                                @endif
                            </dd>
                        </div>
                    </dl>

                    <div class="mt-4 flex flex-wrap gap-2">
                        @can('update', $record)
                            <a href="{{ route('performance.edit', $record) }}" class="btn-secondary flex-1">Edit</a>
                        @endcan
                        @can('delete', $record)
                            <button type="button" wire:click="confirmDelete({{ $record->id }})" class="btn-danger flex-1">
                                Delete
                            </button>
                        @endcan
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Desktop: table --}}
        <div class="card hidden overflow-hidden sm:block">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-600">
                                Bird
                            </th>
                            @foreach ([
                                'event_date' => 'Date',
                                'event_type' => 'Type of Event',
                                'result' => 'Result',
                                'weight' => 'Weight',
                            ] as $column => $heading)
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-600">
                                    <button type="button" wire:click="sort('{{ $column }}')" class="inline-flex items-center gap-1 hover:text-gray-900">
                                        {{ $heading }}
                                        @if ($sortBy === $column)
                                            <span aria-hidden="true">{{ $sortDirection === 'asc' ? '▲' : '▼' }}</span>
                                            <span class="sr-only">sorted {{ $sortDirection === 'asc' ? 'ascending' : 'descending' }}</span>
                                        @endif
                                    </button>
                                </th>
                            @endforeach
                            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-600">
                                Duration
                            </th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-600">
                                <button type="button" wire:click="sort('rating')" class="inline-flex items-center gap-1 hover:text-gray-900">
                                    Rating
                                    @if ($sortBy === 'rating')
                                        <span aria-hidden="true">{{ $sortDirection === 'asc' ? '▲' : '▼' }}</span>
                                        <span class="sr-only">sorted {{ $sortDirection === 'asc' ? 'ascending' : 'descending' }}</span>
                                    @endif
                                </button>
                            </th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-600">
                                Recorded By
                            </th>
                            <th scope="col" class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-600">
                                <span class="sr-only">Actions</span>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white">
                        @foreach ($this->records as $record)
                            <tr class="hover:bg-gray-50">
                                <td class="whitespace-nowrap px-4 py-3 text-sm font-medium text-gray-900">
                                    @if ($record->broodcock !== null)
                                        <a href="{{ route('broodcocks.show', $record->broodcock) }}" class="text-brand-700 hover:text-brand-800">
                                            {{ $record->broodcock->displayName() }}
                                        </a>
                                    @else
                                        <span class="text-gray-500">Bird removed</span>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-600">
                                    {{ $record->event_date->format('d M Y') }}
                                </td>
                                <td class="whitespace-nowrap px-4 py-3">
                                    <span class="badge {{ $record->event_type->badgeClasses() }}">
                                        {{ $record->event_type->label() }}
                                    </span>
                                </td>
                                <td class="whitespace-nowrap px-4 py-3">
                                    <span class="badge {{ $record->result->badgeClasses() }}">
                                        {{ $record->result->label() }}
                                    </span>
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-600">
                                    {{ $record->weight !== null ? $record->weight.' kg' : '—' }}
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-600">
                                    {{ $record->durationLabel() ?? '—' }}
                                </td>
                                <td class="whitespace-nowrap px-4 py-3">
                                    @if ($record->rating === null)
                                        <span class="text-sm text-gray-400">Not rated</span>
                                    @else
                                        <span class="inline-flex items-center gap-0.5" role="img"
                                              aria-label="{{ $record->rating }} out of 5 stars">
                                            @for ($star = 1; $star <= 5; $star++)
                                                <svg class="h-4 w-4 {{ $star <= $record->rating ? 'text-amber-400' : 'text-gray-300' }}"
                                                     fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                                                    <path d="M10 1.5l2.6 5.27 5.82.85-4.21 4.1.99 5.79L10 14.78l-5.2 2.73.99-5.79-4.21-4.1 5.82-.85L10 1.5z"/>
                                                </svg>
                                            @endfor
                                        </span>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-600">
                                    {{ $record->recordedBy?->full_name ?? '—' }}
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-right text-sm">
                                    <div class="flex items-center justify-end gap-3">
                                        @can('update', $record)
                                            <a href="{{ route('performance.edit', $record) }}" class="font-medium text-brand-700 hover:text-brand-800">
                                                Edit<span class="sr-only">, {{ $record->event_type->label() }} record</span>
                                            </a>
                                        @endcan
                                        @can('delete', $record)
                                            <button type="button" wire:click="confirmDelete({{ $record->id }})" class="font-medium text-rose-700 hover:text-rose-800">
                                                Delete<span class="sr-only">, {{ $record->event_type->label() }} record</span>
                                            </button>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-4">
            {{ $this->records->links() }}
        </div>
    @endif

    {{-- Delete confirmation. Names the exact record - the bird, the type of
         event and the date - so nobody deletes the wrong one by muscle memory. --}}
    @if ($this->recordPendingDeletion !== null)
        @php($pending = $this->recordPendingDeletion)
        <div class="fixed inset-0 z-50 flex items-end justify-center bg-gray-900/50 p-4 sm:items-center"
             role="dialog" aria-modal="true" aria-labelledby="delete-dialog-title"
             wire:keydown.escape="cancelDelete">
            <div class="card w-full max-w-lg p-6">
                <h2 id="delete-dialog-title" class="text-lg font-semibold text-gray-900">
                    Delete this performance record?
                </h2>

                <p class="mt-2 text-sm text-gray-600">
                    You are about to delete the
                    <strong>{{ $pending->event_type->label() }}</strong> record for
                    <strong>{{ $pending->broodcock?->displayName() ?? 'this bird' }}</strong>
                    dated <strong>{{ $pending->event_date->format('d M Y') }}</strong>.
                </p>

                <p class="mt-2 text-sm text-gray-600">
                    It will be removed from the bird's timeline and from its win rate.
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
