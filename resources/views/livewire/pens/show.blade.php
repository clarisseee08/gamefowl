<div>
    @php
        $pen = $this->pen;
        $occupancy = $pen->occupancy();
        $remaining = $pen->remainingCapacity();
        $percent = $pen->capacity > 0 ? min(100, (int) round($occupancy / $pen->capacity * 100)) : null;
    @endphp

    <div class="mb-6">
        <a href="{{ route('pens.index') }}" wire:navigate class="text-sm font-medium text-brand-700 hover:text-brand-800">
            &larr; Back to Pens
        </a>

        <div class="mt-2 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-gray-900">
                    Pen {{ $pen->code }}
                </h1>
                <p class="mt-1 text-sm text-gray-600">{{ $pen->name }}</p>
            </div>

            <div class="flex flex-wrap gap-2">
                @can('update', $pen)
                    <a href="{{ route('pens.assign', $pen) }}" wire:navigate class="btn-primary">Assign Birds</a>
                    <a href="{{ route('pens.edit', $pen) }}" wire:navigate class="btn-secondary">Edit Pen</a>
                @endcan
                @can('delete', $pen)
                    <button type="button" wire:click="confirmDelete" class="btn-danger">Delete Pen</button>
                @endcan
            </div>
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="card p-6 lg:col-span-1">
            <h2 class="text-base font-semibold text-gray-900">Pen Information</h2>

            <dl class="mt-4 space-y-4 text-sm">
                <div>
                    <dt class="font-medium text-gray-500">Location</dt>
                    <dd class="mt-0.5 text-gray-900">{{ $pen->location ?? 'No location recorded' }}</dd>
                </div>

                <div>
                    <dt class="font-medium text-gray-500">Capacity</dt>
                    <dd class="mt-0.5 text-gray-900">
                        {{ $pen->capacity > 0 ? $pen->capacity.' birds' : 'No limit' }}
                    </dd>
                </div>

                <div>
                    <dt class="font-medium text-gray-500">Birds Inside Now</dt>
                    <dd class="mt-0.5 text-2xl font-bold text-gray-900">{{ $occupancy }}</dd>
                </div>

                <div>
                    <dt class="font-medium text-gray-500">Space Left</dt>
                    <dd class="mt-0.5 text-gray-900">
                        @if ($remaining === null)
                            No limit
                        @elseif ($occupancy > $pen->capacity)
                            <span class="font-semibold text-rose-700">Over capacity by {{ $occupancy - $pen->capacity }}</span>
                        @elseif ($remaining === 0)
                            <span class="font-semibold text-rose-700">Full</span>
                        @else
                            {{ $remaining }} {{ $remaining === 1 ? 'space' : 'spaces' }}
                        @endif
                    </dd>
                </div>

                @if ($percent !== null)
                    <div>
                        <div class="h-2.5 w-full overflow-hidden rounded-full bg-gray-200">
                            <div class="h-full rounded-full {{ $occupancy > $pen->capacity ? 'bg-rose-500' : ($percent >= 80 ? 'bg-amber-500' : 'bg-brand-600') }}"
                                 style="width: {{ $percent }}%"></div>
                        </div>
                    </div>
                @endif

                @if ($pen->notes)
                    <div>
                        <dt class="font-medium text-gray-500">Notes</dt>
                        <dd class="mt-0.5 whitespace-pre-line text-gray-900">{{ $pen->notes }}</dd>
                    </div>
                @endif
            </dl>
        </div>

        <div class="lg:col-span-2">
            <div class="card overflow-hidden">
                <div class="border-b border-gray-200 px-4 py-4 sm:px-6">
                    <h2 class="text-base font-semibold text-gray-900">Birds In This Pen</h2>
                    <p class="mt-0.5 text-sm text-gray-600">
                        Every bird currently housed in pen {{ $pen->code }}.
                    </p>
                </div>

                @if ($this->birds->isEmpty())
                    <div class="p-10 text-center">
                        <p class="text-base font-medium text-gray-900">This pen is empty.</p>
                        <p class="mt-1 text-sm text-gray-600">
                            Click "Assign Birds" above to move birds into this pen.
                        </p>
                        @can('update', $pen)
                            <a href="{{ route('pens.assign', $pen) }}" wire:navigate class="btn-primary mt-4">Assign Birds</a>
                        @endcan
                    </div>
                @else
                    {{-- Cards on a phone, table from sm: upward. --}}
                    <ul class="divide-y divide-gray-100 sm:hidden">
                        @foreach ($this->birds as $bird)
                            <li wire:key="bird-card-{{ $bird->id }}" class="p-4">
                                <p class="text-sm font-semibold text-gray-900">{{ $bird->name }}</p>
                                <p class="text-sm text-gray-600">Band Number: {{ $bird->displayBand() }}</p>
                                <p class="mt-1 flex items-center gap-2 text-sm text-gray-600">
                                    <span>{{ $bird->sex->label() }}</span>
                                    <span class="badge {{ $bird->status->badgeClasses() }}">{{ $bird->status->label() }}</span>
                                </p>
                            </li>
                        @endforeach
                    </ul>

                    <table class="hidden min-w-full divide-y divide-gray-200 sm:table">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-600">Name</th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-600">Band Number</th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-600">Sex</th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-600">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white">
                            @foreach ($this->birds as $bird)
                                <tr wire:key="bird-{{ $bird->id }}">
                                    <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ $bird->name }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-600">{{ $bird->displayBand() }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-600">{{ $bird->sex->label() }}</td>
                                    <td class="px-4 py-3">
                                        <span class="badge {{ $bird->status->badgeClasses() }}">{{ $bird->status->label() }}</span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>

                    <div class="border-t border-gray-200 px-4 py-3">
                        {{ $this->birds->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>

    @if ($confirmingDelete)
        <div class="fixed inset-0 z-50 flex items-end justify-center bg-gray-900/50 p-4 sm:items-center"
             role="dialog"
             aria-modal="true"
             aria-labelledby="delete-pen-title">
            <div class="w-full max-w-lg rounded-xl bg-white p-6 shadow-xl">
                <h2 id="delete-pen-title" class="text-lg font-semibold text-gray-900">
                    Delete pen {{ $pen->code }} - {{ $pen->name }}?
                </h2>

                <p class="mt-3 text-sm text-gray-700">
                    @if ($occupancy === 0)
                        This pen is empty. It will be removed from the list.
                    @else
                        The {{ $occupancy }} {{ $occupancy === 1 ? 'bird' : 'birds' }} in this pen will become unassigned.
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
