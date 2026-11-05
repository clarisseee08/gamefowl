@props([
    /** The select's id: the list and its options take it as their prefix. */
    'id',
])

{{--
    THE OPEN LIST OF AN EMS SELECT - the dropdown-menu panel, holding a
    listbox. Shared by <x-ui.select>, <x-form-select> and <x-filter-select>,
    and driven by the emsSelect Alpine component in app.js, whose scope it
    renders inside.

    Fixed-positioned against the trigger and flipped when there is no room
    below, so a list inside a scrolling dialog or table is never clipped. At
    least as wide as the control; the tick marks the chosen option.
--}}
<div x-show="open" x-cloak
     x-anchor.fixed.bottom-start.offset.4="anchor()"
     x-bind:style="{ minWidth: width + 'px' }"
     x-transition:enter="transition ease-out duration-150"
     x-transition:enter-start="scale-95 opacity-0"
     x-transition:enter-end="scale-100 opacity-100"
     x-transition:leave="transition ease-in duration-100"
     x-transition:leave-start="scale-100 opacity-100"
     x-transition:leave-end="scale-95 opacity-0"
     class="z-50 max-h-[min(18rem,60vh)] max-w-[calc(100vw-2rem)] min-w-[8rem] overflow-x-hidden overflow-y-auto rounded-md border border-border bg-popover p-1 text-popover-foreground shadow-md">
    <ul x-ref="list" id="{{ $id }}-listbox" role="listbox" {{ $attributes }}>
        <template x-for="option in options" x-bind:key="option.index">
            <li x-bind:id="'{{ $id }}-opt-' + option.index"
                x-bind:data-index="option.index"
                role="option"
                x-bind:aria-selected="option.index === selected ? 'true' : 'false'"
                x-on:mousedown.prevent
                x-on:click="choose(option.index)"
                x-on:mousemove="active = option.index"
                x-bind:class="option.index === active ? 'bg-muted text-foreground' : 'text-popover-foreground'"
                class="relative flex min-h-11 w-full cursor-default select-none items-center rounded-sm py-2 pr-8 pl-2 text-sm leading-snug lg:min-h-0 lg:py-1.5 lg:text-xs">
                <span x-text="option.label"></span>
                <span x-show="option.index === selected" class="absolute right-2 flex size-3.5 items-center justify-center">
                    <x-ui.icon name="check" class="size-4 lg:size-3.5" />
                </span>
            </li>
        </template>
    </ul>
</div>
