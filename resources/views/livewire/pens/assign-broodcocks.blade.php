<div>
    @php
        $pen = $this->pen;
        $occupancy = $pen->occupancy();
    @endphp

    <div class="mb-6">
        <a href="{{ route('pens.show', $pen) }}" wire:navigate class="text-sm font-medium text-brand-700 hover:text-brand-800">
            &larr; Back to Pen {{ $pen->code }}
        </a>
        <h1 class="mt-2 text-2xl font-bold tracking-tight text-gray-900">
            Assign Birds - Pen {{ $pen->code }}
        </h1>
        <p class="mt-1 text-sm text-gray-600">
            Tick the birds you want to move into this pen, then press "Move into this pen".
            You can also remove a bird from the pen at any time.
        </p>
    </div>

    <div class="card mb-6 p-4">
        <p class="text-sm text-gray-700">
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
        <div class="mb-6 rounded-lg bg-amber-50 p-4 text-sm text-amber-900 ring-1 ring-amber-200" role="status">
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
            <div class="border-b border-gray-200 p-4">
                <h2 class="text-base font-semibold text-gray-900">Move Birds Into This Pen</h2>
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
                <p class="border-b border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700" role="alert">{{ $message }}</p>
            @enderror

            @if ($this->available->isEmpty())
                <div class="p-10 text-center">
                    @if ($search !== '')
                        <p class="text-base font-medium text-gray-900">No birds match "{{ $search }}".</p>
                        <p class="mt-1 text-sm text-gray-600">Clear the search box to see every bird that can be moved.</p>
                        <button type="button" wire:click="$set('search', '')" class="btn-secondary mt-4">Clear search</button>
                    @else
                        <p class="text-base font-medium text-gray-900">There are no other birds to move in.</p>
                        <p class="mt-1 text-sm text-gray-600">
                            Every bird on the farm is already in this pen, or there are no birds recorded yet.
                        </p>
                    @endif
                </div>
            @else
                <ul class="divide-y divide-gray-100">
                    @foreach ($this->available as $bird)
                        <li wire:key="available-{{ $bird->id }}">
                            <label class="flex cursor-pointer items-start gap-3 px-4 py-3.5 hover:bg-gray-50">
                                <input
                                    type="checkbox"
                                    value="{{ $bird->id }}"
                                    wire:model.live="selected"
                                    class="mt-0.5 h-5 w-5 rounded border-gray-300 text-brand-600 focus:ring-brand-600"
                                >
                                <span class="min-w-0 flex-1">
                                    <span class="block text-sm font-medium text-gray-900">{{ $bird->name }}</span>
                                    <span class="block text-sm text-gray-600">
                                        Band Number: {{ $bird->displayBand() }} &middot; {{ $bird->sex->label() }}
                                    </span>
                                    <span class="mt-1 block text-xs text-gray-500">
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

                <div class="space-y-3 border-t border-gray-200 p-4">
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
            <div class="border-b border-gray-200 p-4">
                <h2 class="text-base font-semibold text-gray-900">Birds Currently In This Pen</h2>
                <p class="mt-0.5 text-sm text-gray-600">
                    Removing a bird here does not delete it - it just stops being assigned to a pen.
                </p>
            </div>

            @if ($this->housed->isEmpty())
                <div class="p-10 text-center">
                    <p class="text-base font-medium text-gray-900">This pen is empty.</p>
                    <p class="mt-1 text-sm text-gray-600">Tick birds on the left and press "Move into this pen".</p>
                </div>
            @else
                <ul class="divide-y divide-gray-100">
                    @foreach ($this->housed as $bird)
                        <li wire:key="housed-{{ $bird->id }}" class="flex items-center justify-between gap-3 px-4 py-3.5">
                            <span class="min-w-0">
                                <span class="block text-sm font-medium text-gray-900">{{ $bird->name }}</span>
                                <span class="block text-sm text-gray-600">
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

                <div class="border-t border-gray-200 p-4">
                    {{ $this->housed->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
