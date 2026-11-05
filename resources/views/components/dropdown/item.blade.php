@props([
    /** Render as a link when given, otherwise a button. */
    'href' => null,
    /** Destructive rows read in the destructive colour, never as a red block. */
    'danger' => false,
])

{{--
    One row of a dropdown menu.

    min-h-11 and 15px, not shadcn's 12px: the console floor is a 44px touch
    target because this is used outdoors on a phone.

    The hover surface is d-menu's neutral tint. shadcn uses `accent`, which in
    that system is a neutral - here --color-accent is a brand green, and colour
    in this application means bloodline.

    The <li role="none"> is d-menu's row; the link or button inside it is the
    menuitem; role="none" keeps the list item from being announced between them.
--}}
@php
    $classes = 'flex min-h-11 w-full items-center gap-2.5 px-2.5 text-left text-[15px] '
        .($danger ? 'text-destructive' : '');
@endphp

<li role="none">
    @if ($href)
        <a href="{{ $href }}" role="menuitem" {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</a>
    @else
        <button type="button" role="menuitem" {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</button>
    @endif
</li>
