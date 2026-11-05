@props([
    /** The question or section name the trigger shows. */
    'title',
    /** Opens the item on first render. */
    'open' => false,
])

{{--
    AN ACCORDION ITEM (EMS, Part B §5.14): a native <details>, so Enter and
    Space toggle it and a screen reader announces it as expandable with no
    script. The chevron turns when it opens; the content appears at once
    rather than growing, which the EMS spec allows.
--}}
<details @if ($open) open @endif {{ $attributes->class('group border-b border-border last:border-b-0') }}>
    <summary class="flex min-h-11 cursor-pointer list-none items-start justify-between gap-4 rounded-md py-4 text-left text-sm font-medium text-foreground outline-none transition-all hover:underline focus-visible:ring-[3px] focus-visible:ring-ring/50 lg:min-h-0 [&::-webkit-details-marker]:hidden">
        {{ $title }}
        <x-ui.icon name="chevron-down" class="pointer-events-none size-4 shrink-0 translate-y-0.5 text-muted-foreground transition-transform duration-200 group-open:rotate-180" />
    </summary>
    <div class="pb-4 text-sm text-foreground">
        {{ $slot }}
    </div>
</details>
