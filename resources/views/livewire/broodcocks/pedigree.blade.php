@php
    // Property access (not method calls) so Livewire's #[Computed] memoization
    // actually applies - otherwise the whole tree is re-queried per reference.
    $root = $this->root;
    $generations = $this->generations;
    $completeness = $this->completeness;
    $internal = auth()->user()?->isInternal() ?? false;

    $title = $root->band_number ?: $root->name;
    $realName = ($root->band_number && preg_match('/\p{L}/u', (string) $root->name)) ? $root->name : null;
@endphp

{{--
    THE FAMILY TREE (EMS) - the stud-book page.

    Drawn as a family tree: the bird at the foot, its sire and dam above it,
    their parents above them, every card joined to the bird it descends to.
    Each card carries the bird's photograph and opens its record. An ancestor
    the farm has not recorded is an empty card in the slot it would have
    filled - never invented, never a blank. A generation beyond the parents
    appears only when something in it is recorded (Pedigree::generations).

    Renders in both shells; the public one gives the page its own measure.
--}}
<div @class(['sb-wrap pb-16 pt-8 lg:pb-24 lg:pt-10' => ! $internal])>
    <nav aria-label="Breadcrumb" class="text-[13px] text-muted-foreground lg:text-xs">
        <ol class="flex flex-wrap items-center gap-1.5">
            @if ($internal)
                <li><a href="{{ route('broodcocks.index') }}" wire:navigate class="hover:text-foreground">Gamefowl</a></li>
            @else
                <li><a href="{{ route('catalog.index') }}" wire:navigate class="hover:text-foreground">Catalogue</a></li>
            @endif
            <li aria-hidden="true"><x-ui.icon name="chevron-right" class="size-3.5" /></li>
            <li><a href="{{ route('broodcocks.show', $broodcock) }}" wire:navigate class="hover:text-foreground">{{ $title }}</a></li>
            <li aria-hidden="true"><x-ui.icon name="chevron-right" class="size-3.5" /></li>
            <li class="font-medium text-foreground" aria-current="page">Family Tree</li>
        </ol>
    </nav>

    <x-ui.page-header class="mt-3" :title="'Family Tree · '.$title.($realName ? ' '.$realName : '')">
        <span class="inline-flex items-center gap-1.5 text-foreground">
            <x-band-dot :bloodline="$root->bloodline" />{{ $root->bloodline ?: 'Bloodline not recorded' }}
        </span>

        <x-slot:actions>
            <x-ui.button variant="outline" :href="route('broodcocks.show', $broodcock)" wire:navigate>View Bird</x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    {{-- How much of the tree is recorded, so an empty card reads as "not
         entered yet" rather than "the system is broken". "2 of 2" is ONE
         figure in one .datum span: PedigreePerformanceTest asserts the ratio
         as a string. --}}
    <x-ui.card class="mt-5">
        <x-ui.card.content class="flex flex-col gap-3 sm:flex-row sm:items-center sm:gap-6">
            <div class="shrink-0">
                <p class="text-sm font-semibold text-foreground">Pedigree Recorded</p>
                <p class="text-[13px] text-muted-foreground lg:text-xs">
                    <span class="datum font-semibold text-foreground">{{ $completeness['known'] }} of {{ $completeness['total'] }}</span> ancestors
                    (<span class="datum">{{ $completeness['percent'] }}%</span>)
                </p>
            </div>
            <progress class="d-progress d-progress-primary w-full" value="{{ $completeness['percent'] }}" max="100"
                      role="img" aria-label="{{ $completeness['percent'] }}% of the pedigree recorded"></progress>
        </x-ui.card.content>
        @if ($completeness['known'] === 0)
            <x-ui.card.footer>
                <p class="text-[13px] leading-relaxed text-muted-foreground lg:text-xs">
                    No parents have been recorded for this bird yet.
                    @can('update', $broodcock)
                        <a href="{{ route('broodcocks.edit', $broodcock) }}" wire:navigate class="d-link d-link-primary font-semibold">Edit this bird</a>
                        to add its sire and dam.
                    @endcan
                </p>
            </x-ui.card.footer>
        @endif
    </x-ui.card>

    <x-ui.card class="mt-4" :flush="true">
        <section aria-label="Family tree" class="overflow-x-auto px-5 py-8" data-scroll-x>
            <div class="mx-auto w-max">
                <x-family-tree.branch :generations="$generations" />
            </div>
        </section>
        <p class="border-t border-border px-5 py-3 text-[13px] leading-relaxed text-muted-foreground lg:text-xs">
            The bird is at the foot, its sire on the left and its dam on the right, with their own parents above them.
            The coloured edge on each card is its bloodline's band colour, not its sex. Every card opens that bird's own record.
        </p>
    </x-ui.card>
</div>
