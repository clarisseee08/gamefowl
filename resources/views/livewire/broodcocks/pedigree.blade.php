@php
    // Property access (not method calls) so Livewire's #[Computed] memoization
    // actually applies - otherwise the whole tree is re-queried per reference.
    $root = $this->root;
    $generations = $this->generations;
    $completeness = $this->completeness;
    $labels = ['This Bird', 'Parents', 'Grandparents', 'Great-Grandparents'];
@endphp

<div>
    <div class="mb-10 sm:flex sm:items-center sm:justify-between">
        <div>
            <h1 class="text-[34px] font-semibold tracking-[-0.022em] leading-[1.12] text-ink">
                Family Tree &mdash; {{ $root->name }}
            </h1>
            <p class="mt-3 text-[17px] leading-relaxed text-ink-48">
                Three generations of ancestors, built from the sire and dam recorded on each bird.
            </p>
        </div>
        <a href="{{ route('broodcocks.show', $broodcock) }}" class="btn-secondary mt-4 sm:mt-0">
            Back to bird
        </a>
    </div>

    {{-- Completeness meter: tells the user how much of the tree is actually
         recorded, so an empty branch reads as "not entered yet" rather than
         "the system is broken". --}}
    <div class="card mb-10 p-6">
        <div class="flex items-center justify-between text-sm">
            <span class="font-medium text-ink">Pedigree recorded</span>
            <span class="text-ink-80">
                {{ $completeness['known'] }} of {{ $completeness['total'] }} ancestors
                ({{ $completeness['percent'] }}%)
            </span>
        </div>
        <div class="mt-2 h-2 w-full overflow-hidden rounded-full bg-parchment">
            <div class="h-full rounded-full bg-action" style="width: {{ $completeness['percent'] }}%"></div>
        </div>
        @if ($completeness['known'] === 0)
            <p class="mt-3 text-sm text-ink-80">
                No parents have been recorded for this bird yet.
                @can('update', $broodcock)
                    <a href="{{ route('broodcocks.edit', $broodcock) }}" class="font-medium text-action hover:underline">
                        Edit this bird
                    </a>
                    to add its sire and dam.
                @endcan
            </p>
        @endif
    </div>

    {{-- The chart. Scrolls horizontally on small screens rather than
         squashing - a pedigree bracket is inherently wide. --}}
    <div class="card overflow-x-auto p-4 sm:p-6">
        <div class="flex min-w-max items-stretch gap-7">
            @foreach ($generations as $index => $column)
                <div class="flex flex-col" style="min-width: 13rem;">
                    <p class="mb-3 text-center text-[11px] font-medium uppercase tracking-[0.06em] text-ink-48">
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
                                            <a href="{{ route('broodcocks.show', $ancestor) }}"
                                               @class([
                                                   'block rounded-[4px] border p-3 transition hover:border-ink-48',
                                                   'border-rule-strong bg-pearl' => $index === 0,
                                                   'border-hairline bg-canvas' => $index > 0,
                                               ])>
                                                <x-band-tag :bloodline="$ancestor->bloodline"
                                                            :band="$ancestor->band_number" size="xs" />
                                                <p class="mt-2 truncate text-[15px] font-medium leading-snug text-ink">
                                                    {{ $ancestor->name }}
                                                </p>
                                                {{-- The root is the subject of the tree, not
                                                     somebody's parent - calling it "Sire"
                                                     here is just wrong. It gets its plain sex. --}}
                                                <p class="mt-0.5 truncate text-[12px] text-ink-48">
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

    <p class="mt-4 text-[13px] text-ink-48">
        In every pair the sire is above the dam, and each card says which.
        Card colour is the bloodline band, not the sex. Click any bird to open its own record.
    </p>
</div>
