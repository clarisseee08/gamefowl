@props([
    /** How many filters are set, shown on the button so a closed panel still says so. */
    'count' => 0,
])

{{--
    "FILTERS" ON A PHONE. A list's dropdown filters stacked one per row push
    the list itself below the first screen, so below md they fold into a panel
    behind this button, beside the search. It toggles `filtersOpen` in the
    page's Alpine scope; the panel is the element the page gives
    `x-bind:class="filtersOpen ? 'grid' : 'hidden'"` and `md:contents`, so
    from md up the filters sit inline in the row exactly as before and the
    button is gone.
--}}
<button type="button"
        x-on:click="filtersOpen = ! filtersOpen"
        x-bind:aria-expanded="filtersOpen.toString()"
        {{ $attributes->class('d-btn d-btn-outline shrink-0 md:hidden') }}>
    <x-ui.icon name="sliders-horizontal" />
    Filters
    @if ($count > 0)
        <span class="datum inline-flex h-[18px] min-w-[18px] items-center justify-center rounded-full bg-primary px-1 text-[11px] leading-none font-semibold text-primary-foreground">{{ $count }}</span>
    @endif
</button>
