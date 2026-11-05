@props([
    /**
     * The tab strip. Either a list of ['value' => …, 'label' => …], or an
     * assoc array of value => label, which is shorter at most call sites.
     */
    'tabs' => [],
    /** Which tab opens. Defaults to the first. */
    'active' => null,
    /** Id namespace, so two tab sets on one screen do not collide. */
    'name' => 'tabs',
    /** Accessible name for the strip, e.g. "Bird record sections". */
    'label' => null,
])

{{--
    TABS — one region, several views of it, only one on screen at a time.

    Structure follows D:/ems/employee-management-system's ui/tabs (Radix), which
    is where the roles, the roving tabindex and the arrow-key contract come
    from. The appearance does not follow it, and the difference is deliberate:

      RADIX RENDERS A SEGMENTED CONTROL — a bg-muted pill holding triggers that
      turn white and gain a shadow when active. This system spends no colour on
      chrome, so tabs are the underlined kind: daisyUI's d-tabs d-tabs-border.
      (The pill kind exists too, as x-segmented; a third tab style would be a
      third product.)

      shadcn's triggers are text-sm with an h-10 strip. A d-tab here is 15px
      and 45px tall, because the console floor is a 44px touch target used
      outdoors.

    THE UNDERLINE IS DRAWN PER TAB by d-tabs-border, under whichever tab is
    d-tab-active. There used to be one sliding indicator, positioned by
    measuring the buttons' offsetLeft/offsetWidth; with each tab carrying its
    own underline there is nothing left to measure, so that measuring and its
    resize handler are gone. Inactive tabs take text-muted-foreground because
    d-tab draws them at 50% ink, under the console's 7:1 floor.

    AUTOMATIC ACTIVATION (arrow key both moves focus and switches panel) is the
    WAI-ARIA pattern for tabs whose panels are already in the DOM. Manual
    activation is for panels that cost a request to open; these do not.

    NO DOUBLE QUOTE MAY APPEAR INSIDE x-data — it is an HTML attribute, so the
    first one ends it and Alpine receives truncated JavaScript with no error.
    The selector below is [role=tab], and @js() emits the active value already
    escaped for attribute context.
--}}
@php
    $items = [];

    foreach ($tabs as $key => $tab) {
        $items[] = is_array($tab)
            ? ['value' => (string) ($tab['value'] ?? $key), 'label' => $tab['label'] ?? '']
            : ['value' => (string) $key, 'label' => (string) $tab];
    }

    $active = $active ?? ($items[0]['value'] ?? '');
@endphp

<div x-data="{
        active: @js($active),
        buttons() { return this.$refs.list ? Array.from(this.$refs.list.querySelectorAll('[role=tab]')) : [] },
        select(value) { this.active = value },
        focusTab(el) { if (el) { this.select(el.dataset.tab); el.focus() } },
        step(delta) {
            const items = this.buttons();
            const at = items.findIndex((n) => n.dataset.tab === this.active);
            this.focusTab(items[(at + delta + items.length) % items.length]);
        },
        jump(last) { const items = this.buttons(); this.focusTab(last ? items[items.length - 1] : items[0]); }
     }"
     {{ $attributes }}>

    {{-- The scroll box lets a narrow phone scroll the strip sideways rather
         than the page; flex-nowrap overrides d-tabs' wrapping so it can. --}}
    <div class="overflow-x-auto border-b border-border">
        <div x-ref="list"
             role="tablist"
             @if ($label) aria-label="{{ $label }}" @endif
             class="d-tabs d-tabs-border w-max min-w-full flex-nowrap gap-1"
             x-on:keydown.arrow-right.prevent="step(1)"
             x-on:keydown.arrow-left.prevent="step(-1)"
             x-on:keydown.home.prevent="jump(false)"
             x-on:keydown.end.prevent="jump(true)">

            @foreach ($items as $item)
                @php
                    $slug = \Illuminate\Support\Str::slug($item['value']);

                    /* Js::from is what @js() calls. Holding it in a variable
                       keeps the four bindings below reading as one comparison
                       instead of four, and it is already escaped for attribute
                       context, so {{ }} passes it through untouched. */
                    $value = \Illuminate\Support\Js::from($item['value']);
                @endphp

                {{-- Roving tabindex: the strip is ONE tab stop and the arrow
                     keys move within it. Without it, walking past a six-tab
                     record header costs six presses. --}}
                <button type="button"
                        role="tab"
                        id="{{ $name }}-tab-{{ $slug }}"
                        data-tab="{{ $item['value'] }}"
                        aria-controls="{{ $name }}-panel-{{ $slug }}"
                        x-bind:aria-selected="active === {{ $value }} ? 'true' : 'false'"
                        x-bind:tabindex="active === {{ $value }} ? 0 : -1"
                        x-bind:class="{
                            'd-tab-active text-base-content': active === {{ $value }},
                            'text-muted-foreground hover:text-base-content': active !== {{ $value }},
                        }"
                        x-on:click="select({{ $value }})"
                        class="d-tab shrink-0 text-[15px]">{{ $item['label'] }}</button>
            @endforeach
        </div>
    </div>

    <div class="mt-4">{{ $slot }}</div>
</div>
