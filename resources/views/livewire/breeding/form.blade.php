@php $rates = $this->previewRates; @endphp

<div>
    <div class="mb-10">
        <a href="{{ route('breeding.index') }}" wire:navigate class="text-sm font-medium text-primary hover:underline">
            &larr; Back to breeding records
        </a>
        <h1 class="mt-2 text-[34px] font-semibold tracking-[-0.022em] leading-[1.12] text-foreground">
            {{ $this->isEditing() ? 'Edit Breeding Record' : 'Record a Mating' }}
        </h1>
    </div>

    <form wire:submit="save" class="space-y-10">
        <section class="card p-8">
            <h2 class="text-[21px] font-semibold tracking-[-0.01em] leading-[1.25] text-foreground">The Pair</h2>
            <p class="mt-3 text-[17px] leading-relaxed text-muted-foreground">
                Only male birds can be chosen as the sire and only female birds as the dam.
                If a parent is not one of the farm's own birds &mdash; a borrowed or visiting
                breeder &mdash; pick &ldquo;Someone else's bird&rdquo; at the end of the list
                and type its name. It is recorded as a bird so the family tree keeps that
                branch.
            </p>

            {{-- One control per parent: the way out of the list is the last
                 option IN the list, not a checkbox underneath that replaced the
                 dropdown when ticked. See the broodcock form for the same
                 control and the reasoning behind it. --}}
            <div class="mt-8 grid gap-6 sm:grid-cols-2">
                <div>
                    <label for="sire_choice" class="label">
                        Sire (Father) <span class="text-destructive">*</span>
                    </label>

                    <select id="sire_choice" wire:model.live="sire_choice"
                            class="input mt-1 @error('sire_id') input-error @enderror">
                        <option value="">Choose a male bird</option>
                        @foreach ($this->sires as $bird)
                            <option value="{{ $bird->id }}">
                                {{ $bird->name }}{{ $bird->band_number ? ' ('.$bird->band_number.')' : '' }}
                                {{ $bird->bloodline ? ' - '.$bird->bloodline : '' }}
                            </option>
                        @endforeach
                        <option value="{{ App\Livewire\Breeding\Form::OFF_LIST }}">Someone else's bird</option>
                    </select>
                    @error('sire_id') <p class="error">{{ $message }}</p> @enderror

                    @if ($sire_is_external)
                        {{-- Indented off a hairline so the fields read as belonging
                             to the choice above rather than as two more questions. --}}
                        <div class="mt-4 border-l border-border pl-4">
                            <label for="sire_external_name" class="label">Name of the outside cock</label>
                            <input id="sire_external_name" type="text" wire:model.blur="sire_external_name"
                                   class="input mt-1 @error('sire_external_name') input-error @enderror">
                            @error('sire_external_name') <p class="error">{{ $message }}</p> @enderror

                            <label for="sire_external_bloodline" class="label mt-4">Bloodline</label>
                            <input id="sire_external_bloodline" type="text" wire:model.blur="sire_external_bloodline"
                                   class="input mt-1 @error('sire_external_bloodline') input-error @enderror">
                            <p class="help">Optional. Recorded so the bird carries its bloodline tag.</p>
                            @error('sire_external_bloodline') <p class="error">{{ $message }}</p> @enderror
                        </div>
                    @endif
                </div>

                <div>
                    <label for="dam_choice" class="label">
                        Dam (Mother) <span class="text-destructive">*</span>
                    </label>

                    <select id="dam_choice" wire:model.live="dam_choice"
                            class="input mt-1 @error('dam_id') input-error @enderror">
                        <option value="">Choose a female bird</option>
                        @foreach ($this->dams as $bird)
                            <option value="{{ $bird->id }}">
                                {{ $bird->name }}{{ $bird->band_number ? ' ('.$bird->band_number.')' : '' }}
                                {{ $bird->bloodline ? ' - '.$bird->bloodline : '' }}
                            </option>
                        @endforeach
                        <option value="{{ App\Livewire\Breeding\Form::OFF_LIST }}">Someone else's bird</option>
                    </select>
                    @error('dam_id') <p class="error">{{ $message }}</p> @enderror

                    @if ($dam_is_external)
                        <div class="mt-4 border-l border-border pl-4">
                            <label for="dam_external_name" class="label">Name of the outside hen</label>
                            <input id="dam_external_name" type="text" wire:model.blur="dam_external_name"
                                   class="input mt-1 @error('dam_external_name') input-error @enderror">
                            @error('dam_external_name') <p class="error">{{ $message }}</p> @enderror

                            <label for="dam_external_bloodline" class="label mt-4">Bloodline</label>
                            <input id="dam_external_bloodline" type="text" wire:model.blur="dam_external_bloodline"
                                   class="input mt-1 @error('dam_external_bloodline') input-error @enderror">
                            <p class="help">Optional. Recorded so the bird carries its bloodline tag.</p>
                            @error('dam_external_bloodline') <p class="error">{{ $message }}</p> @enderror
                        </div>
                    @endif
                </div>

                <div>
                    <label for="mating_date" class="label">Mating Date <span class="text-destructive">*</span></label>
                    <input id="mating_date" type="date" wire:model.blur="mating_date" max="{{ today()->toDateString() }}"
                           class="input mt-1 @error('mating_date') input-error @enderror">
                    @error('mating_date') <p class="error">{{ $message }}</p> @enderror
                </div>
            </div>
        </section>

        <section class="card p-8">
            <h2 class="text-[21px] font-semibold tracking-[-0.01em] leading-[1.25] text-foreground">Egg Results</h2>
            <p class="mt-3 text-[17px] leading-relaxed text-muted-foreground">
                Enter the counts and the system works out the rates. Each number must be
                equal to or smaller than the one before it.
            </p>

            <div class="mt-8 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
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
            <div class="mt-8 grid gap-4 rounded-lg bg-muted p-4 sm:grid-cols-2">
                <div>
                    <p class="text-[12px] font-medium uppercase tracking-[0.06em] text-muted-foreground">Fertility Rate</p>
                    <p class="mt-1 text-[34px] font-semibold tracking-[-0.022em] leading-[1.12] text-foreground">
                        {{ $rates['fertility'] !== null ? $rates['fertility'].'%' : '—' }}
                    </p>
                    <p class="text-xs text-muted-foreground">Fertile eggs ÷ eggs set</p>
                </div>
                <div>
                    <p class="text-[12px] font-medium uppercase tracking-[0.06em] text-muted-foreground">Hatch Rate</p>
                    <p class="mt-1 text-[34px] font-semibold tracking-[-0.022em] leading-[1.12] text-foreground">
                        {{ $rates['hatch'] !== null ? $rates['hatch'].'%' : '—' }}
                    </p>
                    <p class="text-xs text-muted-foreground">Hatched ÷ fertile eggs</p>
                </div>
            </div>
        </section>

        <section class="card p-8">
            <label for="notes" class="label">Notes</label>
            <textarea id="notes" rows="3" wire:model.blur="notes"
                      class="input mt-1 @error('notes') input-error @enderror"></textarea>
            @error('notes') <p class="error">{{ $message }}</p> @enderror
        </section>

        <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
            <a href="{{ route('breeding.index') }}" wire:navigate class="btn-secondary">Cancel</a>
            <button type="submit" class="btn-primary" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="save">
                    {{ $this->isEditing() ? 'Save Changes' : 'Save Breeding Record' }}
                </span>
                <span wire:loading wire:target="save">Saving…</span>
            </button>
        </div>
    </form>
</div>
