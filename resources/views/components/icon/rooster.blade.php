{{--
    The GBMS mark — a rooster head in profile, facing right.

    HEAD ONLY, and solid rather than outlined, both for the same reason: this has
    to survive 16px. A full body becomes an unidentifiable smudge at favicon
    size, and an outlined comb loses the separation between its three points
    below about 20px. A solid silhouette keeps its shape all the way down.

    Built from geometry rather than traced: three overlapping circles for the
    comb, a circle for the head, a triangle beak, two circles for the wattle, and
    a knocked-out eye. The eye is the one part that does not inherit currentColor
    - it is punched through with the surface colour so the mark reads on any
    ground.

    OPTICALLY centred, not mathematically. The beak carries visual weight to the
    right, so the silhouette sits about 1px left of the true centre of the 24
    grid; centring it by arithmetic makes it look shifted right.

    Comb circles are r=2.6 rather than the r=2 that geometry alone suggests.
    At 16px the smaller radius merged the three points into a single lump; this
    is the thickening the brief asks for, arrived at by rendering it.
--}}
@props(['eye' => 'var(--color-brand-deep)'])

<svg {{ $attributes->merge(['class' => 'h-6 w-6', 'aria-hidden' => 'true']) }}
     viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">

    {{-- Comb: three overlapping circles across the crown. --}}
    <circle cx="8.5" cy="5.9" r="2.9" fill="currentColor"/>
    <circle cx="12.2" cy="4.8" r="2.9" fill="currentColor"/>
    <circle cx="15.6" cy="6.2" r="2.7" fill="currentColor"/>

    {{-- Head. --}}
    <circle cx="11.9" cy="12.2" r="6.4" fill="currentColor"/>

    {{-- Beak: a triangle off the right cheek, the one element that fixes the
         silhouette as a bird rather than a generic round head. --}}
    <path d="M17.0 10.4 L23.2 12.6 L17.0 14.8 Z" fill="currentColor"/>

    {{-- Wattle: two circles under the beak, the second smaller so the shape
         tapers instead of reading as a stack. --}}
    <circle cx="14.0" cy="18.4" r="2.5" fill="currentColor"/>
    <circle cx="12.4" cy="21.4" r="1.7" fill="currentColor"/>

    {{-- No neck. A block below the head out-weighed the comb and the beak - the
         two elements that actually say "rooster" - and turned the silhouette
         into a chess piece. Head-only, per the brief. --}}

    {{-- Eye, knocked out. Sized to stay visible at 16px - anything under r=0.95
         disappears entirely once the mark is scaled down. --}}
    <circle cx="14.1" cy="10.3" r="1.15" fill="{{ $eye }}"/>
</svg>
