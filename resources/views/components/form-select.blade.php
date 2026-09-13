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
     * THE ERROR STATE IS DERIVED HERE RATHER THAN PASSED IN.
     *
     * The forms used to write `class="@error('sex') input-error @enderror"` on
     * the native select. That cannot survive the move to a component: Blade
     * compiles component tags before directives, so an @error inside an
     * attribute value ends up inside a PHP array literal and the view dies
     * with "syntax error, unexpected token". The same @class([...]) shape the
     * health and mortality forms already used still works and is still
     * honoured through the attribute bag - but since this component renders
     * the message itself, deriving the border from the same key is the one
     * thing that cannot drift out of step with it.
     */
    $errorField = $error ?? $id;
@endphp

{{--
    A FORM SELECT WHOSE OPEN LIST IS OURS, not the operating system's.

    The sibling of x-filter-select, and the same mechanism for the same reason:
    a native select's option list is drawn by the OS, so no stylesheet can
    reach it and a carefully styled control still drops a raw Windows menu out
    of a farm ledger. The native select stays, hidden, holding the value and
    the wire:model binding, so Livewire keeps working and no call site had to
    change its <option> children.

    WHAT IS DIFFERENT FROM x-filter-select. The label sits ABOVE the control in
    normal form layout rather than inside its border, and .help and the @error
    message follow the control - the shape every form in this application
    already uses. The trigger carries .input itself, so `@error('x') input-error
    @enderror` on the call site still turns the border destructive exactly as
    it did on the native select.

    THE HTML `required` ATTRIBUTE IS DELIBERATELY NOT FORWARDED. On a control
    the browser can no longer show, constraint validation blocks the submit and
    anchors its bubble to a one-pixel element - the form simply stops working
    with nothing on screen to say why. Server-side validation already covers
    every one of these fields and renders through the @error block below.

    ACCESSIBILITY is the listbox pattern: a combobox button announcing its
    expanded state, a listbox of options with aria-selected, roving
    aria-activedescendant, and Arrow keys, Home, End, Enter, Escape and
    type-ahead. The native select is aria-hidden so it is not announced twice,
    and the <label> points at the button, which is a labelable element, so
    clicking the label focuses the control the keeper can actually see.

    NO DOUBLE QUOTE MAY APPEAR INSIDE x-data. It is an HTML attribute, so the
    first one ends it and Alpine silently receives truncated JavaScript.
--}}
<div x-data="{
        open: false,
        options: [],
        active: -1,
        selected: -1,
        label: '',
        typed: '',
        typedAt: 0,
        init() {
            this.sync();

            {{-- TWO READS, NOT ONE, AND NEITHER IS OPTIONAL.

                 wire:model compiles to an Alpine x-model on the native select,
                 and Alpine walks the tree in document order - so this x-data
                 initialises BEFORE that binding exists. At the first read the
                 select is still on whatever option the browser defaulted to,
                 which on an edit form is the first one rather than the saved
                 one. Nothing announces the correction either: setting
                 select.value mutates no attribute and fires no event.

                 The second read, one tick later, happens after the binding has
                 run. The observer then covers the other direction - Livewire
                 morphing the option list underneath us, which is what the bird
                 pickers do on every keystroke of their search box. --}}
            this.$nextTick(() => this.sync());

            new MutationObserver(() => this.$nextTick(() => this.sync()))
                .observe(this.$refs.native, { childList: true, subtree: true, attributes: true });

            this.$watch('open', v => { if (v) this.$nextTick(() => this.scrollToActive()); });
        },
        sync() {
            const el = this.$refs.native;
            this.options = Array.from(el.options).map((o, i) => ({ value: o.value, label: o.text, index: i }));
            this.selected = el.selectedIndex;
            this.label = el.selectedIndex > -1 ? el.options[el.selectedIndex].text : '';
            if (! this.open) this.active = el.selectedIndex;
        },
        choose(i) {
            const el = this.$refs.native;
            el.selectedIndex = i;

            {{-- change is what x-model listens for on a select, so this is the
                 event that moves the value into Livewire. blur follows it
                 because four of these fields bind wire:model.blur, and the
                 hidden select can never be blurred by a person - without it
                 their value would sit on the client until the next commit. --}}
            el.dispatchEvent(new Event('change', { bubbles: true }));
            el.dispatchEvent(new Event('blur'));

            this.active = i;
            this.open = false;
            this.$refs.trigger.focus();
        },
        move(delta) {
            if (! this.open) { this.open = true; return; }
            const n = this.options.length;
            if (! n) return;
            this.active = (this.active + delta + n) % n;
            this.scrollToActive();
        },
        scrollToActive() {
            const row = this.$refs.list && this.$refs.list.children[this.active];
            if (row) row.scrollIntoView({ block: 'nearest' });
        },
        search(key) {
            const now = Date.now();
            this.typed = now - this.typedAt > 700 ? key : this.typed + key;
            this.typedAt = now;
            const i = this.options.findIndex(o => o.label.toLowerCase().startsWith(this.typed.toLowerCase()));
            if (i > -1) { this.active = i; this.open = true; this.scrollToActive(); }
        },
     }"
     x-on:click.outside="open = false">

    <label for="{{ $id }}-trigger" id="{{ $id }}-label" @class(['label', 'sr-only' => $labelHidden])>
        {{ $label }}@if ($required) <span class="text-destructive">*</span>@endif
    </label>

    {{-- `relative` is load-bearing twice over: it anchors the list below, and
         it keeps the sr-only select - which is position:absolute - beside the
         control instead of laid out against the document. --}}
    <div class="relative mt-1">
        {{-- The value, the binding and the options. Hidden from the page and
             from the accessibility tree; the listbox below is what speaks. --}}
        <select x-ref="native" x-on:change="sync()" id="{{ $id }}" aria-hidden="true" tabindex="-1"
                {{ $attributes->except('class')->merge(['class' => 'sr-only pointer-events-none']) }}>
            {{ $slot }}
        </select>

        <button type="button"
                x-ref="trigger"
                id="{{ $id }}-trigger"
                role="combobox"
                aria-haspopup="listbox"
                x-bind:aria-expanded="open ? 'true' : 'false'"
                aria-controls="{{ $id }}-listbox"
                aria-labelledby="{{ $id }}-label {{ $id }}-value"
                x-on:click="open = ! open"
                x-on:keydown.arrow-down.prevent="move(1)"
                x-on:keydown.arrow-up.prevent="move(-1)"
                x-on:keydown.home.prevent="active = 0; open = true; scrollToActive()"
                x-on:keydown.end.prevent="active = options.length - 1; open = true; scrollToActive()"
                x-on:keydown.enter.prevent="open ? choose(active) : (open = true)"
                x-on:keydown.space.prevent="open ? choose(active) : (open = true)"
                x-on:keydown.escape.stop="open = false"
                x-on:keydown="if ($event.key.length === 1 && /\S/.test($event.key)) search($event.key)"
                @class([
                    'input flex items-center justify-between gap-2 text-left',
                    'input-error' => $errors->has($errorField),
                    trim((string) $attributes->get('class', '')),
                ])>
            <span id="{{ $id }}-value" class="truncate" x-text="label"></span>

            {{-- Rotating the chevron is a transform, which is composited. --}}
            <svg class="h-4 w-4 shrink-0 text-muted-foreground"
                 x-bind:class="open ? 'rotate-180' : ''"
                 style="transition: transform var(--dur-base) var(--ease-in-out)"
                 fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/>
            </svg>
        </button>

        {{-- The list. The whitelist's dropdown row: opacity plus a 4px rise at
             --dur-base, leaving one step faster at --dur-fast. No zoom - scale
             belongs to the tooltip and the modal panel, and a menu that zooms
             reads as a dialog. The same four transition classes x-dropdown and
             x-filter-select already use, so all three move identically. --}}
        <ul x-show="open"
            x-cloak
            x-ref="list"
            id="{{ $id }}-listbox"
            role="listbox"
            aria-labelledby="{{ $id }}-label"
            x-bind:aria-activedescendant="active > -1 ? '{{ $id }}-opt-' + active : null"
            x-transition:enter="transition-[opacity,transform] ease-out duration-200"
            x-transition:enter-start="opacity-0 -translate-y-1"
            x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="transition-[opacity,transform] ease-in duration-150"
            x-transition:leave-start="opacity-100 translate-y-0"
            x-transition:leave-end="opacity-0 -translate-y-1"
            class="absolute left-0 right-0 top-full z-50 mt-1.5 max-h-72 overflow-y-auto rounded-[var(--radius-md)]
                   border border-border bg-popover p-1"
            style="box-shadow: var(--shadow-e2)">
            <template x-for="option in options" x-bind:key="option.index">
                <li x-bind:id="'{{ $id }}-opt-' + option.index"
                    role="option"
                    x-bind:aria-selected="option.index === selected ? 'true' : 'false'"
                    x-on:click="choose(option.index)"
                    x-on:mouseenter="active = option.index"
                    {{-- bg-muted, NOT bg-accent. shadcn's accent is a neutral;
                         this project's --color-accent is a strong orange, and
                         colour here means bloodline and nothing else. --}}
                    x-bind:class="option.index === active ? 'bg-muted' : ''"
                    class="flex min-h-11 cursor-pointer items-center justify-between gap-2 rounded-[var(--radius-sm)]
                           px-2.5 py-2 text-[15px] leading-snug text-foreground">
                    <span x-text="option.label"></span>
                    <svg x-show="option.index === selected" x-cloak
                         class="h-4 w-4 shrink-0 text-primary" fill="none" stroke="currentColor"
                         stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/>
                    </svg>
                </li>
            </template>
        </ul>
    </div>

    @if ($help) <p class="help">{{ $help }}</p> @endif
    @error($errorField) <p class="error">{{ $message }}</p> @enderror
</div>
