<div>
    {{-- Header --}}
    <div class="mb-10 sm:flex sm:items-center sm:justify-between">
        <div>
            <h1 class="text-[34px] font-semibold tracking-[-0.022em] leading-[1.12] text-ink">Broodcocks</h1>
            <p class="mt-3 text-[17px] leading-relaxed text-ink-48">
                All birds recorded on the farm. Use the search and filters to narrow the list.
            </p>
        </div>

        @can('create', App\Models\Broodcock::class)
            <a href="{{ route('broodcocks.create') }}" class="btn-primary mt-4 w-full sm:mt-0 sm:w-auto">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                </svg>
                Add Broodcock
            </a>
        @endcan
    </div>

    {{-- Search and filters --}}
    <div class="card mb-10 p-6">
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div class="lg:col-span-2">
                <label for="search" class="label">Search</label>
                <input
                    id="search"
                    type="search"
                    wire:model.live.debounce.300ms="search"
                    placeholder="Name, band number, breed or bloodline"
                    class="input mt-1"
                >
            </div>

            <div>
                <label for="status" class="label">Status</label>
                <select id="status" wire:model.live="status" class="input mt-1">
                    <option value="">All statuses</option>
                    @foreach ($this->statusOptions() as $option)
                        <option value="{{ $option->value }}">{{ $option->label() }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="class" class="label">Class</label>
                <select id="class" wire:model.live="class" class="input mt-1">
                    <option value="">All classes</option>
                    @foreach ($this->classOptions() as $option)
                        <option value="{{ $option->value }}">{{ $option->label() }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="sex" class="label">Sex</label>
                <select id="sex" wire:model.live="sex" class="input mt-1">
                    <option value="">Male and female</option>
                    @foreach ($this->sexOptions() as $option)
                        <option value="{{ $option->value }}">{{ $option->farmTerm() }} ({{ $option->label() }})</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="bloodline" class="label">Bloodline</label>
                <select id="bloodline" wire:model.live="bloodline" class="input mt-1">
                    <option value="">All bloodlines</option>
                    @foreach ($this->bloodlineOptions as $option)
                        <option value="{{ $option }}">{{ $option }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="breed" class="label">Breed</label>
                <select id="breed" wire:model.live="breed" class="input mt-1">
                    <option value="">All breeds</option>
                    @foreach ($this->breedOptions as $option)
                        <option value="{{ $option }}">{{ $option }}</option>
                    @endforeach
                </select>
            </div>

            @if ($this->penOptions->isNotEmpty())
                <div>
                    <label for="pen" class="label">Pen</label>
                    <select id="pen" wire:model.live="pen" class="input mt-1">
                        <option value="">All pens</option>
                        @foreach ($this->penOptions as $option)
                            <option value="{{ $option->id }}">{{ $option->code }} - {{ $option->name }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
        </div>

        @if ($this->hasActiveFilters())
            <div class="mt-4 flex items-center justify-between border-t border-hairline pt-4">
                <p class="text-sm text-ink-80">
                    Showing {{ number_format($this->broodcocks->total()) }}
                    {{ Str::plural('bird', $this->broodcocks->total()) }} matching your filters.
                </p>
                <button type="button" wire:click="clearFilters" class="btn-secondary">
                    Clear filters
                </button>
            </div>
        @endif
    </div>

    {{-- Results --}}
    @if ($this->broodcocks->isEmpty())
        <div class="card p-12 text-center">
            <svg class="mx-auto h-12 w-12 text-ink-48" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25"/>
            </svg>

            @if ($this->hasActiveFilters())
                <h3 class="mt-4 text-[21px] font-semibold tracking-[-0.01em] leading-[1.25] text-ink">No birds match your filters</h3>
                <p class="mt-3 text-[17px] leading-relaxed text-ink-48">Try removing a filter or searching for something else.</p>
                <button type="button" wire:click="clearFilters" class="btn-secondary mt-6">Clear filters</button>
            @else
                <h3 class="mt-4 text-[21px] font-semibold tracking-[-0.01em] leading-[1.25] text-ink">No broodcocks recorded yet</h3>
                <p class="mt-3 text-[17px] leading-relaxed text-ink-48">
                    Start by adding your first bird. You will be able to record its health,
                    breeding and performance afterwards.
                </p>
                @can('create', App\Models\Broodcock::class)
                    <a href="{{ route('broodcocks.create') }}" class="btn-primary mt-6">Add your first broodcock</a>
                @endcan
            @endif
        </div>
    @else
        {{-- Mobile: cards. Farm staff are mostly on phones. --}}
        <div class="space-y-3 sm:hidden">
            @foreach ($this->broodcocks as $bird)
                <a href="{{ route('broodcocks.show', $bird) }}" class="card flex gap-4 p-4">
                    <x-photo-thumb :photo="$bird->primaryPhoto" :alt="$bird->name"
                                   class="h-16 w-16 shrink-0 rounded-lg" />
                    <div class="min-w-0 flex-1">
                        <p class="truncate font-semibold text-ink">{{ $bird->name }}</p>
                        <p class="truncate text-sm text-ink-80">{{ $bird->displayBand() }}</p>
                        <div class="mt-2 flex flex-wrap gap-1">
                            <span class="badge {{ $bird->status->badgeClasses() }}">{{ $bird->status->label() }}</span>
                            <span class="badge {{ $bird->class->badgeClasses() }}">{{ $bird->class->label() }}</span>
                        </div>
                    </div>
                </a>
            @endforeach
        </div>

        {{-- Desktop: table --}}
        <div class="card hidden overflow-hidden sm:block">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-divider">
                    <thead class="bg-pearl">
                        <tr>
                            <th scope="col" class="px-6 py-4 text-left text-[12px] font-medium uppercase tracking-[0.06em] text-ink-80">
                                Photo
                            </th>
                            @foreach ([
                                'band_number' => 'Band Number',
                                'name' => 'Name',
                                'sex' => 'Sex',
                                'breed' => 'Breed',
                                'bloodline' => 'Bloodline',
                                'class' => 'Class',
                                'status' => 'Status',
                                'date_hatched' => 'Age',
                            ] as $column => $heading)
                                <th scope="col" class="px-6 py-4 text-left text-[12px] font-medium uppercase tracking-[0.06em] text-ink-80">
                                    @if (in_array($column, ['band_number','name','breed','bloodline','class','status','date_hatched'], true))
                                        <button type="button" wire:click="sort('{{ $column }}')" class="inline-flex items-center gap-1 hover:text-ink">
                                            {{ $heading }}
                                            @if ($sortBy === $column)
                                                <span aria-hidden="true">{{ $sortDirection === 'asc' ? '▲' : '▼' }}</span>
                                                <span class="sr-only">sorted {{ $sortDirection === 'asc' ? 'ascending' : 'descending' }}</span>
                                            @endif
                                        </button>
                                    @else
                                        {{ $heading }}
                                    @endif
                                </th>
                            @endforeach
                            <th scope="col" class="px-6 py-4 text-right text-[12px] font-medium uppercase tracking-[0.06em] text-ink-80">
                                <span class="sr-only">Actions</span>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-divider bg-white">
                        @foreach ($this->broodcocks as $bird)
                            <tr class="hover:bg-pearl">
                                <td class="px-6 py-4">
                                    <x-photo-thumb :photo="$bird->primaryPhoto" :alt="$bird->name"
                                                   placeholder="None" class="h-10 w-10 rounded-lg" />
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm font-medium text-ink">
                                    {{ $bird->displayBand() }}
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-ink">{{ $bird->name }}</td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-ink-80">{{ $bird->sex->label() }}</td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-ink-80">{{ $bird->breed ?? '—' }}</td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-ink-80">{{ $bird->bloodline ?? '—' }}</td>
                                <td class="whitespace-nowrap px-6 py-4">
                                    <span class="badge {{ $bird->class->badgeClasses() }}">{{ $bird->class->label() }}</span>
                                </td>
                                <td class="whitespace-nowrap px-6 py-4">
                                    <span class="badge {{ $bird->status->badgeClasses() }}">{{ $bird->status->label() }}</span>
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-ink-80">
                                    {{ $bird->ageLabel() ?? 'Unknown' }}
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-right text-sm">
                                    <a href="{{ route('broodcocks.show', $bird) }}" class="font-medium text-action hover:underline">
                                        View<span class="sr-only">, {{ $bird->name }}</span>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-4">
            {{ $this->broodcocks->links() }}
        </div>
    @endif
</div>
