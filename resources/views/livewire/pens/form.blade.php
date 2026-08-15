<div class="mx-auto max-w-2xl">
    <div class="mb-10">
        <a href="{{ route('pens.index') }}" wire:navigate class="text-sm font-medium text-action hover:underline">
            &larr; Back to Pens
        </a>
        <h1 class="mt-2 text-[34px] font-semibold tracking-[-0.022em] leading-[1.12] text-ink">
            {{ $this->isEditing() ? 'Edit Pen' : 'Add Pen' }}
        </h1>
        <p class="mt-3 text-[17px] leading-relaxed text-ink-48">
            A pen is a physical housing unit on the farm. Give it a short code so it is easy to find later.
        </p>
    </div>

    <form wire:submit="save" class="card space-y-10 p-6">
        @csrf

        <div>
            <label for="code" class="label">Pen Code</label>
            <input
                id="code"
                type="text"
                wire:model="code"
                required
                autocomplete="off"
                class="input mt-1 @error('code') input-error @enderror"
            >
            <p class="help">A short label written on the pen itself, for example P-01.</p>
            @error('code')
                <p class="error">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="name" class="label">Pen Name</label>
            <input
                id="name"
                type="text"
                wire:model="name"
                required
                class="input mt-1 @error('name') input-error @enderror"
            >
            <p class="help">What the pen is used for, for example "Breeding Pen A".</p>
            @error('name')
                <p class="error">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="location" class="label">Location <span class="font-normal text-ink-48">(optional)</span></label>
            <input
                id="location"
                type="text"
                wire:model="location"
                class="input mt-1 @error('location') input-error @enderror"
            >
            <p class="help">Where on the farm this pen is, for example "North Yard".</p>
            @error('location')
                <p class="error">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="capacity" class="label">Capacity</label>
            <input
                id="capacity"
                type="number"
                min="0"
                step="1"
                inputmode="numeric"
                wire:model.live.blur="capacity"
                required
                class="input mt-1 @error('capacity') input-error @enderror"
            >
            <p class="help">How many birds this pen can hold. Enter <strong>0</strong> if there is no set limit.</p>
            @error('capacity')
                <p class="error">{{ $message }}</p>
            @enderror

            @php $typedCapacity = $this->capacityValue(); @endphp
            @if ($this->isEditing() && $typedCapacity !== null && $typedCapacity > 0 && $typedCapacity < $this->currentOccupancy)
                <p class="mt-2 rounded-lg bg-warn-wash p-3 text-sm text-warn ring-1 ring-warn/20" role="status">
                    This pen already holds {{ $this->currentOccupancy }}
                    {{ $this->currentOccupancy === 1 ? 'bird' : 'birds' }}, which is more than the capacity you entered.
                    You can still save - the pen will simply show as over capacity.
                </p>
            @endif
        </div>

        <div>
            <label for="notes" class="label">Notes <span class="font-normal text-ink-48">(optional)</span></label>
            <textarea
                id="notes"
                rows="4"
                wire:model="notes"
                class="input mt-1 @error('notes') input-error @enderror"
            ></textarea>
            <p class="help">Anything worth remembering about this pen, such as repairs needed or shade cover.</p>
            @error('notes')
                <p class="error">{{ $message }}</p>
            @enderror
        </div>

        <div class="flex flex-col-reverse gap-3 border-t border-hairline pt-6 sm:flex-row sm:justify-end">
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
