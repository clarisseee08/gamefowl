@props([
    'title',
    /** One line under the title saying what the card is for. Optional. */
    'description' => null,
    /** Inline SVG path data for a leading glyph. Optional. */
    'icon' => null,
])

{{--
    A TITLED PANEL for content that is not a single figure — a detail block, a
    filter row above a table, a note, a short list.

    THE QUIET SIBLING OF x-stat-card. That one exists to make one number
    unmissable: 32px, display weight, nothing else in the box. This one exists
    to hold everything else, so its header is 15px, its rule is a hairline, and
    it never raises its voice. Reach for stat-card when the answer is a number
    and this when the answer is a paragraph, a list or a control.

    Structure follows D:/ems/employee-management-system's info-card: glyph,
    title, description, a hairline under the header, then the body. Three
    changes:

      EMS's header is 14px at weight 700. The ladder here stops at 600 and the
      console body is 15px, so the title is 15px at the heading weight it
      inherits. 700 is not loaded at all: the browser would synthesise it by
      smearing the 600, which is visibly worse than the weight it imitates.
      (The utility that sets it is not named here on purpose — Tailwind scans
      this comment as content, so writing the banned class would compile it.)

      EMS's description is 11px. That is the absolute floor of this system and
      wrong for a line meant to be read; it is 13px here, the same step .label
      uses.

      EMS's fields grid is not carried across. A label/value grid rendered from
      an array is how numbers lose .datum — the call site can see that a weight
      is a weight and a generic renderer cannot.

    NO SHADOW BEYOND .card's OWN. Elevation means interactive or layered;
    a panel sitting still on the page is neither.
--}}
<div {{ $attributes->merge(['class' => 'card p-5']) }}>
    <div class="flex flex-col gap-2 border-b border-border pb-3 sm:flex-row sm:items-start sm:justify-between">
        <div class="flex min-w-0 items-start gap-2.5">
            @if ($icon)
                {{-- mt-0.5 seats the glyph on the title's baseline; items-start
                     alone hangs it above the cap height at this size. --}}
                <svg class="mt-0.5 h-[18px] w-[18px] shrink-0 text-muted-foreground" fill="none"
                     stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="{{ $icon }}"/>
                </svg>
            @endif

            <div class="min-w-0">
                <h3 class="text-[15px] leading-snug text-foreground">{{ $title }}</h3>

                @if ($description)
                    <p class="mt-0.5 text-[13px] leading-snug text-muted-foreground">{{ $description }}</p>
                @endif
            </div>
        </div>

        {{-- A named slot rather than a prop, so the action can be a real
             <x-dropdown> or a form button instead of a string. --}}
        @isset($actions)
            <div class="shrink-0">{{ $actions }}</div>
        @endisset
    </div>

    <div class="pt-4">{{ $slot }}</div>
</div>
