{{--
    Recording a death.

    Two steps on purpose. Step one collects and validates; step two names the
    bird and asks for a deliberate confirmation. The person doing this has just
    lost an animal they cared for - a single mis-click should not be able to
    mark the wrong bird dead.
--}}
<div class="mx-auto max-w-2xl">
    <div class="mb-10">
        <a href="{{ route('mortality.index') }}" wire:navigate
           class="inline-flex items-center gap-1 py-2 text-sm font-medium text-ink-80 hover:text-ink">
            &larr; Back to Mortality Records
        </a>

        <h1 class="mt-2 text-[34px] font-semibold tracking-[-0.022em] leading-[1.12] text-ink">Record a Death</h1>
        <p class="mt-3 text-[17px] leading-relaxed text-ink-48">
            Fill this in when a bird dies. The bird will be marked as
            <strong>deceased</strong> and will no longer appear in the active flock
            or in the list of birds you can breed.
        </p>
    </div>

    <form wire:submit="review" class="card space-y-10 p-6">
        @csrf

        {{-- ---------------------------------------------------------------
             Which bird
        ---------------------------------------------------------------- --}}
        <div>
            <label for="birdSearch" class="label">Find the Bird</label>
            <input id="birdSearch"
                   type="search"
                   wire:model.live.debounce.300ms="birdSearch"
                   placeholder="Type a name, band number, breed or bloodline"
                   autocomplete="off"
                   class="input mt-1">
            <p class="help">Optional. Use this if the list below is long.</p>
        </div>

        <div>
            <label for="broodcock_id" class="label">Which Bird Died? <span class="text-alert">*</span></label>

            <select id="broodcock_id"
                    wire:model.live="broodcock_id"
                    @class(['input mt-1', 'input-error' => $errors->has('broodcock_id')])
                    required>
                <option value="">-- Choose a bird --</option>
                @foreach ($this->eligibleBirds as $bird)
                    <option value="{{ $bird->id }}">{{ $bird->displayName() }}</option>
                @endforeach
            </select>

            @error('broodcock_id')
                <p class="error">{{ $message }}</p>
            @enderror

            @if ($this->eligibleBirds->isEmpty())
                <p class="help">
                    @if ($birdSearch !== '')
                        No living bird matches "{{ $birdSearch }}". Clear the search box to see all birds.
                    @else
                        There are no birds available. Every bird on record already has a death recorded.
                    @endif
                </p>
            @else
                <p class="help">
                    Birds already marked as deceased are not shown - a bird can only be recorded as dead once.
                </p>
            @endif
        </div>

        {{-- ---------------------------------------------------------------
             When
        ---------------------------------------------------------------- --}}
        <div>
            <label for="date_of_death" class="label">Date of Death <span class="text-alert">*</span></label>
            <input id="date_of_death"
                   type="date"
                   wire:model="date_of_death"
                   max="{{ now()->toDateString() }}"
                   @if ($this->selectedBird?->date_hatched)
                       min="{{ $this->selectedBird->date_hatched->toDateString() }}"
                   @endif
                   @class(['input mt-1', 'input-error' => $errors->has('date_of_death')])
                   required>

            @error('date_of_death')
                <p class="error">{{ $message }}</p>
            @enderror

            @if ($this->selectedBird?->date_hatched)
                <p class="help">
                    This bird hatched on {{ $this->selectedBird->date_hatched->format('d M Y') }},
                    so the date of death must be on or after that day. It cannot be in the future.
                </p>
            @else
                <p class="help">The date cannot be in the future.</p>
            @endif
        </div>

        {{-- ---------------------------------------------------------------
             Why
        ---------------------------------------------------------------- --}}
        <div>
            <label for="cause_of_death" class="label">Cause of Death <span class="text-alert">*</span></label>
            <input id="cause_of_death"
                   type="text"
                   list="cause-suggestions"
                   wire:model="cause_of_death"
                   placeholder="For example: Disease"
                   maxlength="255"
                   @class(['input mt-1', 'input-error' => $errors->has('cause_of_death')])
                   required>

            <datalist id="cause-suggestions">
                @foreach ($this->causeSuggestions as $suggestion)
                    <option value="{{ $suggestion }}"></option>
                @endforeach
            </datalist>

            @error('cause_of_death')
                <p class="error">{{ $message }}</p>
            @enderror

            <p class="help">
                Pick one of the suggestions or type your own. Using the same words each
                time makes the mortality report easier to read.
            </p>
        </div>

        <div>
            <label for="disposal_method" class="label">How Was the Bird Disposed Of?</label>
            <input id="disposal_method"
                   type="text"
                   list="disposal-suggestions"
                   wire:model="disposal_method"
                   placeholder="For example: Buried"
                   maxlength="120"
                   @class(['input mt-1', 'input-error' => $errors->has('disposal_method')])>

            <datalist id="disposal-suggestions">
                @foreach ($this->disposalSuggestions as $suggestion)
                    <option value="{{ $suggestion }}"></option>
                @endforeach
            </datalist>

            @error('disposal_method')
                <p class="error">{{ $message }}</p>
            @enderror

            <p class="help">You can leave this blank and fill it in later.</p>
        </div>

        <div>
            <label for="remarks" class="label">Remarks</label>
            <textarea id="remarks"
                      rows="4"
                      wire:model="remarks"
                      placeholder="Anything else worth remembering - symptoms, what the vet said, whether other birds are affected."
                      @class(['input mt-1', 'input-error' => $errors->has('remarks')])></textarea>

            @error('remarks')
                <p class="error">{{ $message }}</p>
            @enderror

            <p class="help">Optional. Only farm staff can read this.</p>
        </div>

        <div class="flex flex-col-reverse gap-3 border-t border-hairline pt-6 sm:flex-row sm:justify-end">
            <a href="{{ route('mortality.index') }}" wire:navigate class="btn-secondary">Cancel</a>
            <button type="submit" class="btn-primary" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="review">Record This Death</span>
                <span wire:loading wire:target="review">Checking...</span>
            </button>
        </div>
    </form>

    {{-- ---------------------------------------------------------------
         Confirmation. Names the bird, and says plainly what will change.
    ---------------------------------------------------------------- --}}
    @if ($confirming && $this->selectedBird)
        <div class="fixed inset-0 z-50 flex items-end justify-center bg-ink/40 p-4 sm:items-center"
             role="dialog"
             aria-modal="true"
             aria-labelledby="confirm-mortality-title"
             wire:keydown.escape="cancelConfirmation">
            <div class="w-full max-w-lg rounded-xl bg-white p-6 shadow-xl">
                <h2 id="confirm-mortality-title" class="text-lg font-semibold text-ink">
                    Record the death of {{ $this->selectedBird->displayName() }}?
                </h2>

                <dl class="mt-4 space-y-2 rounded-lg bg-pearl p-4 text-sm">
                    <div class="flex justify-between gap-4">
                        <dt class="text-ink-80">Date of death</dt>
                        <dd class="font-medium text-ink">
                            {{ \Illuminate\Support\Carbon::parse($date_of_death)->format('d M Y') }}
                        </dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-ink-80">Cause</dt>
                        <dd class="font-medium text-ink">{{ $cause_of_death }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-ink-80">Disposal</dt>
                        <dd class="font-medium text-ink">{{ $disposal_method ?: 'Not recorded' }}</dd>
                    </div>
                </dl>

                <p class="mt-4 text-sm text-ink-80">
                    {{ $this->selectedBird->name }} will be marked as <strong>deceased</strong> and
                    removed from the active flock and from breeding. Only the farm owner can undo this.
                </p>

                <div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                    <button type="button" wire:click="cancelConfirmation" class="btn-secondary">
                        Go Back and Check
                    </button>
                    <button type="button" wire:click="save" class="btn-danger" wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="save">Yes, Record the Death</span>
                        <span wire:loading wire:target="save">Saving...</span>
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
