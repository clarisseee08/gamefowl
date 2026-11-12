@props([
    /** The trigger's id - what a <label for> above it points at. */
    'id',
    'placeholder' => 'Select date',
    /** Earliest date allowed: YYYY-MM-DD, or 'today'. */
    'min' => null,
    /** Latest date allowed: YYYY-MM-DD, or 'today'. */
    'max' => null,
    /** An Alpine expression in the page's scope for a moving minimum, e.g. 'from'. */
    'minFrom' => null,
    /** An Alpine expression in the page's scope for a moving maximum, e.g. 'to'. */
    'maxFrom' => null,
    /** Offer the × that empties the field. Off for a field that must have a date. */
    'clearable' => true,
    /** ids of the help or error lines that describe the field. */
    'describedby' => null,
    /** The validation key, when it is not the wire:model name. */
    'error' => null,
])

{{--
    THE DATE PICKER (EMS DatePicker) - in place of the browser's own date
    field, which draws itself differently on every phone and cannot be
    styled to match the console.

    A field-shaped button showing "12 Nov 2026" opens a calendar: arrows for
    the month, and a "November 2026" caption that opens a year stepper over
    the twelve months, so a hatch date years back is three taps, not thirty.
    Dates outside min / max are shown but cannot be picked.

    wire:model, wire:model.live or x-model go on the component exactly as
    they went on the <input type="date">: the root is x-modelable and holds
    the same YYYY-MM-DD string, so the server side is unchanged. Behaviour is
    emsDatePicker in app.js.
--}}
@php
    $resolve = fn (?string $date): ?string => $date === 'today' ? today()->toDateString() : $date;
    $model = $attributes->wire('model')->value();
    $errorKey = $error ?? $model;
    $hasError = $errorKey && isset($errors) && $errors->has($errorKey);
    $weekdays = ['Su', 'Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa'];
@endphp

