@props([
    /** Whether any filter is currently narrowing the list. Drives the footer. */
    'active' => false,
    /** Livewire action that resets every filter, e.g. "clearFilters". */
    'clear' => null,
    /** One line stating what the current filters matched, e.g. "4 birds found." */
    'summary' => null,
    /** The search control. Flexes to fill; everything else shrinks to fit. */
    'search' => null,
])

{{--
    THE FILTER BAR.

    One row: the search field takes the space that is left, and every other
    control shrinks to its own width. That ordering is the whole layout, and it
    is why the bar no longer stretches a five-column grid across a 1440px
    monitor with a third of it empty.

    WHAT THIS REPLACED. Six screens each hand-wrote a grid of controls with a
    label stacked above every one. Three costs, all of them visible:

      - the stacked labels doubled the bar's height for words the control
        already said. A select reading "All bloodlines" does not need
        "Bloodline" written above it.
      - on a narrow screen the grid became a tall column, and on the catalogue
        a customer scrolled past four controls before seeing a single bird.
      - six copies drift. They already had: different column counts, different
        gaps, and two different ways of showing the active-filter count.

    The field name now sits INSIDE the control's border as a prefix - see
    x-filter-select - so the label is still a real <label> with a `for`, still
    read by a screen reader, and no longer costs a line of vertical space.

    THE FOOTER APPEARS ONLY WHEN A FILTER IS ACTIVE. A permanent "0 filters"
    row is chrome that never earns its space, and a Clear button that is
    usually disabled teaches people to ignore it.
--}}
<div {{ $attributes->merge(['class' => 'card mb-8 p-4 sm:mb-10 sm:p-5']) }}>
    {{-- flex-wrap, not a grid: the controls have genuinely different natural
         widths, and a grid forces them into columns the widest one dictates.
         Wrapping lets the row break where it actually runs out of room. --}}
    <div class="flex flex-wrap items-center gap-3">
        @if ($search)
            {{-- min-w-0 is load-bearing. Without it a flex child refuses to
                 shrink below its content width and the row overflows instead
                 of wrapping. --}}
            <div class="min-w-0 flex-1 basis-64">
                {{ $search }}
            </div>
        @endif

        {{ $slot }}
    </div>

    @if ($active)
        <div class="mt-4 flex flex-wrap items-center justify-between gap-3 border-t border-border pt-4">
            @if ($summary)
                <p class="text-[14px] text-muted-foreground">{{ $summary }}</p>
            @else
                <span></span>
            @endif

            @if ($clear)
                <button type="button" wire:click="{{ $clear }}" class="btn-secondary">
                    Clear filters
                </button>
            @endif
        </div>
    @endif
</div>
