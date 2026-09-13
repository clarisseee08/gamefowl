@php
    // Property access, not a method call - #[Computed] only memoizes on
    // property access, and this view references the model many times.
    $bird = $this->bird;
    $canSeeInternal = auth()->user()?->can('viewInternalNotes', $bird) ?? false;

    // Tabs are filtered by role: a customer sees only what the spec promises
    // them - profile, photos, health status and performance history.
    $tabs = collect([
        ['key' => 'overview',    'label' => 'Overview',    'internal' => false],
        ['key' => 'photos',      'label' => 'Photos',      'internal' => false],
        ['key' => 'health',      'label' => 'Health',      'internal' => false],
        ['key' => 'performance', 'label' => 'Performance', 'internal' => false],
        ['key' => 'offspring',   'label' => 'Offspring',   'internal' => true],
    ])->reject(fn ($t) => $t['internal'] && ! $canSeeInternal);
@endphp

<div>
    {{-- Header --}}
    <div class="mb-10">
        {{-- WHERE BACK GOES DEPENDS ON WHO IS LOOKING, because this page is
             public and the broodcock index is not.

             It pointed at broodcocks.index for everyone. A customer reaching a
             bird from the catalogue or the front page therefore met a link
             that bounced them to a login form - the exact thing the sidebar
             comment warns about, that a visitor should never see a door they
             cannot open. Staff keep the console index; everyone else goes back
             to the catalogue, which is where they actually came from. --}}
        @php $backToConsole = auth()->user()?->isInternal() ?? false; @endphp

        <a href="{{ $backToConsole ? route('broodcocks.index') : route('catalog.index') }}" wire:navigate
           class="text-sm font-medium text-primary hover:underline">
            &larr; {{ $backToConsole ? 'Back to broodcocks' : 'Back to the catalogue' }}
        </a>

        <div class="mt-3 sm:flex sm:items-start sm:justify-between">
            <div class="flex items-start gap-4">
                @if ($bird->primaryPhoto)
                    <x-photo-thumb :photo="$bird->primaryPhoto" :alt="'Photo of '.$bird->name"
                                   class="h-20 w-20 shrink-0 rounded-[6px] ring-1 ring-border" />
                @else
                    {{-- Same treatment as the catalogue grid: an empty bordered box says
                         nothing, so the tile carries the bloodline instead. --}}
                    <div class="flex h-20 w-20 shrink-0 items-center justify-center rounded-[6px] ring-1 ring-border"
                         style="background-color: {{ \App\Support\BandTag::hex($bird->bloodline) }}14"
                         aria-hidden="true">
                        <span class="text-[24px] font-medium leading-none opacity-80"
                              style="color: {{ \App\Support\BandTag::hex($bird->bloodline) }}">{{ \App\Support\BandTag::code($bird->bloodline) }}</span>
                    </div>
                @endif

                <div>
                    {{-- The band leads. This is the bird's identity page, and the band
                         is the identifier both a keeper and a buyer actually use. --}}
                    <x-band-tag :bloodline="$bird->bloodline" :band="$bird->band_number" />

                    <h1 class="mt-2 text-[34px] font-semibold tracking-[-0.022em] leading-[1.12] text-foreground">{{ $bird->name }}</h1>

                    <div class="mt-2 flex flex-wrap gap-1.5">
                        <span class="badge {{ $bird->status->badgeClasses() }}">{{ $bird->status->label() }}</span>
                        <span class="badge {{ $bird->class->badgeClasses() }}">{{ $bird->class->label() }}</span>
                        <span class="badge badge-neutral">{{ $bird->sex->farmTerm() }}</span>

                        {{-- Availability was the thirteenth row of a thirteen-row
                             list, reading "For Sale: No" on almost every bird. It
                             is the one fact a customer opens this page to find, so
                             it sits with the other identity badges in the exact
                             vocabulary the catalogue grid already uses: positive
                             case only, because labelling the rule rather than the
                             exception puts a tag on every bird on the farm. --}}
                        @if ($bird->for_sale)
                            <span class="badge badge-ok">For sale</span>
                        @endif
                    </div>
                </div>
            </div>

            <div class="mt-4 flex flex-wrap gap-2 sm:mt-0">
                <a href="{{ route('broodcocks.pedigree', $bird) }}" wire:navigate class="btn-secondary">Family Tree</a>

                @can('update', $bird)
                    <a href="{{ route('broodcocks.edit', $bird) }}" wire:navigate class="btn-secondary">Edit</a>
                @endcan

                @can('delete', $bird)
                    {{-- Deliberately NOT the filled .btn-danger here. A solid crimson
                         button is within a shade of the crimson band colour, and in this
                         system colour means bloodline - a filled crimson control on a
                         bird's own page reads as identity. The filled variant is kept for
                         the confirmation modal, where destroying the record IS the
                         primary action and nothing else competes with it. --}}
                    <button type="button" wire:click="confirmDeletion"
                            class="btn-secondary text-destructive hover:border-destructive">Delete</button>
                @endcan
            </div>
        </div>
    </div>

    {{-- Deceased banner --}}
    @if ($bird->isDeceased() && $bird->mortalityRecord)
        <div class="mb-6 rounded-lg bg-destructive-bg p-4 ring-1 ring-destructive/20">
            <p class="text-sm font-medium text-foreground">
                This bird died on {{ $bird->mortalityRecord->date_of_death->format('j F Y') }}.
            </p>
            @if ($canSeeInternal)
                <p class="mt-1 text-sm text-foreground">Cause: {{ $bird->mortalityRecord->cause_of_death }}</p>
            @endif
        </div>
    @endif

    {{-- Tabs --}}
    <div class="mb-6 border-b border-border">
        <nav class="-mb-px flex flex-wrap gap-x-6" aria-label="Sections">
            @foreach ($tabs as $t)
                <button type="button" wire:click="$set('tab', '{{ $t['key'] }}')"
                        @class([
                            'whitespace-nowrap border-b-2 px-1 py-3 text-sm font-medium transition',
                            'border-primary text-primary' => $tab === $t['key'],
                            'border-transparent text-muted-foreground hover:border-border hover:text-muted-foreground' => $tab !== $t['key'],
                        ])
                        @if ($tab === $t['key']) aria-current="page" @endif>
                    {{ $t['label'] }}
                </button>
            @endforeach
        </nav>
    </div>

    {{-- Overview --}}
    @if ($tab === 'overview')
        {{-- THE RECORD.

             What this replaced is most of the redesign. The old Details list ran
             thirteen rows of equal weight and five of them repeated the header
             word for word: "Name: Tanikala" directly under a 34px <h1> reading
             Tanikala, "Band Number: KL-4003" under a band tag reading KL-4003,
             "Bloodline: Kelso" under the Kelso plate, and Class and Sex under
             the badges that already state them.

             Thirty-eight per cent of the list was restating the header, and the
             cost was not untidiness - it pushed the facts a keeper opens this
             page for (weight, colour, comb, legs, the two dates) below the fold
             on a phone. What remains is grouped by the question it answers and
             set in two narrow columns rather than one wide one, because a label
             and its value have to be trackable across the gap between them. --}}
        <div class="grid gap-6 lg:grid-cols-5">
            <div class="card p-6 sm:p-7 lg:col-span-3">
                @php
                    /*
                     * Nothing here is in the header. Band number, name, bloodline,
                     * class and sex live in the identity block above and are
                     * deliberately absent.
                     *
                     * A row is [label, value, monospaced, what to print when absent].
                     * The value is null when the farm has not recorded it, which is
                     * what lets an absent row set itself in muted ink - the eye
                     * running down the column separates "2.00 kg" from "Not
                     * recorded" without reading either.
                     */
                    $groups = [
                        'Description' => [
                            ['Weight', $bird->weight ? $bird->weight.' kg' : null, true, 'Not recorded'],
                            ['Colour', $bird->color ?: null, false, 'Not recorded'],
                            ['Comb type', $bird->comb_type ?: null, false, 'Not recorded'],
                            ['Leg colour', $bird->leg_color ?: null, false, 'Not recorded'],
                        ],
                        'In the records' => [
                            ['Hatched', $bird->date_hatched?->format('j F Y'), true, 'Not recorded'],
                            ['Acquired', $bird->date_acquired?->format('j F Y'), true, 'Not recorded'],
                            // The catalogue card prints "Unknown" for an age it
                            // cannot derive. The same fact reads the same way on
                            // both surfaces.
                            ['Age', $bird->ageLabel(), true, 'Unknown'],
                        ],
                    ];
                @endphp

                <div class="grid gap-x-10 gap-y-8 sm:grid-cols-2">
                    @foreach ($groups as $heading => $rows)
                        <section>
                            <h2 class="text-[12px] font-medium uppercase tracking-[0.06em] text-muted-foreground">{{ $heading }}</h2>

                            {{-- Label left, value right, hairline between: the rhythm of
                                 the printed record book this system replaces. --}}
                            <dl class="mt-3 divide-y divide-border border-t border-border">
                                @foreach ($rows as [$label, $value, $isDatum, $absent])
                                    <div class="flex items-baseline justify-between gap-4 py-2.5">
                                        <dt class="shrink-0 text-[13px] text-muted-foreground">{{ $label }}</dt>
                                        <dd @class([
                                            'text-right text-[15px]',
                                            'datum' => $isDatum && $value !== null,
                                            'text-foreground' => $value !== null,
                                            'text-muted-foreground' => $value === null,
                                        ])>{{ $value ?? $absent }}</dd>
                                    </div>
                                @endforeach
                            </dl>
                        </section>
                    @endforeach
                </div>

                {{-- Prose runs the full width of the card and is capped at a
                     readable measure rather than at the column. --}}
                @if ($bird->distinguishing_marks)
                    <section class="mt-8">
                        <h2 class="text-[12px] font-medium uppercase tracking-[0.06em] text-muted-foreground">Distinguishing marks</h2>
                        <p class="mt-3 max-w-[60ch] border-t border-border pt-3 text-[15px] leading-relaxed text-foreground">
                            {{ $bird->distinguishing_marks }}
                        </p>
                    </section>
                @endif

                {{-- Internal notes are never rendered for a customer. A Policy
                     check, not a CSS hide. --}}
                @if ($canSeeInternal && $bird->notes)
                    <section class="mt-8">
                        <h2 class="text-[12px] font-medium uppercase tracking-[0.06em] text-muted-foreground">Internal notes</h2>
                        <p class="mt-3 max-w-[60ch] whitespace-pre-line border-t border-border pt-3 text-[15px] leading-relaxed text-foreground">
                            {{ $bird->notes }}
                        </p>
                    </section>
                @endif
            </div>

            {{-- LINEAGE.

                 Each parent is a row carrying its own band, in the grammar the
                 catalogue and the front page already use, so a keeper recognises
                 a sire by the same object everywhere in the application. It was
                 two "Name (BAND)" links under uppercase SIRE (FATHER) and DAM
                 (MOTHER) labels - an arrangement that buries the band, which IS
                 this system's identity channel, and stacks a kicker above a name
                 for no gain. --}}
            <div class="card p-6 sm:p-7 lg:col-span-2">
                <h2 class="text-[12px] font-medium uppercase tracking-[0.06em] text-muted-foreground">Parents</h2>

                <dl class="mt-3 divide-y divide-border border-t border-border">
                    @foreach ([['Sire', $bird->sire], ['Dam', $bird->dam]] as [$role, $parent])
                        <div class="flex items-start gap-3 py-3">
                            <dt class="w-10 shrink-0 pt-px text-[13px] text-muted-foreground">{{ $role }}</dt>
                            <dd class="min-w-0 flex-1">
                                @if ($parent)
                                    <a href="{{ route('broodcocks.show', $parent) }}" wire:navigate
                                       class="group block min-h-11">
                                        <span class="block text-[15px] font-medium text-foreground group-hover:underline">{{ $parent->name }}</span>
                                        <span class="mt-1.5 block">
                                            <x-band-tag :bloodline="$parent->bloodline" :band="$parent->band_number" size="xs" />
                                        </span>
                                    </a>
                                @else
                                    <span class="text-[15px] text-muted-foreground">Not recorded</span>
                                @endif
                            </dd>
                        </div>
                    @endforeach
                </dl>

                <a href="{{ route('broodcocks.pedigree', $bird) }}" wire:navigate class="btn-secondary mt-5 w-full">
                    View full family tree
                </a>
            </div>
        </div>
    @endif

    {{-- Photos --}}
    @if ($tab === 'photos')
        <div class="space-y-5">
            {{--
                The uploader was built, tested and then never put on a page. The
                gallery's own empty state said "Use Add Photos above" while
                nothing above it existed, so adding a photo to a bird was
                reachable only from the test suite.

                It sits above the gallery because Upload dispatches
                `photos-updated` and Gallery listens for it - they were designed
                as a pair and only ever needed to be placed together.
            --}}
            @can('create', App\Models\BroodcockPhoto::class)
                <livewire:photos.upload :broodcock="$bird" :key="'upload-'.$bird->id" />
            @endcan

            <livewire:photos.gallery :broodcock="$bird" :key="'gallery-'.$bird->id" />
        </div>
    @endif

    {{-- Health --}}
    @if ($tab === 'health')
        @if (class_exists(App\Livewire\Health\BroodcockHealthHistory::class))
            <livewire:health.broodcock-health-history :broodcock="$bird" :key="'health-'.$bird->id" />
        @else
            <div class="card p-8 text-center text-sm text-muted-foreground">The health history view is not available yet.</div>
        @endif

        {{-- Performance sits on this tab as well as on its own. A keeper doing a
             weigh-in is already holding the bird, and making them cross to a
             second tab to write the result down is how a reading goes
             unrecorded. It is the same component, so the rules live in one
             place and the two views cannot drift. --}}
        @if (class_exists(App\Livewire\Performance\BroodcockTimeline::class))
            <div class="mt-5">
                <livewire:performance.broodcock-timeline :broodcock="$bird" :key="'perf-on-health-'.$bird->id" />
            </div>
        @endif
    @endif

    {{-- Performance --}}
    @if ($tab === 'performance')
        @if (class_exists(App\Livewire\Performance\BroodcockTimeline::class))
            <livewire:performance.broodcock-timeline :broodcock="$bird" :key="'perf-'.$bird->id" />
        @else
            <div class="card p-8 text-center text-sm text-muted-foreground">The performance timeline is not available yet.</div>
        @endif
    @endif

    {{-- Offspring --}}
    @if ($tab === 'offspring' && $canSeeInternal)
        <div class="card overflow-hidden">
            @if ($this->offspring->isEmpty())
                <div class="p-12 text-center">
                    <h3 class="text-[21px] font-semibold tracking-[-0.01em] leading-[1.25] text-foreground">No offspring recorded</h3>
                    <p class="mt-3 text-[17px] leading-relaxed text-muted-foreground">
                        Offspring appear here once birds are recorded with {{ $bird->name }}
                        as their {{ $bird->sex->parentTerm() }}.
                    </p>
                </div>
            @else
                <table class="min-w-full divide-y divide-border">
                    <thead class="bg-muted">
                        <tr>
                            @foreach (['Band Number', 'Name', 'Sex', 'Bloodline', 'Hatched', 'Status'] as $heading)
                                <th scope="col" class="px-6 py-4 text-left text-[12px] font-medium uppercase tracking-[0.06em] text-muted-foreground">
                                    {{ $heading }}
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border bg-card">
                        @foreach ($this->offspring as $child)
                            <tr class="group row-hover">
                                <td class="px-6 py-4 text-sm font-medium">
                                    <a href="{{ route('broodcocks.show', $child) }}" wire:navigate class="text-primary hover:underline">
                                        {{ $child->displayBand() }}
                                    </a>
                                </td>
                                <td class="px-6 py-4 text-sm text-foreground">{{ $child->name }}</td>
                                <td class="px-6 py-4 text-sm text-muted-foreground">{{ $child->sex->label() }}</td>
                                <td class="px-6 py-4 text-sm text-muted-foreground">{{ $child->bloodline ?? 'Not recorded' }}</td>
                                <td class="px-6 py-4 text-sm text-muted-foreground">
                                    {{ $child->date_hatched?->format('j M Y') ?? 'Not recorded' }}
                                </td>
                                <td class="px-6 py-4">
                                    <span class="badge {{ $child->status->badgeClasses() }}">{{ $child->status->label() }}</span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    @endif

    {{-- Delete confirmation. Names the bird explicitly and says what is kept,
         because "Are you sure?" tells a worried user nothing. --}}
    @if ($confirmingDeletion)
        {{-- The escape handler read ".querySelector(...)" - a bare leading dot, which
             is invalid JS, so Escape silently did nothing on this dialog. It needs
             $el. Nothing caught it because a broken key handler throws in the browser,
             not in the test suite. --}}
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-foreground/70 p-4" x-data x-trap.noscroll="true" @keydown.escape.window="$el.querySelector('.btn-secondary')?.click()" role="dialog" aria-modal="true" aria-labelledby="delete-bird-title">
            <div class="w-full max-w-md rounded-[4px] border border-border bg-card p-6">
                <h2 id="delete-bird-title" class="text-[22px] font-semibold tracking-[-0.01em] leading-[1.2] text-foreground">Delete {{ $bird->name }}?</h2>
                <p class="mt-2 text-sm text-muted-foreground">
                    This will remove <strong>{{ $bird->name }} ({{ $bird->displayBand() }})</strong>
                    from the active records. Its health, breeding and performance history is kept
                    and the deletion is recorded in the activity log, so this can be undone by
                    the system administrator.
                </p>
                <div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                    <button type="button" wire:click="$set('confirmingDeletion', false)" class="btn-secondary">
                        Cancel
                    </button>
                    <button type="button" wire:click="delete" class="btn-danger" wire:loading.attr="disabled">
                        Yes, delete this bird
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
