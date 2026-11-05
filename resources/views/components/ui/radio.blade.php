@props([
    'label' => null,
    'help' => null,
])

{{--
    A RADIO (EMS design system, Part B §5.6): 16px from lg up, 20px on a
    phone, a primary ring with a filled dot when chosen. Radios that share a
    `name` already move and select with the arrow keys; wrap a group in
    role="radiogroup" with an aria-labelledby, and they read as one control.
--}}
@php
    $control = $attributes
        ->class('d-radio d-radio-primary size-5 shrink-0 border border-primary focus:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:ring-offset-background disabled:cursor-not-allowed disabled:opacity-50 lg:size-4')
        ->merge(['type' => 'radio']);
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
