@props([
    /** Text to the right of the box. Omit it when a table column names the box. */
    'label' => null,
    /** One line under the label. */
    'help' => null,
    /** "Some of these" - a dash, never a check, as in a select-all header. */
    'indeterminate' => false,
])

{{--
    THE CHECKBOX (EMS design system, Part B §5.5): a 16px box with a 4px
    radius from lg up, 20px on a phone - where the whole label row, not the
    box, is the 44px target.

    INDETERMINATE is a DOM property, not an attribute, so it is set from
    data-indeterminate by app.js on load and after every Livewire update; the
    server decides it and the browser only draws it. aria-checked="mixed"
    tells a screen reader the same thing.
--}}
@php
    $control = $attributes
        ->class('d-checkbox d-checkbox-primary size-5 shrink-0 rounded-[4px] border border-input shadow-xs outline-none transition-shadow checked:border-primary indeterminate:border-primary focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 disabled:cursor-not-allowed disabled:opacity-50 aria-invalid:border-destructive lg:size-4 dark:bg-input/30')
        ->merge(array_filter([
            'type' => 'checkbox',
            'data-indeterminate' => $indeterminate ? 'true' : null,
            'aria-checked' => $indeterminate ? 'mixed' : null,
        ]));
@endphp

@if ($label)
    <label class="flex min-h-11 cursor-pointer items-start gap-2 py-2.5 lg:min-h-0 lg:py-0">
        <input {{ $control }}>
        <span class="text-sm leading-tight text-foreground lg:text-xs">
            {{ $label }}
            @if ($help)
                <span class="mt-0.5 block text-[13px] text-muted-foreground lg:text-[11px]">{{ $help }}</span>
            @endif
        </span>
    </label>
@else
    <input {{ $control }}>
@endif
