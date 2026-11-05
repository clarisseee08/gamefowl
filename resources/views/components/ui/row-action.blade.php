@props([
    /** A Lucide name: eye (view), pencil (edit), trash-2 (delete). */
    'icon',
    /** What it does, for the tooltip and screen readers: "Edit Tandang". */
    'label',
    /** A link action. Add wire:navigate for a console page. */
    'href' => null,
    /** The red delete action. */
    'destructive' => false,
])

{{--
    ONE ROW ACTION - an icon button in a table's Actions column, the owner's
    choice over a menu: every action visible, one click away. 32px from lg
    up, 44px on a phone. The label is the tooltip and the accessible name,
    so an icon never stands without words.
--}}
@php
    $classes = [
        'inline-flex size-11 items-center justify-center rounded-md outline-none transition-colors focus-visible:ring-[3px] focus-visible:ring-ring/50 lg:size-8 [&_svg]:size-4',
        $destructive ? 'text-destructive hover:bg-destructive/10' : 'text-muted-foreground hover:bg-muted hover:text-foreground',
    ];
@endphp

<x-ui.tooltip :text="$label">
    @if ($href)
        <a href="{{ $href }}" aria-label="{{ $label }}" {{ $attributes->class($classes) }}>
            <x-ui.icon :name="$icon" />
        </a>
    @else
        <button type="button" aria-label="{{ $label }}" {{ $attributes->class($classes) }}>
            <x-ui.icon :name="$icon" />
        </button>
    @endif
</x-ui.tooltip>
