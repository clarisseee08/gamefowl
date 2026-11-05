@props([
    /** The control's id. The label points at it; help and error take it as their prefix. */
    'id',
    /** The field name, above the control. Omit it for a control named by its context. */
    'label' => null,
    /** Keeps the label for screen readers only. */
    'labelHidden' => false,
    /** The red asterisk. The control itself carries aria-required. */
    'required' => false,
    /** One line that adds a constraint or a consequence - never the label again. */
    'help' => null,
    /** The validation key, when it is not the id. */
    'error' => null,
])

{{--
    A FORM FIELD (EMS design system, Part B §5.9): label above the control,
    help and error directly under it.

    The help line hides while an error shows, as in EMS - one message under a
    field at a time. Ids follow the convention every form here already uses,
    {id}-help and {id}-error, so a control's aria-describedby can name them.

    The input, textarea and select wrappers render through this. Use it
    directly only around a control that has no wrapper of its own.
--}}
@php
    $errorKey = $error ?? $id;
    $message = isset($errors) ? $errors->first($errorKey) : null;
@endphp

<div {{ $attributes->class('flex w-full min-w-0 flex-col gap-1.5 lg:gap-1') }}>
    @if ($label)
        <label for="{{ $id }}" @class([
            'flex items-center gap-1 text-sm font-semibold leading-none lg:text-[11px]',
            $message ? 'text-destructive' : 'text-muted-foreground',
            'sr-only' => $labelHidden,
        ])>
            {{ $label }}
            @if ($required)
                <span class="text-destructive" aria-hidden="true">*</span>
            @endif
        </label>
    @endif

    {{ $slot }}

    @if ($help && ! $message)
        <p id="{{ $id }}-help" class="text-[13px] leading-snug text-muted-foreground lg:text-[11px]">{{ $help }}</p>
    @endif

    @if ($message)
        <p id="{{ $id }}-error" class="text-[13px] font-medium leading-snug text-destructive lg:text-[11px]" role="alert">{{ $message }}</p>
    @endif
</div>
