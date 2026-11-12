@php
    $total = $this->birds->total();
    // Staff see the catalogue inside the console, which opens every page on
    // the compact EMS header; visitors see the stud-book page.
    $inConsole = auth()->user()?->isInternal() ?? false;
@endphp

{{--
    THE CATALOGUE — every bird the farm currently holds.

    The same stud-book card the front page uses - photograph at 4:5, the band
    in the display face, the bloodline as a dot and a word, the age in tabular
    figures - so a bird looks the same here as on the page that linked to it.
    No card background and no box: the photograph is the card.
--}}
<div>
    @if ($inConsole)
        <x-ui.page-header title="Our gamefowl"
                          description="Every bird on the farm today. Open one to see its photographs, health record, family tree and performance history." />
    @else
        <div class="max-w-[60ch]">
            <p class="sb-eyebrow sb-line" style="--line: 0ms">Our stock</p>
            {{-- h1 when this IS the page (/catalog), h2 if it is ever embedded
                 under another page's heading. See the note on $headingLevel. --}}
            <{{ $headingLevel }} class="sb-hero-title sb-line mt-3" style="--line: 60ms">Our gamefowl</{{ $headingLevel }}>
            <p class="sb-lead sb-line mt-4" style="--line: 130ms">
                Every bird on the farm today. Open one to see its photographs, health record,
                family tree and performance history.
            </p>
        </div>
    @endif

    {{-- ---------------------------------------------------------------
         FILTERS — one row of real controls, not a box.

         They used to sit in a bordered panel; the controls carry their own
         edges, so the panel drew a second border around things already
         edged. A hairline underneath ends the row instead.
    --------------------------------------------------------------- --}}
    <div x-data="{ filtersOpen: false }" @class([
        'flex flex-col gap-3 lg:flex-row lg:flex-wrap lg:items-center',
        'mt-6 border-b border-border pb-4' => $inConsole,
        'mt-8 rounded-md border border-border bg-card p-3' => ! $inConsole,
    ])>
        {{-- On a phone: the search and a Filters button on one line, the
             dropdowns folded behind it; from md up they sit in the row. --}}
        <div class="flex items-center gap-3 lg:contents">
            <div class="relative min-w-0 flex-1 lg:max-w-md">
                <label for="search" class="sr-only">Search</label>
                <x-ui.icon name="search" class="pointer-events-none absolute top-1/2 left-3 z-10 size-4 -translate-y-1/2 text-muted-foreground" />
                <input id="search" type="search" wire:model.live.debounce.300ms="search"
                       placeholder="Name, band number or bloodline" class="d-input w-full pl-9">
            </div>
            <x-ui.filters-toggle :count="collect([$this->bloodline, $this->sex])->filter(fn ($value) => filled($value))->count()" />
        </div>

        {{-- Folded behind Filters on a phone, one to a row inside the panel
             (two side by side cut the values to "A..." and "Coc..."). --}}
        <div class="grid-cols-1 gap-3 sm:grid-cols-2 md:flex md:flex-wrap md:items-center"
             x-bind:class="filtersOpen ? 'grid' : 'hidden'">
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

            <x-filter-select id="sort" label="Sort" wire:model.live="sort">
                @foreach (\App\Livewire\Catalog\Browse::SORTS as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </x-filter-select>
        </div>

        {{-- AVAILABILITY - and the fix for "no birds appear when I tick this".

             The filter was never broken: it returned nothing because not one
             bird on the farm is marked for sale. A control that can only ever
             empty the page is a trap, and the page then blamed the visitor for
             springing it. So it is DISABLED when nothing is offered, with the
             reason attached - disabled rather than hidden, because a control
             that comes and goes with the stock is a page that moves under you. --}}
        <label @class([
                   'inline-flex min-h-11 shrink-0 items-center gap-2.5 text-[15px] lg:min-h-7 lg:text-xs',
                   'cursor-pointer text-foreground' => $this->anyForSale,
                   'cursor-not-allowed text-muted-foreground' => ! $this->anyForSale,
               ])
               @if (! $this->anyForSale) title="No birds are marked for sale just now." @endif>
            <input type="checkbox" wire:model.live="forSaleOnly" class="d-checkbox d-checkbox-primary"
                   @disabled(! $this->anyForSale)>
            Available birds only
        </label>

        <div class="flex items-center gap-4 text-[14px] text-muted-foreground lg:ml-auto lg:text-xs">
            <span wire:loading.remove wire:target="search,bloodline,sex,class,forSaleOnly,sort">
                <span class="datum font-semibold text-foreground">{{ number_format($total) }}</span> {{ \Illuminate\Support\Str::plural('bird', $total) }} found.
            </span>
            <span wire:loading wire:target="search,bloodline,sex,class,forSaleOnly,sort">Searching&hellip;</span>

            @if ($this->hasActiveFilters())
                <button type="button" wire:click="clearFilters"
                        class="d-link d-link-primary inline-flex min-h-11 items-center font-semibold underline-offset-4">
                    Clear filters
                </button>
            @endif
        </div>

        @if (! $this->anyForSale)
            <p class="w-full text-[13px] text-muted-foreground">No birds are marked for sale just now.</p>
        @endif
    </div>

    @php
        // Everything that replaces the grid with a new answer: the filters,
        // the sort, and paging.
        $gridTargets = 'search,bloodline,sex,class,forSaleOnly,sort,gotoPage,nextPage,previousPage';
        $gridClasses = $inConsole
            ? 'grid grid-cols-2 mt-6 gap-x-3 gap-y-6 md:grid-cols-4 xl:grid-cols-6'
            : 'grid grid-cols-2 mt-6 gap-3 md:grid-cols-3 lg:grid-cols-4 lg:gap-4';
    @endphp

    {{-- WHILE THE NEXT BIRDS ARE FETCHED, the shape of the grid stands in for
         them: the old cards leave at once instead of sitting there looking
         like the answer to a search that has not come back yet. Same columns
         and the same 4:3 photo as x-bird-card, so nothing jumps when they land. --}}
    <ul class="{{ $gridClasses }} hidden" wire:loading.grid wire:target="{{ $gridTargets }}" aria-hidden="true">
        @for ($i = 0; $i < ($inConsole ? 12 : 8); $i++)
            <li class="flex flex-col overflow-hidden rounded-md border border-border bg-card">
                <x-ui.skeleton class="aspect-[4/3] w-full rounded-none" />
                <div @class(['space-y-2', $inConsole ? 'p-2.5' : 'p-3.5'])>
                    <div class="flex items-center justify-between gap-3">
                        <x-ui.skeleton class="h-3.5 {{ ['w-2/3', 'w-1/2', 'w-3/5'][$i % 3] }}" />
                        <x-ui.skeleton class="h-3 w-8" />
                    </div>
                    <x-ui.skeleton class="h-3 w-1/2" />
                </div>
            </li>
        @endfor
    </ul>

    <div wire:loading.remove wire:target="{{ $gridTargets }}">
    @if ($this->birds->isEmpty())
        {{-- THREE EMPTY STATES, because they are three different facts and only
             one of them is the visitor's doing. The for-sale case was the one
             that was wrong: it used to say "Try a different bloodline", advice
             that could not work, about a search the visitor had not botched. --}}
        @php $onlyForSale = $this->forSaleOnly && ! $this->anyForSale; @endphp

        <div @class(['py-16', 'mt-6 flex flex-col items-center rounded-md border border-dashed border-border px-6 text-center' => ! $inConsole])>
            @unless ($inConsole)
                <span class="mb-4 inline-flex size-11 items-center justify-center rounded-full bg-muted text-muted-foreground"><x-ui.icon name="search" class="size-5" /></span>
            @endunless
            <h2 @class(['sb-h2' => $inConsole, 'text-lg font-bold text-foreground' => ! $inConsole])>
                @if ($onlyForSale)
                    No birds are marked for sale just now
                @elseif ($this->hasActiveFilters())
                    No birds match your search
                @else
                    No birds are listed yet
                @endif
            </h2>
            <p class="mt-2 max-w-[56ch] text-[15px] text-muted-foreground">
                @if ($onlyForSale)
                    The farm keeps its broodcocks for breeding and offers only a few at a time.
                    Everything it holds is still worth looking at &mdash; and worth ringing about.
                @elseif ($this->hasActiveFilters())
                    Try a different bloodline or clear the filters to see everything.
                @else
                    Please check back soon.
                @endif
            </p>
            @if ($this->hasActiveFilters())
                <button type="button" wire:click="clearFilters" class="d-btn d-btn-outline mt-6">
                    {{ $onlyForSale ? 'Show every bird on the farm' : 'Clear filters' }}
                </button>
            @endif
        </div>
    @else
        <ul class="{{ $gridClasses }}">
            @foreach ($this->birds as $bird)
                {{-- Keyed so a new search swaps cards whole instead of reusing a
                     card's element for a different bird. --}}
                <li wire:key="bird-{{ $bird->id }}">
                    <x-bird-card :bird="$bird" heading="h2" :details="true" :compact="$inConsole" :delay="($loop->index % 4) * 60" />
                </li>
            @endforeach
        </ul>

    @endif
    </div>

    @if ($this->birds->isNotEmpty())
        <div @class(['mt-12' => $inConsole, 'mt-8' => ! $inConsole])>{{ $this->birds->links() }}</div>
    @endif
</div>
