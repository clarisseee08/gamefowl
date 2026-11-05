@props([
    /** default · table (a toolbar and a table in one card: no padding, no gap) */
    'variant' => 'default',
    /** Makes the whole card one link. */
    'href' => null,
])

{{--
    THE CARD (EMS design system, Part B §5.10). Flat: a border and no shadow.
    Elevation belongs to overlays, and a card that lifts on its own says
    "click me" when it is not a button.

    Compose it from the parts in this folder - header, title, description,
    action, content, footer - never from daisyUI's card-body, whose padding
    and gap differ.

    The table variant holds a toolbar strip and a table edge to edge, with
    the one shadow a card may carry: it is the screen's working surface.
--}}
@php
    $classes = [
        'd-card flex flex-col rounded-md border border-border bg-card text-card-foreground',
        $variant === 'table' ? 'gap-0 overflow-hidden p-0 shadow-sm' : 'gap-6 py-6 shadow-none',
        $href ? 'cursor-pointer outline-none transition-colors hover:border-primary/40 hover:shadow-sm focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:ring-offset-background' : '',
    ];
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->class($classes) }}>{{ $slot }}</a>
@else
    <div {{ $attributes->class($classes) }}>{{ $slot }}</div>
@endif