<div x-data="emsDatePicker({ min: @js($resolve($min)), max: @js($resolve($max)) })"
     x-modelable="value"
     @if ($minFrom) x-effect="dynMin = ({{ $minFrom }}) || null" @endif
     @if ($maxFrom) x-effect="dynMax = ({{ $maxFrom }}) || null" @endif
     x-on:click.outside="close()"
     x-on:keydown.escape="if (open) { $event.stopPropagation(); close(true) }"
     {{ $attributes->class('relative w-full') }}>
    <button type="button" id="{{ $id }}" x-ref="trigger"
            x-on:click="toggle()"
            aria-haspopup="dialog"
            x-bind:aria-expanded="open.toString()"
            @if ($describedby) aria-describedby="{{ $describedby }}" @endif
            @if ($hasError) aria-invalid="true" @endif
            @class([
                'd-input datum flex w-full cursor-pointer items-center gap-2 text-left',
                'pr-9' => $clearable,
                'd-input-error' => $hasError,
            ])>
        <x-ui.icon name="calendar" class="size-4 shrink-0 text-muted-foreground lg:size-3.5" />
        <span class="min-w-0 flex-1 truncate"
              x-bind:class="value ? 'text-foreground' : 'text-muted-foreground'"
              x-text="value ? display(value) : @js($placeholder)">{{ $placeholder }}</span>
    </button>

    @if ($clearable)
        <button type="button" x-show="value" x-cloak x-on:click="pick('')"
                class="absolute top-1/2 right-1.5 z-10 inline-flex size-7 min-h-0 -translate-y-1/2 items-center justify-center rounded-md text-muted-foreground outline-none hover:bg-muted hover:text-foreground focus-visible:ring-2 focus-visible:ring-ring/50 lg:size-5"
                aria-label="Clear the date">
            <x-ui.icon name="x" class="size-3.5 lg:size-3" />
        </button>
    @endif

    <div x-show="open" x-cloak
         x-anchor.fixed.bottom-start.offset.4="$refs.trigger"
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="scale-95 opacity-0"
         x-transition:enter-end="scale-100 opacity-100"
         x-transition:leave="transition ease-in duration-100"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         role="dialog" aria-label="Choose a date"
         class="z-50 w-[17.5rem] max-w-[calc(100vw-2rem)] rounded-md border border-border bg-popover p-3 text-popover-foreground shadow-md">

        {{-- Month, with the arrows either side. --}}
        <div class="flex items-center justify-between gap-1">
            <button type="button" x-on:click="mode === 'days' ? step(-1) : panelYear--"
                    class="inline-flex size-8 min-h-0 items-center justify-center rounded-full outline-none hover:bg-muted focus-visible:ring-2 focus-visible:ring-ring/50"
                    x-bind:aria-label="mode === 'days' ? 'Previous month' : 'Previous year'">
                <x-ui.icon name="chevron-left" class="size-4" />
            </button>
            <button type="button" x-on:click="toggleMonths()"
                    class="inline-flex h-8 min-h-0 items-center gap-1.5 rounded-md border border-input px-2.5 text-xs font-medium outline-none hover:bg-muted focus-visible:ring-2 focus-visible:ring-ring/50"
                    x-bind:aria-expanded="(mode === 'months').toString()">
                <span x-text="mode === 'days' ? caption : panelYear"></span>
                <x-ui.icon name="chevron-down" class="size-3.5 text-muted-foreground transition-transform" x-bind:class="mode === 'months' ? 'rotate-180' : ''" />
            </button>
            <button type="button" x-on:click="mode === 'days' ? step(1) : panelYear++"
                    class="inline-flex size-8 min-h-0 items-center justify-center rounded-full outline-none hover:bg-muted focus-visible:ring-2 focus-visible:ring-ring/50"
                    x-bind:aria-label="mode === 'days' ? 'Next month' : 'Next year'">
                <x-ui.icon name="chevron-right" class="size-4" />
            </button>
        </div>
        <p class="sr-only" aria-live="polite" x-text="mode === 'days' ? caption : panelYear"></p>

        {{-- Days: six weeks, Sunday first. --}}
        <div x-show="mode === 'days'" class="mt-2">
            <div class="grid grid-cols-7 text-center text-[11px] font-medium text-muted-foreground" aria-hidden="true">
                @foreach ($weekdays as $weekday)
                    <span class="py-1">{{ $weekday }}</span>
                @endforeach
            </div>
            <div x-ref="grid" class="grid grid-cols-7 gap-y-0.5" role="grid" x-on:keydown="keydown($event)">
                <template x-for="cell in cells" x-bind:key="cell.iso">
                    <button type="button"
                            x-bind:data-iso="cell.iso"
                            x-on:click="pick(cell.iso)"
                            x-bind:disabled="cell.disabled"
                            x-bind:tabindex="cell.iso === focused ? 0 : -1"
                            x-bind:aria-pressed="cell.selected.toString()"
                            x-bind:aria-label="display(cell.iso)"
                            x-bind:class="{
                                'bg-primary text-primary-foreground font-semibold hover:bg-primary': cell.selected,
                                'border border-border font-semibold': cell.today && ! cell.selected,
                                'text-muted-foreground/60': cell.outside && ! cell.selected,
                                'hover:bg-muted': ! cell.selected && ! cell.disabled,
                                'cursor-not-allowed opacity-35': cell.disabled,
                            }"
                            class="datum mx-auto inline-flex size-9 min-h-0 items-center justify-center rounded-md text-[13px] outline-none focus-visible:ring-2 focus-visible:ring-ring/50 lg:size-8 lg:text-xs"
                            x-text="cell.day"></button>
                </template>
            </div>
        </div>

        {{-- Months: the year above, twelve months below. --}}
        <div x-show="mode === 'months'" x-cloak class="mt-2 grid grid-cols-3 gap-1.5">
            <template x-for="(name, index) in months" x-bind:key="name">
                <button type="button"
                        x-on:click="chooseMonth(index)"
                        x-bind:disabled="! monthAllowed(index)"
                        x-bind:class="index === viewMonth && panelYear === viewYear ? 'bg-primary text-primary-foreground hover:bg-primary' : 'hover:bg-muted'"
                        class="inline-flex h-9 min-h-0 items-center justify-center rounded-md text-xs font-medium outline-none focus-visible:ring-2 focus-visible:ring-ring/50 disabled:cursor-not-allowed disabled:opacity-35"
                        x-text="name.slice(0, 3)"></button>
            </template>
        </div>

        <div class="mt-3 flex items-center justify-between border-t border-border pt-2.5">
            <button type="button" x-on:click="pick(todayIso())" x-bind:disabled="! allowed(todayIso())"
                    class="inline-flex h-7 min-h-0 items-center rounded-md px-2 text-xs font-medium text-primary outline-none hover:bg-muted focus-visible:ring-2 focus-visible:ring-ring/50 disabled:opacity-40">
                Today
            </button>
            @if ($clearable)
                <button type="button" x-on:click="pick('')"
                        class="inline-flex h-7 min-h-0 items-center rounded-md px-2 text-xs font-medium text-muted-foreground outline-none hover:bg-muted hover:text-foreground focus-visible:ring-2 focus-visible:ring-ring/50">
                    Clear
                </button>
            @endif
        </div>
    </div>
</div>
