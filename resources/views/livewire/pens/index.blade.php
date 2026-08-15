<div>
    {{-- Page head. Console density: 32px display, 15px lede at 7:1 so it stays
         readable in daylight. --}}
    <div class="mb-8 flex flex-col gap-4 border-b border-hairline pb-6 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="text-[32px] font-semibold leading-[1.15] tracking-[-0.02em] text-ink">Pens</h1>
            <p class="mt-2 max-w-[62ch] text-[15px] leading-relaxed text-ink-80">
                The housing units on the farm. Each pen shows how many birds are in it right now.
            </p>
        </div>

        @can('create', App\Models\Pen::class)
            <a href="{{ route('pens.create') }}" wire:navigate class="btn-primary shrink-0">
                Add Pen
            </a>
        @endcan
    </div>

    <div class="card mb-6 p-4 sm:p-5">
        <div class="max-w-[420px]">
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
    </div>

    @if ($this->pens->isEmpty())
        <div class="card p-10 text-center">
            @if ($search !== '')
                <p class="text-[15px] font-medium text-ink">No pens match "{{ $search }}".</p>
                <p class="mx-auto mt-2 max-w-[52ch] text-[13px] leading-relaxed text-ink-80">Check the spelling, or clear the search box to see all pens.</p>
                <button type="button" wire:click="$set('search', '')" class="btn-secondary mt-5">
                    Clear search
                </button>
            @else
                <p class="text-[15px] font-medium text-ink">No pens yet.</p>
                <p class="mx-auto mt-2 max-w-[52ch] text-[13px] leading-relaxed text-ink-80">
                    Click "Add Pen" to record your first pen, then you can move birds into it.
                </p>
                @can('create', App\Models\Pen::class)
                    <a href="{{ route('pens.create') }}" wire:navigate class="btn-primary mt-5">Add Pen</a>
                @endcan
            @endif
        </div>
    @else
        {{-- Below sm: each pen is a card. A 6-column table is unreadable on a phone. --}}
        <div class="space-y-3 sm:hidden">
            @foreach ($this->pens as $pen)
                @php
                    $occupancy = $pen->occupancy();
                    $remaining = $pen->remainingCapacity();
                    $percent = $pen->capacity > 0 ? min(100, (int) round($occupancy / $pen->capacity * 100)) : null;
                @endphp
                <div wire:key="pen-card-{{ $pen->id }}" class="card p-4">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <a href="{{ route('pens.show', $pen) }}" wire:navigate class="datum text-[19px] font-semibold leading-[1.2] tracking-[-0.01em] text-action hover:underline">
                                {{ $pen->code }}
                            </a>
                            <p class="mt-0.5 text-[15px] leading-snug text-ink">{{ $pen->name }}</p>
                            <p class="text-[13px] leading-snug text-ink-80">{{ $pen->location ?? 'No location recorded' }}</p>
                        </div>
                        <span class="badge {{ $pen->isFull() ? 'badge-warn' : 'badge-ok' }} shrink-0">
                            <span class="datum">{{ $occupancy }}</span> {{ $occupancy === 1 ? 'bird' : 'birds' }}
                        </span>
                    </div>

                    <div class="mt-3 border-t border-hairline pt-3">
                        @if ($percent === null)
                            <p class="datum text-[13px] text-ink-80">No limit set</p>
                        @else
                            <div class="h-[6px] w-full overflow-hidden rounded-[2px] bg-pearl" aria-hidden="true">
                                <div class="h-full {{ $occupancy > $pen->capacity ? 'bg-alert' : ($percent >= 80 ? 'bg-warn' : 'bg-action') }}"
                                     style="width: {{ $percent }}%"></div>
                            </div>
                            <p class="datum mt-1.5 text-[13px] {{ $remaining === 0 ? 'text-alert' : 'text-ink-80' }}">
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
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead class="border-b border-rule-strong bg-pearl">
                        <tr>
                            <th scope="col" class="px-4 py-3 text-left text-[11px] font-medium uppercase tracking-[0.06em] text-ink-80">Pen Code</th>
                            <th scope="col" class="px-4 py-3 text-left text-[11px] font-medium uppercase tracking-[0.06em] text-ink-80">Name</th>
                            <th scope="col" class="px-4 py-3 text-left text-[11px] font-medium uppercase tracking-[0.06em] text-ink-80">Location</th>
                            <th scope="col" class="px-4 py-3 text-left text-[11px] font-medium uppercase tracking-[0.06em] text-ink-80">Birds Inside</th>
                            <th scope="col" class="px-4 py-3 text-left text-[11px] font-medium uppercase tracking-[0.06em] text-ink-80">How Full</th>
                            <th scope="col" class="px-4 py-3 text-right text-[11px] font-medium uppercase tracking-[0.06em] text-ink-80">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="table-hairline bg-canvas">
                        @foreach ($this->pens as $pen)
                            @php
                                $occupancy = $pen->occupancy();
                                $remaining = $pen->remainingCapacity();
                                $percent = $pen->capacity > 0 ? min(100, (int) round($occupancy / $pen->capacity * 100)) : null;
                            @endphp
                            <tr wire:key="pen-{{ $pen->id }}" class="hover:bg-pearl">
                                <td class="whitespace-nowrap px-4 py-3">
                                    <a href="{{ route('pens.show', $pen) }}" wire:navigate class="datum text-[15px] font-medium text-action hover:underline">
                                        {{ $pen->code }}
                                    </a>
                                </td>
                                <td class="px-4 py-3 text-[15px] text-ink">{{ $pen->name }}</td>
                                <td class="px-4 py-3 text-[15px] text-ink-80">{{ $pen->location ?? '--' }}</td>
                                <td class="datum whitespace-nowrap px-4 py-3 text-[15px] text-ink">
                                    <span class="font-medium">{{ $occupancy }}</span>
                                    <span class="text-ink-80">
                                        @if ($pen->capacity > 0)
                                            of {{ $pen->capacity }}
                                        @else
                                            (no limit)
                                        @endif
                                    </span>
                                </td>
                                <td class="w-52 px-4 py-3">
                                    @if ($percent === null)
                                        <span class="datum text-[13px] text-ink-80">No limit</span>
                                    @else
                                        <div class="h-[6px] w-full overflow-hidden rounded-[2px] bg-pearl" aria-hidden="true">
                                            <div class="h-full {{ $occupancy > $pen->capacity ? 'bg-alert' : ($percent >= 80 ? 'bg-warn' : 'bg-action') }}"
                                                 style="width: {{ $percent }}%"></div>
                                        </div>
                                        <p class="datum mt-1.5 text-[13px] {{ $remaining === 0 ? 'text-alert' : 'text-ink-80' }}">
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
        </div>

        <div class="mt-6">
            {{ $this->pens->links() }}
        </div>
    @endif

    {{-- Destructive actions are always confirmed, and the dialog names the pen
         and says exactly what happens to the birds inside it. --}}
    @if ($this->penPendingDeletion)
        <div class="fixed inset-0 z-50 flex items-end justify-center bg-ink/40 p-4 sm:items-center"
             x-data x-trap.noscroll="true" @keydown.escape.window="$el.querySelector('.btn-secondary')?.click()" role="dialog"
             aria-modal="true"
             aria-labelledby="delete-pen-title">
            <div class="card w-full max-w-lg border-rule-strong p-6">
                <h2 id="delete-pen-title" class="text-[22px] font-semibold leading-[1.25] tracking-[-0.01em] text-ink">
                    Delete pen {{ $this->penPendingDeletion->code }} - {{ $this->penPendingDeletion->name }}?
                </h2>

                <p class="mt-3 max-w-[58ch] text-[15px] leading-relaxed text-ink-80">
                    @php $count = $this->penPendingDeletion->occupancy(); @endphp
                    @if ($count === 0)
                        This pen is empty. It will be removed from the list.
                    @else
                        The {{ $count }} {{ $count === 1 ? 'bird' : 'birds' }} in this pen will become unassigned.
                        <strong class="font-medium text-ink">No birds are deleted</strong> - you can move them into another pen afterwards.
                    @endif
                </p>

                <div class="mt-6 flex flex-col-reverse gap-2 border-t border-hairline pt-5 sm:flex-row sm:justify-end">
                    <button type="button" wire:click="cancelDelete" class="btn-secondary">Keep this pen</button>
                    <button type="button" wire:click="delete" class="btn-danger">Yes, delete this pen</button>
                </div>
            </div>
        </div>
    @endif
</div>
