@props([
    'id',
    'label',
    /** Help text under the control. Optional. */
    'help' => null,
])

{{--
    A SWITCH — an on/off setting that applies immediately.

    Structure from D:/ems/employee-management-system's ui/switch: a rounded
    track with a thumb that moves across it. The control is daisyUI's d-toggle,
    which draws the track and the thumb on the checkbox itself, so the real
    input is the visible one - no hidden peer and no decorative spans.

      The track is about 24px tall. That is fine for a mouse and too small for
      a thumb outdoors, so the whole control sits inside a 44px label - the
      LABEL carries the touch target, because stretching the track itself
      would distort it.

    USE A CHECKBOX INSTEAD when the change is not applied until a form is
    submitted. A switch that needs a Save button afterwards is a lie about what
    just happened.
--}}
<div class="flex items-start gap-3">
    <label for="{{ $id }}" class="inline-flex min-h-11 shrink-0 cursor-pointer items-center">
        <input id="{{ $id }}" type="checkbox" role="switch"
               {{ $attributes->merge(['class' => 'd-toggle d-toggle-primary']) }}>
    </label>

    <label for="{{ $id }}" class="min-h-11 cursor-pointer py-2.5 text-[15px] leading-snug text-base-content">
        {{ $label }}
        @if ($help)
            <span class="mt-0.5 block text-[13px] text-muted-foreground">{{ $help }}</span>
        @endif
    </label>
</div>
