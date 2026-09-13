@props([
    /** The person's name. Supplies the initials and the alt text. */
    'name' => '',
    /** Photo URL. Falls back to initials when absent or when it 404s. */
    'src' => null,
    /** sm 32px | md 40px | lg 48px. */
    'size' => 'md',
])

{{--
    AN AVATAR — a photo when there is one, the person's initials when there is
    not.

    Structure follows D:/ems/employee-management-system's ui/avatar (Radix): a
    round clipping frame, an image layer, and a fallback layer underneath it.
    Radix swaps the two in JavaScript after the image loads; here the fallback
    is simply behind the image, so a broken or slow URL shows initials instead
    of a hole with no script involved.

    THE INITIALS ARE NEUTRAL INK, NEVER A GENERATED COLOUR. Hashing a name to a
    hue is the standard move and it is the one thing this system cannot do:
    colour means bloodline, and a coloured circle beside a keeper's name spends
    that meaning on decoration. `.avatar` resolves to ink-200 on ink-700 for
    exactly this reason.

    IT IS aria-hidden, AND THE NAME MUST APPEAR AS TEXT BESIDE IT. The same rule
    the band tag follows: the visual is never the only channel. Two letters in a
    circle are not a person's name, so announcing them adds noise ahead of the
    real one. x-icon-user makes the same call and says why at length.

    NO 44px TARGET, deliberately: this is not a control. When an avatar sits
    inside a menu button, the BUTTON carries the target.

    AN EMPTY NAME RENDERS AN EMPTY CIRCLE, not a "?". A question mark reads as
    an error state, and a missing name is a gap in the record, not a fault —
    the same reasoning that gives an unbanded bird "Not yet banded" rather than
    a dash.
--}}
@php
    $sizes = [
        'sm' => 'h-8 w-8 text-[12px]',
        'md' => 'h-10 w-10 text-[13px]',
        'lg' => 'h-12 w-12 text-[15px]',
    ];

    $frame = $sizes[$size] ?? $sizes['md'];

    /* mb_* throughout: keeper names in this farm's own records carry
       diacritics, and substr() on a multi-byte first letter returns half a
       character, which renders as a replacement glyph. */
    $words = array_values(array_filter(preg_split('/\s+/u', trim((string) $name)) ?: []));

    $initials = match (count($words)) {
        0 => '',
        1 => mb_strtoupper(mb_substr($words[0], 0, 2)),
        default => mb_strtoupper(mb_substr($words[0], 0, 1).mb_substr($words[count($words) - 1], 0, 1)),
    };
@endphp

<span {{ $attributes->merge(['class' => 'avatar relative '.$frame]) }} aria-hidden="true">
    <span class="font-medium leading-none tracking-[0.02em]">{{ $initials }}</span>

    @if ($src)
        {{-- Absolute, so it covers the initials rather than displacing them:
             if the file is missing the letters are already in place behind it.
             object-cover because a portrait cropped to a circle by the browser
             beats one squashed into it. --}}
        <img src="{{ $src }}" alt="" loading="lazy" decoding="async"
             class="absolute inset-0 h-full w-full object-cover">
    @endif
</span>
