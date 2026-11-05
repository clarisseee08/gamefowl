@props([
    'value' => 0,
    /** neutral · active (on a primary item) · alert (needs attention) */
    'tone' => 'neutral',
])

{{--
    A COUNT PILL (EMS, Part B §5.11) - the number beside a tab, a nav item or
    a header control. Not a badge: it counts, it does not classify. Over 99 it
    reads "99+". 11px, where EMS sets 9px: nothing on the console is smaller.
--}}
<span {{ $attributes->class([
    'datum inline-flex h-[18px] min-w-[18px] items-center justify-center rounded-full px-1 text-[11px] leading-none font-semibold',
    [
        'neutral' => 'bg-primary/10 text-primary',
        'active' => 'bg-primary-foreground/20 text-primary-foreground',
        'alert' => 'bg-destructive text-destructive-foreground',
    ][$tone] ?? 'bg-primary/10 text-primary',
]) }}>{{ (int) $value > 99 ? '99+' : (int) $value }}</span>
