@props([
    'orientation' => 'horizontal',
])

{{-- A hairline rule. Elevation in this system is a rule and a background step,
     never a shadow, so this is the workhorse divider. --}}
<div role="separator"
     aria-orientation="{{ $orientation }}"
     {{ $attributes->merge([
         'class' => $orientation === 'vertical'
             ? 'h-full w-0 shrink-0 border-l border-border'
             : 'h-0 w-full shrink-0 border-t border-border',
     ]) }}></div>
