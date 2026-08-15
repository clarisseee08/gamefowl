<div>
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-gray-900">Pens</h1>
            <p class="mt-1 text-sm text-gray-600">
                The housing units on the farm. Each pen shows how many birds are in it right now.
            </p>
        </div>

        @can('create', App\Models\Pen::class)
            <a href="{{ route('pens.create') }}" wire:navigate class="btn-primary">
                Add Pen
            </a>
        @endcan
    </div>

    <div class="card mb-6 p-4">
        <label for="pen-search" class="label">Search Pens</label>
        <input
            id="pen-search"
            type="search"
            wire:model.live.debounce.400ms="search"
            placeholder="Type a pen code, name, or location"
            autocomplete="off"
            class="input mt-1"
        >
        <p class="help">For example: P-01, Breeding Pen, or North Yard.</p>
    </div>

    @if ($this->pens->isEmpty())
        <div class="card p-10 text-center">
            @if ($search !== '')
                <p class="text-base font-medium text-gray-900">No pens match "{{ $search }}".</p>
                <p class="mt-1 text-sm text-gray-600">Check the spelling, or clear the search box to see all pens.</p>
                <button type="button" wire:click="$set('search', '')" class="btn-secondary mt-4">
                    Clear search
                </button>
            @else
                <p class="text-base font-medium text-gray-900">No pens yet.</p>
                <p class="mt-1 text-sm text-gray-600">
                    Click "Add Pen" to record your first pen, then you can move birds into it.
                </p>
                @can('create', App\Models\Pen::class)
                    <a href="{{ route('pens.create') }}" wire:navigate class="btn-primary mt-4">Add Pen</a>
                @endcan
            @endif
        </div>
    @else
        {{-- Below sm: each pen is a card. A 6-column table is unreadable on a phone. --}}
        <div class="space-y-4 sm:hidden">
            @foreach ($this->pens as $pen)
                @php
                    $occupancy = $pen->occupancy();
                    $remaining = $pen->remainingCapacity();
                    $percent = $pen->capacity > 0 ? min(100, (int) round($occupancy / $pen->capacity * 100)) : null;
                @endphp
                <div wire:key="pen-card-{{ $pen->id }}" class="card p-4">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <a href="{{ route('pens.show', $pen) }}" wire:navigate class="text-base font-semibold text-brand-700">
                                {{ $pen->code }}
                            </a>
                            <p class="text-sm text-gray-900">{{ $pen->name }}</p>
                            <p class="text-sm text-gray-500">{{ $pen->location ?? 'No location recorded' }}</p>
                        </div>
                        <span class="badge {{ $pen->isFull() ? 'bg-amber-100 text-amber-800 ring-amber-600/20' : 'bg-emerald-100 text-emerald-800 ring-emerald-600/20' }}">
                            {{ $occupancy }} {{ $occupancy === 1 ? 'bird' : 'birds' }}
                        </span>
                    </div>

                    <div class="mt-3">
                        @if ($percent === null)
                            <p class="text-xs font-medium text-gray-500">No limit set</p>
                        @else
                            <div class="h-2.5 w-full overflow-hidden rounded-full bg-gray-200">
                                <div class="h-full rounded-full {{ $occupancy > $pen->capacity ? 'bg-rose-500' : ($percent >= 80 ? 'bg-amber-500' : 'bg-brand-600') }}"
                                     style="width: {{ $percent }}%"></div>
                            </div>
                            <p class="mt-1 text-xs {{ $remaining === 0 ? 'font-semibold text-rose-700' : 'text-gray-600' }}">
                                @if ($occupancy > $pen->capacity)
                                    Over capacity by {{ $occupancy - $pen->capacity }}
                                @elseif ($remaining === 0)
                                    Full
                                @else
                                    {{ $remaining }} {{ $remaining === 1 ? 'space' : 'spaces' }} left
                                @endif
                            </p>
                        @endif
                    </div>

                    <div class="mt-4 flex flex-wrap gap-2">
                        <a href="{{ route('pens.show', $pen) }}" wire:navigate class="btn-secondary">View</a>
                        @can('update', $pen)
                            <a href="{{ route('pens.edit', $pen) }}" wire:navigate class="btn-secondary">Edit</a>
                        @endcan
                        @can('delete', $pen)
                            <button type="button" wire:click="confirmDelete({{ $pen->id }})" class="btn-danger">Delete</button>
                        @endcan
                    </div>
                </div>
            @endforeach
        </div>

        <div class="card hidden overflow-hidden sm:block">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-600">Pen Code</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-600">Name</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-600">Location</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-600">Birds Inside</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-600">How Full</th>
                        <th scope="col" class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-600">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 bg-white">
                    @foreach ($this->pens as $pen)
                        @php
                            $occupancy = $pen->occupancy();
                            $remaining = $pen->remainingCapacity();
                            $percent = $pen->capacity > 0 ? min(100, (int) round($occupancy / $pen->capacity * 100)) : null;
                        @endphp
                        <tr wire:key="pen-{{ $pen->id }}" class="hover:bg-gray-50">
                            <td class="whitespace-nowrap px-4 py-3">
                                <a href="{{ route('pens.show', $pen) }}" wire:navigate class="font-semibold text-brand-700 hover:text-brand-800">
                                    {{ $pen->code }}
                                </a>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-900">{{ $pen->name }}</td>
                            <td class="px-4 py-3 text-sm text-gray-600">{{ $pen->location ?? '--' }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-900">
                                <span class="font-semibold">{{ $occupancy }}</span>
                                <span class="text-gray-500">
                                    @if ($pen->capacity > 0)
                                        of {{ $pen->capacity }}
                                    @else
                                        (no limit)
                                    @endif
                                </span>
                            </td>
                            <td class="w-56 px-4 py-3">
                                @if ($percent === null)
                                    <span class="text-xs font-medium text-gray-500">No limit</span>
                                @else
                                    <div class="h-2.5 w-full overflow-hidden rounded-full bg-gray-200">
                                        <div class="h-full rounded-full {{ $occupancy > $pen->capacity ? 'bg-rose-500' : ($percent >= 80 ? 'bg-amber-500' : 'bg-brand-600') }}"
                                             style="width: {{ $percent }}%"></div>
                                    </div>
                                    <p class="mt-1 text-xs {{ $remaining === 0 ? 'font-semibold text-rose-700' : 'text-gray-600' }}">
                                        @if ($occupancy > $pen->capacity)
                                            Over capacity by {{ $occupancy - $pen->capacity }}
                                        @elseif ($remaining === 0)
                                            Full
                                        @else
                                            {{ $remaining }} {{ $remaining === 1 ? 'space' : 'spaces' }} left
                                        @endif
                                    </p>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-right">
                                <div class="inline-flex gap-2">
                                    @can('update', $pen)
                                        <a href="{{ route('pens.edit', $pen) }}" wire:navigate class="btn-secondary">Edit</a>
                                    @endcan
                                    @can('delete', $pen)
                                        <button type="button" wire:click="confirmDelete({{ $pen->id }})" class="btn-danger">Delete</button>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $this->pens->links() }}
        </div>
    @endif

    {{-- Destructive actions are always confirmed, and the dialog names the pen
         and says exactly what happens to the birds inside it. --}}
    @if ($this->penPendingDeletion)
        <div class="fixed inset-0 z-50 flex items-end justify-center bg-gray-900/50 p-4 sm:items-center"
             role="dialog"
             aria-modal="true"
             aria-labelledby="delete-pen-title">
            <div class="w-full max-w-lg rounded-xl bg-white p-6 shadow-xl">
                <h2 id="delete-pen-title" class="text-lg font-semibold text-gray-900">
                    Delete pen {{ $this->penPendingDeletion->code }} - {{ $this->penPendingDeletion->name }}?
                </h2>

                <p class="mt-3 text-sm text-gray-700">
                    @php $count = $this->penPendingDeletion->occupancy(); @endphp
                    @if ($count === 0)
                        This pen is empty. It will be removed from the list.
                    @else
                        The {{ $count }} {{ $count === 1 ? 'bird' : 'birds' }} in this pen will become unassigned.
                        <strong>No birds are deleted</strong> - you can move them into another pen afterwards.
                    @endif
                </p>

                <div class="mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                    <button type="button" wire:click="cancelDelete" class="btn-secondary">Keep this pen</button>
                    <button type="button" wire:click="delete" class="btn-danger">Yes, delete this pen</button>
                </div>
            </div>
        </div>
    @endif
</div>
