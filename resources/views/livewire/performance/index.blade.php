<div>
    {{-- Livewire re-renders only this component, so the confirmation lives
         here rather than in the layout's session flash. --}}
    @if ($statusMessage !== '')
        <div class="mb-6 flex items-start justify-between gap-4 rounded-[4px] bg-ok-wash px-4 py-3 text-[15px] text-ok"
             role="status">
            <p>{{ $statusMessage }}</p>
            <button type="button" wire:click="dismissStatus" class="-my-3 shrink-0 font-medium underline">
                Dismiss
            </button>
        </div>
    @endif

    {{-- Header --}}
    <div class="mb-8 border-b border-rule-strong pb-6 sm:flex sm:items-end sm:justify-between sm:gap-8">
        <div>
            <h1 class="text-[32px] font-semibold leading-[1.15] tracking-[-0.02em] text-ink">Performance Records</h1>
            <p class="mt-2 max-w-[65ch] text-[15px] leading-relaxed text-ink-80">
                Every sparring session, derby, conditioning session and weigh-in recorded on the farm.
            </p>
        </div>

        @can('create', App\Models\PerformanceRecord::class)
            <a href="{{ route('performance.create') }}" class="btn-primary mt-5 w-full shrink-0 sm:mt-0 sm:w-auto">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                </svg>
                Add Performance Record
            </a>
        @endcan
    </div>

    {{-- Search and filters --}}
    <div class="card mb-6 p-5">
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
            <div class="mt-5 flex flex-col gap-3 border-t border-hairline pt-4 sm:flex-row sm:items-center sm:justify-between">
                <p class="text-[15px] text-ink-80">
                    Showing <span class="datum">{{ number_format($this->records->total()) }}</span>
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
        <div class="card px-6 py-12 text-center">
            <svg class="mx-auto h-8 w-8 text-ink-48" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z"/>
            </svg>

            @if ($this->hasActiveFilters())
                <h3 class="mt-4 text-[18px] font-medium text-ink">No records match your filters</h3>
                <p class="mx-auto mt-2 max-w-[52ch] text-[15px] leading-relaxed text-ink-80">
                    Try a different date range, or clear the filters to see everything.
                </p>
                <button type="button" wire:click="clearFilters" class="btn-secondary mt-6">Clear filters</button>
            @else
                <h3 class="mt-4 text-[18px] font-medium text-ink">No performance records yet</h3>
                <p class="mx-auto mt-2 max-w-[52ch] text-[15px] leading-relaxed text-ink-80">
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
        <div class="space-y-2.5 sm:hidden">
            @foreach ($this->records as $record)
                <div class="card p-4">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="truncate text-[15px] font-medium text-ink">
                                {{ $record->broodcock?->displayName() ?? 'Bird removed' }}
                            </p>
                            <p class="datum mt-0.5 text-[13px] text-ink-80">{{ $record->event_date->format('d M Y') }}</p>
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

                    <dl class="mt-3.5 grid grid-cols-3 gap-3 border-t border-hairline pt-3">
                        <div>
                            <dt class="text-[11px] font-medium uppercase tracking-[0.06em] text-ink-80">Weight</dt>
                            <dd class="datum mt-1 text-[15px] text-ink">{{ $record->weight !== null ? $record->weight.' kg' : '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-[11px] font-medium uppercase tracking-[0.06em] text-ink-80">Duration</dt>
                            <dd class="datum mt-1 text-[15px] text-ink">{{ $record->durationLabel() ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-[11px] font-medium uppercase tracking-[0.06em] text-ink-80">Rating</dt>
                            <dd class="mt-1">
                                @if ($record->rating === null)
                                    <span class="text-[13px] text-ink-80">Not rated</span>
                                @else
                                    {{-- Ink stars, not amber: colour in this system means
                                         bloodline, so a rating is drawn with fill and
                                         outline instead of hue. --}}
                                    <span class="inline-flex items-center gap-0.5" role="img"
                                          aria-label="{{ $record->rating }} out of 5 stars">
                                        @for ($star = 1; $star <= 5; $star++)
                                            <svg class="h-4 w-4 {{ $star <= $record->rating ? 'text-ink' : 'text-ink-48' }}"
                                                 viewBox="0 0 20 20" aria-hidden="true"
                                                 fill="{{ $star <= $record->rating ? 'currentColor' : 'none' }}"
                                                 stroke="currentColor"
                                                 stroke-width="{{ $star <= $record->rating ? '0' : '1.25' }}">
                                                <path stroke-linejoin="round" d="M10 1.5l2.6 5.27 5.82.85-4.21 4.1.99 5.79L10 14.78l-5.2 2.73.99-5.79-4.21-4.1 5.82-.85L10 1.5z"/>
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
                <table class="min-w-full">
                    <thead class="border-b border-rule-strong bg-pearl">
                        <tr>
                            <th scope="col" class="px-4 py-2 text-left text-[11px] font-medium uppercase tracking-[0.06em] text-ink-80">
                                Bird
                            </th>
                            @foreach ([
                                'event_date' => 'Date',
                                'event_type' => 'Type of Event',
                                'result' => 'Result',
                                'weight' => 'Weight',
                            ] as $column => $heading)
                                {{-- Numeric columns are right-aligned so the mono digits
                                     stack into a single readable column. --}}
                                <th scope="col" @class([
                                    'px-4 py-2 text-[11px] font-medium uppercase tracking-[0.06em] text-ink-80',
                                    'text-right' => $column === 'weight',
                                    'text-left' => $column !== 'weight',
                                ])>
                                    <button type="button" wire:click="sort('{{ $column }}')"
                                            class="-mx-1 inline-flex items-center gap-1 rounded-[4px] px-1 uppercase tracking-[0.06em] hover:text-ink">
                                        {{ $heading }}
                                        @if ($sortBy === $column)
                                            <svg class="h-3 w-3 shrink-0 text-action" viewBox="0 0 12 12" fill="currentColor" aria-hidden="true">
                                                <path d="{{ $sortDirection === 'asc' ? 'M6 3l3.5 5h-7z' : 'M6 9L2.5 4h7z' }}"/>
                                            </svg>
                                            <span class="sr-only">sorted {{ $sortDirection === 'asc' ? 'ascending' : 'descending' }}</span>
                                        @endif
                                    </button>
                                </th>
                            @endforeach
                            <th scope="col" class="px-4 py-2 text-right text-[11px] font-medium uppercase tracking-[0.06em] text-ink-80">
                                Duration
                            </th>
                            <th scope="col" class="px-4 py-2 text-left text-[11px] font-medium uppercase tracking-[0.06em] text-ink-80">
                                <button type="button" wire:click="sort('rating')"
                                        class="-mx-1 inline-flex items-center gap-1 rounded-[4px] px-1 uppercase tracking-[0.06em] hover:text-ink">
                                    Rating
                                    @if ($sortBy === 'rating')
                                        <svg class="h-3 w-3 shrink-0 text-action" viewBox="0 0 12 12" fill="currentColor" aria-hidden="true">
                                            <path d="{{ $sortDirection === 'asc' ? 'M6 3l3.5 5h-7z' : 'M6 9L2.5 4h7z' }}"/>
                                        </svg>
                                        <span class="sr-only">sorted {{ $sortDirection === 'asc' ? 'ascending' : 'descending' }}</span>
                                    @endif
                                </button>
                            </th>
                            <th scope="col" class="px-4 py-2 text-left text-[11px] font-medium uppercase tracking-[0.06em] text-ink-80">
                                Recorded By
                            </th>
                            <th scope="col" class="px-4 py-2 text-right text-[11px] font-medium uppercase tracking-[0.06em] text-ink-80">
                                <span class="sr-only">Actions</span>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="table-hairline bg-canvas">
                        @foreach ($this->records as $record)
                            <tr class="hover:bg-pearl">
                                <td class="whitespace-nowrap px-4 py-3 text-[15px] font-medium text-ink">
                                    @if ($record->broodcock !== null)
                                        <a href="{{ route('broodcocks.show', $record->broodcock) }}" class="text-action hover:underline">
                                            {{ $record->broodcock->displayName() }}
                                        </a>
                                    @else
                                        <span class="font-normal text-ink-80">Bird removed</span>
                                    @endif
                                </td>
                                <td class="datum whitespace-nowrap px-4 py-3 text-[15px] text-ink">
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
                                <td class="datum whitespace-nowrap px-4 py-3 text-right text-[15px] text-ink">
                                    {{ $record->weight !== null ? $record->weight.' kg' : '—' }}
                                </td>
                                <td class="datum whitespace-nowrap px-4 py-3 text-right text-[15px] text-ink">
                                    {{ $record->durationLabel() ?? '—' }}
                                </td>
                                <td class="whitespace-nowrap px-4 py-3">
                                    @if ($record->rating === null)
                                        <span class="text-[13px] text-ink-80">Not rated</span>
                                    @else
                                        <span class="inline-flex items-center gap-0.5" role="img"
                                              aria-label="{{ $record->rating }} out of 5 stars">
                                            @for ($star = 1; $star <= 5; $star++)
                                                <svg class="h-4 w-4 {{ $star <= $record->rating ? 'text-ink' : 'text-ink-48' }}"
                                                     viewBox="0 0 20 20" aria-hidden="true"
                                                     fill="{{ $star <= $record->rating ? 'currentColor' : 'none' }}"
                                                     stroke="currentColor"
                                                     stroke-width="{{ $star <= $record->rating ? '0' : '1.25' }}">
                                                    <path stroke-linejoin="round" d="M10 1.5l2.6 5.27 5.82.85-4.21 4.1.99 5.79L10 14.78l-5.2 2.73.99-5.79-4.21-4.1 5.82-.85L10 1.5z"/>
                                                </svg>
                                            @endfor
                                        </span>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-[15px] text-ink-80">
                                    {{ $record->recordedBy?->full_name ?? '—' }}
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-right text-[15px]">
                                    <div class="flex items-center justify-end gap-4">
                                        @can('update', $record)
                                            <a href="{{ route('performance.edit', $record) }}" class="font-medium text-action hover:underline">
                                                Edit<span class="sr-only">, {{ $record->event_type->label() }} record</span>
                                            </a>
                                        @endcan
                                        @can('delete', $record)
                                            <button type="button" wire:click="confirmDelete({{ $record->id }})" class="font-medium text-alert hover:underline">
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

        <div class="mt-5 border-t border-hairline pt-4">
            {{ $this->records->links() }}
        </div>
    @endif

    {{-- Delete confirmation. Names the exact record - the bird, the type of
         event and the date - so nobody deletes the wrong one by muscle memory. --}}
    @if ($this->recordPendingDeletion !== null)
        @php($pending = $this->recordPendingDeletion)
        <div class="fixed inset-0 z-50 flex items-end justify-center bg-ink/40 p-4 sm:items-center" x-data x-trap.noscroll="true" @keydown.escape.window="$el.querySelector('.btn-secondary')?.click()"
             role="dialog" aria-modal="true" aria-labelledby="delete-dialog-title"
             wire:keydown.escape="cancelDelete">
            <div class="card w-full max-w-lg p-6">
                <h2 id="delete-dialog-title" class="text-[22px] font-semibold leading-[1.2] tracking-[-0.01em] text-ink">
                    Delete this performance record?
                </h2>

                <p class="mt-3 text-[15px] leading-relaxed text-ink-80">
                    You are about to delete the
                    <strong class="font-medium text-ink">{{ $pending->event_type->label() }}</strong> record for
                    <strong class="font-medium text-ink">{{ $pending->broodcock?->displayName() ?? 'this bird' }}</strong>
                    dated <strong class="datum font-medium text-ink">{{ $pending->event_date->format('d M Y') }}</strong>.
                </p>

                <p class="mt-2 text-[15px] leading-relaxed text-ink-80">
                    It will be removed from the bird's timeline and from its win rate.
                    The farm owner can restore it later if this was a mistake.
                </p>

                <div class="mt-6 flex flex-col-reverse gap-3 border-t border-hairline pt-5 sm:flex-row sm:justify-end">
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
