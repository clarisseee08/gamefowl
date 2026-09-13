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

    The hover surface is bg-muted. shadcn uses `accent`, which in that system is
    a neutral - here --color-accent is a strong orange, and colour in this
    application means bloodline.
--}}
@php
    $classes = 'flex min-h-11 w-full items-center gap-2.5 rounded-[var(--radius-sm)] px-2.5 text-left text-[15px] '
        .($danger ? 'text-destructive' : 'text-foreground')
        .' hover:bg-muted focus-visible:bg-muted focus:outline-none';
@endphp

@if ($href)
    <a href="{{ $href }}" role="menuitem" {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</a>
@else
    <button type="button" role="menuitem" {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</button>
@endif
