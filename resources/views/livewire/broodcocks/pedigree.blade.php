@php
    // Property access (not method calls) so Livewire's #[Computed] memoization
    // actually applies - otherwise the whole tree is re-queried per reference.
    $root = $this->root;
    $generations = $this->generations;
    $completeness = $this->completeness;
    $labels = ['This Bird', 'Parents', 'Grandparents', 'Great-Grandparents'];
@endphp

<div>
    <div class="mb-6 sm:flex sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-gray-900">
                Family Tree &mdash; {{ $root->name }}
            </h1>
            <p class="mt-1 text-sm text-gray-600">
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
    <div class="card mb-6 p-4">
        <div class="flex items-center justify-between text-sm">
            <span class="font-medium text-gray-900">Pedigree recorded</span>
            <span class="text-gray-600">
                {{ $completeness['known'] }} of {{ $completeness['total'] }} ancestors
                ({{ $completeness['percent'] }}%)
            </span>
        </div>
        <div class="mt-2 h-2 w-full overflow-hidden rounded-full bg-gray-200">
            <div class="h-full rounded-full bg-brand-600" style="width: {{ $completeness['percent'] }}%"></div>
        </div>
        @if ($completeness['known'] === 0)
            <p class="mt-3 text-sm text-gray-600">
                No parents have been recorded for this bird yet.
                @can('update', $broodcock)
                    <a href="{{ route('broodcocks.edit', $broodcock) }}" class="font-medium text-brand-700 hover:text-brand-800">
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
        <div class="flex min-w-max gap-4">
            @foreach ($generations as $index => $column)
                <div class="flex flex-col justify-around gap-2" style="min-width: 12rem;">
                    <p class="mb-1 text-center text-xs font-semibold uppercase tracking-wide text-gray-500">
                        {{ $labels[$index] ?? 'Generation '.$index }}
                    </p>

                    @foreach ($column as $ancestor)
                        @if ($ancestor)
                            <a href="{{ route('broodcocks.show', $ancestor) }}"
                               @class([
                                   'block rounded-lg border p-3 transition hover:shadow-md',
                                   'border-brand-300 bg-brand-50' => $index === 0,
                                   'border-sky-200 bg-sky-50' => $index > 0 && $ancestor->sex === App\Enums\Sex::Male,
                                   'border-rose-200 bg-rose-50' => $index > 0 && $ancestor->sex === App\Enums\Sex::Female,
                               ])>
                                <p class="truncate text-sm font-semibold text-gray-900">{{ $ancestor->name }}</p>
                                <p class="truncate text-xs text-gray-600">{{ $ancestor->displayBand() }}</p>
                                @if ($ancestor->bloodline)
                                    <p class="mt-1 truncate text-xs text-gray-500">{{ $ancestor->bloodline }}</p>
                                @endif
                                <span class="mt-1 inline-block text-xs text-gray-500">
                                    {{ $ancestor->sex->parentTerm() }}
                                </span>
                            </a>
                        @else
                            <div class="rounded-lg border border-dashed border-gray-300 bg-gray-50 p-3 text-center">
                                <p class="text-xs text-gray-400">Not recorded</p>
                            </div>
                        @endif
                    @endforeach
                </div>
            @endforeach
        </div>
    </div>

    <p class="mt-4 text-xs text-gray-500">
        Blue cards are male ancestors (sires), pink cards are female ancestors (dams).
        Click any bird to open its own record.
    </p>
</div>
