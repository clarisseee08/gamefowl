<div>
    <div class="mb-10">
        {{-- h1 when this IS the page (/catalog), h2 when it is embedded under the
             farm's nameplate on the front page. See the note on $headingLevel. --}}
        <{{ $headingLevel }} class="page-title-marked text-[26px] font-semibold leading-[1.2] text-foreground">Our Gamefowl</{{ $headingLevel }}>
        <p class="mt-1 max-w-[68ch] text-[14px] leading-relaxed text-muted-foreground">
            Browse the birds currently on the farm. Tap any bird to see its photos,
            health record, family tree and performance history.
        </p>
    </div>

    {{-- Filters. Fewer and plainer than the staff screen - a customer does not
         need to filter by pen or by internal status. --}}
    {{-- One row. The search field takes what is left; the rest shrink to fit
         and wrap when they run out of room. This used to be a five-column grid
         of controls with a label stacked above each one, which on a phone
         became a tall column a customer scrolled past before seeing a bird. --}}
    <x-filter-bar :active="$this->hasActiveFilters()"
                  clear="clearFilters"
                  :summary="number_format($this->birds->total()).' '.Str::plural('bird', $this->birds->total()).' found.'">
        <x-slot:search>
            <label for="search" class="sr-only">Search</label>
            <input id="search" type="search" wire:model.live.debounce.300ms="search"
                   placeholder="Name, band number or bloodline" class="input">
        </x-slot:search>

        <x-filter-select id="bloodline" label="Bloodline" wire:model.live="bloodline">
            <option value="">All bloodlines</option>
            @foreach ($this->bloodlineOptions as $option)
                <option value="{{ $option }}">{{ $option }}</option>
            @endforeach
        </x-filter-select>

        <x-filter-select id="sex" label="Type" wire:model.live="sex">
            <option value="">Cocks and hens</option>
            @foreach ($this->sexOptions() as $option)
                <option value="{{ $option->value }}">{{ $option->farmTerm() }}</option>
            @endforeach
        </x-filter-select>

        {{-- Availability.

             A checkbox rather than a select, and off by default: the farm marks
             only a handful of birds for sale at a time, so defaulting this on
             would greet a customer with an empty catalogue. The default view is
             "what this farm keeps"; this narrows it to "what you can buy". --}}
        <label class="flex min-h-11 shrink-0 cursor-pointer items-center gap-2.5 pl-1 text-[15px] text-foreground">
            <input type="checkbox" wire:model.live="forSaleOnly" class="h-5 w-5 accent-primary">
            Available birds only
        </label>
    </x-filter-bar>

    @if ($this->birds->isEmpty())
        <div class="card p-12 text-center">
            <h3 class="text-[21px] font-semibold tracking-[-0.01em] leading-[1.25] text-foreground">
                {{ $this->hasActiveFilters() ? 'No birds match your search' : 'No birds are listed yet' }}
            </h3>
            <p class="mt-1 max-w-[68ch] text-[14px] leading-relaxed text-muted-foreground">
                {{ $this->hasActiveFilters()
                    ? 'Try a different bloodline or clear the filters to see everything.'
                    : 'Please check back soon.' }}
            </p>
            @if ($this->hasActiveFilters())
                <button type="button" wire:click="clearFilters" class="btn-secondary mt-6">Clear filters</button>
            @endif
        </div>
    @else
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 2xl:grid-cols-6">
            @foreach ($this->birds as $bird)
                <a href="{{ route('broodcocks.show', $bird) }}" wire:navigate
                   class="card card-interactive group overflow-hidden">
                    @if ($bird->primaryPhoto)
                        <x-photo-thumb :photo="$bird->primaryPhoto" :alt="'Photo of '.$bird->name"
                                       class="aspect-[4/3] w-full" />
                    @else
                        {{-- Most birds on a working farm have no photo, so this well is
                             the largest element on the page and it was showing nothing.
                             Filling it with the bloodline's band colour turns the dead
                             space into the strongest scanning signal in the grid: you can
                             read the bloodline mix of a page at arm's length. --}}
                        <div class="relative flex aspect-[4/3] w-full flex-col items-center justify-center gap-2"
                             style="background-color: {{ \App\Support\BandTag::hex($bird->bloodline) }}0f">
                            {{-- Held at 38px on purpose: large enough to scan a page of
                                 bloodlines at arm's length, quiet enough that the band tag
                                 below stays the signature. --}}
                            <span class="text-[30px] font-medium leading-none tracking-[-0.01em] opacity-80"
                                  style="color: {{ \App\Support\BandTag::hex($bird->bloodline) }}"
                                  aria-hidden="true">{{ \App\Support\BandTag::code($bird->bloodline) }}</span>
                            <span class="text-[12px] text-muted-foreground">No photo yet</span>
                        </div>
                    @endif

                    <div class="border-t border-border p-4">
                        {{-- Identity first: the band is how a keeper and a buyer both
                             refer to the bird, so it leads rather than trailing the name
                             as grey subtext. --}}
                        <x-band-tag :bloodline="$bird->bloodline" :band="$bird->band_number" size="xs" />

                        <h2 class="mt-2.5 truncate text-[17px] font-medium leading-snug text-foreground group-hover:text-primary">
                            {{ $bird->name }}
                        </h2>

                        <dl class="mt-3 space-y-1.5 text-[14px]">
                            <div class="flex justify-between gap-3">
                                <dt class="shrink-0 text-muted-foreground">Bloodline</dt>
                                <dd class="truncate text-foreground">{{ $bird->bloodline ?: 'Not recorded' }}</dd>
                            </div>
                            <div class="flex justify-between gap-3">
                                <dt class="shrink-0 text-muted-foreground">Age</dt>
                                <dd class="datum text-foreground">{{ $bird->ageLabel() ?? 'Unknown' }}</dd>
                            </div>
                        </dl>

                        <div class="mt-3.5 flex flex-wrap gap-1.5 border-t border-border pt-3.5">
                            <span class="badge {{ $bird->class->badgeClasses() }}">{{ $bird->class->label() }}</span>
                            <span class="badge badge-neutral">{{ $bird->sex->farmTerm() }}</span>
                            {{-- Only the positive case is badged. "Not for sale"
                                 is the normal state of almost every bird here,
                                 and labelling the rule rather than the exception
                                 would put a grey tag on every card in the grid. --}}
                            @if ($bird->for_sale)
                                <span class="badge badge-ok">For sale</span>
                            @endif
                        </div>
                    </div>
                </a>
            @endforeach
        </div>

        <div class="mt-6">{{ $this->birds->links() }}</div>
    @endif
</div>
