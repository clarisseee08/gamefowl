@props([
    /** Whether any filter is currently narrowing the list. Drives the footer. */
    'active' => false,
    /** Livewire action that resets every filter, e.g. "clearFilters". */
    'clear' => null,
    /** One line stating what the current filters matched, e.g. "4 birds found." */
    'summary' => null,
    /** The search control. Flexes to fill; everything else shrinks to fit. */
    'search' => null,
    /** An in-flight indicator, e.g. a wire:loading "Searching…". Optional. */
    'loading' => null,
])

@php
    /*
     * EVERY NUMBER IN THIS APPLICATION IS MONOSPACED, and the summary count is
     * a number. `:summary` takes a plain string, so "Showing 4 birds matching
     * your filters." shipped with proportional digits - which on a filter bar
     * is the one place a figure visibly jitters, because it changes as you
     * type.
     *
     * Escaping first and wrapping digit runs afterwards is what makes the
     * unescaped echo below safe: the only markup in the result is the span
     * this line puts there. Doing it here rather than at six call sites also
     * means no screen's copy changed by a single character - the assertions
     * that target these strings still match.
     */
    $summaryHtml = $summary === null
        ? null
        : preg_replace('/\d[\d,.]*/', '<span class="datum">$0</span>', e($summary));
@endphp

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

        {{-- An in-flight indicator belongs beside the controls that caused it,
             not in the footer: the footer only exists once a filter is active,
             so a loading state living there is invisible on the first search. --}}
        @if ($loading)
            <div class="shrink-0 text-[14px] text-muted-foreground">{{ $loading }}</div>
        @endif
    </div>

    @if ($active)
        <div class="mt-4 flex flex-wrap items-center justify-between gap-3 border-t border-border pt-4">
            @if ($summaryHtml)
                {{-- Unescaped deliberately. The string was escaped in the @php
                     block above and the only markup added afterwards is the
                     .datum span, so there is no path from caller input to
                     rendered HTML here. --}}
                <p class="text-[14px] text-muted-foreground">{!! $summaryHtml !!}</p>
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
