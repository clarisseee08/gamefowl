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
    <div class="mb-6">
        <a href="{{ route('broodcocks.index') }}" class="text-sm font-medium text-brand-700 hover:text-brand-800">
            &larr; Back to broodcocks
        </a>

        <div class="mt-3 sm:flex sm:items-start sm:justify-between">
            <div class="flex items-start gap-4">
                <x-photo-thumb :photo="$bird->primaryPhoto" :alt="'Photo of '.$bird->name"
                               class="h-20 w-20 shrink-0 rounded-xl ring-1 ring-gray-200" />

                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-gray-900">{{ $bird->name }}</h1>
                    <p class="text-sm text-gray-600">{{ $bird->displayBand() }}</p>
                    <div class="mt-2 flex flex-wrap gap-1.5">
                        <span class="badge {{ $bird->status->badgeClasses() }}">{{ $bird->status->label() }}</span>
                        <span class="badge {{ $bird->class->badgeClasses() }}">{{ $bird->class->label() }}</span>
                        <span class="badge bg-gray-100 text-gray-700 ring-gray-500/20">{{ $bird->sex->farmTerm() }}</span>
                    </div>
                </div>
            </div>

            <div class="mt-4 flex flex-wrap gap-2 sm:mt-0">
                <a href="{{ route('broodcocks.pedigree', $bird) }}" class="btn-secondary">Family Tree</a>

                @can('update', $bird)
                    <a href="{{ route('broodcocks.edit', $bird) }}" class="btn-secondary">Edit</a>
                @endcan

                @can('delete', $bird)
                    <button type="button" wire:click="confirmDeletion" class="btn-danger">Delete</button>
                @endcan
            </div>
        </div>
    </div>

    {{-- Deceased banner --}}
    @if ($bird->isDeceased() && $bird->mortalityRecord)
        <div class="mb-6 rounded-lg bg-rose-50 p-4 ring-1 ring-rose-200">
            <p class="text-sm font-medium text-rose-900">
                This bird died on {{ $bird->mortalityRecord->date_of_death->format('j F Y') }}.
            </p>
            @if ($canSeeInternal)
                <p class="mt-1 text-sm text-rose-800">Cause: {{ $bird->mortalityRecord->cause_of_death }}</p>
            @endif
        </div>
    @endif

    {{-- Tabs --}}
    <div class="mb-6 border-b border-gray-200">
        <nav class="-mb-px flex flex-wrap gap-x-6" aria-label="Sections">
            @foreach ($tabs as $t)
                <button type="button" wire:click="$set('tab', '{{ $t['key'] }}')"
                        @class([
                            'whitespace-nowrap border-b-2 px-1 py-3 text-sm font-medium transition',
                            'border-brand-600 text-brand-700' => $tab === $t['key'],
                            'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700' => $tab !== $t['key'],
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
                <h2 class="text-base font-semibold text-gray-900">Details</h2>
                <dl class="mt-4 grid gap-x-6 gap-y-4 sm:grid-cols-2">
                    @foreach ([
                        'Band Number' => $bird->displayBand(),
                        'Name' => $bird->name,
                        'Sex' => $bird->sex->label(),
                        'Breed' => $bird->breed ?: 'Not recorded',
                        'Bloodline' => $bird->bloodline ?: 'Not recorded',
                        'Class' => $bird->class->label(),
                        'Age' => $bird->ageLabel() ?? 'Unknown (no hatch date)',
                        'Date Hatched' => $bird->date_hatched?->format('j F Y') ?? 'Not recorded',
                        'Date Acquired' => $bird->date_acquired?->format('j F Y') ?? 'Not recorded',
                        'Weight' => $bird->weight ? $bird->weight.' kg' : 'Not recorded',
                        'Colour' => $bird->color ?: 'Not recorded',
                        'Comb Type' => $bird->comb_type ?: 'Not recorded',
                        'Leg Colour' => $bird->leg_color ?: 'Not recorded',
                    ] as $label => $value)
                        <div>
                            <dt class="text-xs font-medium uppercase tracking-wide text-gray-500">{{ $label }}</dt>
                            <dd class="mt-0.5 text-sm text-gray-900">{{ $value }}</dd>
                        </div>
                    @endforeach

                    @if ($bird->distinguishing_marks)
                        <div class="sm:col-span-2">
                            <dt class="text-xs font-medium uppercase tracking-wide text-gray-500">Distinguishing Marks</dt>
                            <dd class="mt-0.5 text-sm text-gray-900">{{ $bird->distinguishing_marks }}</dd>
                        </div>
                    @endif

                    {{-- Internal notes are never rendered for a customer. This is
                         a Policy check, not just a CSS hide. --}}
                    @if ($canSeeInternal && $bird->notes)
                        <div class="sm:col-span-2">
                            <dt class="text-xs font-medium uppercase tracking-wide text-gray-500">Internal Notes</dt>
                            <dd class="mt-0.5 whitespace-pre-line text-sm text-gray-900">{{ $bird->notes }}</dd>
                        </div>
                    @endif
                </dl>
            </div>

            <div class="space-y-6">
                <div class="card p-6">
                    <h2 class="text-base font-semibold text-gray-900">Parents</h2>
                    <div class="mt-4 space-y-3">
                        @foreach ([['Sire (Father)', $bird->sire], ['Dam (Mother)', $bird->dam]] as [$label, $parent])
                            <div>
                                <p class="text-xs font-medium uppercase tracking-wide text-gray-500">{{ $label }}</p>
                                @if ($parent)
                                    <a href="{{ route('broodcocks.show', $parent) }}" class="text-sm font-medium text-brand-700 hover:text-brand-800">
                                        {{ $parent->name }} ({{ $parent->displayBand() }})
                                    </a>
                                @else
                                    <p class="text-sm text-gray-500">Not recorded</p>
                                @endif
                            </div>
                        @endforeach
                    </div>
                    <a href="{{ route('broodcocks.pedigree', $bird) }}" class="btn-secondary mt-4 w-full">
                        View full family tree
                    </a>
                </div>

                @if ($canSeeInternal)
                    <div class="card p-6">
                        <h2 class="text-base font-semibold text-gray-900">Housing</h2>
                        <p class="mt-2 text-sm text-gray-900">
                            @if ($bird->pen && Route::has('pens.show'))
                                <a href="{{ route('pens.show', $bird->pen) }}" class="font-medium text-brand-700 hover:text-brand-800">
                                    {{ $bird->pen->code }} &mdash; {{ $bird->pen->name }}
                                </a>
                            @elseif ($bird->pen)
                                <span class="font-medium">{{ $bird->pen->code }} &mdash; {{ $bird->pen->name }}</span>
                            @else
                                <span class="text-gray-500">Not assigned to a pen</span>
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
            <div class="card p-8 text-center text-sm text-gray-500">The photo gallery is not available yet.</div>
        @endif
    @endif

    {{-- Health --}}
    @if ($tab === 'health')
        @if (class_exists(App\Livewire\Health\BroodcockHealthHistory::class))
            <livewire:health.broodcock-health-history :broodcock="$bird" :key="'health-'.$bird->id" />
        @else
            <div class="card p-8 text-center text-sm text-gray-500">The health history view is not available yet.</div>
        @endif
    @endif

    {{-- Performance --}}
    @if ($tab === 'performance')
        @if (class_exists(App\Livewire\Performance\BroodcockTimeline::class))
            <livewire:performance.broodcock-timeline :broodcock="$bird" :key="'perf-'.$bird->id" />
        @else
            <div class="card p-8 text-center text-sm text-gray-500">The performance timeline is not available yet.</div>
        @endif
    @endif

    {{-- Offspring --}}
    @if ($tab === 'offspring' && $canSeeInternal)
        <div class="card overflow-hidden">
            @if ($this->offspring->isEmpty())
                <div class="p-12 text-center">
                    <h3 class="text-base font-semibold text-gray-900">No offspring recorded</h3>
                    <p class="mt-1 text-sm text-gray-600">
                        Offspring appear here once birds are recorded with {{ $bird->name }}
                        as their {{ $bird->sex->parentTerm() }}.
                    </p>
                </div>
            @else
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            @foreach (['Band Number', 'Name', 'Sex', 'Bloodline', 'Hatched', 'Status'] as $heading)
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-600">
                                    {{ $heading }}
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white">
                        @foreach ($this->offspring as $child)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 text-sm font-medium">
                                    <a href="{{ route('broodcocks.show', $child) }}" class="text-brand-700 hover:text-brand-800">
                                        {{ $child->displayBand() }}
                                    </a>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-900">{{ $child->name }}</td>
                                <td class="px-4 py-3 text-sm text-gray-600">{{ $child->sex->label() }}</td>
                                <td class="px-4 py-3 text-sm text-gray-600">{{ $child->bloodline ?? '—' }}</td>
                                <td class="px-4 py-3 text-sm text-gray-600">
                                    {{ $child->date_hatched?->format('j M Y') ?? '—' }}
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

    {{-- Delete confirmation. Names the bird explicitly and says what is kept,
         because "Are you sure?" tells a worried user nothing. --}}
    @if ($confirmingDeletion)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/50 p-4" role="dialog" aria-modal="true">
            <div class="w-full max-w-md rounded-xl bg-white p-6 shadow-xl">
                <h2 class="text-lg font-semibold text-gray-900">Delete {{ $bird->name }}?</h2>
                <p class="mt-2 text-sm text-gray-600">
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
