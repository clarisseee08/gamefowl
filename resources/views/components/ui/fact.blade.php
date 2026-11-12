@props([
    /** What the value is. */
    'label',
])

{{-- One labelled value inside a record card's <dl>: the label small and muted above it. --}}
<div {{ $attributes->class('min-w-0') }}>
    <dt class="text-[11px] text-muted-foreground">{{ $label }}</dt>
    <dd class="mt-0.5 truncate text-[13px] font-medium text-foreground lg:text-xs">{{ $slot }}</dd>
</div>
