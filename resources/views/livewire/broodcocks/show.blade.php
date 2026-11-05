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
        ['key' => 'performance', 'label' => 'Performance', 'internal' => false, 'count' => null, 'cocksOnly' => true],
        ['key' => 'offspring',   'label' => 'Offspring',   'internal' => true,  'count' => null],
    ])->reject(fn ($t) => ($t['internal'] && ! $canSeeInternal) || (($t['cocksOnly'] ?? false) && ! $this->hasPerformance()));

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
<div @class(['sb-wrap pb-16 pt-8 lg:pb-20 lg:pt-10' => ! $internal])>
    {{-- ONE LAYOUT (EMS) for staff and visitors: a trail back to the list,
         the page header with the bird's actions, then the photograph and the
         particulars side by side as cards. What differs is the trail's first
         step (the console list, or the catalogue a visitor can open) and the
         actions - staff edit and delete, a visitor asks or arranges a visit.
         Delete sits in the ⋯ menu, away from Edit. --}}
    <nav aria-label="Breadcrumb" @class(['text-[13px] text-muted-foreground', 'lg:text-xs' => $internal])>
        <ol class="flex flex-wrap items-center gap-1.5">
            @if ($internal)
                <li><a href="{{ route('broodcocks.index') }}" wire:navigate class="hover:text-foreground">Gamefowl</a></li>
            @else
                <li><a href="{{ route('catalog.index') }}" wire:navigate class="inline-flex min-h-9 items-center hover:text-foreground">Our gamefowl</a></li>
            @endif
            <li aria-hidden="true"><x-ui.icon name="chevron-right" class="size-3.5" /></li>
            <li class="font-medium text-foreground" aria-current="page">{{ $title }}</li>
        </ol>
    </nav>

    <x-ui.page-header :class="$internal ? 'mt-3' : 'mt-2'" :title="$realName ? $title.' · '.$realName : $title">
        <span class="flex flex-wrap items-center gap-2">
            <span class="inline-flex items-center gap-1.5 text-foreground">
                <x-band-dot :bloodline="$bird->bloodline" />{{ $bird->bloodline ?: 'Bloodline not recorded' }}
            </span>
            <span class="d-badge d-badge-soft {{ $bird->status->badgeClasses() }}">{{ $bird->status->label() }}</span>
            @if ($bird->for_sale)
                <span class="font-semibold text-primary">For sale</span>
            @endif
            @unless ($bird->band_number)
                <span>Not yet banded</span>
            @endunless
        </span>

        <x-slot:actions>
            @if ($internal)
                <x-ui.button variant="outline" :href="route('broodcocks.pedigree', $bird)" wire:navigate>Family Tree</x-ui.button>
                @can('update', $bird)
                    <x-ui.button :href="route('broodcocks.edit', $bird)" x-on:click.prevent="$dispatch('ems-dialog', 'broodcocks-form')">
                        <x-ui.icon name="pencil" /> Edit Bird
                    </x-ui.button>
                @endcan
                @can('delete', $bird)
                    <x-ui.dropdown-menu align="end" :label="'More actions for '.$bird->name" trigger-variant="button">
                        <x-slot:trigger aria-label="More actions" class="px-2.5 lg:px-2"><x-ui.icon name="ellipsis" /></x-slot:trigger>
                        <x-ui.dropdown-menu.item icon="trash-2" :destructive="true" wire:click="confirmDeletion">Delete Bird</x-ui.dropdown-menu.item>
                    </x-ui.dropdown-menu>
                @endcan
            @else
                <x-ui.button variant="ghost" :href="route('broodcocks.pedigree', $bird)" wire:navigate>Family Tree</x-ui.button>
                <x-ui.button variant="outline" :href="route('public.visit')" wire:navigate>
                    <x-ui.icon name="map-pin" /> Arrange a visit
                </x-ui.button>
                @if ($ask)
                    <x-ui.button :href="$ask">
                        <x-ui.icon name="message-square" /> Ask about this bird
                    </x-ui.button>
                @endif
            @endif
        </x-slot:actions>
    </x-ui.page-header>

        <div class="mt-5 grid gap-4 lg:grid-cols-3">
            {{-- The photographs: the main one, with the rest as a strip that
                 swaps it in place. Without one, staff are offered the way to
                 add it rather than a large empty panel. --}}
            <x-ui.card :flush="true" x-data="{ shown: 0 }">
                @if ($photos->isNotEmpty())
                    @foreach ($photos as $p)
                        <figure class="aspect-[4/5] max-h-[28rem] w-full overflow-hidden bg-muted" x-show="shown === {{ $loop->index }}" @if (! $loop->first) x-cloak @endif>
                            <img src="{{ route('photos.show', $p) }}" alt="{{ $p->caption ?: ($realName ?? 'Broodcock '.$title) }}"
                                 width="800" height="1000" decoding="async" class="h-full w-full object-cover" @if ($loop->first) fetchpriority="high" @else loading="lazy" @endif>
                        </figure>
                    @endforeach

                    @if ($photos->count() > 1)
                        <ul class="grid grid-cols-5 gap-2 p-3" aria-label="More photographs">
                            @foreach ($photos as $p)
                                <li>
                                    <button type="button" x-on:click="shown = {{ $loop->index }}"
                                            x-bind:aria-pressed="(shown === {{ $loop->index }}).toString()"
                                            aria-label="Show photograph {{ $loop->iteration }} of {{ $photos->count() }}"
                                            class="block w-full overflow-hidden rounded-md ring-offset-2 ring-offset-card transition-shadow"
                                            x-bind:class="shown === {{ $loop->index }} ? 'ring-2 ring-primary' : 'opacity-80 hover:opacity-100'">
                                        <img src="{{ route('photos.show', ['photo' => $p, 'size' => 'thumb']) }}" alt=""
                                             class="aspect-square w-full object-cover" loading="lazy" decoding="async">
                                    </button>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                @else
                    <div class="flex aspect-[4/3] flex-col items-center justify-center gap-2 bg-muted/60 p-6 text-center lg:aspect-auto lg:h-full lg:min-h-64">
                        <x-band-dot :bloodline="$bird->bloodline" class="h-3 w-3" />
                        <p @class(['text-sm font-medium text-foreground', 'lg:text-xs' => $internal])>No photograph yet</p>
                        @can('create', App\Models\BroodcockPhoto::class)
                            <x-ui.button variant="outline" class="mt-2" wire:click="$set('tab', 'photos')">
                                <x-ui.icon name="plus" /> Add Photos
                            </x-ui.button>
                        @endcan
                    </div>
                @endif
            </x-ui.card>

            <x-ui.card class="lg:col-span-2">
                <x-ui.card.header>
                    <x-ui.card.title>Particulars</x-ui.card.title>
                    <x-ui.card.description>As written in the farm’s records</x-ui.card.description>
                </x-ui.card.header>
                <x-ui.card.content>
                    @php
                        $particulars = [
                            ['Age', $bird->ageLabel(), true],
                            ['Sex', $bird->sex->farmTerm(), false],
                            ['Hatched', $bird->date_hatched?->format('j M Y'), true],
                            ['Class', $bird->class->label(), false],
                            ['Weight', $bird->weight ? $bird->weight.' kg' : null, true],
                            ['Acquired', $bird->date_acquired?->format('j M Y'), true],
                        ];
                    @endphp
                    <dl class="grid grid-cols-2 gap-3 sm:grid-cols-3">
                        @foreach ($particulars as [$label, $value, $tabular])
                            <div class="rounded-md bg-muted/60 px-3 py-2.5">
                                <dt @class(['text-xs text-muted-foreground', 'lg:text-[11px]' => $internal])>{{ $label }}</dt>
                                <dd @class(['mt-0.5 text-sm', 'lg:text-xs' => $internal, 'datum' => $tabular && $value !== null, 'font-semibold text-foreground' => $value !== null, 'text-muted-foreground' => $value === null])>{{ $value ?? 'Not recorded' }}</dd>
                            </div>
                        @endforeach
                    </dl>

                    {{-- Internal notes: staff only, by Policy - never rendered
                         for a visitor or a customer. --}}
                    @if ($canSeeInternal && $bird->notes)
                        <div class="mt-5 border-t border-border pt-4">
                            <h2 class="text-xs font-semibold text-muted-foreground lg:text-[11px]">Internal Notes</h2>
                            <p class="mt-1 max-w-[70ch] whitespace-pre-line text-sm leading-relaxed text-foreground lg:text-xs">{{ $bird->notes }}</p>
                        </div>
                    @endif
                </x-ui.card.content>
            </x-ui.card>
        </div>

    {{-- Deceased: stated once, plainly. The cause is internal. --}}
    @if ($bird->isDeceased() && $bird->mortalityRecord)
        <div class="d-alert d-alert-soft d-alert-error mt-5 flex flex-col items-start gap-0 text-base-content" role="note">
            <p class="text-[15px] font-semibold text-foreground">
                This bird died on <span class="datum">{{ $bird->mortalityRecord->date_of_death->format('j F Y') }}</span>.
            </p>
            @if ($canSeeInternal)
                <p class="mt-1 text-[15px] text-foreground">Cause: {{ $bird->mortalityRecord->cause_of_death }}</p>
            @endif
        </div>
    @endif

    {{-- THE SECTIONS - EMS's underline tabs instead of a long scroll. --}}
    <div class="mt-6">
        <x-ui.tabs :segments="$tabSegments" :active="$tab" model="tab" label="Bird record sections" />
    </div>

    {{-- A tab's content waits for the server; the tab itself does not. While
         it loads, the shape of a section stands in for it. --}}
    <div wire:loading.flex wire:target="tab" class="mt-5 hidden flex-col gap-4 rounded-md border border-border bg-card p-5" aria-busy="true">
        <span class="sr-only" role="status">Loading the section&hellip;</span>
        <x-ui.skeleton class="h-4 w-40" />
        <x-ui.skeleton class="h-3 w-64" />
        <div class="mt-2 grid grid-cols-2 gap-3 sm:grid-cols-3">
            @for ($i = 0; $i < 6; $i++)
                <x-ui.skeleton class="h-14 w-full" />
            @endfor
        </div>
    </div>

    <div wire:loading.remove wire:target="tab" class="mt-5">
    @if ($tab === 'overview')
        <x-ui.card>
            <x-ui.card.header>
                <x-ui.card.title>Parents</x-ui.card.title>
                <x-ui.card.description>As written in the farm’s records. Every bird in the tree opens its own page</x-ui.card.description>
                <x-ui.card.action>
                    <x-ui.button variant="ghost" :href="route('broodcocks.pedigree', $bird)" wire:navigate>
                        View Full Family Tree <x-ui.icon name="arrow-up-right" />
                    </x-ui.button>
                </x-ui.card.action>
            </x-ui.card.header>
            <x-ui.card.content>
                <x-pedigree-tree :bird="$bird" :compact="true" />
            </x-ui.card.content>
        </x-ui.card>
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
        <div class="grid gap-4 lg:grid-cols-3">
            <x-ui.card class="lg:col-span-2">
                <x-ui.card.header>
                    <x-ui.card.title>Appearance and Records</x-ui.card.title>
                </x-ui.card.header>
                <x-ui.card.content>
                    <dl class="grid grid-cols-2 gap-3 sm:grid-cols-3">
                        @foreach ($rows as [$label, $value, $tabular])
                            <div class="rounded-md bg-muted/60 px-3 py-2.5">
                                <dt @class(['text-xs text-muted-foreground', 'lg:text-[11px]' => $internal])>{{ $label }}</dt>
                                <dd @class(['mt-0.5 text-sm', 'lg:text-xs' => $internal, 'datum' => $tabular && $value !== null, 'font-semibold text-foreground' => $value !== null, 'text-muted-foreground' => $value === null])>{{ $value ?? 'Not recorded' }}</dd>
                            </div>
                        @endforeach
                    </dl>
                </x-ui.card.content>
            </x-ui.card>

            <x-ui.card>
                <x-ui.card.header>
                    <x-ui.card.title>Distinguishing Marks</x-ui.card.title>
                </x-ui.card.header>
                <x-ui.card.content>
                    <p @class(['whitespace-pre-line text-sm leading-relaxed', 'lg:text-xs' => $internal, 'text-foreground' => $bird->distinguishing_marks, 'text-muted-foreground' => ! $bird->distinguishing_marks])>{{ $bird->distinguishing_marks ?: 'Not recorded' }}</p>
                </x-ui.card.content>
            </x-ui.card>
        </div>
    @endif

    {{-- Photos --}}
    @if ($tab === 'photos')
        <div class="space-y-4">
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
                <div class="rounded-md border border-border bg-card p-5">
                    <livewire:photos.upload :broodcock="$bird" :key="'upload-'.$bird->id" />
                </div>
            @endcan

            <div class="rounded-md border border-border bg-card p-5">
                <livewire:photos.gallery :broodcock="$bird" :key="'gallery-'.$bird->id" />
            </div>
        </div>
    @endif

    {{-- Health --}}
    @if ($tab === 'health')
        @if (class_exists(App\Livewire\Health\BroodcockHealthHistory::class))
            <livewire:health.broodcock-health-history :broodcock="$bird" :key="'health-'.$bird->id" />
        @else
            <div class="py-10 text-[15px] text-muted-foreground">The health history view is not available yet.</div>
        @endif
    @endif

    {{-- Performance --}}
    @if ($tab === 'performance' && $this->hasPerformance())
        @if (class_exists(App\Livewire\Performance\BroodcockTimeline::class))
            <livewire:performance.broodcock-timeline :broodcock="$bird" :key="'perf-'.$bird->id" />
        @else
            <div class="py-10 text-[15px] text-muted-foreground">The performance timeline is not available yet.</div>
        @endif
    @endif

    {{-- Offspring --}}
    @if ($tab === 'offspring' && $canSeeInternal)
        @php
            $children = $this->offspring;
        @endphp
        <x-ui.card :flush="true">
            <x-ui.card.header class="py-5">
                <x-ui.card.title>Offspring</x-ui.card.title>
                <x-ui.card.description>
                    @if ($children->isEmpty())
                        Birds recorded with {{ $bird->name }} as their {{ $bird->sex->parentTerm() }}
                    @else
                        <span class="datum font-semibold text-foreground">{{ $children->count() }}</span>
                        {{ Str::plural('bird', $children->count()) }} with {{ $bird->name }} as their {{ $bird->sex->parentTerm() }}
                    @endif
                </x-ui.card.description>
            </x-ui.card.header>

            @if ($children->isEmpty())
                <div class="border-t border-border px-5 py-10 text-center">
                    <h3 class="text-sm font-semibold text-foreground">No offspring recorded</h3>
                    <p class="mt-1 text-[14px] leading-relaxed text-muted-foreground lg:text-xs">
                        Offspring appear here once birds are recorded with {{ $bird->name }}
                        as their {{ $bird->sex->parentTerm() }}.
                    </p>
                </div>
            @else
                <div class="overflow-x-auto border-t border-border" data-scroll-x>
                    <table class="d-table w-full">
                        <thead>
                            <tr class="border-b border-border">
                                @foreach (['Band number', 'Name', 'Sex', 'Hatched', 'Status'] as $heading)
                                    <th scope="col" class="px-5 text-[13px] font-medium text-muted-foreground lg:text-xs">{{ $heading }}</th>
                                @endforeach
                                <th scope="col" class="px-5 text-right text-[13px] font-medium text-muted-foreground lg:text-xs">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($children as $child)
                                <tr class="group row-hover border-b border-border last:border-b-0">
                                    <td class="px-5">
                                        <a href="{{ route('broodcocks.show', $child) }}" wire:navigate
                                           class="inline-flex items-center gap-2 text-sm font-semibold text-foreground hover:text-primary lg:text-xs">
                                            <x-band-dot :bloodline="$child->bloodline" />
                                            <span @class(['datum' => $child->band_number])>{{ $child->displayBand() }}</span>
                                        </a>
                                    </td>
                                    <td class="px-5 text-sm text-foreground lg:text-xs">{{ $child->name }}</td>
                                    <td class="px-5 text-sm text-muted-foreground lg:text-xs">{{ $child->sex->label() }}</td>
                                    <td class="px-5 text-sm text-muted-foreground lg:text-xs">
                                        @if ($child->date_hatched)
                                            <span class="datum">{{ $child->date_hatched->format('j M Y') }}</span>
                                        @else
                                            Not recorded
                                        @endif
                                    </td>
                                    <td class="px-5">
                                        <span class="d-badge d-badge-soft {{ $child->status->badgeClasses() }}">{{ $child->status->label() }}</span>
                                    </td>
                                    <td class="px-5 text-right">
                                        <x-ui.row-action icon="eye" :label="'View '.$child->name" :href="route('broodcocks.show', $child)" wire:navigate />
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-ui.card>
    @endif

    </div>

    {{-- Delete confirmation. Names the bird explicitly and says what is kept,
         because "Are you sure?" tells a worried user nothing. --}}
    @if ($confirmingDeletion)
        {{-- The escape handler read ".querySelector(...)" - a bare leading dot, which
             is invalid JS, so Escape silently did nothing on this dialog. It needs
             $el. Nothing caught it because a broken key handler throws in the browser,
             not in the test suite. --}}
        <div class="d-modal d-modal-open" x-data x-trap.noscroll="true" @keydown.escape.window="$el.querySelector('.d-btn-outline')?.click()" role="dialog" aria-modal="true" aria-labelledby="delete-bird-title">
            <div class="d-modal-box elev-3 w-full max-w-md">
                <h2 id="delete-bird-title" class="font-display text-[22px] font-semibold tracking-[-0.015em] text-foreground">Delete {{ $bird->name }}?</h2>
                <p class="mt-3 text-[15px] leading-relaxed text-muted-foreground">
                    This will remove <strong>{{ $bird->name }} ({{ $bird->displayBand() }})</strong>
                    from the active records. Its health, breeding and performance history is kept
                    and the deletion is recorded in the activity log, so this can be undone by
                    the system administrator.
                </p>
                <div class="d-modal-action flex-col-reverse gap-3 sm:flex-row">
                    <button type="button" wire:click="$set('confirmingDeletion', false)" class="d-btn d-btn-outline">
                        Cancel
                    </button>
                    <button type="button" wire:click="delete" class="d-btn d-btn-error" wire:loading.attr="disabled">
                        Yes, delete this bird
                    </button>
                </div>
            </div>
        </div>
    @endif

    @can('update', $this->broodcock)
        {{-- Edit opens here, in a dialog over this page. --}}
        <x-ui.form-dialog id="broodcocks-form" model="showForm" size="xl" :wrap="false" :title="'Edit '.$this->broodcock->name">
            <livewire:broodcocks.form :broodcock="$this->broodcock" :in-dialog="true" :key="'broodcocks-show-form-'.$formNonce" />
        </x-ui.form-dialog>
    @endcan
</div>
