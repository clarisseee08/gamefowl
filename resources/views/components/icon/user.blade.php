@props(['tone' => 'light'])   {{-- light (on card/muted) | onDark (on the red rail) --}}

{{--
    A neutral user glyph, not initials.

    Initials avatars signal a multi-user social product full of people you have
    not met. This is a three-role internal tool where every account is known and
    named, so a glyph beside the actual name is the honest reading.

    It also removes an inconsistency: a two-character coloured circle spends
    colour decoratively in a system whose whole rule is that colour means
    bloodline. The glyph spends none.
--}}
@php
    $ring = $tone === 'onDark' ? 'var(--color-brand-deeper)' : 'var(--color-border)';
    $fill = $tone === 'onDark' ? 'var(--color-brand-deeper)' : 'var(--color-muted)';
    $ink = $tone === 'onDark' ? 'var(--color-brand-muted-fg)' : 'var(--color-muted-foreground)';
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex h-8 w-8 items-center justify-center rounded-full']) }}
      style="background-color: {{ $fill }}; box-shadow: inset 0 0 0 1px {{ $ring }}"
      aria-hidden="true">
    {{-- Lucide `user`, matching the stroke weight of the nav icons already in use. --}}
    <svg viewBox="0 0 24 24" fill="none" stroke="{{ $ink }}" stroke-width="1.7"
         stroke-linecap="round" stroke-linejoin="round" class="h-[18px] w-[18px]">
        <path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/>
        <circle cx="12" cy="7" r="4"/>
    </svg>
</span>
