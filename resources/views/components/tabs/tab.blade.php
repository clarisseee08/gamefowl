@props([
    /** Must match the `value` of one entry in the parent's `tabs` array. */
    'value',
    /**
     * Declared so it is CONSUMED rather than passed through. @aware alone
     * leaves `name` in the attribute bag, which renders a stray name="…" on
     * the panel element — invalid HTML, and invisible until something
     * validates it.
     */
    'name' => 'tabs',
])

{{-- @aware reads the id namespace off <x-tabs>, so a panel and its button agree
     on ids without the caller repeating `name` on every child. The parent is
     the authority: it owns the strip, and two halves of one control disagreeing
     about their ids is the failure this prevents. --}}
@aware(['name' => 'tabs'])

{{--
    ONE TAB PANEL.

    It holds no state. `active` is read straight out of the <x-tabs> Alpine
    scope it is nested in, which is why these have to stay inside the parent
    component rather than being placed anywhere on the page.

    NOT x-if, NOT a conditional render. The panel exists in the DOM whether or
    not it is showing, for two reasons: Ctrl+F finds text in a panel the user
    has not opened yet, and switching costs no re-render — which is what earns
    the automatic-activation keyboard pattern in the parent.

    NO TRANSITION. The motion whitelist gives tabs exactly one animation, the
    indicator. A panel that fades on every arrow-key press turns walking the
    strip into a strobe.

    tabindex=0 is required by the ARIA tabs pattern: a panel whose content has
    no focusable element must be reachable itself, or Tab from the strip leaves
    the region entirely.

    x-cloak, because the inactive panels are all visible until Alpine boots and
    a record page would otherwise flash every section stacked on top of each
    other on every load.
--}}
<div x-show="active === @js($value)"
     x-cloak
     role="tabpanel"
     id="{{ $name }}-panel-{{ \Illuminate\Support\Str::slug($value) }}"
     aria-labelledby="{{ $name }}-tab-{{ \Illuminate\Support\Str::slug($value) }}"
     tabindex="0"
     {{ $attributes->merge(['class' => 'focus:outline-none']) }}>
    {{ $slot }}
</div>
