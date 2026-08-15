@php $rates = $this->previewRates; @endphp

<div>
    <div class="mb-6">
        <a href="{{ route('breeding.index') }}" class="text-sm font-medium text-brand-700 hover:text-brand-800">
            &larr; Back to breeding records
        </a>
        <h1 class="mt-2 text-2xl font-bold tracking-tight text-gray-900">
            {{ $this->isEditing() ? 'Edit Breeding Record' : 'Record a Mating' }}
        </h1>
    </div>

    <form wire:submit="save" class="space-y-6">
        <section class="card p-6">
            <h2 class="text-base font-semibold text-gray-900">The Pair</h2>
            <p class="mt-1 text-sm text-gray-600">
                Only male birds can be chosen as the sire and only female birds as the dam.
            </p>

            <div class="mt-6 grid gap-5 sm:grid-cols-2">
                <div>
                    <label for="sire_id" class="label">Sire (Father) <span class="text-rose-600">*</span></label>
                    <select id="sire_id" wire:model.blur="sire_id" class="input mt-1 @error('sire_id') input-error @enderror">
                        <option value="">Choose a male bird</option>
                        @foreach ($this->sires as $bird)
                            <option value="{{ $bird->id }}">
                                {{ $bird->name }}{{ $bird->band_number ? ' ('.$bird->band_number.')' : '' }}
                                {{ $bird->bloodline ? ' - '.$bird->bloodline : '' }}
                            </option>
                        @endforeach
                    </select>
                    @error('sire_id') <p class="error">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="dam_id" class="label">Dam (Mother) <span class="text-rose-600">*</span></label>
                    <select id="dam_id" wire:model.blur="dam_id" class="input mt-1 @error('dam_id') input-error @enderror">
                        <option value="">Choose a female bird</option>
                        @foreach ($this->dams as $bird)
                            <option value="{{ $bird->id }}">
                                {{ $bird->name }}{{ $bird->band_number ? ' ('.$bird->band_number.')' : '' }}
                                {{ $bird->bloodline ? ' - '.$bird->bloodline : '' }}
                            </option>
                        @endforeach
                    </select>
                    @error('dam_id') <p class="error">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="mating_date" class="label">Mating Date <span class="text-rose-600">*</span></label>
                    <input id="mating_date" type="date" wire:model.blur="mating_date" max="{{ today()->toDateString() }}"
                           class="input mt-1 @error('mating_date') input-error @enderror">
                    @error('mating_date') <p class="error">{{ $message }}</p> @enderror
                </div>
            </div>
        </section>

        <section class="card p-6">
            <h2 class="text-base font-semibold text-gray-900">Egg Results</h2>
            <p class="mt-1 text-sm text-gray-600">
                Enter the counts and the system works out the rates. Each number must be
                equal to or smaller than the one before it.
            </p>

            <div class="mt-6 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                <div>
                    <label for="eggs_set" class="label">Eggs Set</label>
                    <input id="eggs_set" type="number" min="0" inputmode="numeric" wire:model.live="eggs_set"
                           class="input mt-1 @error('eggs_set') input-error @enderror">
                    <p class="help">Total eggs put in the incubator.</p>
                    @error('eggs_set') <p class="error">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="eggs_fertile" class="label">Fertile Eggs</label>
                    <input id="eggs_fertile" type="number" min="0" inputmode="numeric" wire:model.live="eggs_fertile"
                           class="input mt-1 @error('eggs_fertile') input-error @enderror">
                    <p class="help">Of those, how many were fertile.</p>
                    @error('eggs_fertile') <p class="error">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="eggs_hatched" class="label">Eggs Hatched</label>
                    <input id="eggs_hatched" type="number" min="0" inputmode="numeric" wire:model.live="eggs_hatched"
                           class="input mt-1 @error('eggs_hatched') input-error @enderror">
                    <p class="help">How many chicks actually hatched.</p>
                    @error('eggs_hatched') <p class="error">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="offspring_count" class="label">Offspring Registered</label>
                    <input id="offspring_count" type="number" min="0" inputmode="numeric" wire:model.live="offspring_count"
                           class="input mt-1 @error('offspring_count') input-error @enderror">
                    <p class="help">Leave at 0 — you can add the chicks as bird records afterwards.</p>
                    @error('offspring_count') <p class="error">{{ $message }}</p> @enderror
                </div>
            </div>

            {{-- Live rate preview. These are always calculated, never typed. --}}
            <div class="mt-6 grid gap-4 rounded-lg bg-gray-50 p-4 sm:grid-cols-2">
                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Fertility Rate</p>
                    <p class="mt-1 text-2xl font-bold text-gray-900">
                        {{ $rates['fertility'] !== null ? $rates['fertility'].'%' : '—' }}
                    </p>
                    <p class="text-xs text-gray-500">Fertile eggs ÷ eggs set</p>
                </div>
                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Hatch Rate</p>
                    <p class="mt-1 text-2xl font-bold text-gray-900">
                        {{ $rates['hatch'] !== null ? $rates['hatch'].'%' : '—' }}
                    </p>
                    <p class="text-xs text-gray-500">Hatched ÷ fertile eggs</p>
                </div>
            </div>
        </section>

        <section class="card p-6">
            <label for="notes" class="label">Notes</label>
            <textarea id="notes" rows="3" wire:model.blur="notes"
                      class="input mt-1 @error('notes') input-error @enderror"></textarea>
            @error('notes') <p class="error">{{ $message }}</p> @enderror
        </section>

        <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
            <a href="{{ route('breeding.index') }}" class="btn-secondary">Cancel</a>
            <button type="submit" class="btn-primary" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="save">
                    {{ $this->isEditing() ? 'Save Changes' : 'Save Breeding Record' }}
                </span>
                <span wire:loading wire:target="save">Saving…</span>
            </button>
        </div>
    </form>
</div>
