{{--
    Create / edit a health record.

    Validation messages sit directly under the field they belong to and are
    written in plain sentences, because the people filling this in are farm
    staff, not developers.
--}}
<div class="mx-auto max-w-3xl">
    <div class="mb-6">
        <a href="{{ route('health.index') }}" wire:navigate class="text-sm font-medium text-brand-700 hover:text-brand-800">
            &larr; Back to Health Records
        </a>

        <h1 class="mt-2 text-2xl font-bold tracking-tight text-gray-900">
            {{ $this->isEditing() ? 'Edit Health Record' : 'Add Health Record' }}
        </h1>
        <p class="mt-1 text-sm text-gray-600">
            Record a vaccination, medication, deworming, treatment or check-up for one bird.
        </p>
    </div>

    {{-- wire:submit posts through Livewire, which carries the CSRF token on
         every request automatically. --}}
    <form wire:submit="save" class="card p-6 sm:p-8">
        <div class="grid gap-6 sm:grid-cols-2">
            <div class="sm:col-span-2">
                <label for="broodcock_id" class="label">Bird <span class="text-rose-600">*</span></label>
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

            <div>
                <label for="record_type" class="label">Record Type <span class="text-rose-600">*</span></label>
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
                       @class(['input mt-1', 'input-error' => $errors->has('dosage')])>
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

            <div>
                <label for="checkup_date" class="label">Check-up Date <span class="text-rose-600">*</span></label>
                <input id="checkup_date"
                       type="date"
                       wire:model.live="checkup_date"
                       max="{{ today()->toDateString() }}"
                       @class(['input mt-1', 'input-error' => $errors->has('checkup_date')])>
                @error('checkup_date') <p class="error">{{ $message }}</p> @enderror
                <p class="help">The day this was actually done. It cannot be in the future.</p>
            </div>

            <div>
                <label for="next_due_date" class="label">Next Due Date</label>
                <input id="next_due_date"
                       type="date"
                       wire:model="next_due_date"
                       min="{{ $checkup_date ?: today()->toDateString() }}"
                       @class(['input mt-1', 'input-error' => $errors->has('next_due_date')])>
                @error('next_due_date') <p class="error">{{ $message }}</p> @enderror

                {{-- A nudge, not a rule. Vaccinations and dewormings recur, so
                     leaving this blank is usually a mistake - but a one-off
                     booster legitimately has no follow-up. --}}
                @if ($this->expectsNextDueDate && ! $next_due_date)
                    <p class="mt-1 rounded-lg bg-amber-50 p-2 text-xs text-amber-800 ring-1 ring-amber-200">
                        A {{ $this->selectedTypeLabel }} usually needs a
                        follow-up. Adding a next due date puts this bird on the vaccination schedule
                        so nobody forgets. You can still save without one.
                    </p>
                @else
                    <p class="help">Leave blank if no follow-up is needed.</p>
                @endif
            </div>

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

        <div class="mt-8 flex flex-col-reverse gap-3 border-t border-gray-200 pt-6 sm:flex-row sm:justify-end">
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
