@props([
    'id' => null,
    'label' => null,
    'labelHidden' => false,
    'required' => false,
    'help' => null,
    'error' => null,
    /** sm is the app textarea and the default; md and lg are taller. */
    'size' => 'sm',
    'wrapperClass' => null,
])

{{--
    THE TEXTAREA (EMS design system, Part B §5.3). The input's twin: same
    border, focus ring, error handling and two-step sizing. It grows with its
    content where the browser supports field-sizing, and holds its minimum
    height everywhere else.
--}}
@php
    $model = $attributes->wire('model')->value();
    $id ??= $model ?: $attributes->get('name');
    $errorKey = $error ?? ($model ?: $id);
    $message = $errorKey && isset($errors) ? $errors->first($errorKey) : null;
    $describedBy = trim(($help && ! $message ? $id.'-help ' : '').($message ? $id.'-error' : '')) ?: null;

    $sizes = [
        'sm' => 'min-h-24 px-3 py-2.5 text-base lg:min-h-16 lg:px-2 lg:py-2 lg:text-xs',
        'md' => 'min-h-24 px-3 py-2.5 text-base lg:text-sm',
        'lg' => 'min-h-32 px-4 py-3 text-base',
    ];

    $control = $attributes
        ->class([
            'd-textarea field-sizing-content w-full rounded-md border bg-card font-normal text-foreground shadow-xs outline-none transition-[color,box-shadow] placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-1 focus-visible:ring-primary/30 disabled:cursor-not-allowed disabled:opacity-50 dark:bg-muted',
            $sizes[$size] ?? $sizes['sm'],
            $message ? 'border-destructive focus-visible:ring-destructive' : 'border-input',
        ])
        ->merge(array_filter([
            'id' => $id,
            'aria-invalid' => $message ? 'true' : null,
            'aria-describedby' => $describedBy,
            'aria-required' => $required ? 'true' : null,
        ]));
@endphp

<x-ui.field :id="$id" :label="$label" :label-hidden="$labelHidden" :required="$required"
            :help="$help" :error="$errorKey" :class="$wrapperClass">
    <textarea {{ $control }}>{{ $slot }}</textarea>
</x-ui.field>
