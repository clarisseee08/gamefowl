@props([
    'size' => 'md',      // sm (topbar) | md (sidebar) | lg (auth)
    'markOnly' => false, // the collapsed rail
    'tone' => 'brand',   // brand (on light) | onDark (on the sidebar)
])

@php
    /*
     * The lockup: mark + wordmark, with a defined minimum below which the
     * wordmark drops and the mark stands alone.
     *
     * Clear space is one mark-height on all sides. It is applied as padding on
     * the wrapper rather than left to whoever places it, because "leave some
     * room around the logo" is the instruction everyone ignores.
     */
    $marks = ['sm' => 'h-5 w-5', 'md' => 'h-7 w-7', 'lg' => 'h-16 w-16'];
    $gaps = ['sm' => 'gap-2', 'md' => 'gap-2.5', 'lg' => 'gap-4'];
    $names = ['sm' => 'text-[14px]', 'md' => 'text-[15px]', 'lg' => 'text-[26px]'];
    $subs = ['sm' => 'text-[11px]', 'md' => 'text-[11px]', 'lg' => 'text-[14px]'];

    // On the red sidebar the mark is the light foreground and the eye is punched
    // through in the surface colour; on light ground it is brand red on paper.
    $markColour = $tone === 'onDark' ? 'text-brand-foreground' : 'text-brand';
    $eye = $tone === 'onDark' ? 'var(--color-brand-deep)' : 'var(--color-card)';
    $nameColour = $tone === 'onDark' ? 'text-brand-foreground' : 'text-foreground';
    $subColour = $tone === 'onDark' ? 'text-brand-muted-fg' : 'text-muted-foreground';

    $lockupName = $size === 'lg' ? config('gfms.system.name') : config('gfms.system.short');
    $lockupSub = $size === 'lg' ? config('gfms.system.tagline') : config('gfms.farm.name');

    /*
     * On this installation gfms.system.short IS the farm's name, so the md
     * lockup printed "SSGuad Game Farm" twice, one line above the other. Same
     * collision head-meta hit when it appended system.short to a page title
     * that was already the farm name.
     *
     * Compared rather than special-cased, so an installation where the two
     * genuinely differ still gets both lines.
     */
    if ($lockupSub === $lockupName) {
        $lockupSub = null;
    }
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center '.$gaps[$size]]) }}>
    <x-brand-mark :size="$size === 'lg' ? 64 : ($size === 'md' ? 28 : 20)" />

    @unless ($markOnly)
        <span class="min-w-0">
            <span class="block truncate {{ $names[$size] }} font-semibold leading-tight {{ $nameColour }}">
                {{ $lockupName }}
            </span>
            @if ($lockupSub)
                <span class="block truncate {{ $subs[$size] }} leading-tight {{ $subColour }}">
                    {{ $lockupSub }}
                </span>
            @endif
        </span>
    @endunless

    <span class="sr-only">{{ config('gfms.system.name') }}</span>
</span>
