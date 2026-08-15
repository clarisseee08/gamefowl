<div>
    @php
        $pen = $this->pen;
        $occupancy = $pen->occupancy();
    @endphp

    <div class="mb-8 border-b border-hairline pb-6">
        <a href="{{ route('pens.show', $pen) }}" wire:navigate class="inline-flex min-h-11 items-center text-[13px] font-medium text-action hover:underline">
            &larr; Back to Pen <span class="datum ml-1">{{ $pen->code }}</span>
        </a>
        <h1 class="mt-1 text-[32px] font-semibold leading-[1.15] tracking-[-0.02em] text-ink">
            Assign Birds - Pen <span class="datum font-medium">{{ $pen->code }}</span>
        </h1>
        <p class="mt-2 max-w-[68ch] text-[15px] leading-relaxed text-ink-80">
            Tick the birds you want to move into this pen, then press "Move into this pen".
            You can also remove a bird from the pen at any time.
        </p>
    </div>

    {{-- A sunk well, not a card: this is a running total, not a record. --}}
    <div class="mb-6 rounded-[4px] border border-hairline bg-pearl px-5 py-4">
        <p class="text-[15px] leading-relaxed text-ink-80">
            This pen holds <strong class="datum font-medium text-ink">{{ $occupancy }}</strong>
            {{ $occupancy === 1 ? 'bird' : 'birds' }} right now.
            @if ($pen->capacity > 0)
                Its stated capacity is <strong class="datum font-medium text-ink">{{ $pen->capacity }}</strong>.
            @else
                It has <strong class="font-medium text-ink">no stated limit</strong>.
            @endif
        </p>
    </div>

    {{-- Over-capacity is a warning, never a block. Farm staff must be able to
         record what is physically in the pen even when it is overfull. --}}
    @if ($this->overCapacityBy > 0)
        <div class="mb-6 rounded-[4px] border border-warn/25 bg-warn-wash px-4 py-3.5" role="status">
            <p class="text-[15px] font-semibold text-warn">This pen would be over its stated capacity.</p>
            <p class="mt-1 max-w-[68ch] text-[13px] leading-relaxed text-warn">
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
            <div class="border-b border-hairline px-4 py-4">
                <h2 class="text-[18px] font-medium leading-[1.3] text-ink">Move Birds Into This Pen</h2>
                <label for="bird-search" class="label mt-3">Search Birds</label>
                <input
                    id="bird-search"
                    type="search"
                    wire:model.live.debounce.400ms="search"
                    placeholder="Type a name, band number, breed, or bloodline"
                    autocomplete="off"
                    class="input mt-1.5"
                >
            </div>

            @error('selected')
                <p class="border-b border-alert/25 bg-alert-wash px-4 py-3 text-[13px] text-alert" role="alert">{{ $message }}</p>
            @enderror

            @if ($this->available->isEmpty())
                <div class="p-10 text-center">
                    @if ($search !== '')
                        <p class="text-[15px] font-medium text-ink">No birds match "{{ $search }}".</p>
                        <p class="mx-auto mt-2 max-w-[46ch] text-[13px] leading-relaxed text-ink-80">Clear the search box to see every bird that can be moved.</p>
                        <button type="button" wire:click="$set('search', '')" class="btn-secondary mt-5">Clear search</button>
                    @else
                        <p class="text-[15px] font-medium text-ink">There are no other birds to move in.</p>
                        <p class="mx-auto mt-2 max-w-[46ch] text-[13px] leading-relaxed text-ink-80">
                            Every bird on the farm is already in this pen, or there are no birds recorded yet.
                        </p>
                    @endif
                </div>
            @else
                <ul class="divide-y divide-hairline">
                    @foreach ($this->available as $bird)
                        <li wire:key="available-{{ $bird->id }}">
                            <label class="flex cursor-pointer items-start gap-3 px-4 py-3.5 hover:bg-pearl">
                                {{-- accent-color, not a ring: there is no forms plugin in this project,
                                     so a native checkbox would otherwise paint itself the browser's
                                     own blue - the one colour this system cannot afford to show. --}}
                                <input
                                    type="checkbox"
                                    value="{{ $bird->id }}"
                                    wire:model.live="selected"
                                    class="mt-0.5 h-6 w-6 shrink-0 cursor-pointer accent-action"
                                >
                                <span class="min-w-0 flex-1">
                                    <span class="block text-[15px] font-medium leading-snug text-ink">{{ $bird->name }}</span>
                                    <span class="mt-1.5 flex flex-wrap items-center gap-x-2 gap-y-1.5 text-[13px] text-ink-80">
                                        <span>Band Number:</span>
                                        <x-band-tag :bloodline="$bird->bloodline" :band="$bird->band_number" size="xs" />
                                        <span aria-hidden="true">&middot;</span>
                                        <span>{{ $bird->sex->label() }}</span>
                                    </span>
                                    <span class="mt-1.5 block text-[12px] leading-snug text-ink-80">
                                        @if ($bird->pen)
                                            Currently in pen {{ $bird->pen->code }} - moving it here takes it out of that pen.
                                        @else
                                            Not in any pen yet.
                                        @endif
                                    </span>
                                </span>
                                <span class="badge {{ $bird->status->badgeClasses() }} shrink-0">{{ $bird->status->label() }}</span>
                            </label>
                        </li>
                    @endforeach
                </ul>

                <div class="space-y-3 border-t border-hairline bg-pearl p-4">
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
            <div class="border-b border-hairline px-4 py-4">
                <h2 class="text-[18px] font-medium leading-[1.3] text-ink">Birds Currently In This Pen</h2>
                <p class="mt-1 max-w-[52ch] text-[13px] leading-relaxed text-ink-80">
                    Removing a bird here does not delete it - it just stops being assigned to a pen.
                </p>
            </div>

            @if ($this->housed->isEmpty())
                <div class="p-10 text-center">
                    <p class="text-[15px] font-medium text-ink">This pen is empty.</p>
                    <p class="mx-auto mt-2 max-w-[46ch] text-[13px] leading-relaxed text-ink-80">Tick birds on the left and press "Move into this pen".</p>
                </div>
            @else
                <ul class="divide-y divide-hairline">
                    @foreach ($this->housed as $bird)
                        <li wire:key="housed-{{ $bird->id }}" class="flex items-center justify-between gap-3 px-4 py-3.5">
                            <span class="min-w-0">
                                <span class="block text-[15px] font-medium leading-snug text-ink">{{ $bird->name }}</span>
                                <span class="mt-1.5 flex flex-wrap items-center gap-x-2 gap-y-1.5 text-[13px] text-ink-80">
                                    <span>Band Number:</span>
                                    <x-band-tag :bloodline="$bird->bloodline" :band="$bird->band_number" size="xs" />
                                    <span aria-hidden="true">&middot;</span>
                                    <span>{{ $bird->sex->label() }}</span>
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

                <div class="border-t border-hairline bg-pearl p-4">
                    {{ $this->housed->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
