@props([
    /** How many columns the table has, so the skeleton lines up with the head. */
    'cols' => 5,
    /** How many placeholder rows to draw. */
    'rows' => 5,
])

{{--
    THE SHAPE OF THE ANSWER, while the answer is being fetched.

    WHY THIS IS WORTH HAVING HERE SPECIFICALLY. Changing a filter is a Livewire
    round trip, and production runs Supabase in Tokyo against Render in
    Singapore - roughly 240ms each way before the query itself. Livewire morphs
    the table only when the response lands, so for half a second or more the old
    rows sat there looking like the new answer. A keeper who has just narrowed to
    "Kelso" reads the Hatch birds still on screen as the result.

    So this replaces the body rather than overlaying it: the wrong answer leaves
    immediately and something honestly unreadable takes its place.

    THE BAR WIDTHS VARY, and that is the whole trick. A grid of identical bars
    reads as a loading GRAPHIC; ragged line lengths read as text that has not
    arrived. The widths are deterministic per cell rather than random, so the
    skeleton does not reshuffle on every keystroke - which is its own kind of
    flicker.

    .skeleton itself was already in app.css and used by nothing but the design
    gallery. Its own comment says "skeletons match real layout, so the page does
    not reflow when data lands", which is exactly the promise this keeps by
    taking the column count from the caller.

    NOTHING IS ANNOUNCED FROM HERE. The obvious move is an sr-only "Loading" in
    a live region, but a <caption> is only valid as a table's FIRST child and
    this renders after the head - and a stray <div> inside a <table> is dropped
    by the parser. The filter bar already carries a polite "Searching…" for the
    same request, which is the right place for it: one announcement, not two.
--}}
@php
    // Five widths, cycled. Nothing lands on the same width as its neighbour, and
    // the first column is widest because in every table here it carries the
    // name or the band.
    $widths = ['w-3/4', 'w-1/2', 'w-2/3', 'w-2/5', 'w-3/5'];
@endphp

<tbody {{ $attributes }} aria-hidden="true">
    @for ($row = 0; $row < $rows; $row++)
        <tr class="border-t border-border">
            @for ($col = 0; $col < $cols; $col++)
                <td class="px-4 py-4">
                    <div class="skeleton h-3 {{ $widths[($row + $col) % count($widths)] }}"></div>
                </td>
            @endfor
        </tr>
    @endfor
</tbody>
