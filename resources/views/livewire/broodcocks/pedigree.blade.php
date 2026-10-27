@php
    // Property access (not method calls) so Livewire's #[Computed] memoization
    // actually applies - otherwise the whole tree is re-queried per reference.
    $root = $this->root;
    $generations = $this->generations;
    $completeness = $this->completeness;
    $internal = auth()->user()?->isInternal() ?? false;

    // Two columns, because config('gfms.pedigree_generations') is 1. This array
    // is the limit on how deep the chart can be LABELLED - the loop below falls
    // back to "Generation 3" if the config outgrows it, which is legible but not
    // what anyone wants on screen. Extend this in the same commit as the config.
    $labels = ['This bird', 'Parents'];

    $title = $root->band_number ?: $root->name;
    $realName = ($root->band_number && preg_match('/\p{L}/u', (string) $root->name)) ? $root->name : null;

    /*
     * The grid: a column per generation with a 32px bracket lane between
     * each. Written as an inline template rather than a utility because the
     * column count comes from config - a class name built at runtime is
     * absent from the production CSS bundle.
     */
    $template = implode(' 32px ', array_fill(0, count($generations), 'minmax(0,1fr)'));
@endphp

{{--
    THE FAMILY TREE - the stud-book page.

    The same look as the bird page's pedigree: the bird on the left, a
    hairline bracket, its parents to the right, each name in the display face
    with its bloodline as the thin line beside it. Every bird links to its own
    page. A parent the farm has not recorded reads "Not recorded" in the slot
    it would have filled - never invented, never a blank.

    Renders in both shells. In the public one the page needs its own measure
    and breathing room; the console already provides both.
--}}
<div @class(['sb-wrap pb-16 pt-8 lg:pb-24 lg:pt-10' => ! $internal])>
    <a href="{{ route('broodcocks.show', $broodcock) }}" wire:navigate
       class="inline-flex min-h-11 items-center gap-1.5 text-[15px] font-semibold text-primary hover:underline hover:underline-offset-4">
        <span aria-hidden="true">&larr;</span> Back to {{ $title }}
    </a>

    <header class="mt-4 border-b border-border pb-8">
        <p class="sb-line flex items-center gap-2 text-[15px] font-semibold text-foreground" style="--line: 0ms">
            <x-band-dot :bloodline="$root->bloodline" />{{ $root->bloodline ?: 'Bloodline not recorded' }}
        </p>
        <h1 class="sb-line mt-3 font-display text-[2.2rem] font-semibold leading-[1.04] tracking-[-0.03em] text-foreground sm:text-[2.8rem]" style="--line: 60ms">
            <span class="sr-only">Family tree of </span>{{ $title }}
        </h1>
        <p class="sb-line mt-1 font-display text-[1.25rem] font-medium tracking-[-0.015em] text-muted-foreground" style="--line: 100ms">
            {{ $realName ? $realName.' · ' : '' }}Family tree
        </p>

        {{-- How much of the tree is actually recorded, so an empty branch reads
             as "not entered yet" rather than "the system is broken".

             "1 of 2" is ONE figure in one .datum span, not two numbers with a
             proportional "of" between them. PedigreePerformanceTest asserts
             the ratio as a string, which is visible copy doing its job as the
             test API. --}}
        <div class="sb-line mt-8 max-w-md" style="--line: 160ms">
            <div class="flex items-baseline justify-between gap-4 text-[15px]">
                <span class="font-semibold text-foreground">Pedigree recorded</span>
                <span class="text-muted-foreground">
                    <span class="datum text-foreground">{{ $completeness['known'] }} of {{ $completeness['total'] }}</span> ancestors
                    (<span class="datum">{{ $completeness['percent'] }}%</span>)
                </span>
            </div>
            <div class="meter mt-2.5" role="img" aria-label="{{ $completeness['percent'] }}% of the pedigree recorded">
                <span class="bg-foreground" style="width: {{ $completeness['percent'] }}%"></span>
            </div>
            @if ($completeness['known'] === 0)
                <p class="mt-3 text-[15px] leading-relaxed text-muted-foreground">
                    No parents have been recorded for this bird yet.
                    @can('update', $broodcock)
                        <a href="{{ route('broodcocks.edit', $broodcock) }}" wire:navigate class="font-semibold text-primary underline underline-offset-4">Edit this bird</a>
                        to add its sire and dam.
                    @endcan
                </p>
            @endif
        </div>
    </header>

    {{-- THE CHART.

         Left-aligned and capped, like every console screen: two columns should
         not stretch across a 1900px monitor, and centring the short bracket
         would read as a web page pasted into an application. overflow-x-auto
         is the backstop for a deeper config on a narrow phone - the one
         sanctioned way for a diagram to be wider than its page. --}}
    <section class="mt-10" aria-label="Family tree" data-reveal>
        <div class="overflow-x-auto" data-scroll-x>
            <div class="grid min-w-[340px] max-w-3xl items-stretch gap-x-3 sm:gap-x-6" style="grid-template-columns: {{ $template }}">
                @foreach ($generations as $index => $column)
                    @php
                        $slots = $column instanceof \Illuminate\Support\Collection ? $column->all() : (array) $column;
                    @endphp

                    {{-- The bracket lane before every generation after the first:
                         one bracket per sire/dam pair, so each pair visibly
                         belongs to the bird it descends to. --}}
                    @if ($index > 0)
                        <div class="flex flex-col justify-around" style="grid-row: 2; grid-column: {{ $index * 2 }}">
                            @for ($pair = 0; $pair < intdiv(count($slots), 2); $pair++)
                                <x-pedigree-bracket />
                            @endfor
                        </div>
                    @endif

                    <p class="pb-4 text-[13px] font-semibold text-muted-foreground" style="grid-row: 1; grid-column: {{ $index * 2 + 1 }}">
                        {{ $labels[$index] ?? 'Generation '.$index }}
                    </p>

                    <ul class="flex min-h-[220px] flex-col justify-around gap-6" style="grid-row: 2; grid-column: {{ $index * 2 + 1 }}">
                        @foreach ($slots as $slot => $ancestor)
                            @php
                                $role = $index === 0
                                    ? ($ancestor?->sex->label() ?? 'This bird')
                                    : ($slot % 2 === 0 ? 'Sire' : 'Dam');
                            @endphp
                            <li class="border-l-2 py-1 pl-3 sm:pl-4"
                                style="border-color: {{ $ancestor ? \App\Support\BandTag::hex($ancestor->bloodline) : 'var(--color-border)' }}">
                                <p class="text-[13px] font-semibold text-muted-foreground">{{ $role }}</p>

                                @if ($ancestor)
                                    @php
                                        $nodeTitle = $ancestor->band_number ?: $ancestor->name;
                                        $nodeName = ($ancestor->band_number && preg_match('/\p{L}/u', (string) $ancestor->name)) ? $ancestor->name : null;
                                    @endphp
                                    <a href="{{ route('broodcocks.show', $ancestor) }}" wire:navigate
                                       class="group inline-flex min-h-11 max-w-full flex-col justify-center">
                                        <span @class([
                                            'break-words font-display font-semibold tracking-[-0.015em] text-foreground group-hover:underline group-hover:underline-offset-4',
                                            'text-[22px]' => $index === 0,
                                            'text-[18px]' => $index > 0,
                                        ])>{{ $nodeTitle }}</span>
                                        @if ($nodeName)
                                            <span class="break-words text-[15px] font-medium text-muted-foreground">{{ $nodeName }}</span>
                                        @endif
                                        <span class="mt-0.5 flex flex-wrap items-center gap-x-2 text-[14px] text-muted-foreground">
                                            <x-band-dot :bloodline="$ancestor->bloodline" />{{ $ancestor->bloodline ?: 'Bloodline not recorded' }}@if ($ancestor->is_external)<span>&middot; outside bird</span>@endif
                                        </span>
                                    </a>
                                @else
                                    <p class="min-h-11 py-2 text-[16px] text-muted-foreground">Not recorded</p>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                @endforeach
            </div>
        </div>

        <p class="mt-8 max-w-[60ch] text-[14px] leading-relaxed text-muted-foreground">
            The sire is above the dam, and each entry says which. The line beside each name is its
            bloodline's band colour, not its sex. Every bird opens its own record.
        </p>
    </section>
</div>
