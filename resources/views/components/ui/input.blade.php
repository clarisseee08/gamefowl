@props([
    /** Defaults to the wire:model name, then to `name`. */
    'id' => null,
    'label' => null,
    'labelHidden' => false,
    'required' => false,
    'help' => null,
    /** The validation key, when it is not the wire:model name. */
    'error' => null,
    /** A Lucide name drawn inside the left edge - search fields take `search`. */
    'icon' => null,
    /** A Lucide name drawn inside the right edge. */
    'suffixIcon' => null,
    /** sm is the app field and the default; md and lg are the roomier forms. */
    'size' => 'sm',
    'type' => 'text',
    /** Classes for the field's outer wrapper - width in a toolbar, say. */
    'wrapperClass' => null,
])

{{--
    THE INPUT (EMS design system, Part B §5.2) - EMS's app field: 28px with
    12px text from lg up, and 44px with 16px text below it, where a smaller
    font makes iOS zoom the page on focus.

    Every attribute you pass lands on the <input> itself, wire:model included.
    The error comes from the validation bag under the wire:model name, so a
    form needs no @error block of its own; the control is marked aria-invalid
    and described by the message. aria-required, not the HTML attribute: the
    server validates, and a browser bubble would pre-empt its message.
--}}
@php
    $model = $attributes->wire('model')->value();
    $id ??= $model ?: $attributes->get('name');
    $errorKey = $error ?? ($model ?: $id);
    $message = $errorKey && isset($errors) ? $errors->first($errorKey) : null;
    $describedBy = trim(($help && ! $message ? $id.'-help ' : '').($message ? $id.'-error' : '')) ?: null;

    $sizes = [
        'sm' => 'h-11 px-3 text-base lg:h-7 lg:px-2 lg:text-xs',
        'md' => 'h-11 px-3 text-base lg:h-10',
        'lg' => 'h-12 px-4 text-lg',
    ];

    $control = $attributes
        ->class([
            'd-input w-full min-w-0 rounded-md border bg-card font-normal text-foreground shadow-xs outline-none transition-[color,box-shadow] placeholder:text-muted-foreground selection:bg-primary selection:text-primary-foreground focus-visible:border-ring focus-visible:ring-1 focus-visible:ring-primary/30 disabled:pointer-events-none disabled:cursor-not-allowed disabled:opacity-50 dark:bg-muted',
            $sizes[$size] ?? $sizes['sm'],
            $message ? 'border-destructive focus-visible:ring-destructive' : 'border-input',
            $icon ? 'pl-10 lg:pl-8' : '',
            $suffixIcon ? 'pr-10 lg:pr-8' : '',
        ])
        ->merge(array_filter([
            'id' => $id,
            'type' => $type,
            'aria-invalid' => $message ? 'true' : null,
            'aria-describedby' => $describedBy,
            'aria-required' => $required ? 'true' : null,
        ]));
@endphp

<x-ui.field :id="$id" :label="$label" :label-hidden="$labelHidden" :required="$required"
            :help="$help" :error="$errorKey" :class="$wrapperClass">
    <div class="relative flex w-full items-center">
        @if ($icon)
            <x-ui.icon :name="$icon" class="pointer-events-none absolute left-3 z-10 size-4 text-muted-foreground lg:left-2 lg:size-3.5" />
        @endif

        <input {{ $control }}>

        @if ($suffixIcon)
            <x-ui.icon :name="$suffixIcon" class="pointer-events-none absolute right-3 z-10 size-4 text-muted-foreground lg:right-2 lg:size-3.5" />
        @endif
    </div>
</x-ui.field>
