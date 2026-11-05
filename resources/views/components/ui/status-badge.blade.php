@props([
    /**
     * success · warning · danger · info · neutral · primary - or an enum's
     * badgeClasses() value ('d-badge-success' …), which maps to the same.
     */
    'tone' => 'neutral',
    /** sm · md (default) · lg */
    'size' => 'md',
    /** A leading dot in the badge's own colour. */
    'dot' => false,
    /** The rare filled badge. */
    'solid' => false,
])

{{--
    THE STATUS BADGE (EMS StatusBadge, Part B §5.11). Every status or enum
    value renders through this, never as coloured text on its own.

    EMS's SHAPE, GFMS's COLOURS - the owner's decision. The five enums still
    say which colour a value is (badgeClasses()), and that is passed straight
    in as the tone; what changed is the shape: a bordered, rounded 11px
    label. Each tone is a measured status pair from the palette, which also
    fixes the red: destructive text on its own tint clears 4.5:1 where
    daisyUI's soft red measured 4.48.

    The text always says the status. Colour is never the only signal.
--}}
@php
    $key = str_replace('d-badge-', '', (string) $tone);
    $key = ['error' => 'danger'][$key] ?? $key;

    $tinted = [
        'success' => 'border-success/20 bg-success-bg text-success',
        'warning' => 'border-warning/20 bg-warning-bg text-warning',
        'danger' => 'border-destructive/20 bg-destructive-bg text-destructive',
        'info' => 'border-info/20 bg-info-bg text-info',
        'primary' => 'border-primary/20 bg-primary/10 text-primary',
        'neutral' => 'border-border bg-muted text-muted-foreground',
    ];

    $filled = [
        'success' => 'border-success bg-success text-success-content',
        'warning' => 'border-warning bg-warning text-warning-content',
        'danger' => 'border-destructive bg-destructive text-destructive-foreground',
        'info' => 'border-info bg-info text-info-content',
        'primary' => 'border-primary bg-primary text-primary-foreground',
        'neutral' => 'border-foreground bg-foreground text-background',
    ];

    $sizes = [
        'sm' => 'gap-1 px-2 py-[0.115rem] text-[11px]',
        'md' => 'gap-1 px-2.5 py-0.5 text-[11px]',
        'lg' => 'gap-1.5 px-3 py-1 text-[12px]',
    ];
@endphp

<span {{ $attributes->class([
    'inline-flex w-fit shrink-0 cursor-default items-center justify-center overflow-hidden whitespace-nowrap rounded-md border font-medium shadow-none select-none transition-all duration-200',
    $sizes[$size] ?? $sizes['md'],
    ($solid ? $filled : $tinted)[$key] ?? ($solid ? $filled : $tinted)['neutral'],
]) }}>
    @if ($dot)
        <span @class(['shrink-0 rounded-full bg-current', $size === 'lg' ? 'size-2' : 'size-1.5']) aria-hidden="true"></span>
    @endif
    {{ $slot }}
</span>
