{{--
    Create / edit a health record.

    Validation messages sit directly under the field they belong to and are
    written in plain sentences, because the people filling this in are farm
    staff, not developers.

    Console surface: the `.input` floor is 16px so iOS never zooms the page on
    focus, and every control clears 44px because this is filled in one-handed,
    outdoors, with a bird in the other hand.
--}}
<div class="mx-auto max-w-3xl">
    <div class="mb-8 border-b border-border pb-6">
        <a href="{{ route('health.index') }}" wire:navigate
           class="inline-flex min-h-11 items-center text-[13px] font-medium text-primary hover:underline">
            &larr; Back to Health Records
        </a>

        <h1 class="mt-1 text-[32px] font-semibold leading-[1.15] tracking-[-0.02em] text-foreground">
            {{ $this->isEditing() ? 'Edit Health Record' : 'Add Health Record' }}
        </h1>
        <p class="mt-2 max-w-[65ch] text-[15px] leading-relaxed text-muted-foreground">
            Record a vaccination, medication, deworming, treatment or check-up for one bird.
        </p>
    </div>

    {{-- wire:submit posts through Livewire, which carries the CSRF token on
         every request automatically. --}}
    <form wire:submit="save" class="card overflow-hidden">
        <div class="grid gap-6 p-5 sm:grid-cols-2 sm:p-8">
            <div class="sm:col-span-2">
                <label for="broodcock_id" class="label">Bird <span class="text-destructive">*</span></label>
                <select id="broodcock_id"
                        wire:model="broodcock_id"
                        @class(['input mt-1', 'input-error' => $errors->has('broodcock_id')])>
                    <option value="">Choose a bird&hellip;</option>
                    @foreach ($this->birds as $bird)
                        <option value="{{ $bird->id }}">{{ $bird->displayName() }}</option>
                    @endforeach
                </select>
                @error('broodcock_id') <p class="error">{{ $message }}</p> @enderror
                <p class="help">Birds are listed by name, with the band number in brackets.</p>
            </div>

            {{-- A rule, not a gap: the record's identity is settled above, its
                 substance below. --}}
            <hr class="border-border sm:col-span-2">

            <div>
                <label for="record_type" class="label">Record Type <span class="text-destructive">*</span></label>
                <select id="record_type"
                        wire:model.live="record_type"
                        @class(['input mt-1', 'input-error' => $errors->has('record_type')])>
                    <option value="">Choose a type&hellip;</option>
                    @foreach ($this->recordTypes as $type)
                        <option value="{{ $type->value }}">{{ $type->label() }}</option>
                    @endforeach
                </select>
                @error('record_type') <p class="error">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="product_name" class="label">Product Name</label>
                <input id="product_name"
                       type="text"
                       wire:model="product_name"
                       placeholder="e.g. Newcastle Disease vaccine"
                       @class(['input mt-1', 'input-error' => $errors->has('product_name')])>
                @error('product_name') <p class="error">{{ $message }}</p> @enderror
                <p class="help">The name of the vaccine, medicine or dewormer used.</p>
            </div>

            <div>
                <label for="dosage" class="label">Dosage</label>
                <input id="dosage"
                       type="text"
                       wire:model="dosage"
                       placeholder="e.g. 0.5 ml"
                       @class(['input datum mt-1', 'input-error' => $errors->has('dosage')])>
                @error('dosage') <p class="error">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="condition" class="label">Condition</label>
                <input id="condition"
                       type="text"
                       wire:model="condition"
                       placeholder="e.g. Healthy, coughing, wound on left leg"
                       @class(['input mt-1', 'input-error' => $errors->has('condition')])>
                @error('condition') <p class="error">{{ $message }}</p> @enderror
                <p class="help">How the bird was on the day.</p>
            </div>

            <hr class="border-border sm:col-span-2">

            <div>
                <label for="checkup_date" class="label">Check-up Date <span class="text-destructive">*</span></label>
                <input id="checkup_date"
                       type="date"
                       wire:model.live="checkup_date"
                       max="{{ today()->toDateString() }}"
                       @class(['input datum mt-1', 'input-error' => $errors->has('checkup_date')])>
                @error('checkup_date') <p class="error">{{ $message }}</p> @enderror
                <p class="help">The day this was actually done. It cannot be in the future.</p>
            </div>

            <div>
                <label for="next_due_date" class="label">Next Due Date</label>
                <input id="next_due_date"
                       type="date"
                       wire:model="next_due_date"
                       min="{{ $checkup_date ?: today()->toDateString() }}"
                       @class(['input datum mt-1', 'input-error' => $errors->has('next_due_date')])>
                @error('next_due_date') <p class="error">{{ $message }}</p> @enderror

                {{-- A nudge, not a rule. Vaccinations and dewormings recur, so
                     leaving this blank is usually a mistake - but a one-off
                     booster legitimately has no follow-up. --}}
                @if ($this->expectsNextDueDate && ! $next_due_date)
                    <p class="mt-2 rounded-[4px] border-l-2 border-warning bg-warning-bg px-3 py-2 text-[12px] leading-snug text-warning">
                        A {{ $this->selectedTypeLabel }} usually needs a
                        follow-up. Adding a next due date puts this bird on the vaccination schedule
                        so nobody forgets. You can still save without one.
                    </p>
                @else
                    <p class="help">Leave blank if no follow-up is needed.</p>
                @endif
            </div>

            <hr class="border-border sm:col-span-2">

            <div class="sm:col-span-2">
                <label for="remarks" class="label">Remarks</label>
                <textarea id="remarks"
                          rows="3"
                          wire:model="remarks"
                          placeholder="Anything else worth remembering about this treatment."
                          @class(['input mt-1', 'input-error' => $errors->has('remarks')])></textarea>
                @error('remarks') <p class="error">{{ $message }}</p> @enderror
                <p class="help">Internal note for farm staff only. Customers never see this.</p>
            </div>
        </div>

        {{-- The action bar is sunk, so the form's edge is unmistakable on a
             phone where the card runs to the fold. --}}
        <div class="flex flex-col-reverse gap-3 border-t border-border bg-muted px-5 py-4 sm:flex-row sm:justify-end sm:px-8">
            <a href="{{ route('health.index') }}" wire:navigate class="btn-secondary">Cancel</a>

            <button type="submit" class="btn-primary" wire:loading.attr="disabled" wire:target="save">
                <span wire:loading.remove wire:target="save">
                    {{ $this->isEditing() ? 'Save Changes' : 'Save Health Record' }}
                </span>
                <span wire:loading wire:target="save">Saving&hellip;</span>
            </button>
        </div>
    </form>
</div>
