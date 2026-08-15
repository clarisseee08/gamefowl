<div>
    @php
        $pen = $this->pen;
        $occupancy = $pen->occupancy();
        $remaining = $pen->remainingCapacity();
        $percent = $pen->capacity > 0 ? min(100, (int) round($occupancy / $pen->capacity * 100)) : null;
    @endphp

    <div class="mb-10">
        <a href="{{ route('pens.index') }}" wire:navigate class="text-sm font-medium text-action hover:underline">
            &larr; Back to Pens
        </a>

        <div class="mt-2 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <h1 class="text-[34px] font-semibold tracking-[-0.022em] leading-[1.12] text-ink">
                    Pen {{ $pen->code }}
                </h1>
                <p class="mt-3 text-[17px] leading-relaxed text-ink-48">{{ $pen->name }}</p>
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
            <h2 class="text-[21px] font-semibold tracking-[-0.01em] leading-[1.25] text-ink">Pen Information</h2>

            <dl class="mt-4 space-y-4 text-sm">
                <div>
                    <dt class="font-medium text-ink-48">Location</dt>
                    <dd class="mt-0.5 text-ink">{{ $pen->location ?? 'No location recorded' }}</dd>
                </div>

                <div>
                    <dt class="font-medium text-ink-48">Capacity</dt>
                    <dd class="mt-0.5 text-ink">
                        {{ $pen->capacity > 0 ? $pen->capacity.' birds' : 'No limit' }}
                    </dd>
                </div>

                <div>
                    <dt class="font-medium text-ink-48">Birds Inside Now</dt>
                    <dd class="mt-0.5 text-[34px] font-semibold tracking-[-0.022em] leading-[1.12] text-ink">{{ $occupancy }}</dd>
                </div>

                <div>
                    <dt class="font-medium text-ink-48">Space Left</dt>
                    <dd class="mt-0.5 text-ink">
                        @if ($remaining === null)
                            No limit
                        @elseif ($occupancy > $pen->capacity)
                            <span class="font-semibold text-alert">Over capacity by {{ $occupancy - $pen->capacity }}</span>
                        @elseif ($remaining === 0)
                            <span class="font-semibold text-alert">Full</span>
                        @else
                            {{ $remaining }} {{ $remaining === 1 ? 'space' : 'spaces' }}
                        @endif
                    </dd>
                </div>

                @if ($percent !== null)
                    <div>
                        <div class="h-2.5 w-full overflow-hidden rounded-full bg-parchment">
                            <div class="h-full rounded-full {{ $occupancy > $pen->capacity ? 'bg-alert' : ($percent >= 80 ? 'bg-warn-wash' : 'bg-action') }}"
                                 style="width: {{ $percent }}%"></div>
                        </div>
                    </div>
                @endif

                @if ($pen->notes)
                    <div>
                        <dt class="font-medium text-ink-48">Notes</dt>
                        <dd class="mt-0.5 whitespace-pre-line text-ink">{{ $pen->notes }}</dd>
                    </div>
                @endif
            </dl>
        </div>

        <div class="lg:col-span-2">
            <div class="card overflow-hidden">
                <div class="border-b border-hairline px-4 py-4 sm:px-6">
                    <h2 class="text-[21px] font-semibold tracking-[-0.01em] leading-[1.25] text-ink">Birds In This Pen</h2>
                    <p class="mt-0.5 text-sm text-ink-80">
                        Every bird currently housed in pen {{ $pen->code }}.
                    </p>
                </div>

                @if ($this->birds->isEmpty())
                    <div class="p-10 text-center">
                        <p class="text-base font-medium text-ink">This pen is empty.</p>
                        <p class="mt-3 text-[17px] leading-relaxed text-ink-48">
                            Click "Assign Birds" above to move birds into this pen.
                        </p>
                        @can('update', $pen)
                            <a href="{{ route('pens.assign', $pen) }}" wire:navigate class="btn-primary mt-4">Assign Birds</a>
                        @endcan
                    </div>
                @else
                    {{-- Cards on a phone, table from sm: upward. --}}
                    <ul class="divide-y divide-divider sm:hidden">
                        @foreach ($this->birds as $bird)
                            <li wire:key="bird-card-{{ $bird->id }}" class="p-4">
                                <p class="text-sm font-semibold text-ink">{{ $bird->name }}</p>
                                <p class="text-sm text-ink-80">Band Number: {{ $bird->displayBand() }}</p>
                                <p class="mt-1 flex items-center gap-2 text-sm text-ink-80">
                                    <span>{{ $bird->sex->label() }}</span>
                                    <span class="badge {{ $bird->status->badgeClasses() }}">{{ $bird->status->label() }}</span>
                                </p>
                            </li>
                        @endforeach
                    </ul>

                    <table class="hidden min-w-full divide-y divide-divider sm:table">
                        <thead class="bg-pearl">
                            <tr>
                                <th scope="col" class="px-6 py-4 text-left text-[12px] font-medium uppercase tracking-[0.06em] text-ink-80">Name</th>
                                <th scope="col" class="px-6 py-4 text-left text-[12px] font-medium uppercase tracking-[0.06em] text-ink-80">Band Number</th>
                                <th scope="col" class="px-6 py-4 text-left text-[12px] font-medium uppercase tracking-[0.06em] text-ink-80">Sex</th>
                                <th scope="col" class="px-6 py-4 text-left text-[12px] font-medium uppercase tracking-[0.06em] text-ink-80">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-divider bg-white">
                            @foreach ($this->birds as $bird)
                                <tr wire:key="bird-{{ $bird->id }}">
                                    <td class="px-6 py-4 text-sm font-medium text-ink">{{ $bird->name }}</td>
                                    <td class="px-6 py-4 text-sm text-ink-80">{{ $bird->displayBand() }}</td>
                                    <td class="px-6 py-4 text-sm text-ink-80">{{ $bird->sex->label() }}</td>
                                    <td class="px-6 py-4">
                                        <span class="badge {{ $bird->status->badgeClasses() }}">{{ $bird->status->label() }}</span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>

                    <div class="border-t border-hairline px-4 py-3">
                        {{ $this->birds->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>

    @if ($confirmingDelete)
        <div class="fixed inset-0 z-50 flex items-end justify-center bg-ink/40 p-4 sm:items-center"
             role="dialog"
             aria-modal="true"
             aria-labelledby="delete-pen-title">
            <div class="w-full max-w-lg rounded-xl bg-white p-6 shadow-xl">
                <h2 id="delete-pen-title" class="text-[24px] font-semibold tracking-[-0.015em] leading-[1.2] text-ink">
                    Delete pen {{ $pen->code }} - {{ $pen->name }}?
                </h2>

                <p class="mt-3 text-sm text-ink-80">
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
