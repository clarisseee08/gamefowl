@props([
    /** The control's id. Required. */
    'id',
    /** The field name, rendered inside the control's border as a prefix. */
    'label',
])

{{--
    A SELECT WHOSE OPEN LIST IS OURS, not the operating system's.

    WHY THIS IS NOT A NATIVE <select>. A native select's option list is drawn by
    the OS, not the page. No stylesheet can reach it - not the blue highlight,
    not the system font, not the square corners - so a styled control still
    dropped a Windows menu out of a farm ledger. shadcn has the same problem and
    the same answer: Radix Select is a custom listbox, never a native one.

    WHY THE NATIVE SELECT IS STILL HERE, hidden. It holds the value and carries
    the wire:model binding, so Livewire keeps working exactly as before and not
    one of the six call sites had to change - they still pass plain <option>
    children. Alpine reads those options out of the DOM at init to build the
    visible list, and writing back dispatches a real `change` event, which is
    the event Livewire is already listening for.

    THE FIELD NAME SITS INSIDE THE BORDER. The label and the control share one
    bordered group, so the pair reads as a single object rather than two stacked
    elements, and the bar is one row high instead of two. The group is a
    daisyUI d-input with a d-label prefix - daisyUI's own shape for a field with
    a leading label - and the open list is a d-menu.

    ACCESSIBILITY is the listbox pattern rather than a hidden native control: a
    combobox button announcing its expanded state, a listbox of options with
    aria-selected, roving aria-activedescendant, and full keyboard support -
    Arrow keys, Home, End, Enter, Escape, and type-ahead. The native select is
    aria-hidden precisely so it is not announced twice.

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

            {{-- A SECOND READ, ONE TICK LATER. wire:model compiles to an Alpine
                 x-model on the native select, and Alpine walks the tree in
                 document order - so this x-data initialises before that binding
                 exists, and the first read sees whatever option the browser
                 defaulted to. On a bar whose filters come off the query string
                 that is the wrong one, and nothing announces the correction:
                 setting select.value mutates no attribute and fires no event. --}}
            this.$nextTick(() => this.sync());

            {{-- WHY AN OBSERVER AND NOT A GETTER.

                 The trigger's text was a getter reading
                 $refs.native.selectedIndex. Alpine tracks reactive state, not
                 DOM properties, so it never re-evaluated: picking an option
                 filtered the list correctly and left the trigger reading its
                 old value. Livewire morphs this subtree and sets the select
                 programmatically, which fires no `change` event either, so
                 there was nothing to listen for.

                 The observer watches the select itself and re-reads after any
                 morph, which is the only thing that catches BOTH a user
                 choosing and the server re-rendering. --}}
            new MutationObserver(() => this.sync())
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
            el.dispatchEvent(new Event('change', { bubbles: true }));
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
     x-on:click.outside="open = false"
     class="relative w-full sm:w-auto sm:shrink-0">

    {{-- The value, the binding, and the option list. Hidden from both the page
         and the accessibility tree: the listbox below is what is announced. --}}
    <select x-ref="native" x-on:change="sync()" id="{{ $id }}" aria-hidden="true" tabindex="-1"
            {{ $attributes->merge(['class' => 'sr-only pointer-events-none']) }}>
        {{ $slot }}
    </select>

    {{-- d-input rather than d-select: d-select paints its own chevron, and
         this one has to rotate. The d-label as first child is what gives the
         prefix its divider. --}}
    <div class="d-input w-full cursor-pointer items-center gap-0 sm:w-auto">
        <span id="{{ $id }}-label"
              class="d-label inline-flex shrink-0 items-center self-stretch text-[13px] leading-none font-medium lg:text-[11px]">
            {{ $label }}
        </span>

        <button type="button"
                x-ref="trigger"
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
                class="flex h-full w-full min-w-0 cursor-pointer items-center justify-between gap-2 text-left
                       text-[15px] text-base-content focus:outline-none lg:text-xs">
            <span id="{{ $id }}-value" class="truncate" x-text="label"></span>

            {{-- Rotating the chevron is a transform, which is composited. --}}
            <svg class="size-4 shrink-0 text-muted-foreground lg:size-3.5"
                 x-bind:class="open ? 'rotate-180' : ''"
                 style="transition: transform var(--dur-base) var(--ease-in-out)"
                 fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/>
            </svg>
        </button>
    </div>

    {{-- The list. Motion is the whitelist's dropdown row: opacity plus a 4px
         rise at --dur-base, leaving one step faster. No zoom - scale is
         reserved for the tooltip and the modal panel, and a menu that zooms
         reads as a dialog.

         It is a d-menu, held to a listbox: w-auto undoes d-menu's fit-content
         so the absolute left/right still stretch it, and flex-nowrap stops a
         long list wrapping into a second column at max-h-72. Each option's
         visible row is the element INSIDE the <li>, because that is what
         d-menu styles; the <li> keeps the role, the id the active descendant
         points at, and the handlers. --}}
    <ul x-show="open"
        x-cloak
        x-ref="list"
        id="{{ $id }}-listbox"
        role="listbox"
        x-bind:aria-activedescendant="active > -1 ? '{{ $id }}-opt-' + active : null"
        x-transition:enter="transition-[opacity,transform] ease-out duration-200"
        x-transition:enter-start="opacity-0 -translate-y-1"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition-[opacity,transform] ease-in duration-150"
        x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 -translate-y-1"
        class="d-menu absolute left-0 right-0 top-full z-50 mt-1.5 max-h-72 w-auto flex-nowrap overflow-y-auto
               rounded-box border border-base-300 bg-base-100 p-1 text-[15px] shadow-md lg:text-xs
               sm:left-auto sm:right-0 sm:min-w-full">
        <template x-for="option in options" x-bind:key="option.index">
            <li x-bind:id="'{{ $id }}-opt-' + option.index"
                role="option"
                x-bind:aria-selected="option.index === selected ? 'true' : 'false'"
                x-on:click="choose(option.index)"
                x-on:mouseenter="active = option.index"
                class="cursor-pointer">
                {{-- d-menu-focus, NOT bg-accent: d-menu's own neutral tint,
                     the same one a hovered row gets. shadcn's accent is a
                     neutral; this project's --color-accent is a brand green,
                     and colour here means bloodline and nothing else. --}}
                <div x-bind:class="option.index === active ? 'd-menu-focus' : ''"
                     class="flex min-h-11 items-center justify-between gap-2 px-2.5 text-[15px] lg:min-h-7 lg:px-2 lg:text-xs">
                    <span class="truncate" x-text="option.label"></span>
                    <svg x-show="option.index === selected" x-cloak
                         class="h-4 w-4 shrink-0 text-primary" fill="none" stroke="currentColor"
                         stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/>
                    </svg>
                </div>
            </li>
        </template>
    </ul>
</div>
