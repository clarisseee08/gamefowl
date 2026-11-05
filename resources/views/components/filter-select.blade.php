@props([
    /** The control's id. Required. */
    'id',
    /** The field name, rendered inside the control's border as a prefix. */
    'label',
    /** For a crowded filter row: from lg to 102rem of screen the field name steps aside. */
    'compact' => false,
])

{{--
    A FILTER SELECT: the EMS select with its field name inside the border, so
    a filter bar is one control high rather than two.

    The open list is the EMS dropdown panel (<x-ui.select.listbox>) and the
    behaviour is emsSelect in app.js, shared with <x-ui.select> and
    <x-form-select>. The hidden native select keeps the id, the wire:model
    binding and the <option> children, so call sites pass plain options.
--}}
<div x-data="emsSelect" x-on:click.outside="close(false)" class="relative w-full sm:w-auto sm:shrink-0">
    <select x-ref="native" x-on:change="sync()" id="{{ $id }}" aria-hidden="true" tabindex="-1"
            {{ $attributes->merge(['class' => 'sr-only pointer-events-none']) }}>
        {{ $slot }}
    </select>

    {{-- d-input with a d-label prefix: daisyUI's own shape for a field with a
         leading label, corrected to EMS sizes in app.css. --}}
    <div x-ref="anchor" class="d-input w-full cursor-pointer items-center gap-0 sm:w-auto"
         x-bind:class="open ? 'border-ring' : ''"
         x-bind:title="@js($label.': ') + label">
        {{-- Compact: on a crowded row the field name steps aside when the
             screen is short of room. The value alone carries the control; the
             name stays for screen readers and in the tooltip. --}}
        <span id="{{ $id }}-label"
              @class([
                  'd-label inline-flex shrink-0 items-center self-center text-[13px] leading-none font-medium lg:text-[11px]',
                  'lg:max-[102rem]:sr-only' => $compact,
              ])>
            {{ $label }}
        </span>

        <button type="button" x-ref="trigger"
                role="combobox" aria-haspopup="listbox" aria-controls="{{ $id }}-listbox"
                aria-labelledby="{{ $id }}-label {{ $id }}-value"
                x-bind:aria-expanded="open ? 'true' : 'false'"
                x-bind:aria-activedescendant="open && active > -1 ? '{{ $id }}-opt-' + active : null"
                x-on:click="toggle()"
                x-on:keydown="keydown($event)"
                class="flex h-full w-full min-w-0 cursor-pointer items-center justify-between gap-2 text-left text-[15px] text-foreground outline-none lg:text-xs">
            <span id="{{ $id }}-value" class="truncate" x-text="label"></span>
            <x-ui.icon name="chevron-down" class="size-4 shrink-0 opacity-50 transition-transform duration-200 lg:size-3.5"
                       x-bind:class="open ? 'rotate-180' : ''" />
        </button>
    </div>

    <x-ui.select.listbox :id="$id" aria-labelledby="{{ $id }}-label" />
</div>
