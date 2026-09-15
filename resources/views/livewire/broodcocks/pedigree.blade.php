@php
    // Property access (not method calls) so Livewire's #[Computed] memoization
    // actually applies - otherwise the whole tree is re-queried per reference.
    $root = $this->root;
    $generations = $this->generations;
    $completeness = $this->completeness;
    // Two columns, because config('gfms.pedigree_generations') is 1. This array
    // is the limit on how deep the chart can be LABELLED - the loop below falls
    // back to "Generation 3" if the config outgrows it, which is legible but not
    // what anyone wants on screen. Extend this in the same commit as the config.
    $labels = ['This Bird', 'Parents'];
@endphp

<div>
    <div class="mb-10 sm:flex sm:items-center sm:justify-between">
        <div>
            <h1 class="page-title-marked text-[26px] font-semibold leading-[1.2] text-foreground">
                Family Tree &mdash; {{ $root->name }}
            </h1>
            <p class="mt-1 max-w-[68ch] text-[14px] leading-relaxed text-muted-foreground">
                The sire and dam recorded on this bird.
            </p>
        </div>
        <a href="{{ route('broodcocks.show', $broodcock) }}" wire:navigate class="btn-secondary mt-4 sm:mt-0">
            Back to bird
        </a>
    </div>

    {{-- Completeness meter: tells the user how much of the tree is actually
         recorded, so an empty branch reads as "not entered yet" rather than
         "the system is broken". --}}
    <div class="card mb-10 p-6">
        <div class="flex items-center justify-between text-sm">
            <span class="font-medium text-foreground">Pedigree recorded</span>
            {{-- "3 of 14" is ONE figure and sits in one .datum span rather than
                 two with a proportional "of" between them. Two spans set the two
                 numbers in mono and the word joining them in Inter, which makes a
                 single ratio look like two unrelated counts - and it also split a
                 string PedigreePerformanceTest asserts, which is the suite doing
                 its job: visible copy is this project's test API. --}}
            <span class="text-muted-foreground">
                <span class="datum">{{ $completeness['known'] }} of {{ $completeness['total'] }}</span> ancestors
                (<span class="datum">{{ $completeness['percent'] }}%</span>)
            </span>
        </div>
        <div class="meter mt-2.5">
            <span class="bg-primary" style="width: {{ $completeness['percent'] }}%"></span>
        </div>
        @if ($completeness['known'] === 0)
            <p class="mt-3 text-sm text-muted-foreground">
                No parents have been recorded for this bird yet.
                @can('update', $broodcock)
                    <a href="{{ route('broodcocks.edit', $broodcock) }}" wire:navigate class="font-medium text-primary hover:underline">
                        Edit this bird
                    </a>
                    to add its sire and dam.
                @endcan
            </p>
        @endif
    </div>

    {{-- The chart.

         CAPPED AND LEFT-ALIGNED, NOT CENTRED. Four columns filled any monitor
         and two do not, so the obvious move was an auto horizontal margin
         against this same width cap, to sit the short bracket in the middle of
         the card. DesignSystemGuardTest refuses that pairing, and it is right
         to: a centred max-width column is the single thing that reads as a web
         page pasted into an application, and the rule does not stop applying
         because this particular column happens to be short. The cap stays - two
         columns should not stretch across a 1900px monitor either - and the
         alignment is the same left edge as every other console screen.

         (Written the long way round on purpose. That guard greps the file as
         text, comments included, so naming the two utilities side by side here
         would trip it on the prose explaining why they are not used.)

         The columns take min-widths rather than a fixed 13rem so the bracket
         fits a 390px phone without scrolling; it no longer has four columns to
         find room for. overflow-x-auto stays as the backstop, which is the one
         sanctioned way for a diagram to be wider than its page. --}}
    <div class="card overflow-x-auto p-3 sm:p-6">
        <div class="flex max-w-2xl items-stretch gap-5 sm:gap-10">
            @foreach ($generations as $index => $column)
                <div class="flex min-w-[9.5rem] flex-1 flex-col sm:min-w-[13rem]">
                    <p class="mb-3 text-center text-[11px] font-medium uppercase tracking-[0.06em] text-muted-foreground">
                        {{ $labels[$index] ?? 'Generation '.$index }}
                    </p>

                    <div class="flex flex-1 flex-col justify-around gap-2">
                        {{-- Ancestors are chunked into sire/dam pairs so each pair can
                             carry one bracket spine back toward its descendant. Without
                             the bracket you cannot tell which grandparent belongs to
                             which parent, which is the question this screen exists to
                             answer. Generation 0 is the bird itself and has no pair. --}}
                        @php
                            $cards = $column instanceof \Illuminate\Support\Collection ? $column->all() : (array) $column;
                            $pairs = array_chunk($cards, $index === 0 ? 1 : 2);
                        @endphp
                        @foreach ($pairs as $pair)
                            <div @class(['ped-branch' => $index > 0, 'flex flex-col justify-around gap-2' => $index === 0])>
                                @foreach ($pair as $ancestor)
                                    <div @class(['ped-node' => $index > 0])>
                                        @if ($ancestor)
                                            <a href="{{ route('broodcocks.show', $ancestor) }}" wire:navigate
                                               {{-- transition-colors, not the bare `transition`
                                                    shorthand. That one also animates box-shadow,
                                                    transform, filter and backdrop-filter - four
                                                    properties this card never changes and two the
                                                    motion whitelist does not allow it to. The only
                                                    thing moving here is a border colour. --}}
                                               @class([
                                                   'block rounded-[4px] border p-3 transition-colors hover:border-muted-foreground',
                                                   'border-border bg-muted' => $index === 0,
                                                   'border-border bg-card' => $index > 0,
                                               ])>
                                                <x-band-tag :bloodline="$ancestor->bloodline"
                                                            :band="$ancestor->band_number" size="xs" />
                                                <p class="mt-2 truncate text-[15px] font-medium leading-snug text-foreground">
                                                    {{ $ancestor->name }}
                                                </p>
                                                {{-- The root is the subject of the tree, not
                                                     somebody's parent - calling it "Sire"
                                                     here is just wrong. It gets its plain sex.

                                                     The second half of this comment used to
                                                     argue for ink-80 over ink-48 on the pearl
                                                     ground. None of those three tokens exist:
                                                     pearl, ink-48 and ink-80 were all retired
                                                     with the field-ledger direction, and the
                                                     class here has been muted-foreground for as
                                                     long as this file has compiled. A comment
                                                     defending a decision in a vocabulary the
                                                     code no longer speaks is worse than none. --}}
                                                <p class="mt-0.5 truncate text-[12px] text-muted-foreground">
                                                    {{ $index === 0 ? $ancestor->sex->label() : $ancestor->sex->parentTerm() }}@if ($ancestor->bloodline) &middot; {{ $ancestor->bloodline }}@endif
                                                </p>
                                            </a>
                                        @else
                                            <div class="ped-empty">Not recorded</div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <p class="mt-4 text-[13px] text-muted-foreground">
        The sire is above the dam, and each card says which.
        Card colour is the bloodline band, not the sex. Click any bird to open its own record.
    </p>
</div>
