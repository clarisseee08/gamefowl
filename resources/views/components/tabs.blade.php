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
      turn white and gain a shadow when active. This system has no shadows and
      spends no colour on chrome, so tabs are the underlined kind that app.css
      already defines: `.tab`, `.tab-active`, `.tab-indicator`. Using the
      existing classes is the whole point; a second tab style would be a second
      product.

      shadcn's triggers are text-sm with an h-10 strip. `.tab` is 15px and
      min-h-11, because the console floor is a 44px touch target used outdoors.

    THE INDICATOR IS THE ONE PLACE WIDTH IS ANIMATED, and app.css says so: an
    underline that slides without resizing cannot track tabs of different
    lengths. Everything else about it is a transform.

    IT IS MEASURED, NOT GUESSED. Tab widths depend on rendered text, so there is
    no server-side geometry to emit; `move()` reads offsetLeft/offsetWidth off
    the real buttons. It runs SYNCHRONOUSLY in init() rather than in $nextTick
    so the indicator's first style binding already has its position — deferring
    it would leave the bar at width 0 for a frame and then animate it out from
    the left edge, which is a page-load animation and reads as a glitch.

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
        ind: { x: 0, w: 0 },
        init() { this.move() },
        buttons() { return this.$refs.list ? Array.from(this.$refs.list.querySelectorAll('[role=tab]')) : [] },
        move() {
            const el = this.buttons().find((n) => n.dataset.tab === this.active);
            if (el) { this.ind = { x: el.offsetLeft, w: el.offsetWidth } }
        },
        select(value) { this.active = value; this.$nextTick(() => this.move()) },
        focusTab(el) { if (el) { this.select(el.dataset.tab); el.focus() } },
        step(delta) {
            const items = this.buttons();
            const at = items.findIndex((n) => n.dataset.tab === this.active);
            this.focusTab(items[(at + delta + items.length) % items.length]);
        },
        jump(last) { const items = this.buttons(); this.focusTab(last ? items[items.length - 1] : items[0]); }
     }"
     x-on:resize.window="move()"
     {{ $attributes }}>

    {{-- The scroll box is the positioned ancestor, so offsetLeft and the
         indicator share one origin and the bar keeps up when a narrow phone
         scrolls the strip sideways. --}}
    <div class="relative overflow-x-auto border-b border-border">
        <div x-ref="list"
             role="tablist"
             @if ($label) aria-label="{{ $label }}" @endif
             class="flex w-max min-w-full gap-1"
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
                        x-bind:class="{ 'tab-active': active === {{ $value }} }"
                        x-on:click="select({{ $value }})"
                        class="tab shrink-0">{{ $item['label'] }}</button>
            @endforeach
        </div>

        <span class="tab-indicator left-0" aria-hidden="true"
              x-bind:style="'transform: translateX(' + ind.x + 'px); width: ' + ind.w + 'px'"></span>
    </div>

    <div class="mt-4">{{ $slot }}</div>
</div>
