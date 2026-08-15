@props([
    'bloodline' => null,
    'band' => null,
    'size' => 'md',   // xs (table rows) | md (cards, detail)
    'showCode' => true,
])

@php
    use App\Support\BandTag;

    $hasBand = trim((string) $band) !== '';
    $hex = BandTag::hex($bloodline);
    $code = BandTag::code($bloodline);
    $aria = BandTag::label($bloodline, $band);

    // Sizes are written out in full, never interpolated. Tailwind v4 scans for
    // literal class strings, so a built class name would work in `npm run dev`
    // and silently disappear from `npm run build`.
    $scale = $size === 'xs'
        ? 'py-0.5 pl-1 pr-2 text-[11px] gap-1'
        : 'py-1.5 pl-2 pr-3 text-[14px] gap-2';
@endphp

@if ($hasBand)
    {{-- The signature. A filled capsule in the bloodline's anodised colour,
         carrying the band number in mono - modelled on the physical leg band.
         Colour arrives as an inline hex because the bloodline is free text and
         its slot is resolved in PHP; a dynamic Tailwind class would not survive
         a production build. --}}
    <span {{ $attributes->merge(['class' => "band-tag {$scale}"]) }}
          style="background-color: {{ $hex }}"
          title="{{ $aria }}">
        @if ($showCode)
            <span class="band-code" aria-hidden="true">{{ $code }}</span>
        @endif
        <span class="band-number">{{ $band }}</span>
        <span class="sr-only">{{ $aria }}</span>
    </span>
@else
    {{-- Not an error. Birds are banded at an age, not at hatch, so "unbanded"
         is a real state that deserves to say so rather than render a dash. --}}
    <span {{ $attributes->merge(['class' => "band-tag-none {$scale}"]) }}
          title="{{ $aria }}">
        <span class="h-2 w-2 rounded-full" style="background-color: {{ $hex }}" aria-hidden="true"></span>
        Not yet banded
        <span class="sr-only">{{ $aria }}</span>
    </span>
@endif
