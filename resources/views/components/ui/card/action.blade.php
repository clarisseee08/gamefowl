{{-- One control at the header's top right - a menu or a small button. --}}
<div data-slot="card-action" {{ $attributes->class('col-start-2 row-span-2 row-start-1 self-start justify-self-end') }}>
    {{ $slot }}
</div>
