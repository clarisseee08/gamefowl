@php
    $rates = $this->previewRates;
    $editing = $this->isEditing();
@endphp

{{--
    RECORDING A MATING. One column - the pair, the eggs, then notes - and a
    Save bar that stays in reach at the bottom of the screen.

    The typed-in outside parent is untouched in behaviour: picking "Someone
    else's bird" sets <role>_is_external through updatedSireChoice /
    updatedDamChoice, and ResolveExternalParent turns the typed name into a
    real broodcock row on save. Only the markup around it changed.
--}}
<div @class(['flex min-h-0 flex-1 flex-col' => $inDialog])>
    @unless ($inDialog)
    <a href="{{ route('breeding.index') }}" wire:navigate
       class="d-link d-link-hover d-link-primary inline-flex min-h-11 items-center gap-1.5 text-[15px] lg:text-xs font-semibold underline-offset-4">
        <span aria-hidden="true">&larr;</span> Back to breeding records
    </a>

    <x-anchor-block class="mt-2" eyebrow="Breeding" :title="$editing ? 'Edit Breeding Record' : 'Record a Mating'" />
    @endunless

    <form wire:submit="save" @class(['mt-8' => ! $inDialog, 'flex min-h-0 flex-1 flex-col' => $inDialog])>
        <div @class(['max-w-[40rem] space-y-12 pb-10' => ! $inDialog, 'min-h-0 flex-1 space-y-8 overflow-y-auto px-5 pb-5' => $inDialog])>
            {{-- -------------------------------------------------------
                 The pair
            ------------------------------------------------------- --}}
            <section aria-labelledby="pair-heading">
                <h2 id="pair-heading" class="border-b border-border pb-3 font-display text-[19px] lg:text-sm font-semibold tracking-[-0.015em] text-foreground">The Pair</h2>
                <p class="mt-3 text-[15px] lg:text-xs leading-relaxed text-muted-foreground">
                    Only male birds can be chosen as the sire and only female birds as the dam.
                    If a parent is not one of the farm's own birds &mdash; a borrowed or visiting
                    breeder &mdash; pick &ldquo;Someone else's bird&rdquo; at the end of the list
                    and type its name. It is recorded as a bird so the family tree keeps that
                    branch.
                </p>

                <div class="mt-6 space-y-6">
                    {{-- One control per parent: the way out of the list is the
                         last option IN the list, not a checkbox underneath. --}}
                    <div>
                        <x-form-select id="sire_choice" label="Sire (Father)" required error="sire_id"
                                       wire:model.live="sire_choice">
                            <option value="">Choose a male bird</option>
                            @foreach ($this->sires as $bird)
                                <option value="{{ $bird->id }}">
                                    {{ $bird->name }}{{ $bird->band_number ? ' ('.$bird->band_number.')' : '' }}
                                    {{ $bird->bloodline ? ' - '.$bird->bloodline : '' }}
                                </option>
                            @endforeach
                            <option value="{{ App\Livewire\Breeding\Form::OFF_LIST }}">Someone else's bird</option>
                        </x-form-select>

                        @if ($sire_is_external)
                            {{-- Indented off a hairline so the fields read as
                                 belonging to the choice above. --}}
                            <div class="mt-4 space-y-4 border-l-2 border-border pl-4">
                                <div>
                                    <label for="sire_external_name" class="d-fieldset-legend">Name of the outside cock</label>
                                    <input id="sire_external_name" type="text" wire:model.blur="sire_external_name"
                                           @error('sire_external_name') aria-invalid="true" aria-describedby="sire_external_name-error" @enderror
                                           class="d-input w-full mt-1 @error('sire_external_name') d-input-error @enderror">
                                    @error('sire_external_name') <p id="sire_external_name-error" class="mt-1.5 text-[13px] lg:text-[11px] font-medium text-error" role="alert">{{ $message }}</p> @enderror
                                </div>

                                <div>
                                    <label for="sire_external_bloodline" class="d-fieldset-legend">Bloodline</label>
                                    <input id="sire_external_bloodline" type="text" wire:model.blur="sire_external_bloodline"
                                           aria-describedby="sire_external_bloodline-help @error('sire_external_bloodline') sire_external_bloodline-error @enderror"
                                           class="d-input w-full mt-1 @error('sire_external_bloodline') d-input-error @enderror">
                                    <p id="sire_external_bloodline-help" class="d-label mt-1">Optional. Recorded so the bird carries its bloodline tag.</p>
                                    @error('sire_external_bloodline') <p id="sire_external_bloodline-error" class="mt-1.5 text-[13px] lg:text-[11px] font-medium text-error" role="alert">{{ $message }}</p> @enderror
                                </div>
                            </div>
                        @endif
                    </div>

                    <div>
                        <x-form-select id="dam_choice" label="Dam (Mother)" required error="dam_id"
                                       wire:model.live="dam_choice">
                            <option value="">Choose a female bird</option>
                            @foreach ($this->dams as $bird)
                                <option value="{{ $bird->id }}">
                                    {{ $bird->name }}{{ $bird->band_number ? ' ('.$bird->band_number.')' : '' }}
                                    {{ $bird->bloodline ? ' - '.$bird->bloodline : '' }}
                                </option>
                            @endforeach
                            <option value="{{ App\Livewire\Breeding\Form::OFF_LIST }}">Someone else's bird</option>
                        </x-form-select>

                        @if ($dam_is_external)
                            <div class="mt-4 space-y-4 border-l-2 border-border pl-4">
                                <div>
                                    <label for="dam_external_name" class="d-fieldset-legend">Name of the outside hen</label>
                                    <input id="dam_external_name" type="text" wire:model.blur="dam_external_name"
                                           @error('dam_external_name') aria-invalid="true" aria-describedby="dam_external_name-error" @enderror
                                           class="d-input w-full mt-1 @error('dam_external_name') d-input-error @enderror">
                                    @error('dam_external_name') <p id="dam_external_name-error" class="mt-1.5 text-[13px] lg:text-[11px] font-medium text-error" role="alert">{{ $message }}</p> @enderror
                                </div>

                                <div>
                                    <label for="dam_external_bloodline" class="d-fieldset-legend">Bloodline</label>
                                    <input id="dam_external_bloodline" type="text" wire:model.blur="dam_external_bloodline"
                                           aria-describedby="dam_external_bloodline-help @error('dam_external_bloodline') dam_external_bloodline-error @enderror"
                                           class="d-input w-full mt-1 @error('dam_external_bloodline') d-input-error @enderror">
                                    <p id="dam_external_bloodline-help" class="d-label mt-1">Optional. Recorded so the bird carries its bloodline tag.</p>
                                    @error('dam_external_bloodline') <p id="dam_external_bloodline-error" class="mt-1.5 text-[13px] lg:text-[11px] font-medium text-error" role="alert">{{ $message }}</p> @enderror
                                </div>
                            </div>
                        @endif
                    </div>

                    <div>
                        <label for="mating_date" class="d-fieldset-legend">Mating Date <span class="text-destructive">*</span></label>
                        <input id="mating_date" type="date" wire:model.blur="mating_date" max="{{ today()->toDateString() }}"
                               @error('mating_date') aria-invalid="true" aria-describedby="mating_date-error" @enderror
                               class="d-input w-full datum mt-1 sm:max-w-[16rem] @error('mating_date') d-input-error @enderror">
                        @error('mating_date') <p id="mating_date-error" class="mt-1.5 text-[13px] lg:text-[11px] font-medium text-error" role="alert">{{ $message }}</p> @enderror
                    </div>
                </div>
            </section>

            {{-- -------------------------------------------------------
                 Egg results
            ------------------------------------------------------- --}}
            <section aria-labelledby="eggs-heading">
                <h2 id="eggs-heading" class="border-b border-border pb-3 font-display text-[19px] lg:text-sm font-semibold tracking-[-0.015em] text-foreground">Egg Results</h2>
                <p class="mt-3 text-[15px] lg:text-xs leading-relaxed text-muted-foreground">
                    Enter the counts and the system works out the rates. Each number must be
                    equal to or smaller than the one before it.
                </p>

                {{-- DEBOUNCED AT 400ms, and the reason is the distance to the
                     database. These four are wire:model.live because the rates
                     below recompute as you type. Undebounced it is one server
                     round trip PER KEYSTROKE against Supabase in Tokyo. --}}
                <div class="mt-6 space-y-6">
                    @foreach ([
                        ['eggs_set', 'Eggs Set', 'Total eggs put in the incubator.'],
                        ['eggs_fertile', 'Fertile Eggs', 'Of those, how many were fertile.'],
                        ['eggs_hatched', 'Eggs Hatched', 'How many chicks actually hatched.'],
                        ['offspring_count', 'Offspring Registered', 'Leave at 0 — you can add the chicks as bird records afterwards.'],
                    ] as [$field, $label, $help])
                        <div>
                            <label for="{{ $field }}" class="d-fieldset-legend">{{ $label }}</label>
                            <input id="{{ $field }}" type="number" min="0" inputmode="numeric"
                                   wire:model.live.debounce.400ms="{{ $field }}"
                                   aria-describedby="{{ $field }}-help @error($field) {{ $field }}-error @enderror"
                                   @error($field) aria-invalid="true" @enderror
                                   class="d-input w-full datum mt-1 sm:max-w-[12rem] @error($field) d-input-error @enderror">
                            <p id="{{ $field }}-help" class="d-label mt-1">{{ $help }}</p>
                            @error($field) <p id="{{ $field }}-error" class="mt-1.5 text-[13px] lg:text-[11px] font-medium text-error" role="alert">{{ $message }}</p> @enderror
                        </div>
                    @endforeach
                </div>

                {{-- Live rate preview. Always calculated, never typed. --}}
                <dl class="mt-8 grid grid-cols-2 gap-6 border-t border-border pt-5" aria-live="polite">
                    <div>
                        <dt class="text-[14px] lg:text-xs font-medium text-muted-foreground">Fertility Rate</dt>
                        <dd class="datum mt-1 font-display text-[28px] lg:text-base font-semibold leading-tight tracking-[-0.02em] text-foreground">
                            {{ $rates['fertility'] !== null ? $rates['fertility'].'%' : 'No data' }}
                        </dd>
                        <dd class="mt-1 text-[13px] lg:text-[11px] text-muted-foreground">Fertile eggs ÷ eggs set</dd>
                    </div>
                    <div>
                        <dt class="text-[14px] lg:text-xs font-medium text-muted-foreground">Hatch Rate</dt>
                        <dd class="datum mt-1 font-display text-[28px] lg:text-base font-semibold leading-tight tracking-[-0.02em] text-foreground">
                            {{ $rates['hatch'] !== null ? $rates['hatch'].'%' : 'No data' }}
                        </dd>
                        <dd class="mt-1 text-[13px] lg:text-[11px] text-muted-foreground">Hatched ÷ fertile eggs</dd>
                    </div>
                </dl>
            </section>

            {{-- -------------------------------------------------------
                 Notes
            ------------------------------------------------------- --}}
            <div class="border-t border-border pt-6">
                <label for="notes" class="d-fieldset-legend">Notes</label>
                <textarea id="notes" rows="4" wire:model.blur="notes"
                          @error('notes') aria-invalid="true" aria-describedby="notes-error" @enderror
                          class="d-textarea w-full mt-1 @error('notes') d-textarea-error @enderror"></textarea>
                @error('notes') <p id="notes-error" class="mt-1.5 text-[13px] lg:text-[11px] font-medium text-error" role="alert">{{ $message }}</p> @enderror
            </div>
        </div>

        {{-- The Save bar stays in reach at the bottom of the screen, so a
             keeper on a phone never scrolls back up to find it. --}}
        <div @class(['sticky bottom-0 z-10 -mx-4 flex justify-end gap-3 border-t border-border bg-background/95 px-4 py-3 sm:-mx-6 sm:px-6 lg:-mx-8 lg:px-8' => ! $inDialog, 'flex shrink-0 justify-end gap-2 border-t border-border/60 bg-background/60 p-3' => $inDialog])>
            @if ($inDialog)
                <button type="button" class="d-btn d-btn-outline" x-on:click="$dispatch('ems-close-form')">Cancel</button>
            @else
                <a href="{{ route('breeding.index') }}" wire:navigate class="d-btn d-btn-outline">Cancel</a>
            @endif
            <button type="submit" class="d-btn d-btn-primary" wire:loading.attr="disabled" wire:target="save">
                <span class="d-loading d-loading-spinner d-loading-sm" wire:loading wire:target="save"></span>
                <span wire:loading.remove wire:target="save">{{ $editing ? 'Save Changes' : 'Save Breeding Record' }}</span>
                <span wire:loading wire:target="save">Saving&hellip;</span>
            </button>
        </div>
    </form>
</div>
