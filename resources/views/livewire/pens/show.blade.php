<div>
    @php
        $pen = $this->pen;
        $occupancy = $pen->occupancy();
        $remaining = $pen->remainingCapacity();
        $percent = $pen->capacity > 0 ? min(100, (int) round($occupancy / $pen->capacity * 100)) : null;
    @endphp

    <div class="mb-8 border-b border-border pb-6">
        <a href="{{ route('pens.index') }}" wire:navigate class="inline-flex min-h-11 items-center text-[13px] font-medium text-primary hover:underline">
            &larr; Back to Pens
        </a>

        <div class="mt-1 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h1 class="text-[32px] font-semibold leading-[1.15] tracking-[-0.02em] text-foreground">
                    Pen <span class="datum font-medium">{{ $pen->code }}</span>
                </h1>
                <p class="mt-2 text-[17px] leading-snug text-muted-foreground">{{ $pen->name }}</p>
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
        {{-- The pen's own record. Field name on the left in label type, value
             below it; every figure is mono so the column reads as a ledger. --}}
        <div class="card lg:col-span-1">
            <div class="border-b border-border px-5 py-4">
                <h2 class="text-[18px] font-medium leading-[1.3] text-foreground">Pen Information</h2>
            </div>

            <dl class="divide-y divide-border">
                <div class="px-5 py-3.5">
                    <dt class="text-[11px] font-medium uppercase tracking-[0.06em] text-muted-foreground">Location</dt>
                    <dd class="mt-1 text-[15px] text-foreground">{{ $pen->location ?? 'No location recorded' }}</dd>
                </div>

                <div class="px-5 py-3.5">
                    <dt class="text-[11px] font-medium uppercase tracking-[0.06em] text-muted-foreground">Capacity</dt>
                    <dd class="datum mt-1 text-[15px] text-foreground">
                        {{ $pen->capacity > 0 ? $pen->capacity.' birds' : 'No limit' }}
                    </dd>
                </div>

                <div class="px-5 py-3.5">
                    <dt class="text-[11px] font-medium uppercase tracking-[0.06em] text-muted-foreground">Birds Inside Now</dt>
                    <dd class="mt-1">
                        <span class="datum block text-[32px] font-medium leading-[1.1] tracking-[-0.02em] text-foreground">{{ $occupancy }}</span>

                        @if ($percent !== null)
                            <span class="mt-2 block h-[6px] w-full overflow-hidden rounded-[2px] bg-muted" aria-hidden="true">
                                <span class="block h-full {{ $occupancy > $pen->capacity ? 'bg-destructive' : ($percent >= 80 ? 'bg-warning' : 'bg-foreground') }}"
                                      style="width: {{ $percent }}%"></span>
                            </span>
                        @endif
                    </dd>
                </div>

                <div class="px-5 py-3.5">
                    <dt class="text-[11px] font-medium uppercase tracking-[0.06em] text-muted-foreground">Space Left</dt>
                    <dd class="datum mt-1 text-[15px] text-foreground">
                        @if ($remaining === null)
                            No limit
                        @elseif ($occupancy > $pen->capacity)
                            <span class="font-medium text-destructive">Over capacity by {{ $occupancy - $pen->capacity }}</span>
                        @elseif ($remaining === 0)
                            <span class="font-medium text-destructive">Full</span>
                        @else
                            {{ $remaining }} {{ $remaining === 1 ? 'space' : 'spaces' }}
                        @endif
                    </dd>
                </div>

                @if ($pen->notes)
                    <div class="px-5 py-3.5">
                        <dt class="text-[11px] font-medium uppercase tracking-[0.06em] text-muted-foreground">Notes</dt>
                        <dd class="mt-1 whitespace-pre-line text-[15px] leading-relaxed text-foreground">{{ $pen->notes }}</dd>
                    </div>
                @endif
            </dl>
        </div>

        <div class="lg:col-span-2">
            <div class="card overflow-hidden">
                <div class="border-b border-border px-4 py-4 sm:px-5">
                    <h2 class="text-[18px] font-medium leading-[1.3] text-foreground">Birds In This Pen</h2>
                    <p class="mt-1 text-[13px] text-muted-foreground">
                        Every bird currently housed in pen {{ $pen->code }}.
                    </p>
                </div>

                @if ($this->birds->isEmpty())
                    <div class="p-10 text-center">
                        <p class="text-[15px] font-medium text-foreground">This pen is empty.</p>
                        <p class="mx-auto mt-2 max-w-[52ch] text-[13px] leading-relaxed text-muted-foreground">
                            Click "Assign Birds" above to move birds into this pen.
                        </p>
                        @can('update', $pen)
                            <a href="{{ route('pens.assign', $pen) }}" wire:navigate class="btn-primary mt-5">Assign Birds</a>
                        @endcan
                    </div>
                @else
                    {{-- Cards on a phone, table from sm: upward. --}}
                    <ul class="divide-y divide-border sm:hidden">
                        @foreach ($this->birds as $bird)
                            <li wire:key="bird-card-{{ $bird->id }}" class="px-4 py-3.5">
                                <p class="text-[15px] font-medium leading-snug text-foreground">{{ $bird->name }}</p>
                                <div class="mt-1.5 flex flex-wrap items-center gap-x-2 gap-y-1.5">
                                    <span class="text-[13px] text-muted-foreground">Band Number:</span>
                                    <x-band-tag :bloodline="$bird->bloodline" :band="$bird->band_number" size="xs" />
                                </div>
                                <p class="mt-1.5 flex flex-wrap items-center gap-2 text-[13px] text-muted-foreground">
                                    <span>{{ $bird->sex->label() }}</span>
                                    <span class="badge {{ $bird->status->badgeClasses() }}">{{ $bird->status->label() }}</span>
                                </p>
                            </li>
                        @endforeach
                    </ul>

                    <div class="hidden overflow-x-auto sm:block">
                        <table class="min-w-full">
                            <thead class="border-b border-border bg-muted">
                                <tr>
                                    <th scope="col" class="px-4 py-3 text-left text-[11px] font-medium uppercase tracking-[0.06em] text-muted-foreground">Name</th>
                                    <th scope="col" class="px-4 py-3 text-left text-[11px] font-medium uppercase tracking-[0.06em] text-muted-foreground">Band Number</th>
                                    <th scope="col" class="px-4 py-3 text-left text-[11px] font-medium uppercase tracking-[0.06em] text-muted-foreground">Sex</th>
                                    <th scope="col" class="px-4 py-3 text-left text-[11px] font-medium uppercase tracking-[0.06em] text-muted-foreground">Status</th>
                                </tr>
                            </thead>
                            <tbody class="table-hairline bg-card">
                                @foreach ($this->birds as $bird)
                                    <tr wire:key="bird-{{ $bird->id }}" class="group row-hover">
                                        <td class="px-4 py-3 text-[15px] font-medium text-foreground">{{ $bird->name }}</td>
                                        <td class="whitespace-nowrap px-4 py-3">
                                            <x-band-tag :bloodline="$bird->bloodline" :band="$bird->band_number" size="xs" />
                                        </td>
                                        <td class="whitespace-nowrap px-4 py-3 text-[15px] text-muted-foreground">{{ $bird->sex->label() }}</td>
                                        <td class="whitespace-nowrap px-4 py-3">
                                            <span class="badge {{ $bird->status->badgeClasses() }}">{{ $bird->status->label() }}</span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="border-t border-border px-4 py-3">
                        {{ $this->birds->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>

    @if ($confirmingDelete)
        <div class="fixed inset-0 z-50 flex items-end justify-center bg-foreground/40 p-4 sm:items-center"
             x-data x-trap.noscroll="true" @keydown.escape.window="$el.querySelector('.btn-secondary')?.click()" role="dialog"
             aria-modal="true"
             aria-labelledby="delete-pen-title">
            <div class="card w-full max-w-lg border-border p-6">
                <h2 id="delete-pen-title" class="text-[22px] font-semibold leading-[1.25] tracking-[-0.01em] text-foreground">
                    Delete pen {{ $pen->code }} - {{ $pen->name }}?
                </h2>

                <p class="mt-3 max-w-[58ch] text-[15px] leading-relaxed text-muted-foreground">
                    @if ($occupancy === 0)
                        This pen is empty. It will be removed from the list.
                    @else
                        The {{ $occupancy }} {{ $occupancy === 1 ? 'bird' : 'birds' }} in this pen will become unassigned.
                        <strong class="font-medium text-foreground">No birds are deleted</strong> - you can move them into another pen afterwards.
                    @endif
                </p>

                <div class="mt-6 flex flex-col-reverse gap-2 border-t border-border pt-5 sm:flex-row sm:justify-end">
                    <button type="button" wire:click="cancelDelete" class="btn-secondary">Keep this pen</button>
                    <button type="button" wire:click="delete" class="btn-danger">Yes, delete this pen</button>
                </div>
            </div>
        </div>
    @endif
</div>
