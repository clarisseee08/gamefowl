{{--
    Recording a death.

    Two steps on purpose. Step one collects and validates; step two names the
    bird and asks for a deliberate confirmation. The person doing this has just
    lost an animal they cared for - a single mis-click should not be able to
    mark the wrong bird dead. The tone stays plain throughout.
--}}
<div @class(['flex min-h-0 flex-1 flex-col' => $inDialog])>
    @unless ($inDialog)
    <a href="{{ route('mortality.index') }}" wire:navigate
       class="d-link d-link-hover d-link-primary inline-flex min-h-11 items-center gap-1.5 text-[15px] lg:text-xs font-semibold underline-offset-4">
        <span aria-hidden="true">&larr;</span> Back to Mortality Records
    </a>

    <x-anchor-block class="mt-2" eyebrow="Mortality" title="Record a Death">
        <span class="block max-w-[60ch] leading-relaxed">
            Fill this in when a bird dies. The bird will be marked as
            <strong class="font-semibold text-foreground">deceased</strong> and will no longer appear in the active flock
            or in the list of birds you can breed.
        </span>
    </x-anchor-block>
    @endunless

    <form wire:submit="review" @class(['mt-8' => ! $inDialog, 'flex min-h-0 flex-1 flex-col' => $inDialog])>
        @csrf

        <div @class(['max-w-[40rem] space-y-12 pb-10' => ! $inDialog, 'min-h-0 flex-1 space-y-6 overflow-y-auto px-5 pb-5' => $inDialog])>
            {{-- -------------------------------------------------------
                 Which bird
            ------------------------------------------------------- --}}
            <section aria-labelledby="bird-heading" class="space-y-6">
                <h2 id="bird-heading" class="border-b border-border pb-3 font-display text-[19px] lg:text-sm font-semibold tracking-[-0.015em] text-foreground">The bird</h2>

                <div>
                    <label for="birdSearch" class="d-fieldset-legend">Find the Bird</label>
                    <input id="birdSearch"
                           type="search"
                           wire:model.live.debounce.300ms="birdSearch"
                           placeholder="Type a name, band number, breed or bloodline"
                           autocomplete="off"
                           aria-describedby="birdSearch-help"
                           class="d-input w-full mt-1">
                    <p id="birdSearch-help" class="d-label mt-1">Optional. Use this if the list below is long.</p>
                </div>

                <div>
                    {{-- No HTML `required` on a select the browser can no longer
                         show: it would block the submit with nothing on screen.
                         The server rule renders its message under the control. --}}
                    <x-form-select id="broodcock_id" label="Which Bird Died?" required
                                   wire:model.live="broodcock_id">
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
                    <x-bird-preview :bird="$this->selectedBird" target="broodcock_id" class="!mt-3" />
                </div>
            </section>

            {{-- -------------------------------------------------------
                 When and why
            ------------------------------------------------------- --}}
            <section aria-labelledby="when-heading" class="space-y-6">
                <h2 id="when-heading" class="border-b border-border pb-3 font-display text-[19px] lg:text-sm font-semibold tracking-[-0.015em] text-foreground">When and why</h2>

                <div>
                    <label for="date_of_death" class="d-fieldset-legend">Date of Death <span class="text-destructive">*</span></label>
                    <input id="date_of_death"
                           type="date"
                           wire:model="date_of_death"
                           max="{{ now()->toDateString() }}"
                           @if ($this->selectedBird?->date_hatched)
                               min="{{ $this->selectedBird->date_hatched->toDateString() }}"
                           @endif
                           aria-describedby="date_of_death-help @error('date_of_death') date_of_death-error @enderror"
                           @error('date_of_death') aria-invalid="true" @enderror
                           @class(['d-input w-full datum mt-1 sm:max-w-[16rem]', 'd-input-error' => $errors->has('date_of_death')])
                           required>

                    @error('date_of_death')
                        <p id="date_of_death-error" class="mt-1.5 text-[13px] lg:text-[11px] font-medium text-error" role="alert">{{ $message }}</p>
                    @enderror

                    @if ($this->selectedBird?->date_hatched)
                        <p id="date_of_death-help" class="d-label mt-1">
                            This bird hatched on <span class="datum">{{ $this->selectedBird->date_hatched->format('d M Y') }}</span>,
                            so the date of death must be on or after that day. It cannot be in the future.
                        </p>
                    @else
                        <p id="date_of_death-help" class="d-label mt-1">The date cannot be in the future.</p>
                    @endif
                </div>

                <div>
                    {{-- The dropdown sets cause_of_death; "Other" clears it and
                         opens the text box below, which binds to it directly. --}}
                    <x-form-select id="cause_choice" label="Cause of Death" required error="cause_of_death"
                                   wire:model.live="cause_choice"
                                   :help="$cause_choice === $otherChoice ? null : 'Choose the closest cause, or Other to describe it yourself. Using the same words each time makes the mortality report easier to read.'">
                        <option value="">-- Choose a cause --</option>
                        @foreach (\App\Livewire\Mortality\Form::causeOptions() as $cause => $explanation)
                            <option value="{{ $cause }}">{{ $cause }} ({{ $explanation }})</option>
                        @endforeach
                        <option value="{{ $otherChoice }}">Other (type your own)</option>
                    </x-form-select>

                    @if ($cause_choice === $otherChoice)
                        <label for="cause_of_death" class="sr-only">Describe the cause of death</label>
                        <textarea id="cause_of_death"
                                  rows="2"
                                  wire:model="cause_of_death"
                                  maxlength="255"
                                  placeholder="Describe the cause of death"
                                  aria-describedby="cause_of_death-help"
                                  @error('cause_of_death') aria-invalid="true" @enderror
                                  @class(['d-textarea w-full mt-2', 'd-textarea-error' => $errors->has('cause_of_death')])></textarea>
                        <p id="cause_of_death-help" class="d-label mt-1">In a few words, as you would want to read it in the report.</p>
                    @endif
                </div>
            </section>

            {{-- -------------------------------------------------------
                 Afterwards
            ------------------------------------------------------- --}}
            <section aria-labelledby="after-heading" class="space-y-6">
                <h2 id="after-heading" class="border-b border-border pb-3 font-display text-[19px] lg:text-sm font-semibold tracking-[-0.015em] text-foreground">Afterwards</h2>

                <div>
                    <x-form-select id="disposal_choice" label="How Was the Bird Disposed Of?" error="disposal_method"
                                   wire:model.live="disposal_choice"
                                   help="You can leave this blank and fill it in later.">
                        <option value="">-- Not recorded yet --</option>
                        @foreach (\App\Livewire\Mortality\Form::disposalOptions() as $method => $explanation)
                            <option value="{{ $method }}">{{ $method }} ({{ $explanation }})</option>
                        @endforeach
                        <option value="{{ $otherChoice }}">Other (type your own)</option>
                    </x-form-select>

                    @if ($disposal_choice === $otherChoice)
                        <label for="disposal_method" class="sr-only">Describe how the bird was disposed of</label>
                        <textarea id="disposal_method"
                                  rows="2"
                                  wire:model="disposal_method"
                                  maxlength="120"
                                  placeholder="Describe how the bird was disposed of"
                                  @error('disposal_method') aria-invalid="true" @enderror
                                  @class(['d-textarea w-full mt-2', 'd-textarea-error' => $errors->has('disposal_method')])></textarea>
                    @endif
                </div>

                <div>
                    <label for="remarks" class="d-fieldset-legend">Remarks</label>
                    <textarea id="remarks"
                              rows="4"
                              wire:model="remarks"
                              placeholder="Anything else worth remembering - symptoms, what the vet said, whether other birds are affected."
                              aria-describedby="remarks-help @error('remarks') remarks-error @enderror"
                              @error('remarks') aria-invalid="true" @enderror
                              @class(['d-textarea w-full mt-1', 'd-textarea-error' => $errors->has('remarks')])></textarea>

                    @error('remarks')
                        <p id="remarks-error" class="mt-1.5 text-[13px] lg:text-[11px] font-medium text-error" role="alert">{{ $message }}</p>
                    @enderror

                    <p id="remarks-help" class="d-label mt-1">Optional. Only farm staff can read this.</p>
                </div>
            </section>
        </div>

        {{-- The bar stays in reach at the bottom of the screen. Pressing it
             only checks the form - nothing is written until the bird is named
             back in the confirmation below. --}}
        <div @class(['sticky bottom-0 z-10 -mx-4 flex justify-end gap-3 border-t border-border bg-background/95 px-4 py-3 sm:-mx-6 sm:px-6 lg:-mx-8 lg:px-8' => ! $inDialog, 'flex shrink-0 justify-end gap-2 border-t border-border/60 bg-background/60 p-3' => $inDialog])>
            @if ($inDialog)
                <button type="button" class="d-btn d-btn-outline" x-on:click="$dispatch('ems-close-form')">Cancel</button>
            @else
                <a href="{{ route('mortality.index') }}" wire:navigate class="d-btn d-btn-outline">Cancel</a>
            @endif
            <button type="submit" class="d-btn d-btn-primary" wire:loading.attr="disabled" wire:target="review">
                <span class="d-loading d-loading-spinner d-loading-sm" wire:loading wire:target="review"></span>
                <span wire:loading.remove wire:target="review">Record This Death</span>
                <span wire:loading wire:target="review">Checking&hellip;</span>
            </button>
        </div>
    </form>

    {{-- ---------------------------------------------------------------
         Confirmation. Names the bird, and says plainly what will change.
    ---------------------------------------------------------------- --}}
    @if ($confirming && $this->selectedBird)
        <div class="d-modal d-modal-open"
             x-data x-trap.noscroll="true"
             x-on:keydown.escape.window="$wire.cancelConfirmation()"
             role="dialog" aria-modal="true" aria-labelledby="confirm-mortality-title">
            <div class="d-modal-box elev-3 max-w-md">
                <h2 id="confirm-mortality-title" class="font-display text-[22px] lg:text-base font-semibold leading-[1.2] tracking-[-0.015em] text-foreground">
                    Record the death of {{ $this->selectedBird->displayName() }}?
                </h2>

                {{-- A read-back of exactly what is about to be written. --}}
                <dl class="mt-4 border-t border-border text-[15px] lg:text-xs">
                    <div class="flex justify-between gap-4 border-b border-border py-2.5">
                        <dt class="text-muted-foreground">Date of death</dt>
                        <dd class="datum font-medium text-foreground">
                            {{ \Illuminate\Support\Carbon::parse($date_of_death)->format('d M Y') }}
                        </dd>
                    </div>
                    <div class="flex justify-between gap-4 border-b border-border py-2.5">
                        <dt class="text-muted-foreground">Cause</dt>
                        <dd class="text-right font-medium text-foreground">{{ $cause_of_death }}</dd>
                    </div>
                    <div class="flex justify-between gap-4 border-b border-border py-2.5">
                        <dt class="text-muted-foreground">Disposal</dt>
                        <dd @class(['text-right font-medium', 'text-foreground' => $disposal_method, 'text-muted-foreground' => ! $disposal_method])>{{ $disposal_method ?: 'Not recorded' }}</dd>
                    </div>
                </dl>

                <p class="d-alert d-alert-soft d-alert-error mt-4 block px-3 py-2.5 text-[15px] lg:text-xs leading-relaxed text-base-content">
                    {{ $this->selectedBird->name }} will be marked as <strong class="font-semibold">deceased</strong> and
                    removed from the active flock and from breeding. Only the farm owner can undo this.
                </p>

                <div class="d-modal-action flex-col-reverse gap-3 sm:flex-row">
                    <button type="button" wire:click="cancelConfirmation" class="d-btn d-btn-outline">
                        Go Back and Check
                    </button>
                    <button type="button" wire:click="save" class="d-btn d-btn-error" wire:loading.attr="disabled" wire:target="save">
                        <span class="d-loading d-loading-spinner d-loading-sm" wire:loading wire:target="save"></span>
                        <span wire:loading.remove wire:target="save">Yes, Record the Death</span>
                        <span wire:loading wire:target="save">Saving&hellip;</span>
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
