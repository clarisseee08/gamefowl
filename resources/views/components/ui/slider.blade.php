@props([
    'min' => 0,
    'max' => 100,
    /** The starting value. With wire:model, the bound property wins. */
    'value' => null,
])

{{--
    THE SLIDER (EMS design system, Part B §5.8): a 6px track filled in the
    primary colour up to a 16px thumb. A native range input under app.css's
    .ems-range, so it keeps arrow-key, Home/End and screen-reader behaviour.
    The filled part follows the value through --value, which Alpine keeps in
    step as the thumb moves.
--}}
@php
    $start = $value ?? $min;
@endphp

<input type="range" min="{{ $min }}" max="{{ $max }}" @if ($value !== null) value="{{ $value }}" @endif
       x-data="{ sync() { $el.style.setProperty('--value', (($el.value - {{ $min }}) / ({{ $max }} - {{ $min }}) * 100) + '%') } }"
       x-init="sync()" x-on:input="sync()"
       style="--value: {{ ($max - $min) > 0 ? (($start - $min) / ($max - $min)) * 100 : 0 }}%"
       {{ $attributes->class('ems-range w-full') }}>
