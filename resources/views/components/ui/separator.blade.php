@props([
    /** horizontal, or vertical between items in a row. */
    'orientation' => 'horizontal',
])

{{-- A RULE (EMS, Part B §5.24). Decorative: it separates, it does not announce. --}}
<div role="none" {{ $attributes->class($orientation === 'vertical' ? 'h-full w-0 shrink-0 border-l border-border' : 'h-0 w-full shrink-0 border-t border-border') }}></div>
