@props([
    /** Text to the right of the switch. */
    'label' => null,
    'help' => null,
])

{{--
    THE SWITCH (EMS design system, Part B §5.7): an 18.4 x 32px track whose
    thumb slides across when on. A native checkbox with role="switch" sits
    over the whole track, so it stays keyboard and screen-reader accessible
    with no script; the track and thumb beside it are decoration keyed off
    the checkbox's own state.

    A switch applies at once. If the change waits for a Save button, use a
    checkbox - a switch that needs saving afterwards misstates what happened.
--}}
@php
    $control = $attributes
        ->class('peer absolute inset-0 z-10 m-0 size-full cursor-pointer appearance-none rounded-full outline-none disabled:cursor-not-allowed')
        ->merge(['type' => 'checkbox', 'role' => 'switch']);
@endphp

<label @class(['flex min-h-11 items-center gap-2 lg:min-h-0', 'cursor-pointer' => $label])>
    <span class="relative inline-flex h-[1.15rem] w-8 shrink-0">
        <input {{ $control }}>
        <span aria-hidden="true"
              class="pointer-events-none inline-flex size-full items-center rounded-full border border-transparent bg-input shadow-xs transition-all peer-checked:bg-primary peer-focus-visible:border-ring peer-focus-visible:ring-[3px] peer-focus-visible:ring-ring/50 peer-disabled:opacity-50 dark:bg-input/80 [&>span]:translate-x-0 peer-checked:[&>span]:translate-x-[calc(100%-2px)] dark:peer-checked:[&>span]:bg-primary-foreground">
            <span class="block size-4 rounded-full bg-background ring-0 transition-transform dark:bg-foreground"></span>
        </span>
    </span>

    @if ($label)
        <span class="text-sm leading-tight text-foreground lg:text-xs">
            {{ $label }}
            @if ($help)
                <span class="mt-0.5 block text-[13px] text-muted-foreground lg:text-[11px]">{{ $help }}</span>
            @endif
        </span>
    @endif
</label>
