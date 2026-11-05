@props([
    /** A Lucide name drawn before the label. */
    'icon' => null,
    /** A link item. Add wire:navigate for a page inside the console. */
    'href' => null,
    /** The red item - Delete. Always the last item in its menu. */
    'destructive' => false,
    'disabled' => false,
    /** Lines an icon-less item up with the iconed ones. */
    'inset' => false,
    /** item · radio · checkbox. Radio and checkbox items take `checked`. */
    'type' => 'item',
    'checked' => false,
])

{{--
    ONE MENU ITEM. Focus is the highlight, so hovering focuses it and the
    arrow keys move it; choosing an item closes the menu and returns focus to
    the trigger. Everything else - wire:click, wire:navigate, x-on:click -
    passes straight through to the element.
--}}
@php
    $role = ['radio' => 'menuitemradio', 'checkbox' => 'menuitemcheckbox'][$type] ?? 'menuitem';
    $hasIndicator = $type !== 'item';

    $classes = [
        "relative flex min-h-11 w-full cursor-default select-none items-center gap-2 rounded-sm px-2 py-1.5 text-left text-sm outline-none lg:min-h-0 lg:text-xs [&_svg]:pointer-events-none [&_svg]:shrink-0 [&_svg:not([class*='size-'])]:size-4 lg:[&_svg:not([class*='size-'])]:size-3.5",
        $destructive ? 'text-destructive focus:bg-destructive/10 [&_svg]:text-destructive' : 'text-popover-foreground focus:bg-muted focus:text-foreground [&_svg]:text-muted-foreground',
        $disabled ? 'pointer-events-none opacity-50' : '',
        ($inset || $hasIndicator) ? 'pl-8' : '',
    ];

    /*
     * The item's own click (close the menu) and any click passed in run as
     * ONE handler. Written as two x-on:click attributes, the browser keeps
     * only the first - the menu closed and the passed action (Sign out)
     * silently never ran.
     */
    $onClick = trim('close(); '.(string) $attributes->get('x-on:click', ''));
    $attributes = $attributes->except('x-on:click');

    $attrs = $attributes->class($classes)->merge(array_filter([
        'role' => $role,
        'tabindex' => '-1',
        'aria-disabled' => $disabled ? 'true' : null,
        'aria-checked' => $hasIndicator ? ($checked ? 'true' : 'false') : null,
    ]));
@endphp

@if ($href && ! $disabled)
    <a href="{{ $href }}" x-on:mouseenter="$el.focus()" x-on:keydown.space.prevent="$el.click()" x-on:click="{{ $onClick }}" {{ $attrs }}>
@else
    <button type="button" x-on:mouseenter="$el.focus()" x-on:click="{{ $onClick }}" @disabled($disabled) {{ $attrs }}>
@endif
    @if ($hasIndicator && $checked)
        <span class="absolute left-2 flex size-3.5 items-center justify-center">
            <x-ui.icon name="check" class="size-3.5" />
        </span>
    @endif
    @if ($icon)
        <x-ui.icon :name="$icon" />
    @endif
    {{ $slot }}
@if ($href && ! $disabled)
    </a>
@else
    </button>
@endif
