{{-- One measured column, left-aligned: labels above, help and errors under,
     related fields grouped under a small heading and a hairline. The save bar
     sits outside the column so it can run the full width of the canvas. --}}
<div>
    <a href="{{ route('performance.index') }}" wire:navigate
       class="-mt-2 inline-flex min-h-11 items-center gap-1.5 text-[14px] font-medium text-primary hover:underline">
        <span aria-hidden="true">&larr;</span> Back to performance records
    </a>

    <x-anchor-block class="mt-2" eyebrow="Performance"
                    :title="$this->isEditing() ? 'Edit Performance Record' : 'Add a Performance Record'">
        Fields marked with <span class="text-destructive">*</span> are required. Everything else can be filled in later.
    </x-anchor-block>

    <form wire:submit="save" class="mt-8">
        <div class="max-w-[40rem] space-y-12">
            {{-- What happened --}}
            <section aria-labelledby="event-heading">
                <h2 id="event-heading" class="font-display text-[19px] font-semibold tracking-[-0.015em] text-foreground">The Event</h2>
                <p class="mt-1 text-[15px] leading-relaxed text-muted-foreground">Which bird, when, and what kind of event it was.</p>

                <div class="mt-5 space-y-6 border-t border-border pt-6">
                    <x-form-select id="broodcock_id" label="Bird" required wire:model.live="broodcock_id">
                        <option value="">Choose a bird</option>
                        @foreach ($this->birdOptions as $bird)
                            <option value="{{ $bird->id }}">{{ $bird->displayName() }}</option>
                        @endforeach
                    </x-form-select>

                    <div>
                        <label for="event_date" class="label">Date of the Event <span class="text-destructive">*</span></label>
                        <input id="event_date" type="date" wire:model.live.blur="event_date"
                               max="{{ now()->toDateString() }}"
                               aria-describedby="event_date-help @error('event_date') event_date-error @enderror"
                               @error('event_date') aria-invalid="true" @enderror
                               class="input datum @error('event_date') input-error @enderror">
                        <p id="event_date-help" class="help">The day the event actually took place. It cannot be a future date.</p>
                        @error('event_date') <p id="event_date-error" class="error" role="alert">{{ $message }}</p> @enderror
                    </div>

                    <x-form-select id="event_type" label="Type of Event" required wire:model.live="event_type">
                        @foreach ($this->eventTypeOptions() as $option)
                            <option value="{{ $option->value }}">{{ $option->label() }}</option>
                        @endforeach
                    </x-form-select>
                </div>
            </section>

            {{-- Outcome --}}
            <section aria-labelledby="outcome-heading">
                <h2 id="outcome-heading" class="font-display text-[19px] font-semibold tracking-[-0.015em] text-foreground">How the Bird Did</h2>
                <p class="mt-1 text-[15px] leading-relaxed text-muted-foreground">Measurements taken and, for a contest, the outcome.</p>

                <div class="mt-5 space-y-6 border-t border-border pt-6">
                    {{-- A weigh-in or conditioning session has no winner, so the
                         selector is not merely disabled - it is absent, and the
                         server forces "Not applicable" regardless of what is sent. --}}
                    @if ($this->resultApplies())
                        <x-form-select id="result" label="Result" required wire:model.live.blur="result">
                            <option value="">Choose the result</option>
                            @foreach ($this->resultOptions() as $option)
                                <option value="{{ $option->value }}">{{ $option->label() }}</option>
                            @endforeach
                        </x-form-select>
                    @else
                        <p class="border-l-2 border-border pl-3 text-[15px] leading-relaxed text-muted-foreground">
                            A
                            <strong class="font-medium text-foreground">{{ $this->eventTypeLabel() }}</strong>
                            has no winner or loser, so no result is recorded for it.
                            It will not count towards this bird's win rate.
                        </p>
                    @endif

                    <div>
                        <label for="weight" class="label">Weight (kg)</label>
                        <input id="weight" type="number" step="0.01" min="0" inputmode="decimal"
                               wire:model.live.blur="weight" placeholder="e.g. 2.10"
                               aria-describedby="weight-help @error('weight') weight-error @enderror"
                               @error('weight') aria-invalid="true" @enderror
                               class="input datum @error('weight') input-error @enderror">
                        <p id="weight-help" class="help">Leave empty if the bird was not weighed at this event.</p>
                        @error('weight') <p id="weight-error" class="error" role="alert">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="duration_seconds" class="label">Duration (seconds)</label>
                        <input id="duration_seconds" type="number" step="1" min="0" inputmode="numeric"
                               wire:model.live.blur="duration_seconds" placeholder="e.g. 180"
                               aria-describedby="duration_seconds-help @error('duration_seconds') duration_seconds-error @enderror"
                               @error('duration_seconds') aria-invalid="true" @enderror
                               class="input datum @error('duration_seconds') input-error @enderror">
                        <p id="duration_seconds-help" class="help">How long it lasted, in seconds. 180 seconds is 3 minutes.</p>
                        @error('duration_seconds') <p id="duration_seconds-error" class="error" role="alert">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <span class="label" id="rating-label">Rating</span>

                        {{-- Ink stars, not amber. Colour in this system means
                             bloodline, so "chosen" is drawn as a filled shape
                             against an outlined one - which also survives a
                             monochrome printout. --}}
                        <div class="flex flex-wrap items-center gap-1" role="group" aria-labelledby="rating-label" aria-describedby="rating-help">
                            @for ($star = 1; $star <= 5; $star++)
                                <button type="button"
                                        wire:click="setRating({{ $star }})"
                                        aria-pressed="{{ (int) $rating === $star ? 'true' : 'false' }}"
                                        class="inline-flex h-11 w-11 items-center justify-center rounded-[8px] transition-colors hover:bg-muted focus-visible:ring-2 focus-visible:ring-primary">
                                    <span class="sr-only">{{ $star }} {{ Str::plural('star', $star) }}</span>
                                    <svg class="h-7 w-7 {{ $rating !== null && $star <= (int) $rating ? 'text-foreground' : 'text-muted-foreground' }}"
                                         viewBox="0 0 20 20" aria-hidden="true"
                                         fill="{{ $rating !== null && $star <= (int) $rating ? 'currentColor' : 'none' }}"
                                         stroke="currentColor"
                                         stroke-width="{{ $rating !== null && $star <= (int) $rating ? '0' : '1.25' }}">
                                        <path stroke-linejoin="round" d="M10 1.5l2.6 5.27 5.82.85-4.21 4.1.99 5.79L10 14.78l-5.2 2.73.99-5.79-4.21-4.1 5.82-.85L10 1.5z"/>
                                    </svg>
                                </button>
                            @endfor

                            <span @class([
                                'ml-2 text-[15px] text-muted-foreground',
                                'datum' => $rating !== null && $rating !== '',
                            ])>
                                {{ $rating !== null && $rating !== '' ? $rating.' of 5' : 'Not rated' }}
                            </span>
                        </div>

                        <p id="rating-help" class="help">
                            How well the bird performed, from 1 star (poor) to 5 stars (excellent).
                            Tap a star again to clear the rating.
                        </p>
                        @error('rating') <p class="error" role="alert">{{ $message }}</p> @enderror
                    </div>
                </div>
            </section>

            {{-- Internal notes. Customers can read performance history but must
                 never see remarks - the policy is the gate, this is the courtesy. --}}
            @if (auth()->user()?->isInternal())
                <section aria-labelledby="remarks-heading">
                    <h2 id="remarks-heading" class="font-display text-[19px] font-semibold tracking-[-0.015em] text-foreground">Remarks</h2>
                    <p class="mt-1 text-[15px] leading-relaxed text-muted-foreground">
                        Notes for farm staff only. Customers never see these.
                    </p>

                    <div class="mt-5 border-t border-border pt-6">
                        <label for="remarks" class="label">Remarks</label>
                        <textarea id="remarks" rows="4" wire:model.live.blur="remarks"
                                  placeholder="e.g. Tired in the last minute, needs more conditioning."
                                  @error('remarks') aria-invalid="true" aria-describedby="remarks-error" @enderror
                                  class="input @error('remarks') input-error @enderror"></textarea>
                        @error('remarks') <p id="remarks-error" class="error" role="alert">{{ $message }}</p> @enderror
                    </div>
                </section>
            @endif
        </div>

        <div class="sticky bottom-0 z-20 -mx-4 mt-12 flex justify-end gap-3 border-t border-border bg-background/95 px-4 py-3 sm:-mx-6 sm:px-6 lg:-mx-8 lg:px-8">
            <a href="{{ route('performance.index') }}" wire:navigate class="btn-secondary">Cancel</a>
            <button type="submit" class="btn-primary" wire:loading.attr="disabled" wire:target="save">
                <span class="btn-spinner" wire:loading wire:target="save"></span>
                {{ $this->isEditing() ? 'Save Changes' : 'Save Performance Record' }}
            </button>
        </div>
    </form>
</div>
