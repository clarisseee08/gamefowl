{{--
    Create / edit a health record.

    One measured column, left-aligned: labels above, help and errors under,
    related fields grouped under a small heading and a hairline. The save bar
    sits outside the column so it can run the full width of the canvas, and it
    is sticky because on a phone the last field is a long way from the top.

    Validation messages sit directly under the field they belong to and are
    written in plain sentences, because the people filling this in are farm
    staff, not developers. The `.d-input` floor is 16px so iOS never zooms on
    focus, and every control clears 44px: this is filled in one-handed,
    outdoors, with a bird in the other hand.
--}}
@php
    /** aria-describedby for a field: its help line, plus its error when there is one. */
    $describedBy = fn (string $field, bool $hasHelp = true): string => trim(
        ($hasHelp ? $field.'-help' : '').($errors->has($field) ? ' '.$field.'-error' : '')
    );
@endphp
<div @class(['flex min-h-0 flex-1 flex-col' => $inDialog])>
    @unless ($inDialog)
    <a href="{{ route('health.index') }}" wire:navigate
       class="d-link d-link-hover d-link-primary -mt-2 inline-flex min-h-11 items-center gap-1.5 text-[14px] lg:text-xs font-medium">
        <span aria-hidden="true">&larr;</span> Back to Health Records
    </a>

    <x-anchor-block class="mt-2" eyebrow="Health"
                    :title="$this->isEditing() ? 'Edit Health Record' : 'Add Health Record'">
        Record a vaccination, medication, deworming, treatment or check-up for one bird.
    </x-anchor-block>
    @endunless

    {{-- wire:submit posts through Livewire, which carries the CSRF token on
         every request automatically. --}}
    <form wire:submit="save" @class(['mt-8' => ! $inDialog, 'flex min-h-0 flex-1 flex-col' => $inDialog])>
        <div @class(['max-w-[40rem] space-y-12' => ! $inDialog, 'min-h-0 flex-1 space-y-6 overflow-y-auto px-5 pb-5' => $inDialog])>
            {{-- Which bird --}}
            {{-- No heading: the field's own label already says "Bird". --}}
            <section aria-label="Bird">
                <div class="space-y-6">
                    <div>
                        <x-form-select id="broodcock_id" label="Bird" required
                                       wire:model.live="broodcock_id"
                                       help="Birds are listed by name, with the band number in brackets.">
                            <option value="">Choose a bird&hellip;</option>
                            @foreach ($this->birds as $bird)
                                <option value="{{ $bird->id }}">{{ $bird->displayName() }}</option>
                            @endforeach
                        </x-form-select>

                        {{-- The chosen bird's photograph, so the keeper can check it
                             is the bird in their hands before saving. --}}
                        <x-bird-preview :bird="$this->selectedBird" target="broodcock_id" />
                    </div>
                </div>
            </section>

            {{-- What was done --}}
            <section aria-labelledby="treatment-heading">
                <h2 id="treatment-heading" class="font-display text-[19px] lg:text-sm font-semibold tracking-[-0.015em] text-foreground">What Was Done</h2>

                <div class="mt-5 space-y-6 border-t border-border pt-6">
                    <x-form-select id="record_type" label="Record Type" required
                                   wire:model.live="record_type">
                        <option value="">Choose a type&hellip;</option>
                        @foreach ($this->recordTypes as $type)
                            <option value="{{ $type->value }}">{{ $type->label() }}</option>
                        @endforeach
                    </x-form-select>

                    {{-- Product and condition: the farm's usual entries, then
                         "Others", which opens a box for anything not listed. --}}
                    <div>
                        <x-form-select id="product_choice" label="Product Name" error="product_name"
                                       wire:model="product_choice"
                                       help="The vaccine, medicine or dewormer used.">
                            <option value="">Choose a product&hellip;</option>
                            @foreach ($this->productOptions as $option)
                                <option value="{{ $option }}">{{ $option }}</option>
                            @endforeach
                            <option value="{{ \App\Livewire\Health\Form::OTHER }}">Others</option>
                        </x-form-select>
                        <label for="product_name" class="sr-only">Other product</label>
                        <textarea id="product_name" x-show="$wire.product_choice === @js(\App\Livewire\Health\Form::OTHER)" x-cloak rows="2" wire:model="product_name"
                                  placeholder="Type the product name"
                                  @class(['d-textarea mt-2 w-full', 'd-textarea-error' => $errors->has('product_name')])></textarea>
                    </div>

                    {{-- Dosage: an amount and its unit, saved together as "0.5 ml". --}}
                    <div>
                        <label for="dosage_amount" class="d-fieldset-legend">Dosage</label>
                        <div class="mt-1 flex items-start gap-2">
                            <input id="dosage_amount"
                                   type="text"
                                   x-bind:inputmode="$wire.dosage_unit === @js(\App\Livewire\Health\Form::OTHER) ? 'text' : 'decimal'"
                                   wire:model="dosage_amount"
                                   x-bind:placeholder="$wire.dosage_unit === @js(\App\Livewire\Health\Form::OTHER) ? 'e.g. 1 tablet' : 'e.g. 0.5'"
                                   @if ($errors->has('dosage_amount') || $errors->has('dosage')) aria-describedby="dosage-error" aria-invalid="true" @endif
                                   @class(['d-input datum w-full max-w-[10rem]', 'd-input-error' => $errors->has('dosage_amount') || $errors->has('dosage')])>
                            <div class="w-32">
                                <x-form-select id="dosage_unit" label="Unit" :label-hidden="true" wire:model="dosage_unit">
                                    @foreach ($this->dosageUnits as $unit)
                                        <option value="{{ $unit }}">{{ $unit }}</option>
                                    @endforeach
                                    <option value="{{ \App\Livewire\Health\Form::OTHER }}">Others</option>
                                </x-form-select>
                            </div>
                        </div>
                        @if ($errors->has('dosage_amount') || $errors->has('dosage'))
                            <p id="dosage-error" class="mt-1.5 text-[13px] lg:text-[11px] font-medium text-error" role="alert">{{ $errors->first('dosage_amount') ?: $errors->first('dosage') }}</p>
                        @endif
                    </div>

                    <div>
                        <x-form-select id="condition_choice" label="Condition" error="condition"
                                       wire:model="condition_choice"
                                       help="How the bird was on the day.">
                            <option value="">Choose a condition&hellip;</option>
                            @foreach ($this->conditionOptions as $option)
                                <option value="{{ $option }}">{{ $option }}</option>
                            @endforeach
                            <option value="{{ \App\Livewire\Health\Form::OTHER }}">Others</option>
                        </x-form-select>
                        <label for="condition" class="sr-only">Other condition</label>
                        <textarea id="condition" x-show="$wire.condition_choice === @js(\App\Livewire\Health\Form::OTHER)" x-cloak rows="2" wire:model="condition"
                                  placeholder="Describe how the bird was, e.g. wound on left leg"
                                  @class(['d-textarea mt-2 w-full', 'd-textarea-error' => $errors->has('condition')])></textarea>
                    </div>
                </div>
            </section>

            {{-- When --}}
            <section aria-labelledby="dates-heading">
                <h2 id="dates-heading" class="font-display text-[19px] lg:text-sm font-semibold tracking-[-0.015em] text-foreground">Dates</h2>

                <div class="mt-5 space-y-6 border-t border-border pt-6">
                    <div>
                        <label for="checkup_date" class="d-fieldset-legend">Check-up Date <span class="text-destructive">*</span></label>
                        <x-ui.date-picker id="checkup_date" wire:model.live="checkup_date" max="today" :clearable="false"
                                           :describedby="$describedBy('checkup_date')"
                                           class="max-w-[16rem]" />
                        <p id="checkup_date-help" class="d-label mt-1">The day this was actually done. It cannot be in the future.</p>
                        @error('checkup_date') <p id="checkup_date-error" class="mt-1.5 text-[13px] lg:text-[11px] font-medium text-error" role="alert">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="next_due_date" class="d-fieldset-legend">Next Due Date</label>
                        <x-ui.date-picker id="next_due_date" wire:model="next_due_date"
                                           :min="$checkup_date ?: today()->toDateString()"
                                           wire:key="next-due-{{ $checkup_date ?: 'today' }}"
                                           :describedby="$describedBy('next_due_date')"
                                           class="max-w-[16rem]" />

                        {{-- A nudge, not a rule. Vaccinations and dewormings recur, so
                             leaving this blank is usually a mistake - but a one-off
                             booster legitimately has no follow-up. x-alert keeps the
                             text in foreground ink, so the warning ground still clears
                             the console's 7:1 floor. --}}
                        @if ($this->expectsNextDueDate && ! $next_due_date)
                            <div id="next_due_date-help" class="mt-2">
                                <x-alert variant="warning" title="A follow-up is usually needed">
                                    A {{ $this->selectedTypeLabel }} normally recurs. Adding a next due
                                    date puts this bird on the vaccination schedule so nobody forgets.
                                    You can still save without one.
                                </x-alert>
                            </div>
                        @else
                            <p id="next_due_date-help" class="d-label mt-1">Leave blank if no follow-up is needed.</p>
                        @endif
                        @error('next_due_date') <p id="next_due_date-error" class="mt-1.5 text-[13px] lg:text-[11px] font-medium text-error" role="alert">{{ $message }}</p> @enderror
                    </div>
                </div>
            </section>

            {{-- Notes --}}
            <section aria-labelledby="notes-heading">
                <h2 id="notes-heading" class="font-display text-[19px] lg:text-sm font-semibold tracking-[-0.015em] text-foreground">Notes</h2>

                <div class="mt-5 space-y-6 border-t border-border pt-6">
                    <div>
                        <label for="remarks" class="d-fieldset-legend">Remarks</label>
                        <textarea id="remarks"
                                  rows="3"
                                  wire:model="remarks"
                                  placeholder="Anything else worth remembering about this treatment."
                                  aria-describedby="{{ $describedBy('remarks') }}"
                                  @error('remarks') aria-invalid="true" @enderror
                                  @class(['d-textarea w-full', 'd-textarea-error' => $errors->has('remarks')])></textarea>
                        <p id="remarks-help" class="d-label mt-1">Internal note for farm staff only. Customers never see this.</p>
                        @error('remarks') <p id="remarks-error" class="mt-1.5 text-[13px] lg:text-[11px] font-medium text-error" role="alert">{{ $message }}</p> @enderror
                    </div>
                </div>
            </section>
        </div>

        <div @class(['sticky bottom-0 z-20 -mx-4 mt-12 flex justify-end gap-3 border-t border-border bg-background/95 px-4 py-3 sm:-mx-6 sm:px-6 lg:-mx-8 lg:px-8' => ! $inDialog, 'flex shrink-0 justify-end gap-2 border-t border-border/60 bg-background/60 p-3' => $inDialog])>
            @if ($inDialog)
                <button type="button" class="d-btn d-btn-outline" x-on:click="$dispatch('ems-close-form')">Cancel</button>
            @else
                <a href="{{ route('health.index') }}" wire:navigate class="d-btn d-btn-outline">Cancel</a>
            @endif

            <button type="submit" class="d-btn d-btn-primary" wire:loading.attr="disabled" wire:target="save">
                <span class="d-loading d-loading-spinner d-loading-sm" wire:loading wire:target="save"></span>
                {{ $this->isEditing() ? 'Save Changes' : 'Save Health Record' }}
            </button>
        </div>
    </form>
</div>
