@props([
    /** The id prefix the tabs were given (their `id`, or "tabs-" + their model). */
    'for',
    /** The selected tab's key, so the panel is labelled by it. */
    'active',
])

{{-- The content under an <x-ui.tabs> strip, labelled by the selected tab. --}}
<div role="tabpanel" id="{{ $for }}-panel" aria-labelledby="{{ $for }}-{{ $active }}" tabindex="0"
     {{ $attributes->class('outline-none focus-visible:ring-2 focus-visible:ring-ring/50') }}>
    {{ $slot }}
</div>
