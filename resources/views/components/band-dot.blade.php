@props(['bloodline' => null])

{{--
    A bloodline's band colour, as a small dot.

    The ONLY form band colour takes outside the band tag itself. Colour in this
    system means bloodline, so it stays - but as a mark beside the name, never
    as a fill, and never as the only channel: the bloodline's name is always
    written next to it. aria-hidden for that reason.
--}}
<span {{ $attributes->merge(['class' => 'band-dot']) }}
      style="background-color: {{ \App\Support\BandTag::hex($bloodline) }}" aria-hidden="true"></span>
