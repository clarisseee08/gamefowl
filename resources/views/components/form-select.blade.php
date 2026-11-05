@props([
    /** The control's id. The native select keeps it, byte for byte. */
    'id',
    /** The field name, rendered as a real <label> ABOVE the control. */
    'label',
    /** Appends the asterisk the forms already use. Adds no HTML `required`. */
    'required' => false,
    /** Leaves the label to screen readers, for a control the page names in view. */
    'labelHidden' => false,
    /** Guidance under the control. A string, or an <x-slot:help> for markup. */
    'help' => null,
    /** The validation key when it differs from the id - sire_choice / sire_id. */
    'error' => null,
])

@php
    /*
     * The error state is derived from the validation key here rather than
     * passed in: Blade compiles component tags before directives, so an
     * @error inside an attribute value would land inside a PHP array literal.
     */
    $errorField = $error ?? $id;
    $hasError = isset($errors) && $errors->has($errorField);
@endphp

{{--
    A FORM SELECT: the EMS select with the label above it and the help and
    error lines under it, the shape every form here uses.

    The open list is the EMS dropdown panel (<x-ui.select.listbox>) and the
    behaviour is emsSelect in app.js. The hidden native select keeps the id,
    the wire:model binding and the <option> children, so no call site changed.

    THE HTML `required` ATTRIBUTE IS DELIBERATELY NOT FORWARDED. On a control
    the browser cannot show, constraint validation blocks the submit with
    nothing on screen to say why. Server-side validation covers every one of
    these fields and renders through the @error line below.
--}}
<div x-data="emsSelect" x-on:click.outside="close(false)">
    {{-- justify-start: d-fieldset-legend spreads its children apart, which
         would push the asterisk to the far edge of the form. --}}
    <label for="{{ $id }}-trigger" id="{{ $id }}-label" @class(['d-fieldset-legend justify-start gap-1', 'sr-only' => $labelHidden])>
        {{ $label }}@if ($required) <span class="text-destructive">*</span>@endif
    </label>

    {{-- `relative` keeps the sr-only select, which is absolutely positioned,
         beside the control instead of laid out against the document. --}}
    <div class="relative mt-1">
        <select x-ref="native" x-on:change="sync()" id="{{ $id }}" aria-hidden="true" tabindex="-1"
                {{ $attributes->except('class')->merge(['class' => 'sr-only pointer-events-none']) }}>
            {{ $slot }}
        </select>

        <button type="button" x-ref="trigger" id="{{ $id }}-trigger"
                role="combobox" aria-haspopup="listbox" aria-controls="{{ $id }}-listbox"
                aria-labelledby="{{ $id }}-label {{ $id }}-value"
                x-bind:aria-expanded="open ? 'true' : 'false'"
                x-bind:aria-activedescendant="open && active > -1 ? '{{ $id }}-opt-' + active : null"
                @if ($hasError) aria-invalid="true" @endif
                x-on:click="toggle()"
                x-on:keydown="keydown($event)"
                @class([
                    'd-select w-full cursor-pointer justify-between gap-2 bg-none pe-3 text-left aria-expanded:border-ring lg:pe-2',
                    'd-select-error' => $hasError,
                    trim((string) $attributes->get('class', '')),
                ])>
            <span id="{{ $id }}-value" class="truncate" x-bind:class="empty ? 'text-muted-foreground' : ''" x-text="label"></span>
            <x-ui.icon name="chevron-down" class="size-4 shrink-0 opacity-50 transition-transform duration-200 lg:size-3.5"
                       x-bind:class="open ? 'rotate-180' : ''" />
        </button>

        <x-ui.select.listbox :id="$id" aria-labelledby="{{ $id }}-label" />
    </div>

    @if ($help) <p class="d-label mt-1">{{ $help }}</p> @endif
    @error($errorField) <p class="mt-1.5 text-[13px] font-medium text-error lg:text-[11px]">{{ $message }}</p> @enderror
</div>
