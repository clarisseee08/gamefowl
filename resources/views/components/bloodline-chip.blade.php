@props([
    'bloodline' => null,
    'size' => 'xs',   // xs | md
])

@php
    use App\Support\BandTag;

    $hex = BandTag::hex($bloodline);
    // Resolved, never assumed. The band palette is bright enough that three of
    // the six cannot carry white text - amber sits at 2.08:1 - so each chip
    // takes the foreground that actually passes, including for whatever colour
    // the deterministic hash hands an unanticipated bloodline.
    $fg = BandTag::foreground($bloodline);
    $code = BandTag::code($bloodline);

    // Same reasoning as the code chip on x-band-tag: the tint has to move the
    // ground AWAY from the text, not toward it, so a light foreground darkens
    // and a dark foreground lightens.
    $chipTint = $fg === config('gfms-brand.band_foreground_light')
        ? 'rgb(0 0 0 / 0.22)'
        : 'rgb(255 255 255 / 0.45)';

    // Written out in full, never interpolated: Tailwind v4 scans for literal
    // class strings, so a built class name survives `npm run dev` and vanishes
    // from `npm run build`.
    $scale = $size === 'md'
        ? 'py-1.5 pl-2 pr-3 text-[14px] gap-2'
        : 'py-0.5 pl-1 pr-2 text-[11px] gap-1';
@endphp

{{--
    A BLOODLINE, as a chip.

    NOT x-band-tag, and the difference is not cosmetic. That component describes
    one BIRD: its slot is a band number, and BandTag::label renders "Band
    SW-3101, Sweater". Passing a bloodline into it produced "Band Sweater,
    Sweater" for a screen reader, and passing nothing produced "Not yet banded"
    - which is a true statement about a bird and a meaningless one about a
    bloodline.

    So this is the same colour, resolved by the same App\Support\BandTag, with
    the right semantics: the thing named IS the bloodline.

    COLOUR IS NEVER THE ONLY CHANNEL. The chip carries the two-letter code and
    the bloodline's name in text, so it survives being printed in grey, read
    aloud, or looked at by someone who cannot separate the six hues.
--}}
<span {{ $attributes->merge(['class' => "band-tag {$scale}"]) }}
      style="background-color: {{ $hex }}; color: {{ $fg }}"
      title="{{ $bloodline }}">
    <span class="band-code" aria-hidden="true"
          style="background-color: {{ $chipTint }}">{{ $code }}</span>
    <span class="band-number">{{ $bloodline }}</span>
</span>
