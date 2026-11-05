@props([
    'id' => null,
    'label' => null,
    'labelHidden' => false,
    'required' => false,
    'help' => null,
    'error' => null,
    /** value => label. Or pass <option> elements in the slot instead. */
    'options' => [],
    /** An empty first option, e.g. "All bloodlines". */
    'placeholder' => null,
    'wrapperClass' => null,
])

{{--
    THE NATIVE SELECT (EMS design system, Part B §5.4) - for short fixed
    lists: page size, a sort order, a type. The browser draws the open list,
    which is the point - it is fully keyboard and screen-reader accessible on
    every device, and on a phone it opens the system picker.

    Styled as the app field, with daisyUI's chevron removed and Lucide's
    chevron drawn in its place. Searchable or multi-value lists use
    <x-ui.combobox> instead.
--}}
@php
    $model = $attributes->wire('model')->value();
    $id ??= $model ?: $attributes->get('name');
    $errorKey = $error ?? ($model ?: $id);
    $message = $errorKey && isset($errors) ? $errors->first($errorKey) : null;
    $describedBy = trim(($help && ! $message ? $id.'-help ' : '').($message ? $id.'-error' : '')) ?: null;

    $control = $attributes
        ->class([
            'd-select h-11 w-full appearance-none rounded-md border bg-card bg-none pr-9 pl-3 text-base font-normal text-foreground shadow-xs outline-none focus-visible:border-ring focus-visible:ring-1 focus-visible:ring-primary/30 disabled:cursor-not-allowed disabled:opacity-50 lg:h-7 lg:pr-7 lg:pl-2 lg:text-xs dark:bg-muted',
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
    <div class="relative w-full">
        <select {{ $control }}>
            @if ($placeholder !== null)
                <option value="">{{ $placeholder }}</option>
            @endif
            @foreach ($options as $value => $optionLabel)
                <option value="{{ $value }}">{{ $optionLabel }}</option>
            @endforeach
            {{ $slot }}
        </select>
        <x-ui.icon name="chevron-down" class="pointer-events-none absolute top-1/2 right-3 size-4 -translate-y-1/2 opacity-50 lg:right-2 lg:size-3.5" />
    </div>
</x-ui.field>
