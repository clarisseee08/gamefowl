@props([
    /** What the tooltip says. For an icon-only control, the same words go in its aria-label. */
    'text',
    /** top (default) · right · bottom · left - right for the collapsed sidebar. */
    'side' => 'top',
])

{{--
    THE TOOLTIP (EMS, Part B §5.18): primary-filled 12px text, with no open
    delay, on hover and on keyboard focus; Escape, leaving or blurring hides
    it. Wrap the one control it describes; that control is pointed at the
    tooltip with aria-describedby.

    Anchored with fixed positioning, so it is not clipped inside a scrolling
    table. A tooltip only repeats or explains - never put the only copy of
    something a person needs in one, because a touch screen cannot hover.
--}}
@php
    $placement = in_array($side, ['top', 'right', 'bottom', 'left'], true) ? $side : 'top';
@endphp

<span x-data="{ shown: false }"
      x-init="$el.firstElementChild?.setAttribute('aria-describedby', $id('tooltip'))"
      x-on:mouseenter="shown = true" x-on:mouseleave="shown = false"
      x-on:focusin="shown = true" x-on:focusout="shown = false"
      x-on:keydown.escape="shown = false"
      {{ $attributes->class('relative inline-flex') }}>
    {{ $slot }}

    <span x-show="shown" x-cloak role="tooltip" x-bind:id="$id('tooltip')"
          x-anchor.fixed.{{ $placement }}.offset.6="$root"
          x-transition:enter="transition ease-out duration-150"
          x-transition:enter-start="scale-95 opacity-0"
          x-transition:enter-end="scale-100 opacity-100"
          class="pointer-events-none z-50 w-fit max-w-xs rounded-md bg-primary px-3 py-1.5 text-xs text-balance text-primary-foreground shadow-md">
        {{ $text }}
    </span>
</span>
