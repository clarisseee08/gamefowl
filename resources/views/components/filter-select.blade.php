@props([
    /** The control's id, and the label's `for`. Required. */
    'id',
    /** The field name, rendered inside the control's border as a prefix. */
    'label',
])

{{--
    A select whose FIELD NAME SITS INSIDE ITS BORDER rather than above it.

    The label and the control share one bordered group, so the pair reads as a
    single object - "Bloodline | All bloodlines" - instead of two stacked
    elements. That is the whole reason the filter bar is now one row high
    rather than two.

    IT IS STILL A REAL LABEL. `for` points at the select, so a screen reader
    announces "Bloodline, combo box" exactly as it did when the label was
    stacked above. A placeholder or an aria-label would have looked the same
    and been worse.

    THE GROUP CARRIES THE BORDER, NOT THE SELECT. The select inside is
    borderless and transparent, because two borders one pixel apart is the
    usual way this pattern goes wrong. The group also owns the focus state, so
    focusing the select rings the whole thing rather than a control floating
    inside a box.

    44px on the group is the console's touch-target floor, and the select keeps
    16px text - anything smaller and iOS Safari zooms the viewport on focus.
--}}
{{-- Full width on a phone, natural width from `sm` up. A pill sized to its
     content looks deliberate in a row and looks like a mistake stacked in a
     column, with a third of the line empty beside it. --}}
<div class="flex min-h-11 w-full items-center rounded-[var(--radius-sm)] border border-input bg-card
            focus-within:border-primary sm:w-auto sm:shrink-0">
    <label for="{{ $id }}"
           class="shrink-0 whitespace-nowrap border-r border-input py-2.5 pl-3 pr-3 text-[13px] font-medium text-muted-foreground">
        {{ $label }}
    </label>

    {{-- appearance-none removes the platform chevron so the group can supply
         its own; without it the native arrow sits against the group's border
         and the control looks double-edged. --}}
    <select id="{{ $id }}"
            {{ $attributes->merge([
                'class' => 'min-h-11 w-full min-w-0 appearance-none rounded-r-[var(--radius-sm)] border-0 bg-transparent
                            py-2.5 pl-3 pr-8 text-[16px] text-foreground focus:outline-none',
            ]) }}>
        {{ $slot }}
    </select>

    <svg class="pointer-events-none -ml-6 h-4 w-4 shrink-0 text-muted-foreground" fill="none"
         stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/>
    </svg>

    <span class="w-2 shrink-0"></span>
</div>
