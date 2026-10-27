@php
    $total = $this->birds->total();
@endphp

{{--
    THE CATALOGUE — every bird the farm currently holds.

    The same stud-book card the front page uses - photograph at 4:5, the band
    in the display face, the bloodline as a dot and a word, the age in tabular
    figures - so a bird looks the same here as on the page that linked to it.
    No card background and no box: the photograph is the card.
--}}
<div>
    <div class="max-w-[60ch]">
        {{-- h1 when this IS the page (/catalog), h2 if it is ever embedded
             under another page's heading. See the note on $headingLevel. --}}
        <{{ $headingLevel }} class="sb-h2 lg:text-[2.4rem]">Our gamefowl</{{ $headingLevel }}>
        <p class="mt-3 text-[16px] text-muted-foreground">
            Every bird on the farm today. Open one to see its photographs, health record,
            family tree and performance history.
        </p>
    </div>

    {{-- ---------------------------------------------------------------
         FILTERS — one row of real controls, not a box.

         They used to sit in a bordered panel; the controls carry their own
         edges, so the panel drew a second border around things already
         edged. A hairline underneath ends the row instead.
    --------------------------------------------------------------- --}}
    <div class="mt-8 flex flex-col gap-3 border-b border-border pb-6 lg:flex-row lg:flex-wrap lg:items-center">
        <div class="min-w-0 flex-1 lg:max-w-md">
            <label for="search" class="sr-only">Search</label>
            <input id="search" type="search" wire:model.live.debounce.300ms="search"
                   placeholder="Name, band number or bloodline" class="input">
        </div>

        <div class="grid grid-cols-2 gap-3 sm:flex sm:flex-wrap sm:items-center">
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
        </div>

        {{-- AVAILABILITY - and the fix for "no birds appear when I tick this".

             The filter was never broken: it returned nothing because not one
             bird on the farm is marked for sale. A control that can only ever
             empty the page is a trap, and the page then blamed the visitor for
             springing it. So it is DISABLED when nothing is offered, with the
             reason attached - disabled rather than hidden, because a control
             that comes and goes with the stock is a page that moves under you. --}}
        <label @class([
                   'inline-flex min-h-11 shrink-0 items-center gap-2.5 text-[15px]',
                   'cursor-pointer text-foreground' => $this->anyForSale,
                   'cursor-not-allowed text-muted-foreground' => ! $this->anyForSale,
               ])
               @if (! $this->anyForSale) title="No birds are marked for sale just now." @endif>
            <input type="checkbox" wire:model.live="forSaleOnly" class="h-5 w-5 accent-primary"
                   @disabled(! $this->anyForSale)>
            Available birds only
        </label>

        <div class="flex items-center gap-4 text-[14px] text-muted-foreground lg:ml-auto">
            <span wire:loading.remove wire:target="search,bloodline,sex,class,forSaleOnly">
                <span class="datum font-semibold text-foreground">{{ number_format($total) }}</span> {{ \Illuminate\Support\Str::plural('bird', $total) }} found.
            </span>
            <span wire:loading wire:target="search,bloodline,sex,class,forSaleOnly">Searching&hellip;</span>

            @if ($this->hasActiveFilters())
                <button type="button" wire:click="clearFilters"
                        class="inline-flex min-h-11 items-center font-semibold text-primary underline decoration-primary/30 underline-offset-4 hover:decoration-primary">
                    Clear filters
                </button>
            @endif
        </div>

        @if (! $this->anyForSale)
            <p class="w-full text-[13px] text-muted-foreground">No birds are marked for sale just now.</p>
        @endif
    </div>

    @if ($this->birds->isEmpty())
        {{-- THREE EMPTY STATES, because they are three different facts and only
             one of them is the visitor's doing. The for-sale case was the one
             that was wrong: it used to say "Try a different bloodline", advice
             that could not work, about a search the visitor had not botched. --}}
        @php $onlyForSale = $this->forSaleOnly && ! $this->anyForSale; @endphp

        <div class="py-16">
            <h2 class="sb-h2">
                @if ($onlyForSale)
                    No birds are marked for sale just now
                @elseif ($this->hasActiveFilters())
                    No birds match your search
                @else
                    No birds are listed yet
                @endif
            </h2>
            <p class="mt-3 max-w-[56ch] text-[16px] text-muted-foreground">
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
                <button type="button" wire:click="clearFilters" class="btn-secondary mt-7">
                    {{ $onlyForSale ? 'Show every bird on the farm' : 'Clear filters' }}
                </button>
            @endif
        </div>
    @else
        <ul class="mt-10 grid grid-cols-2 gap-x-4 gap-y-10 md:grid-cols-3 lg:grid-cols-4 lg:gap-x-6 lg:gap-y-12">
            @foreach ($this->birds as $bird)
                <li>
                    <x-bird-card :bird="$bird" heading="h2" :details="true" :delay="($loop->index % 4) * 60" />
                </li>
            @endforeach
        </ul>

        <div class="mt-12">{{ $this->birds->links() }}</div>
    @endif
</div>
