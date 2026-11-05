{{--
    A SKELETON BLOCK (EMS, Part B §5.22): a flat muted shape that pulses,
    sized like the content it stands in for - text h-4, a label h-3, a KPI
    figure h-6 w-20. No shimmer. The region holding skeletons carries
    aria-busy="true" and announces loading once; the blocks themselves are
    silent.
--}}
<div {{ $attributes->class('animate-pulse rounded-md bg-muted') }} aria-hidden="true"></div>
