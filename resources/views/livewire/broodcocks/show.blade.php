@php
    // Property access, not a method call - #[Computed] only memoizes on
    // property access, and this view references the model many times.
    $bird = $this->bird;
    $canSeeInternal = auth()->user()?->can('viewInternalNotes', $bird) ?? false;
    $internal = auth()->user()?->isInternal() ?? false;
    $farm = config('gfms.farm');

    $countOf = fn (string $relation): ?int => $bird->relationLoaded($relation) ? $bird->getRelation($relation)->count() : null;

    // Sections are filtered by role: a visitor sees what the catalogue
    // promises - the pedigree, the particulars, photographs, health and
    // performance. Offspring is the farm's breeding record and stays internal.
    $tabs = collect([
        ['key' => 'overview',    'label' => 'Pedigree',    'internal' => false, 'count' => null],
        ['key' => 'details',     'label' => 'Details',     'internal' => false, 'count' => null],
        ['key' => 'photos',      'label' => 'Photos',      'internal' => false, 'count' => $countOf('photos')],
        ['key' => 'health',      'label' => 'Health',      'internal' => false, 'count' => null],
        ['key' => 'performance', 'label' => 'Performance', 'internal' => false, 'count' => null],
        ['key' => 'offspring',   'label' => 'Offspring',   'internal' => true,  'count' => null],
    ])->reject(fn ($t) => $t['internal'] && ! $canSeeInternal);

    // Built in PHP: an arrow function inside a Blade component attribute does
    // not survive compilation (see .ai/rules/views.md).
    $tabSegments = $tabs->map(fn (array $t): array => ['key' => $t['key'], 'label' => $t['label'], 'count' => $t['count']])->values()->all();

    $title = $bird->band_number ?: $bird->name;
    $realName = ($bird->band_number && preg_match('/\p{L}/u', (string) $bird->name)) ? $bird->name : null;
    $photos = $bird->relationLoaded('photos') ? $bird->photos : collect();

    $ask = $farm['email']
        ? 'mailto:'.$farm['email'].'?subject='.rawurlencode('About '.$title.($bird->bloodline ? ' ('.$bird->bloodline.')' : ''))
        : ($farm['phone'] ? 'tel:'.preg_replace('/[^0-9+]/', '', $farm['phone']) : null);
@endphp

