@props([
    /** start (default), or end - row actions line up with the trigger's end. */
    'align' => 'start',
    /** The menu's accessible name: "Actions for Tandang". */
    'label' => null,
    /** icon (the row-actions kebab, the default) · button (an outline app button) · bare (style it yourself) */
    'triggerVariant' => 'icon',
])

{{--
    THE DROPDOWN MENU (EMS, Part B §5.16): row actions, the account menu,
    overflow menus.

    Put the trigger's content in <x-slot:trigger> - an icon, or a label - and
    give an icon-only trigger an aria-label there. The items go in the body:
    <x-ui.dropdown-menu.item>, with .label and .separator for grouping. Row
    actions run View, then other navigation, then Edit, then the destructive
    item LAST, after a separator once the menu has three or more items.

    Behaviour is the emsMenu Alpine component in app.js - the keyboard model
    there is the whole point of using this instead of a <details>. The panel
    is fixed-positioned against the trigger and flips when there is no room
    below, so a menu inside a scrolling table is never clipped by it.
--}}
@php
    $placement = $align === 'end' ? 'bottom-end' : 'bottom-start';

    $triggerClasses = [
        'icon' => "inline-flex size-11 shrink-0 items-center justify-center rounded-md text-muted-foreground outline-none transition-colors hover:bg-muted hover:text-foreground focus-visible:ring-[3px] focus-visible:ring-ring/50 aria-expanded:bg-muted aria-expanded:text-foreground lg:size-8 [&_svg:not([class*='size-'])]:size-4",
        'button' => "inline-flex h-11 shrink-0 items-center gap-2 rounded-lg border border-border bg-background px-4 text-sm font-medium text-foreground shadow-sm outline-none transition-colors hover:bg-muted focus-visible:ring-[3px] focus-visible:ring-ring/50 lg:h-7 lg:px-3 lg:text-xs [&_svg:not([class*='size-'])]:size-4 lg:[&_svg:not([class*='size-'])]:size-3.5",
        'bare' => 'rounded-md outline-none focus-visible:ring-[3px] focus-visible:ring-ring/50',
    ][$triggerVariant] ?? '';
@endphp

<div x-data="emsMenu" x-on:click.outside="close(false)" {{ $attributes->class('relative inline-flex') }}>
    <button type="button" x-ref="trigger"
            aria-haspopup="menu" x-bind:aria-expanded="open ? 'true' : 'false'"
            x-on:click="toggle()"
            x-on:keydown.enter.prevent="show('first')"
            x-on:keydown.space.prevent="show('first')"
            x-on:keyup.space.prevent
            x-on:keydown.arrow-down.prevent="show('first')"
            x-on:keydown.arrow-up.prevent="show('last')"
            {{ $trigger->attributes->class($triggerClasses) }}>
        {{ $trigger }}
    </button>

    <div x-ref="panel" x-show="open" x-cloak
         x-anchor.fixed.{{ $placement }}.offset.4="$refs.trigger"
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="scale-95 opacity-0"
         x-transition:enter-end="scale-100 opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="scale-100 opacity-100"
         x-transition:leave-end="scale-95 opacity-0"
         role="menu" @if ($label) aria-label="{{ $label }}" @endif
         x-on:keydown.arrow-down.prevent="move(1)"
         x-on:keydown.arrow-up.prevent="move(-1)"
         x-on:keydown.home.prevent="edge('first')"
         x-on:keydown.end.prevent="edge('last')"
         x-on:keydown.escape.prevent.stop="close()"
         x-on:keydown.tab="close(false)"
         x-on:keydown="typeahead($event)"
         class="z-50 max-h-[min(24rem,70vh)] min-w-[8rem] overflow-x-hidden overflow-y-auto rounded-md border border-border bg-popover p-1 text-popover-foreground shadow-md">
        {{ $slot }}
    </div>
</div>
