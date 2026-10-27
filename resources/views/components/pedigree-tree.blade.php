@props(['bird'])

@php
    /*
     * A node: the band number (or the name, for a bird never banded), the
     * bloodline as a dot and a word, and whether it is an outside bird. Every
     * bird in the tree links to its own page.
     */
    $label = fn ($b) => $b->band_number ?: $b->name;
    $realName = fn ($b) => ($b->band_number && preg_match('/\p{L}/u', (string) $b->name)) ? $b->name : null;

    $parents = [
        ['role' => 'Sire', 'bird' => $bird->sire],
        ['role' => 'Dam', 'bird' => $bird->dam],
    ];

    /*
     * GRANDPARENTS ONLY WHERE RECORDED. The farm asked for empty ancestor
     * columns to go - a chart two-thirds "Not recorded" reads as a page that
     * failed - so the third generation is drawn only when at least one
     * grandparent actually exists in the records, and then only for the
     * parent that has them.
     */
    $grand = fn ($parent) => $parent ? array_filter([
        ['role' => 'Grandsire', 'bird' => $parent->sire],
        ['role' => 'Granddam', 'bird' => $parent->dam],
    ], fn ($g) => $g['bird'] !== null) : [];
    $hasGrandparents = collect($parents)->contains(fn ($p) => count($grand($p['bird'])) > 0);
@endphp

{{--
    THE PEDIGREE TREE - drawn the way a pedigree chart has always been drawn:
    the bird on the left, a bracket, its parents on the right, and their
    parents beyond them when the records have them. Hairlines only; the
    bloodline colour appears as the thin line beside each name.

    Missing data is never invented. A parent the farm has not recorded reads
    "Not recorded", in the muted ink, in the slot it would have filled.
--}}
<div {{ $attributes->merge(['class' => 'sm:overflow-x-auto']) }} data-scroll-x>
    <div @class([
        'grid items-center gap-y-6 sm:min-w-[540px] sm:gap-x-6 sm:gap-y-0',
        'sm:grid-cols-[minmax(0,1fr)_32px_minmax(0,1fr)]' => ! $hasGrandparents,
        'sm:grid-cols-[minmax(0,1fr)_32px_minmax(0,1fr)_32px_minmax(0,1fr)]' => $hasGrandparents,
    ])>
        {{-- The bird. --}}
        <div class="border-l-2 py-1 pl-4" style="border-color: {{ \App\Support\BandTag::hex($bird->bloodline) }}">
            <p class="text-[13px] font-semibold text-muted-foreground">This bird</p>
            <p class="font-display text-[22px] font-semibold tracking-[-0.015em] text-foreground">{{ $label($bird) }}</p>
            <p class="flex items-center gap-2 text-[14px] text-muted-foreground">
                <x-band-dot :bloodline="$bird->bloodline" />{{ $bird->bloodline ?: 'Bloodline not recorded' }}
            </p>
        </div>

        {{-- On a phone the generations stack: the bird, then its parents
             indented beneath it, then their parents. No sideways scroll. --}}
        <div class="hidden sm:contents"><x-pedigree-bracket /></div>

        {{-- The parents. --}}
        <ul class="flex h-full flex-col justify-around gap-4 pl-5 sm:min-h-[200px] sm:gap-6 sm:pl-0">
            @foreach ($parents as $parent)
                <li class="border-l-2 py-1 pl-4"
                    style="border-color: {{ $parent['bird'] ? \App\Support\BandTag::hex($parent['bird']->bloodline) : 'var(--color-border)' }}">
                    <p class="text-[13px] font-semibold text-muted-foreground">{{ $parent['role'] }}</p>
                    @if ($parent['bird'])
                        <a href="{{ route('broodcocks.show', $parent['bird']) }}" wire:navigate
                           class="group inline-flex min-h-11 flex-col justify-center">
                            <span class="font-display text-[18px] font-semibold tracking-[-0.015em] text-foreground group-hover:underline group-hover:underline-offset-4">
                                {{ $label($parent['bird']) }}@if ($realName($parent['bird']))<span class="font-sans text-[15px] font-medium text-muted-foreground"> &nbsp;{{ $realName($parent['bird']) }}</span>@endif
                            </span>
                            <span class="flex items-center gap-2 text-[14px] text-muted-foreground">
                                <x-band-dot :bloodline="$parent['bird']->bloodline" />{{ $parent['bird']->bloodline ?: 'Bloodline not recorded' }}@if ($parent['bird']->is_external)<span>&middot; outside bird</span>@endif
                            </span>
                        </a>
                    @else
                        <p class="min-h-11 py-2 text-[16px] text-muted-foreground">Not recorded</p>
                    @endif
                </li>
            @endforeach
        </ul>

        @if ($hasGrandparents)
            <div class="hidden sm:contents"><x-pedigree-bracket /></div>

            <ul class="flex h-full flex-col justify-around gap-3 pl-10 sm:min-h-[200px] sm:gap-4 sm:pl-0">
                @foreach ($parents as $parent)
                    @foreach ($grand($parent['bird']) as $g)
                        <li class="border-l-2 py-0.5 pl-3" style="border-color: {{ \App\Support\BandTag::hex($g['bird']->bloodline) }}">
                            <p class="text-[12px] font-semibold text-muted-foreground">{{ $g['role'] }} &middot; {{ strtolower($parent['role']) }}'s side</p>
                            <a href="{{ route('broodcocks.show', $g['bird']) }}" wire:navigate
                               class="inline-flex min-h-11 items-center font-display text-[16px] font-semibold tracking-[-0.015em] text-foreground hover:underline hover:underline-offset-4">
                                {{ $label($g['bird']) }}
                            </a>
                        </li>
                    @endforeach
                @endforeach
            </ul>
        @endif
    </div>
</div>
