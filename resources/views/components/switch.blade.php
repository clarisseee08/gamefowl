@props([
    'id',
    'label',
    /** Help text under the control. Optional. */
    'help' => null,
])

{{--
    A SWITCH — an on/off setting that applies immediately.

    Structure from D:/ems/employee-management-system's ui/switch: a rounded
    track with a thumb that translates across it. Two adaptations:

      The thumb moves with translateX, which is on the motion whitelist and
      composited. Animating `left` would force layout on every frame.

      EMS's track is h-[1.15rem] w-8, about 18px tall. That is fine for a mouse
      and too small for a thumb outdoors, so the whole control sits inside a
      44px label - the LABEL carries the touch target, because stretching the
      track itself would distort it.

    USE A CHECKBOX INSTEAD when the change is not applied until a form is
    submitted. A switch that needs a Save button afterwards is a lie about what
    just happened.
--}}
<div class="flex items-start gap-3">
    <label for="{{ $id }}" class="inline-flex min-h-11 shrink-0 cursor-pointer items-center">
        <span class="relative inline-flex">
            {{-- The real control, visually hidden but focusable and announced.
                 sr-only rather than display:none, which would remove it from
                 the accessibility tree and from the tab order. --}}
            <input id="{{ $id }}" type="checkbox" role="switch"
                   {{ $attributes->merge(['class' => 'peer sr-only']) }}>

            <span aria-hidden="true"
                  class="block h-5 w-9 rounded-full bg-input transition-colors peer-checked:bg-primary
                         peer-focus-visible:outline peer-focus-visible:outline-2 peer-focus-visible:outline-offset-2
                         peer-focus-visible:outline-primary"
                  style="transition-duration: var(--dur-fast); transition-timing-function: var(--ease-in-out)"></span>

            <span aria-hidden="true"
                  class="pointer-events-none absolute left-0.5 top-0.5 block h-4 w-4 rounded-full bg-card
                         peer-checked:translate-x-4"
                  style="transition: transform var(--dur-fast) var(--ease-in-out)"></span>
        </span>
    </label>

    <label for="{{ $id }}" class="min-h-11 cursor-pointer py-2.5 text-[15px] leading-snug text-foreground">
        {{ $label }}
        @if ($help)
            <span class="mt-0.5 block text-[13px] text-muted-foreground">{{ $help }}</span>
        @endif
    </label>
</div>
