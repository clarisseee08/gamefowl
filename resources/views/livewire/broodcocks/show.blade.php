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
        <a href="{{ route('broodcocks.index') }}" class="text-sm font-medium text-action hover:underline">
            &larr; Back to broodcocks
        </a>

        <div class="mt-3 sm:flex sm:items-start sm:justify-between">
            <div class="flex items-start gap-4">
                @if ($bird->primaryPhoto)
                    <x-photo-thumb :photo="$bird->primaryPhoto" :alt="'Photo of '.$bird->name"
                                   class="h-20 w-20 shrink-0 rounded-[6px] ring-1 ring-hairline" />
                @else
                    {{-- Same treatment as the catalogue grid: an empty bordered box says
                         nothing, so the tile carries the bloodline instead. --}}
                    <div class="flex h-20 w-20 shrink-0 items-center justify-center rounded-[6px] ring-1 ring-hairline"
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

                    <h1 class="mt-2 text-[34px] font-semibold tracking-[-0.022em] leading-[1.12] text-ink">{{ $bird->name }}</h1>

                    <div class="mt-2 flex flex-wrap gap-1.5">
                        <span class="badge {{ $bird->status->badgeClasses() }}">{{ $bird->status->label() }}</span>
                        <span class="badge {{ $bird->class->badgeClasses() }}">{{ $bird->class->label() }}</span>
                        <span class="badge badge-neutral">{{ $bird->sex->farmTerm() }}</span>
                    </div>
                </div>
            </div>

            <div class="mt-4 flex flex-wrap gap-2 sm:mt-0">
                <a href="{{ route('broodcocks.pedigree', $bird) }}" class="btn-secondary">Family Tree</a>

                @can('update', $bird)
                    <a href="{{ route('broodcocks.edit', $bird) }}" class="btn-secondary">Edit</a>
                @endcan

                @can('delete', $bird)
                    {{-- Deliberately NOT the filled .btn-danger here. A solid crimson
                         button is within a shade of the crimson band colour, and in this
                         system colour means bloodline - a filled crimson control on a
                         bird's own page reads as identity. The filled variant is kept for
                         the confirmation modal, where destroying the record IS the
                         primary action and nothing else competes with it. --}}
                    <button type="button" wire:click="confirmDeletion"
                            class="btn-secondary text-alert hover:border-alert">Delete</button>
                @endcan
            </div>
        </div>
    </div>

    {{-- Deceased banner --}}
    @if ($bird->isDeceased() && $bird->mortalityRecord)
        <div class="mb-6 rounded-lg bg-alert-wash p-4 ring-1 ring-alert/20">
            <p class="text-sm font-medium text-alert">
                This bird died on {{ $bird->mortalityRecord->date_of_death->format('j F Y') }}.
            </p>
            @if ($canSeeInternal)
                <p class="mt-1 text-sm text-alert">Cause: {{ $bird->mortalityRecord->cause_of_death }}</p>
            @endif
        </div>
    @endif

    {{-- Tabs --}}
    <div class="mb-6 border-b border-hairline">
        <nav class="-mb-px flex flex-wrap gap-x-6" aria-label="Sections">
            @foreach ($tabs as $t)
                <button type="button" wire:click="$set('tab', '{{ $t['key'] }}')"
                        @class([
                            'whitespace-nowrap border-b-2 px-1 py-3 text-sm font-medium transition',
                            'border-action text-action' => $tab === $t['key'],
                            'border-transparent text-ink-48 hover:border-hairline hover:text-ink-80' => $tab !== $t['key'],
                        ])
                        @if ($tab === $t['key']) aria-current="page" @endif>
                    {{ $t['label'] }}
                </button>
            @endforeach
        </nav>
    </div>

    {{-- Overview --}}
    @if ($tab === 'overview')
        <div class="grid gap-6 lg:grid-cols-3">
            <div class="card p-6 lg:col-span-2">
                <h2 class="text-[21px] font-semibold tracking-[-0.01em] leading-[1.25] text-ink">Details</h2>
                {{-- A ruled ledger rather than thirteen floating pairs. Label left,
                     value right, hairline between: the rhythm of the printed record
                     book this system replaces, and it gives the eye a single column
                     to run down instead of a zig-zag across a two-column grid.
                     The boolean marks a registry value - it renders in mono with
                     tabular figures so dates and weights align down the column. --}}
                <dl class="mt-4 divide-y divide-hairline border-t border-hairline">
                    @foreach ([
                        ['Band Number', $bird->displayBand(), true],
                        ['Name', $bird->name, false],
                        ['Sex', $bird->sex->label(), false],
                        ['Breed', $bird->breed ?: 'Not recorded', false],
                        ['Bloodline', $bird->bloodline ?: 'Not recorded', false],
                        ['Class', $bird->class->label(), false],
                        ['Age', $bird->ageLabel() ?? 'Unknown (no hatch date)', true],
                        ['Date Hatched', $bird->date_hatched?->format('j F Y') ?? 'Not recorded', true],
                        ['Date Acquired', $bird->date_acquired?->format('j F Y') ?? 'Not recorded', true],
                        ['Weight', $bird->weight ? $bird->weight.' kg' : 'Not recorded', true],
                        ['Colour', $bird->color ?: 'Not recorded', false],
                        ['Comb Type', $bird->comb_type ?: 'Not recorded', false],
                        ['Leg Colour', $bird->leg_color ?: 'Not recorded', false],
                    ] as [$label, $value, $isDatum])
                        <div class="flex items-baseline justify-between gap-6 py-2.5">
                            <dt class="shrink-0 text-[13px] text-ink-48">{{ $label }}</dt>
                            <dd class="{{ $isDatum ? 'datum' : '' }} text-right text-[15px] text-ink">{{ $value }}</dd>
                        </div>
                    @endforeach

                    @if ($bird->distinguishing_marks)
                        <div class="py-2.5">
                            <dt class="text-[13px] text-ink-48">Distinguishing Marks</dt>
                            <dd class="mt-1 text-[15px] text-ink">{{ $bird->distinguishing_marks }}</dd>
                        </div>
                    @endif

                    {{-- Internal notes are never rendered for a customer. This is
                         a Policy check, not just a CSS hide. --}}
                    @if ($canSeeInternal && $bird->notes)
                        <div class="py-2.5">
                            <dt class="text-[13px] text-ink-48">Internal Notes</dt>
                            <dd class="mt-1 whitespace-pre-line text-[15px] text-ink">{{ $bird->notes }}</dd>
                        </div>
                    @endif
                </dl>
            </div>

            <div class="space-y-10">
                <div class="card p-8">
                    <h2 class="text-[21px] font-semibold tracking-[-0.01em] leading-[1.25] text-ink">Parents</h2>
                    <div class="mt-4 space-y-3">
                        @foreach ([['Sire (Father)', $bird->sire], ['Dam (Mother)', $bird->dam]] as [$label, $parent])
                            <div>
                                <p class="text-[12px] font-medium uppercase tracking-[0.06em] text-ink-48">{{ $label }}</p>
                                @if ($parent)
                                    <a href="{{ route('broodcocks.show', $parent) }}" class="text-sm font-medium text-action hover:underline">
                                        {{ $parent->name }} ({{ $parent->displayBand() }})
                                    </a>
                                @else
                                    <p class="text-sm text-ink-48">Not recorded</p>
                                @endif
                            </div>
                        @endforeach
                    </div>
                    <a href="{{ route('broodcocks.pedigree', $bird) }}" class="btn-secondary mt-4 w-full">
                        View full family tree
                    </a>
                </div>

                @if ($canSeeInternal)
                    <div class="card p-8">
                        <h2 class="text-[21px] font-semibold tracking-[-0.01em] leading-[1.25] text-ink">Housing</h2>
                        <p class="mt-2 text-sm text-ink">
                            @if ($bird->pen && Route::has('pens.show'))
                                <a href="{{ route('pens.show', $bird->pen) }}" class="font-medium text-action hover:underline">
                                    {{ $bird->pen->code }} &mdash; {{ $bird->pen->name }}
                                </a>
                            @elseif ($bird->pen)
                                <span class="font-medium">{{ $bird->pen->code }} &mdash; {{ $bird->pen->name }}</span>
                            @else
                                <span class="text-ink-48">Not assigned to a pen</span>
                            @endif
                        </p>
                    </div>
                @endif
            </div>
        </div>
    @endif

    {{-- Photos --}}
    @if ($tab === 'photos')
        @if (class_exists(App\Livewire\Photos\Gallery::class))
            <livewire:photos.gallery :broodcock="$bird" :key="'gallery-'.$bird->id" />
        @else
            <div class="card p-8 text-center text-sm text-ink-48">The photo gallery is not available yet.</div>
        @endif
    @endif

    {{-- Health --}}
    @if ($tab === 'health')
        @if (class_exists(App\Livewire\Health\BroodcockHealthHistory::class))
            <livewire:health.broodcock-health-history :broodcock="$bird" :key="'health-'.$bird->id" />
        @else
            <div class="card p-8 text-center text-sm text-ink-48">The health history view is not available yet.</div>
        @endif
    @endif

    {{-- Performance --}}
    @if ($tab === 'performance')
        @if (class_exists(App\Livewire\Performance\BroodcockTimeline::class))
            <livewire:performance.broodcock-timeline :broodcock="$bird" :key="'perf-'.$bird->id" />
        @else
            <div class="card p-8 text-center text-sm text-ink-48">The performance timeline is not available yet.</div>
        @endif
    @endif

    {{-- Offspring --}}
    @if ($tab === 'offspring' && $canSeeInternal)
        <div class="card overflow-hidden">
            @if ($this->offspring->isEmpty())
                <div class="p-12 text-center">
                    <h3 class="text-[21px] font-semibold tracking-[-0.01em] leading-[1.25] text-ink">No offspring recorded</h3>
                    <p class="mt-3 text-[17px] leading-relaxed text-ink-48">
                        Offspring appear here once birds are recorded with {{ $bird->name }}
                        as their {{ $bird->sex->parentTerm() }}.
                    </p>
                </div>
            @else
                <table class="min-w-full divide-y divide-divider">
                    <thead class="bg-pearl">
                        <tr>
                            @foreach (['Band Number', 'Name', 'Sex', 'Bloodline', 'Hatched', 'Status'] as $heading)
                                <th scope="col" class="px-6 py-4 text-left text-[12px] font-medium uppercase tracking-[0.06em] text-ink-80">
                                    {{ $heading }}
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-divider bg-white">
                        @foreach ($this->offspring as $child)
                            <tr class="hover:bg-pearl">
                                <td class="px-6 py-4 text-sm font-medium">
                                    <a href="{{ route('broodcocks.show', $child) }}" class="text-action hover:underline">
                                        {{ $child->displayBand() }}
                                    </a>
                                </td>
                                <td class="px-6 py-4 text-sm text-ink">{{ $child->name }}</td>
                                <td class="px-6 py-4 text-sm text-ink-80">{{ $child->sex->label() }}</td>
                                <td class="px-6 py-4 text-sm text-ink-80">{{ $child->bloodline ?? '—' }}</td>
                                <td class="px-6 py-4 text-sm text-ink-80">
                                    {{ $child->date_hatched?->format('j M Y') ?? '—' }}
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
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-ink/70 p-4" x-data x-trap.noscroll="true" @keydown.escape.window="$el.querySelector('.btn-secondary')?.click()" role="dialog" aria-modal="true" aria-labelledby="delete-bird-title">
            <div class="w-full max-w-md rounded-[4px] border border-rule-strong bg-canvas p-6">
                <h2 id="delete-bird-title" class="text-[22px] font-semibold tracking-[-0.01em] leading-[1.2] text-ink">Delete {{ $bird->name }}?</h2>
                <p class="mt-2 text-sm text-ink-80">
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
