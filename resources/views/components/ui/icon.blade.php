@props([
    /** A Lucide name listed in App\Support\Icon. */
    'name',
    /** 2 everywhere; 3 only for the tiny emphasis glyphs EMS thickens (toast discs, delta arrows). */
    'stroke' => 2,
    /** Accessible name. Leave it out for an icon beside text, which is decorative. */
    'label' => null,
])

{{--
    ONE LUCIDE GLYPH (EMS design system, Part B §10).

    No size by default, on purpose: a button sizes the icons inside it that do
    not carry a size of their own (14px in an app button, 16px in a base one),
    so an icon that always arrived sized would defeat that. Anywhere else, give
    it a size - size-4 beside 14px text, size-3.5 inside compact controls.

    Colour is currentColor: an icon takes the colour of the text it sits in.
--}}
<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
     stroke="currentColor" stroke-width="{{ $stroke }}" stroke-linecap="round" stroke-linejoin="round"
     @if ($label) role="img" aria-label="{{ $label }}" @else aria-hidden="true" focusable="false" @endif
     {{ $attributes->merge(['class' => 'shrink-0']) }}>{!! \App\Support\Icon::paths($name) !!}</svg>
