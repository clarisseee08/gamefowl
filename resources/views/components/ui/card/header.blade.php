{{-- The card's title block. An <x-ui.card.action> inside it sits top-right. --}}
<div {{ $attributes->class('grid auto-rows-min grid-rows-[auto_auto] items-start gap-1.5 px-6 has-[[data-slot=card-action]]:grid-cols-[1fr_auto]') }}>
    {{ $slot }}
</div>
