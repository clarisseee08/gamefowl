<div class="max-w-2xl">
    <div class="mb-8 border-b border-border pb-6">
        <a href="{{ route('pens.index') }}" wire:navigate class="inline-flex min-h-11 items-center text-[13px] font-medium text-primary hover:underline">
            &larr; Back to Pens
        </a>
        <h1 class="mt-1 text-[32px] font-semibold leading-[1.15] tracking-[-0.02em] text-foreground">
            {{ $this->isEditing() ? 'Edit Pen' : 'Add Pen' }}
        </h1>
        <p class="mt-2 max-w-[62ch] text-[15px] leading-relaxed text-muted-foreground">
            A pen is a physical housing unit on the farm. Give it a short code so it is easy to find later.
        </p>
    </div>

    {{-- One field per ruled row, the way a paper record sheet is laid out.
         Spacing is not uniform: rows are tight, the section rules do the
         separating. --}}
    <form wire:submit="save" class="card divide-y divide-border overflow-hidden">
        @csrf

        <div class="px-5 py-5">
            <label for="code" class="label">Pen Code</label>
            <input
                id="code"
                type="text"
                wire:model="code"
                required
                autocomplete="off"
                class="datum input mt-1.5 @error('code') input-error @enderror"
            >
            <p class="help">A short label written on the pen itself, for example P-01.</p>
            @error('code')
                <p class="error">{{ $message }}</p>
            @enderror
        </div>

        <div class="px-5 py-5">
            <label for="name" class="label">Pen Name</label>
            <input
                id="name"
                type="text"
                wire:model="name"
                required
                class="input mt-1.5 @error('name') input-error @enderror"
            >
            <p class="help">What the pen is used for, for example "Breeding Pen A".</p>
            @error('name')
                <p class="error">{{ $message }}</p>
            @enderror
        </div>

        <div class="px-5 py-5">
            <label for="location" class="label">Location <span class="font-normal text-muted-foreground">(optional)</span></label>
            <input
                id="location"
                type="text"
                wire:model="location"
                class="input mt-1.5 @error('location') input-error @enderror"
            >
            <p class="help">Where on the farm this pen is, for example "North Yard".</p>
            @error('location')
                <p class="error">{{ $message }}</p>
            @enderror
        </div>

        <div class="px-5 py-5">
            <label for="capacity" class="label">Capacity</label>
            <input
                id="capacity"
                type="number"
                min="0"
                step="1"
                inputmode="numeric"
                wire:model.live.blur="capacity"
                required
                class="datum input mt-1.5 max-w-[10rem] @error('capacity') input-error @enderror"
            >
            <p class="help">How many birds this pen can hold. Enter <strong class="datum font-medium text-foreground">0</strong> if there is no set limit.</p>
            @error('capacity')
                <p class="error">{{ $message }}</p>
            @enderror

            @php $typedCapacity = $this->capacityValue(); @endphp
            @if ($this->isEditing() && $typedCapacity !== null && $typedCapacity > 0 && $typedCapacity < $this->currentOccupancy)
                <p class="mt-3 max-w-[62ch] rounded-[4px] border border-warning/25 bg-warning-bg px-3 py-2.5 text-[13px] leading-relaxed text-warning" role="status">
                    This pen already holds {{ $this->currentOccupancy }}
                    {{ $this->currentOccupancy === 1 ? 'bird' : 'birds' }}, which is more than the capacity you entered.
                    You can still save - the pen will simply show as over capacity.
                </p>
            @endif
        </div>

        <div class="px-5 py-5">
            <label for="notes" class="label">Notes <span class="font-normal text-muted-foreground">(optional)</span></label>
            <textarea
                id="notes"
                rows="4"
                wire:model="notes"
                class="input mt-1.5 @error('notes') input-error @enderror"
            ></textarea>
            <p class="help">Anything worth remembering about this pen, such as repairs needed or shade cover.</p>
            @error('notes')
                <p class="error">{{ $message }}</p>
            @enderror
        </div>

        <div class="flex flex-col-reverse gap-3 bg-muted px-5 py-4 sm:flex-row sm:justify-end">
            <a href="{{ $this->isEditing() ? route('pens.show', $penId) : route('pens.index') }}"
               wire:navigate
               class="btn-secondary">
                Cancel
            </a>
            <button type="submit" class="btn-primary" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="save">{{ $this->isEditing() ? 'Save Changes' : 'Add Pen' }}</span>
                <span wire:loading wire:target="save">Saving...</span>
            </button>
        </div>
    </form>
</div>
