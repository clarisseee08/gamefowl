@props([
    /** What is not here. A statement, not an apology. */
    'title',
    /** How it gets here. Optional, but a list the user can fill should say so. */
    'description' => null,
    /** Inline SVG path data. Optional. */
    'icon' => null,
])

{{--
    AN EMPTY LIST, STATED.

    Required wherever a list can come back with nothing. A table that renders
    its header and then stops looks broken; this looks finished.

    It wraps the `.empty-state` classes app.css already defines rather than
    restyling them, so every empty region in the console is the same object.
    The slot is for ONE action — the thing that would fill the list.

    Structure follows D:/ems/employee-management-system's empty-state: glyph in
    a circle, title, description. Everything decorative about it was dropped,
    and the reasons are rules rather than taste:

      NO MOTION. EMS enters with `animate-in fade-in zoom-in-95 duration-500`
      and grows the icon on hover. app.css states the counter-case in its own
      comment: an empty state is the resting state of a screen, and a thing
      that animates in every time a filter returns nothing punishes the user for
      filtering. 500ms also exceeds the 300ms ceiling outright.

      NO SHADOW ON THE GLYPH CIRCLE, NO DASHED CARD, NO TONE VARIANTS. Elevation
      here is a hairline and a background step, and EMS's `warning` tone is an
      amber wash on a screen that has nothing wrong with it — in an application
      that flags mortality, tinting "no results" as a warning is a real
      misreading, not a stylistic one.

      EMS's title is 14px at weight 700 and its body is 12px.
      `.empty-state-title` is 17px and `.empty-state-body` is 15px, because this
      text is the only thing on the screen at the moment it appears.
--}}
<div {{ $attributes->merge(['class' => 'empty-state']) }}>
    @if ($icon)
        {{-- The circle is a background step, which is how this system draws
             elevation. A shadow would be the other way, and it is banned. --}}
        <span class="mb-4 inline-flex h-11 w-11 items-center justify-center rounded-full bg-muted">
            <svg class="h-5 w-5 text-muted-foreground" fill="none" stroke="currentColor"
                 stroke-width="1.6" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="{{ $icon }}"/>
            </svg>
        </span>
    @endif

    <p class="empty-state-title">{{ $title }}</p>

    @if ($description)
        <p class="empty-state-body">{{ $description }}</p>
    @endif

    @if (trim($slot) !== '')
        <div class="mt-5">{{ $slot }}</div>
    @endif
</div>