{{--
    A BIRD'S PAGE - the most important page after the front page.

    The photograph large, with the rest of its photographs beneath it; the
    band number in the display face; the particulars as label/value rows; and
    one clear action. Everything else - the pedigree, the full details,
    photographs, health and performance - sits in sections behind a
    segmented control, so the page never becomes one long scroll.

    The same markup serves a visitor (in the public shell) and staff (in the
    console). Staff additionally see Edit, Delete, internal notes and the
    offspring record; a Policy decides each, not a CSS hide.
--}}
<div @class(['sb-wrap pb-16 pt-8 lg:pb-24 lg:pt-10' => ! $internal])>
    <a href="{{ $internal ? route('broodcocks.index') : route('catalog.index') }}" wire:navigate
       class="inline-flex min-h-11 items-center gap-1.5 text-[15px] font-semibold text-primary hover:underline hover:underline-offset-4">
        <span aria-hidden="true">&larr;</span> {{ $internal ? 'Back to broodcocks' : 'Back to the catalogue' }}
    </a>

    <div class="mt-4 grid gap-10 lg:grid-cols-[7fr_5fr] lg:gap-16">
        {{-- THE PHOTOGRAPHS. The main one large; the rest as a strip beneath
             that swaps it in place. With a single photograph there is no
             strip. --}}
        <div x-data="{ shown: 0 }">
            @if ($photos->isNotEmpty())
                @foreach ($photos as $p)
                    <figure class="sb-photo sb-photo-in aspect-[4/5]" x-show="shown === {{ $loop->index }}" @if (! $loop->first) x-cloak @endif>
                        <img src="{{ route('photos.show', $p) }}" alt="{{ $p->caption ?: ($realName ?? 'Broodcock '.$title) }}"
                             width="800" height="1000" decoding="async" @if ($loop->first) fetchpriority="high" @else loading="lazy" @endif>
                        @if ($p->caption)
                            <figcaption class="sb-chip">{{ $p->caption }}</figcaption>
                        @endif
                    </figure>
                @endforeach

                @if ($photos->count() > 1)
                    <ul class="mt-3 grid grid-cols-5 gap-2 sm:grid-cols-6" aria-label="More photographs">
                        @foreach ($photos as $p)
                            <li>
                                <button type="button" x-on:click="shown = {{ $loop->index }}"
                                        x-bind:aria-pressed="(shown === {{ $loop->index }}).toString()"
                                        aria-label="Show photograph {{ $loop->iteration }} of {{ $photos->count() }}"
                                        class="block w-full overflow-hidden rounded-[8px] ring-offset-2 ring-offset-background transition-shadow"
                                        x-bind:class="shown === {{ $loop->index }} ? 'ring-2 ring-foreground' : 'opacity-80 hover:opacity-100'">
                                    <img src="{{ route('photos.show', ['photo' => $p, 'size' => 'thumb']) }}" alt=""
                                         class="aspect-square w-full object-cover" loading="lazy" decoding="async">
                                </button>
                            </li>
                        @endforeach
                    </ul>
                @endif
            @else
                <div class="sb-photo flex aspect-[4/5] flex-col items-center justify-center gap-3 text-[15px] text-muted-foreground">
                    <x-band-dot :bloodline="$bird->bloodline" class="h-3 w-3" />
                    No photograph yet
                </div>
            @endif
        </div>

        {{-- THE IDENTITY. --}}
        {{-- Sticky on desktop, so the particulars and the action stay beside the
             photograph while it scrolls past. --}}
        <div @class(['lg:sticky lg:self-start lg:pt-2', 'lg:top-6' => $internal, 'lg:top-24' => ! $internal])>
            <p class="sb-line flex items-center gap-2 text-[15px] font-semibold text-foreground" style="--line: 0ms">
                <x-band-dot :bloodline="$bird->bloodline" />{{ $bird->bloodline ?: 'Bloodline not recorded' }}
            </p>
            <h1 class="sb-line mt-3 font-display text-[2.6rem] font-semibold leading-[1.02] tracking-[-0.03em] text-foreground sm:text-[3.4rem]" style="--line: 60ms">
                {{ $title }}
            </h1>
            @if ($realName)
                <p class="sb-line mt-1 font-display text-[1.4rem] font-medium tracking-[-0.015em] text-muted-foreground" style="--line: 100ms">{{ $realName }}</p>
            @endif

            <p class="sb-line mt-4 flex flex-wrap items-center gap-2" style="--line: 140ms">
                <span class="badge {{ $bird->status->badgeClasses() }}">{{ $bird->status->label() }}</span>
                @if ($bird->for_sale)
                    <span class="text-[14px] font-semibold text-primary">For sale</span>
                @endif
                @unless ($bird->band_number)
                    <span class="text-[14px] text-muted-foreground">Not yet banded</span>
                @endunless
            </p>

            <dl class="sb-line mt-7 border-t border-border" style="--line: 200ms">
                <x-field-row label="Age"><span class="datum">{{ $bird->ageLabel() ?? 'Unknown' }}</span></x-field-row>
                <x-field-row label="Sex">{{ $bird->sex->farmTerm() }}</x-field-row>
                <x-field-row label="Hatched">
                    @if ($bird->date_hatched)<span class="datum">{{ $bird->date_hatched->format('j F Y') }}</span>@else<span class="text-muted-foreground">Not recorded</span>@endif
                </x-field-row>
                <x-field-row label="Class">{{ $bird->class->label() }}</x-field-row>
            </dl>

            {{-- Internal notes sit with the particulars, on first load, for
                 staff only - never rendered for a visitor or a customer. A
                 Policy check, not a CSS hide. --}}
            @if ($canSeeInternal && $bird->notes)
                <div class="sb-line mt-6" style="--line: 230ms">
                    <h2 class="text-[14px] font-semibold text-muted-foreground">Internal notes</h2>
                    <p class="mt-1 max-w-[60ch] whitespace-pre-line text-[16px] leading-relaxed text-foreground">{{ $bird->notes }}</p>
                </div>
            @endif

            <div class="sb-line mt-8 flex flex-wrap gap-3" style="--line: 260ms">
                @if ($internal)
                    @can('update', $bird)
                        <a href="{{ route('broodcocks.edit', $bird) }}" wire:navigate class="btn-primary">Edit bird</a>
                    @endcan
                    <a href="{{ route('broodcocks.pedigree', $bird) }}" wire:navigate class="btn-secondary">Family Tree</a>
                    @can('delete', $bird)
                        <button type="button" wire:click="confirmDeletion" class="btn-secondary text-destructive">Delete</button>
                    @endcan
                @else
                    @if ($ask)
                        <a href="{{ $ask }}" class="btn-primary">Ask about this bird</a>
                    @endif
                    <a href="{{ route('public.visit') }}" wire:navigate class="btn-secondary">Arrange a visit</a>
                    <a href="{{ route('broodcocks.pedigree', $bird) }}" wire:navigate class="btn-quiet">Family Tree</a>
                @endif
            </div>
        </div>
    </div>

    {{-- Deceased: stated once, plainly. The cause is internal. --}}
    @if ($bird->isDeceased() && $bird->mortalityRecord)
        <div class="mt-10 rounded-[8px] bg-destructive-bg px-5 py-4" role="note">
            <p class="text-[15px] font-semibold text-foreground">
                This bird died on <span class="datum">{{ $bird->mortalityRecord->date_of_death->format('j F Y') }}</span>.
            </p>
            @if ($canSeeInternal)
                <p class="mt-1 text-[15px] text-foreground">Cause: {{ $bird->mortalityRecord->cause_of_death }}</p>
            @endif
        </div>
    @endif

    {{-- THE SECTIONS - a segmented control instead of a long scroll. --}}
    <div class="mt-14 lg:mt-20">
        <x-segmented :segments="$tabSegments" :active="$tab" model="tab" label="Bird record sections" />
    </div>

    <div class="mt-10">
    @if ($tab === 'overview')
        <section aria-labelledby="pedigree-heading" data-reveal>
            <div class="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <h2 id="pedigree-heading" class="sb-h2">Parents</h2>
                    <p class="mt-2 text-[15px] text-muted-foreground">As written in the farm's records. Every bird in the tree opens its own page.</p>
                </div>
                <a href="{{ route('broodcocks.pedigree', $bird) }}" wire:navigate class="sb-link">View full family tree <span aria-hidden="true">&rarr;</span></a>
            </div>
            <x-pedigree-tree :bird="$bird" class="mt-8" />
        </section>
    @endif

    @if ($tab === 'details')
        @php
            $rows = [
                ['Weight', $bird->weight ? $bird->weight.' kg' : null, true],
                ['Colour', $bird->color ?: null, false],
                ['Comb type', $bird->comb_type ?: null, false],
                ['Leg colour', $bird->leg_color ?: null, false],
                ['Acquired', $bird->date_acquired?->format('j F Y'), true],
            ];
        @endphp
        <section class="grid gap-12 lg:grid-cols-2 lg:gap-20" aria-label="Details" data-reveal>
            <dl class="border-t border-border">
                @foreach ($rows as [$label, $value, $tabular])
                    <x-field-row :label="$label">
                        @if ($value !== null)<span @class(['datum' => $tabular])>{{ $value }}</span>@else<span class="text-muted-foreground">Not recorded</span>@endif
                    </x-field-row>
                @endforeach
            </dl>

            <div class="space-y-10">
                <div>
                    <h2 class="text-[15px] font-semibold text-foreground">Distinguishing marks</h2>
                    <p class="mt-2 max-w-[60ch] text-[16px] leading-relaxed {{ $bird->distinguishing_marks ? 'text-foreground' : 'text-muted-foreground' }}">
                        {{ $bird->distinguishing_marks ?: 'Not recorded' }}
                    </p>
                </div>

            </div>
        </section>
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
            <div class="py-10 text-[15px] text-muted-foreground">The health history view is not available yet.</div>
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
            <div class="py-10 text-[15px] text-muted-foreground">The performance timeline is not available yet.</div>
        @endif
    @endif

    {{-- Offspring --}}
    @if ($tab === 'offspring' && $canSeeInternal)
        <div class="overflow-x-auto" data-scroll-x>
            @if ($this->offspring->isEmpty())
                <div class="py-12">
                    <h3 class="sb-h2">No offspring recorded</h3>
                    <p class="mt-3 text-[17px] leading-relaxed text-muted-foreground">
                        Offspring appear here once birds are recorded with {{ $bird->name }}
                        as their {{ $bird->sex->parentTerm() }}.
                    </p>
                </div>
            @else
                <table class="min-w-full divide-y divide-border">
                    <thead class="border-b border-border">
                        <tr>
                            @foreach (['Band Number', 'Name', 'Sex', 'Bloodline', 'Hatched', 'Status'] as $heading)
                                <th scope="col" class="px-4 py-3 text-left text-[13px] font-semibold text-muted-foreground">
                                    {{ $heading }}
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        @foreach ($this->offspring as $child)
                            <tr class="group row-hover">
                                <td class="px-4 py-3 text-sm font-medium">
                                    <a href="{{ route('broodcocks.show', $child) }}" wire:navigate class="text-primary hover:underline">
                                        {{ $child->displayBand() }}
                                    </a>
                                </td>
                                <td class="px-4 py-3 text-sm text-foreground">{{ $child->name }}</td>
                                <td class="px-4 py-3 text-sm text-muted-foreground">{{ $child->sex->label() }}</td>
                                <td class="px-4 py-3 text-sm text-muted-foreground">{{ $child->bloodline ?? 'Not recorded' }}</td>
                                <td class="px-4 py-3 text-sm text-muted-foreground">
                                    {{ $child->date_hatched?->format('j M Y') ?? 'Not recorded' }}
                                </td>
                                <td class="px-4 py-3">
                                    <span class="badge {{ $child->status->badgeClasses() }}">{{ $child->status->label() }}</span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    @endif

    </div>

    {{-- Delete confirmation. Names the bird explicitly and says what is kept,
         because "Are you sure?" tells a worried user nothing. --}}
    @if ($confirmingDeletion)
        {{-- The escape handler read ".querySelector(...)" - a bare leading dot, which
             is invalid JS, so Escape silently did nothing on this dialog. It needs
             $el. Nothing caught it because a broken key handler throws in the browser,
             not in the test suite. --}}
        <div class="dialog-backdrop fixed inset-0 z-50 flex items-center justify-center bg-foreground/60 p-4" x-data x-trap.noscroll="true" @keydown.escape.window="$el.querySelector('.btn-secondary')?.click()" role="dialog" aria-modal="true" aria-labelledby="delete-bird-title">
            <div class="dialog-panel elev-3 w-full max-w-md rounded-[12px] bg-card p-6">
                <h2 id="delete-bird-title" class="font-display text-[22px] font-semibold tracking-[-0.015em] text-foreground">Delete {{ $bird->name }}?</h2>
                <p class="mt-3 text-[15px] leading-relaxed text-muted-foreground">
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
