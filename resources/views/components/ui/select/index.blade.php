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
    THE SELECT (EMS design system, Part B §5.5): a field-shaped trigger that
    opens the EMS dropdown panel - never the operating system's list, which
    no stylesheet can reach.

    The hidden native select underneath holds the value and the wire:model
    binding, so it binds like any select; see emsSelect in app.js. The
    trigger takes the id, so the label above points at the control a person
    can actually reach. Searchable lists use <x-ui.combobox> instead.
--}}
@php
    $model = $attributes->wire('model')->value();
    $id ??= $model ?: $attributes->get('name');
    $errorKey = $error ?? ($model ?: $id);
    $message = $errorKey && isset($errors) ? $errors->first($errorKey) : null;
    $describedBy = trim(($help && ! $message ? $id.'-help ' : '').($message ? $id.'-error' : '')) ?: null;
@endphp

<x-ui.field :id="$id" :label="$label" :label-hidden="$labelHidden" :required="$required"
            :help="$help" :error="$errorKey" :class="$wrapperClass">
    <div x-data="emsSelect" x-on:click.outside="close(false)" class="relative w-full">
        <select x-ref="native" x-on:change="sync()" id="{{ $id }}-native" aria-hidden="true" tabindex="-1"
                {{ $attributes->except('class')->class('sr-only pointer-events-none') }}>
            @if ($placeholder !== null)
                <option value="">{{ $placeholder }}</option>
            @endif
            @foreach ($options as $value => $optionLabel)
                <option value="{{ $value }}">{{ $optionLabel }}</option>
            @endforeach
            {{ $slot }}
        </select>

        <button type="button" x-ref="trigger" id="{{ $id }}"
                role="combobox" aria-haspopup="listbox" aria-controls="{{ $id }}-listbox"
                x-bind:aria-expanded="open ? 'true' : 'false'"
                x-bind:aria-activedescendant="open && active > -1 ? '{{ $id }}-opt-' + active : null"
                @if ($message) aria-invalid="true" @endif
                @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif
                @if ($required) aria-required="true" @endif
                x-on:click="toggle()"
                x-on:keydown="keydown($event)"
                @class([
                    'flex h-11 w-full cursor-pointer items-center justify-between gap-2 rounded-md border bg-card pr-3 pl-3 text-left text-base font-normal text-foreground shadow-xs outline-none transition-[color,box-shadow] focus-visible:border-ring focus-visible:ring-1 focus-visible:ring-primary/30 disabled:cursor-not-allowed disabled:opacity-50 aria-expanded:border-ring lg:h-7 lg:pr-2 lg:pl-2 lg:text-xs dark:bg-muted',
                    $message ? 'border-destructive focus-visible:ring-destructive' : 'border-input',
                    trim((string) $attributes->get('class', '')),
                ])>
            <span class="truncate" x-bind:class="empty ? 'text-muted-foreground' : ''" x-text="label"></span>
            <x-ui.icon name="chevron-down" class="size-4 shrink-0 opacity-50 transition-transform duration-200 lg:size-3.5"
                       x-bind:class="open ? 'rotate-180' : ''" />
        </button>

        <x-ui.select.listbox :id="$id" />
    </div>
</x-ui.field>
