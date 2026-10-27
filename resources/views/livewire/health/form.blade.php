{{--
    Create / edit a health record.

    One measured column, left-aligned: labels above, help and errors under,
    related fields grouped under a small heading and a hairline. The save bar
    sits outside the column so it can run the full width of the canvas, and it
    is sticky because on a phone the last field is a long way from the top.

    Validation messages sit directly under the field they belong to and are
    written in plain sentences, because the people filling this in are farm
    staff, not developers. The `.input` floor is 16px so iOS never zooms on
    focus, and every control clears 44px: this is filled in one-handed,
    outdoors, with a bird in the other hand.
--}}
@php
    /** aria-describedby for a field: its help line, plus its error when there is one. */
    $describedBy = fn (string $field, bool $hasHelp = true): string => trim(
        ($hasHelp ? $field.'-help' : '').($errors->has($field) ? ' '.$field.'-error' : '')
    );
@endphp
<div>
    <a href="{{ route('health.index') }}" wire:navigate
       class="-mt-2 inline-flex min-h-11 items-center gap-1.5 text-[14px] font-medium text-primary hover:underline">
        <span aria-hidden="true">&larr;</span> Back to Health Records
    </a>

    <x-anchor-block class="mt-2" eyebrow="Health"
                    :title="$this->isEditing() ? 'Edit Health Record' : 'Add Health Record'">
        Record a vaccination, medication, deworming, treatment or check-up for one bird.
    </x-anchor-block>

    {{-- wire:submit posts through Livewire, which carries the CSRF token on
         every request automatically. --}}
    <form wire:submit="save" class="mt-8">
        <div class="max-w-[40rem] space-y-12">
            {{-- Which bird --}}
            <section aria-labelledby="bird-heading">
                <h2 id="bird-heading" class="font-display text-[19px] font-semibold tracking-[-0.015em] text-foreground">The Bird</h2>

                <div class="mt-5 space-y-6 border-t border-border pt-6">
                    <x-form-select id="broodcock_id" label="Bird" required
                                   wire:model="broodcock_id"
                                   help="Birds are listed by name, with the band number in brackets.">
                        <option value="">Choose a bird&hellip;</option>
                        @foreach ($this->birds as $bird)
                            <option value="{{ $bird->id }}">{{ $bird->displayName() }}</option>
                        @endforeach
                    </x-form-select>
                </div>
            </section>

            {{-- What was done --}}
            <section aria-labelledby="treatment-heading">
                <h2 id="treatment-heading" class="font-display text-[19px] font-semibold tracking-[-0.015em] text-foreground">What Was Done</h2>

                <div class="mt-5 space-y-6 border-t border-border pt-6">
                    <x-form-select id="record_type" label="Record Type" required
                                   wire:model.live="record_type">
                        <option value="">Choose a type&hellip;</option>
                        @foreach ($this->recordTypes as $type)
                            <option value="{{ $type->value }}">{{ $type->label() }}</option>
                        @endforeach
                    </x-form-select>

                    <div>
                        <label for="product_name" class="label">Product Name</label>
                        <input id="product_name"
                               type="text"
                               wire:model="product_name"
                               placeholder="e.g. Newcastle Disease vaccine"
                               aria-describedby="{{ $describedBy('product_name') }}"
                               @error('product_name') aria-invalid="true" @enderror
                               @class(['input', 'input-error' => $errors->has('product_name')])>
                        <p id="product_name-help" class="help">The name of the vaccine, medicine or dewormer used.</p>
                        @error('product_name') <p id="product_name-error" class="error" role="alert">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="dosage" class="label">Dosage</label>
                        <input id="dosage"
                               type="text"
                               wire:model="dosage"
                               placeholder="e.g. 0.5 ml"
                               @if ($errors->has('dosage')) aria-describedby="dosage-error" aria-invalid="true" @endif
                               @class(['input datum max-w-[16rem]', 'input-error' => $errors->has('dosage')])>
                        @error('dosage') <p id="dosage-error" class="error" role="alert">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="condition" class="label">Condition</label>
                        <input id="condition"
                               type="text"
                               wire:model="condition"
                               placeholder="e.g. Healthy, coughing, wound on left leg"
                               aria-describedby="{{ $describedBy('condition') }}"
                               @error('condition') aria-invalid="true" @enderror
                               @class(['input', 'input-error' => $errors->has('condition')])>
                        <p id="condition-help" class="help">How the bird was on the day.</p>
                        @error('condition') <p id="condition-error" class="error" role="alert">{{ $message }}</p> @enderror
                    </div>
                </div>
            </section>

            {{-- When --}}
            <section aria-labelledby="dates-heading">
                <h2 id="dates-heading" class="font-display text-[19px] font-semibold tracking-[-0.015em] text-foreground">Dates</h2>

                <div class="mt-5 space-y-6 border-t border-border pt-6">
                    <div>
                        <label for="checkup_date" class="label">Check-up Date <span class="text-destructive">*</span></label>
                        <input id="checkup_date"
                               type="date"
                               wire:model.live="checkup_date"
                               max="{{ today()->toDateString() }}"
                               aria-describedby="{{ $describedBy('checkup_date') }}"
                               @error('checkup_date') aria-invalid="true" @enderror
                               @class(['input datum max-w-[16rem]', 'input-error' => $errors->has('checkup_date')])>
                        <p id="checkup_date-help" class="help">The day this was actually done. It cannot be in the future.</p>
                        @error('checkup_date') <p id="checkup_date-error" class="error" role="alert">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="next_due_date" class="label">Next Due Date</label>
                        <input id="next_due_date"
                               type="date"
                               wire:model="next_due_date"
                               min="{{ $checkup_date ?: today()->toDateString() }}"
                               aria-describedby="{{ $describedBy('next_due_date') }}"
                               @error('next_due_date') aria-invalid="true" @enderror
                               @class(['input datum max-w-[16rem]', 'input-error' => $errors->has('next_due_date')])>

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
                            <p id="next_due_date-help" class="help">Leave blank if no follow-up is needed.</p>
                        @endif
                        @error('next_due_date') <p id="next_due_date-error" class="error" role="alert">{{ $message }}</p> @enderror
                    </div>
                </div>
            </section>

            {{-- Notes --}}
            <section aria-labelledby="notes-heading">
                <h2 id="notes-heading" class="font-display text-[19px] font-semibold tracking-[-0.015em] text-foreground">Notes</h2>

                <div class="mt-5 space-y-6 border-t border-border pt-6">
                    <div>
                        <label for="remarks" class="label">Remarks</label>
                        <textarea id="remarks"
                                  rows="3"
                                  wire:model="remarks"
                                  placeholder="Anything else worth remembering about this treatment."
                                  aria-describedby="{{ $describedBy('remarks') }}"
                                  @error('remarks') aria-invalid="true" @enderror
                                  @class(['input', 'input-error' => $errors->has('remarks')])></textarea>
                        <p id="remarks-help" class="help">Internal note for farm staff only. Customers never see this.</p>
                        @error('remarks') <p id="remarks-error" class="error" role="alert">{{ $message }}</p> @enderror
                    </div>
                </div>
            </section>
        </div>

        <div class="sticky bottom-0 z-20 -mx-4 mt-12 flex justify-end gap-3 border-t border-border bg-background/95 px-4 py-3 sm:-mx-6 sm:px-6 lg:-mx-8 lg:px-8">
            <a href="{{ route('health.index') }}" wire:navigate class="btn-secondary">Cancel</a>

            <button type="submit" class="btn-primary" wire:loading.attr="disabled" wire:target="save">
                <span class="btn-spinner" wire:loading wire:target="save"></span>
                {{ $this->isEditing() ? 'Save Changes' : 'Save Health Record' }}
            </button>
        </div>
    </form>
</div>
