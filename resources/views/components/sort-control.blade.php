@props([
    /** The sort key this header owns, e.g. 'band_number'. */
    'field',
    /** The column heading. */
    'label',
    /** The key the table is sorted by right now. */
    'current' => null,
    /** 'asc' | 'desc'. Only meaningful while $current === $field. */
    'direction' => 'asc',
    /** 'left' | 'right'. Numeric columns are right-aligned, so their headers are too. */
    'align' => 'left',
    /** Extra classes for the <th> itself — x-show for a hideable column, a width. */
    'cell' => '',
])

{{--
    A SORTABLE TABLE HEADER.

    THE COMPONENT IS THE <th>, not just the button inside it, and that is the
    whole reason it exists: aria-sort belongs on the header cell, and a
    component that rendered only the button could never set it. A screen reader
    in table mode reads aria-sort when it enters the column; without it the
    sort state is a glyph and nothing else, which is to say it is invisible.

    EMS's sort-control is a different shape — a select of fields plus a
    direction toggle, because its lists are cards rather than tables. The
    grammar carried across is the one that matters: field and direction are ONE
    control, a single press, never two things to reconcile.

    `uppercase` IS REPEATED ON THE BUTTON ON PURPOSE. Tailwind's preflight sets
    `button { text-transform: none }`, which beats an inherited `uppercase` on
    the <th>. This exact slip once left the one unsortable column as the only
    header rendering in caps.

    THE 44px TOUCH TARGET IS ALREADY THERE: app.css puts min-height 44px on
    every button unconditionally, so the header cell inherits the console floor
    without this file asking for it.

    THE CHEVRON ONLY APPEARS ON THE SORTED COLUMN. A permanent up/down glyph on
    every header adds six pieces of chrome to say nothing; the direction is only
    information where a direction exists. The sr-only line beside it is for
    browsing modes that never announce aria-sort.

    $attributes LAND ON THE BUTTON, because wire:click="sort('…')" is what a
    caller always has to attach. Anything the CELL needs — x-show for a
    hideable column, a width — goes through `cell`.
--}}
@php
    $isSorted = $current !== null && $current === $field;
    $isAscending = $direction === 'asc';

    $ariaSort = $isSorted ? ($isAscending ? 'ascending' : 'descending') : 'none';

    /* Heroicons chevron-up / chevron-down, the family every other glyph here
       uses. Up means A→Z and smallest first, matching the arrow a spreadsheet
       draws for the same state. */
    $chevron = $isAscending ? 'm4.5 15.75 7.5-7.5 7.5 7.5' : 'm19.5 8.25-7.5 7.5-7.5-7.5';
@endphp

<th scope="col"
    aria-sort="{{ $ariaSort }}"
    class="px-4 py-2.5 text-[11px] font-medium uppercase tracking-[0.06em] text-muted-foreground {{ $align === 'right' ? 'text-right' : 'text-left' }} {{ $cell }}">

    <button type="button"
            {{ $attributes->merge([
                'class' => 'inline-flex items-center gap-1 uppercase tracking-[0.06em] hover:text-foreground'
                    .($align === 'right' ? ' flex-row-reverse' : ''),
            ]) }}>
        {{ $label }}

        @if ($isSorted)
            <svg class="h-3.5 w-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.6"
                 viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="{{ $chevron }}"/>
            </svg>
            <span class="sr-only">sorted {{ $isAscending ? 'ascending' : 'descending' }}</span>
        @endif
    </button>
</th>
