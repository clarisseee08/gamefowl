<div>
    <div class="mb-10">
        <h1 class="text-[34px] font-semibold tracking-[-0.022em] leading-[1.12] text-ink">Our Gamefowl</h1>
        <p class="mt-3 text-[17px] leading-relaxed text-ink-48">
            Browse the birds currently on the farm. Tap any bird to see its photos,
            health record, family tree and performance history.
        </p>
    </div>

    {{-- Filters. Fewer and plainer than the staff screen - a customer does not
         need to filter by pen or by internal status. --}}
    <div class="card mb-10 p-6">
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
            <div class="lg:col-span-2">
                <label for="search" class="label">Search</label>
                <input id="search" type="search" wire:model.live.debounce.300ms="search"
                       placeholder="Name, band number or breed" class="input mt-1">
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

            <div>
                <label for="sex" class="label">Type</label>
                <select id="sex" wire:model.live="sex" class="input mt-1">
                    <option value="">Cocks and hens</option>
                    @foreach ($this->sexOptions() as $option)
                        <option value="{{ $option->value }}">{{ $option->farmTerm() }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        @if ($this->hasActiveFilters())
            <div class="mt-4 flex items-center justify-between border-t border-hairline pt-4">
                <p class="text-sm text-ink-80">
                    {{ number_format($this->birds->total()) }}
                    {{ Str::plural('bird', $this->birds->total()) }} found.
                </p>
                <button type="button" wire:click="clearFilters" class="btn-secondary">Clear filters</button>
            </div>
        @endif
    </div>

    @if ($this->birds->isEmpty())
        <div class="card p-12 text-center">
            <h3 class="text-[21px] font-semibold tracking-[-0.01em] leading-[1.25] text-ink">
                {{ $this->hasActiveFilters() ? 'No birds match your search' : 'No birds are listed yet' }}
            </h3>
            <p class="mt-3 text-[17px] leading-relaxed text-ink-48">
                {{ $this->hasActiveFilters()
                    ? 'Try a different bloodline or clear the filters to see everything.'
                    : 'Please check back soon.' }}
            </p>
            @if ($this->hasActiveFilters())
                <button type="button" wire:click="clearFilters" class="btn-secondary mt-6">Clear filters</button>
            @endif
        </div>
    @else
        <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
            @foreach ($this->birds as $bird)
                <a href="{{ route('broodcocks.show', $bird) }}"
                   class="card group overflow-hidden transition hover:shadow-md">
                    <x-photo-thumb :photo="$bird->primaryPhoto" :alt="'Photo of '.$bird->name"
                                   placeholder="No photo yet"
                                   class="aspect-4/3 w-full" />

                    <div class="p-4">
                        <h2 class="truncate font-semibold text-ink group-hover:text-action">
                            {{ $bird->name }}
                        </h2>
                        <p class="truncate text-sm text-ink-80">{{ $bird->displayBand() }}</p>

                        <dl class="mt-3 space-y-1 text-sm">
                            <div class="flex justify-between gap-2">
                                <dt class="text-ink-48">Bloodline</dt>
                                <dd class="truncate font-medium text-ink">{{ $bird->bloodline ?: 'Not recorded' }}</dd>
                            </div>
                            <div class="flex justify-between gap-2">
                                <dt class="text-ink-48">Breed</dt>
                                <dd class="truncate font-medium text-ink">{{ $bird->breed ?: 'Not recorded' }}</dd>
                            </div>
                            <div class="flex justify-between gap-2">
                                <dt class="text-ink-48">Age</dt>
                                <dd class="font-medium text-ink">{{ $bird->ageLabel() ?? 'Unknown' }}</dd>
                            </div>
                        </dl>

                        <div class="mt-3 flex flex-wrap gap-1.5">
                            <span class="badge {{ $bird->class->badgeClasses() }}">{{ $bird->class->label() }}</span>
                            <span class="badge bg-parchment text-ink-80 ring-hairline">{{ $bird->sex->farmTerm() }}</span>
                        </div>
                    </div>
                </a>
            @endforeach
        </div>

        <div class="mt-6">{{ $this->birds->links() }}</div>
    @endif
</div>
