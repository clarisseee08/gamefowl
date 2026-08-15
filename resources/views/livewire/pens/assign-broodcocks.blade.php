<div>
    @php
        $pen = $this->pen;
        $occupancy = $pen->occupancy();
    @endphp

    <div class="mb-10">
        <a href="{{ route('pens.show', $pen) }}" wire:navigate class="text-sm font-medium text-action hover:underline">
            &larr; Back to Pen {{ $pen->code }}
        </a>
        <h1 class="mt-2 text-[34px] font-semibold tracking-[-0.022em] leading-[1.12] text-ink">
            Assign Birds - Pen {{ $pen->code }}
        </h1>
        <p class="mt-3 text-[17px] leading-relaxed text-ink-48">
            Tick the birds you want to move into this pen, then press "Move into this pen".
            You can also remove a bird from the pen at any time.
        </p>
    </div>

    <div class="card mb-10 p-6">
        <p class="text-sm text-ink-80">
            This pen holds <strong>{{ $occupancy }}</strong>
            {{ $occupancy === 1 ? 'bird' : 'birds' }} right now.
            @if ($pen->capacity > 0)
                Its stated capacity is <strong>{{ $pen->capacity }}</strong>.
            @else
                It has <strong>no stated limit</strong>.
            @endif
        </p>
    </div>

    {{-- Over-capacity is a warning, never a block. Farm staff must be able to
         record what is physically in the pen even when it is overfull. --}}
    @if ($this->overCapacityBy > 0)
        <div class="mb-6 rounded-lg bg-warn-wash p-4 text-sm text-warn ring-1 ring-warn/20" role="status">
            <p class="font-semibold">This pen would be over its stated capacity.</p>
            <p class="mt-1">
                Moving the {{ count($selected) }} ticked
                {{ count($selected) === 1 ? 'bird' : 'birds' }} in would make
                {{ $this->projectedOccupancy }} birds in a pen rated for {{ $pen->capacity }} -
                {{ $this->overCapacityBy }} over.
                You can still go ahead; the pen will simply be shown as over capacity.
            </p>
        </div>
    @endif

    <div class="grid gap-6 lg:grid-cols-2">
        {{-- Move birds IN --------------------------------------------------- --}}
        <div class="card overflow-hidden">
            <div class="border-b border-hairline p-4">
                <h2 class="text-[21px] font-semibold tracking-[-0.01em] leading-[1.25] text-ink">Move Birds Into This Pen</h2>
                <label for="bird-search" class="label mt-3">Search Birds</label>
                <input
                    id="bird-search"
                    type="search"
                    wire:model.live.debounce.400ms="search"
                    placeholder="Type a name, band number, breed, or bloodline"
                    autocomplete="off"
                    class="input mt-1"
                >
            </div>

            @error('selected')
                <p class="border-b border-alert/20 bg-alert-wash px-4 py-3 text-sm text-alert" role="alert">{{ $message }}</p>
            @enderror

            @if ($this->available->isEmpty())
                <div class="p-10 text-center">
                    @if ($search !== '')
                        <p class="text-base font-medium text-ink">No birds match "{{ $search }}".</p>
                        <p class="mt-3 text-[17px] leading-relaxed text-ink-48">Clear the search box to see every bird that can be moved.</p>
                        <button type="button" wire:click="$set('search', '')" class="btn-secondary mt-4">Clear search</button>
                    @else
                        <p class="text-base font-medium text-ink">There are no other birds to move in.</p>
                        <p class="mt-3 text-[17px] leading-relaxed text-ink-48">
                            Every bird on the farm is already in this pen, or there are no birds recorded yet.
                        </p>
                    @endif
                </div>
            @else
                <ul class="divide-y divide-divider">
                    @foreach ($this->available as $bird)
                        <li wire:key="available-{{ $bird->id }}">
                            <label class="flex cursor-pointer items-start gap-3 px-4 py-3.5 hover:bg-pearl">
                                <input
                                    type="checkbox"
                                    value="{{ $bird->id }}"
                                    wire:model.live="selected"
                                    class="mt-0.5 h-5 w-5 rounded border-hairline text-action focus:ring-action"
                                >
                                <span class="min-w-0 flex-1">
                                    <span class="block text-sm font-medium text-ink">{{ $bird->name }}</span>
                                    <span class="block text-sm text-ink-80">
                                        Band Number: {{ $bird->displayBand() }} &middot; {{ $bird->sex->label() }}
                                    </span>
                                    <span class="mt-1 block text-xs text-ink-48">
                                        @if ($bird->pen)
                                            Currently in pen {{ $bird->pen->code }} - moving it here takes it out of that pen.
                                        @else
                                            Not in any pen yet.
                                        @endif
                                    </span>
                                </span>
                                <span class="badge {{ $bird->status->badgeClasses() }}">{{ $bird->status->label() }}</span>
                            </label>
                        </li>
                    @endforeach
                </ul>

                <div class="space-y-3 border-t border-hairline p-4">
                    {{ $this->available->links() }}

                    <button
                        type="button"
                        wire:click="assign"
                        wire:loading.attr="disabled"
                        class="btn-primary w-full"
                    >
                        <span wire:loading.remove wire:target="assign">
                            Move {{ count($selected) > 0 ? count($selected) : '' }}
                            {{ count($selected) === 1 ? 'bird' : 'birds' }} into this pen
                        </span>
                        <span wire:loading wire:target="assign">Moving...</span>
                    </button>
                </div>
            @endif
        </div>

        {{-- Take birds OUT -------------------------------------------------- --}}
        <div class="card overflow-hidden">
            <div class="border-b border-hairline p-4">
                <h2 class="text-[21px] font-semibold tracking-[-0.01em] leading-[1.25] text-ink">Birds Currently In This Pen</h2>
                <p class="mt-0.5 text-sm text-ink-80">
                    Removing a bird here does not delete it - it just stops being assigned to a pen.
                </p>
            </div>

            @if ($this->housed->isEmpty())
                <div class="p-10 text-center">
                    <p class="text-base font-medium text-ink">This pen is empty.</p>
                    <p class="mt-3 text-[17px] leading-relaxed text-ink-48">Tick birds on the left and press "Move into this pen".</p>
                </div>
            @else
                <ul class="divide-y divide-divider">
                    @foreach ($this->housed as $bird)
                        <li wire:key="housed-{{ $bird->id }}" class="flex items-center justify-between gap-3 px-4 py-3.5">
                            <span class="min-w-0">
                                <span class="block text-sm font-medium text-ink">{{ $bird->name }}</span>
                                <span class="block text-sm text-ink-80">
                                    Band Number: {{ $bird->displayBand() }} &middot; {{ $bird->sex->label() }}
                                </span>
                            </span>
                            <button
                                type="button"
                                wire:click="unassign({{ $bird->id }})"
                                wire:loading.attr="disabled"
                                class="btn-secondary shrink-0"
                            >
                                Remove
                            </button>
                        </li>
                    @endforeach
                </ul>

                <div class="border-t border-hairline p-4">
                    {{ $this->housed->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
