@props([
    /** How many columns to draw. */
    'cols' => 5,
    /** How many placeholder rows. */
    'rows' => 6,
    /** Draw it inside a card, as a list that lives in one would be. */
    'card' => true,
])

{{--
    A LIST THAT HAS NOT ARRIVED: a head row and some body rows, drawn with
    divs so it can stand in where there is no <table> yet - a tab switching
    to a kind of record with a different set of columns, or a whole page that
    is still on its way. Inside an existing table, <x-table-skeleton> is the
    one to use.

    Ragged widths, cycled rather than random, for the same reason as
    x-table-skeleton: a grid of equal bars reads as a loading graphic, ragged
    ones as text that is not there yet, and fixed widths do not reshuffle.
--}}
@php
    $widths = ['w-3/4', 'w-1/2', 'w-2/3', 'w-2/5', 'w-3/5'];
@endphp
<div {{ $attributes->class([$card ? 'overflow-hidden rounded-md border border-border bg-card' : '']) }} aria-hidden="true">
    <div class="flex gap-4 border-b border-border px-4 py-3">
        @for ($c = 0; $c < $cols; $c++)
            <div @class(['flex-1', 'hidden sm:block' => $c >= 3])>
                <x-ui.skeleton class="h-2.5 w-16" />
            </div>
        @endfor
    </div>
    @for ($r = 0; $r < $rows; $r++)
        <div class="flex items-center gap-4 border-b border-border px-4 py-3.5 last:border-b-0">
            @for ($c = 0; $c < $cols; $c++)
                <div @class(['flex-1', 'hidden sm:block' => $c >= 3])>
                    <x-ui.skeleton :class="'h-3 '.$widths[($r + $c * 2) % count($widths)]" />
                </div>
            @endfor
        </div>
    @endfor
</div>
