@props([
    /** center (default) · start · end */
    'align' => 'center',
    /** The panel's accessible name, when it has no heading of its own. */
    'label' => null,
    /** Drops the padding and the fixed width - for a picker that brings its own. */
    'flush' => false,
    /** icon · button (the default, an outline app button) · bare */
    'triggerVariant' => 'button',
])

{{--
    THE POPOVER (EMS, Part B §5.17): a small panel of its own content - a
    filter picker, a short explanation - anchored to the control that opened
    it. Focus moves into it on open; Escape or a click outside closes it and
    returns focus to the trigger. A popover holds content; for a list of
    actions use <x-ui.dropdown-menu>.
--}}
@php
    $placement = ['start' => 'bottom-start', 'end' => 'bottom-end'][$align] ?? 'bottom';

    $triggerClasses = [
        'icon' => "inline-flex size-11 shrink-0 items-center justify-center rounded-md text-muted-foreground outline-none transition-colors hover:bg-muted hover:text-foreground focus-visible:ring-[3px] focus-visible:ring-ring/50 lg:size-8 [&_svg:not([class*='size-'])]:size-4",
        'button' => "inline-flex h-11 shrink-0 items-center gap-2 rounded-lg border border-border bg-background px-4 text-sm font-medium text-foreground shadow-sm outline-none transition-colors hover:bg-muted focus-visible:ring-[3px] focus-visible:ring-ring/50 lg:h-7 lg:px-3 lg:text-xs [&_svg:not([class*='size-'])]:size-4 lg:[&_svg:not([class*='size-'])]:size-3.5",
        'bare' => 'rounded-md outline-none focus-visible:ring-[3px] focus-visible:ring-ring/50',
    ][$triggerVariant] ?? '';
@endphp

<div x-data="emsPopover" x-on:click.outside="close(false)" x-on:keydown.escape.prevent.stop="close()"
     {{ $attributes->class('relative inline-flex') }}>
    <button type="button" x-ref="trigger"
            aria-haspopup="dialog" x-bind:aria-expanded="open ? 'true' : 'false'"
            x-on:click="toggle()"
            {{ $trigger->attributes->class($triggerClasses) }}>
        {{ $trigger }}
    </button>

    <div x-ref="panel" x-show="open" x-cloak tabindex="-1"
         x-anchor.fixed.{{ $placement }}.offset.4="$refs.trigger"
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="scale-95 opacity-0"
         x-transition:enter-end="scale-100 opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="scale-100 opacity-100"
         x-transition:leave-end="scale-95 opacity-0"
         role="dialog" @if ($label) aria-label="{{ $label }}" @endif
         @class([
             'z-50 rounded-md border border-border bg-popover text-popover-foreground shadow-md outline-none',
             'w-auto overflow-hidden p-0' => $flush,
             'w-72 p-4' => ! $flush,
         ])>
        {{ $slot }}
    </div>
</div>
