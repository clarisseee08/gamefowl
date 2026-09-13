{{--
    Recording a death.

    Two steps on purpose. Step one collects and validates; step two names the
    bird and asks for a deliberate confirmation. The person doing this has just
    lost an animal they cared for - a single mis-click should not be able to
    mark the wrong bird dead.
--}}
<div class="max-w-2xl">
    <div class="mb-8 border-b border-border pb-6">
        <a href="{{ route('mortality.index') }}" wire:navigate
           class="-mt-2 inline-flex min-h-11 items-center gap-1 text-[13px] font-medium text-primary hover:underline">
            &larr; Back to Mortality Records
        </a>

        <h1 class="page-title-marked text-[32px] font-semibold leading-[1.15] tracking-[-0.02em] text-foreground">Record a Death</h1>
        <p class="mt-2 max-w-[65ch] text-[15px] leading-relaxed text-muted-foreground">
            Fill this in when a bird dies. The bird will be marked as
            <strong class="font-medium text-foreground">deceased</strong> and will no longer appear in the active flock
            or in the list of birds you can breed.
        </p>
    </div>

    <form wire:submit="review" class="card space-y-6 p-6">
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
            {{-- The HTML `required` attribute is gone with the native control.
                 On a select the browser can no longer show, it blocks the
                 submit and anchors its bubble to a hidden element, so the form
                 stops with nothing on screen to explain it. The server rule is
                 unchanged and its message renders under the control. --}}
            <x-form-select id="broodcock_id" label="Which Bird Died?" required
                           wire:model.live="broodcock_id">
                <x-slot:help>
                    @if ($this->eligibleBirds->isEmpty())
                        @if ($birdSearch !== '')
                            No living bird matches "{{ $birdSearch }}". Clear the search box to see all birds.
                        @else
                            There are no birds available. Every bird on record already has a death recorded.
                        @endif
                    @else
                        Birds already marked as deceased are not shown - a bird can only be recorded as dead once.
                    @endif
                </x-slot:help>

                <option value="">-- Choose a bird --</option>
                @foreach ($this->eligibleBirds as $bird)
                    <option value="{{ $bird->id }}">{{ $bird->displayName() }}</option>
                @endforeach
            </x-form-select>
        </div>

        {{-- ---------------------------------------------------------------
             When
        ---------------------------------------------------------------- --}}
        <div class="border-t border-border pt-6">
            <label for="date_of_death" class="label">Date of Death <span class="text-destructive">*</span></label>
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
        <div class="border-t border-border pt-6">
            <label for="cause_of_death" class="label">Cause of Death <span class="text-destructive">*</span></label>
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

        <div class="flex flex-col-reverse gap-3 border-t border-border pt-6 sm:flex-row sm:justify-end">
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
        <div class="fixed inset-0 z-50 flex items-end justify-center bg-foreground/40 p-4 sm:items-center"
             x-data x-trap.noscroll="true" @keydown.escape.window="$el.querySelector('.btn-secondary')?.click()" role="dialog"
             aria-modal="true"
             aria-labelledby="confirm-mortality-title"
             wire:keydown.escape="cancelConfirmation">
            <div class="card w-full max-w-lg p-6">
                <h2 id="confirm-mortality-title" class="text-[22px] font-semibold leading-[1.2] tracking-[-0.01em] text-foreground">
                    Record the death of {{ $this->selectedBird->displayName() }}?
                </h2>

                {{-- A read-back of exactly what is about to be written, laid out
                     as a ledger stub: labels left, values right, dates in mono. --}}
                <dl class="mt-4 divide-y divide-border rounded-[4px] border border-border bg-muted px-4 text-[15px]">
                    <div class="flex justify-between gap-4 py-2.5">
                        <dt class="text-muted-foreground">Date of death</dt>
                        <dd class="datum font-medium text-foreground">
                            {{ \Illuminate\Support\Carbon::parse($date_of_death)->format('d M Y') }}
                        </dd>
                    </div>
                    <div class="flex justify-between gap-4 py-2.5">
                        <dt class="text-muted-foreground">Cause</dt>
                        <dd class="font-medium text-foreground">{{ $cause_of_death }}</dd>
                    </div>
                    <div class="flex justify-between gap-4 py-2.5">
                        <dt class="text-muted-foreground">Disposal</dt>
                        <dd class="font-medium text-foreground">{{ $disposal_method ?: 'Not recorded' }}</dd>
                    </div>
                </dl>

                <p class="mt-4 rounded-[4px] bg-destructive-bg px-3 py-2.5 text-[15px] leading-relaxed text-destructive">
                    {{ $this->selectedBird->name }} will be marked as <strong class="font-medium">deceased</strong> and
                    removed from the active flock and from breeding. Only the farm owner can undo this.
                </p>

                <div class="mt-6 flex flex-col-reverse gap-3 border-t border-border pt-5 sm:flex-row sm:justify-end">
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
