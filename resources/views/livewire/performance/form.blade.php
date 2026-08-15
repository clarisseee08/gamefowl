<div>
    <div class="mb-6">
        <a href="{{ route('performance.index') }}" class="text-sm font-medium text-brand-700 hover:text-brand-800">
            &larr; Back to performance records
        </a>
        <h1 class="mt-2 text-2xl font-bold tracking-tight text-gray-900">
            {{ $this->isEditing() ? 'Edit Performance Record' : 'Add a Performance Record' }}
        </h1>
        <p class="mt-1 text-sm text-gray-600">
            Fields marked with <span class="text-rose-600">*</span> are required. Everything
            else can be filled in later.
        </p>
    </div>

    <form wire:submit="save" class="space-y-6">
        {{-- What happened --}}
        <section class="card p-6">
            <h2 class="text-base font-semibold text-gray-900">The Event</h2>
            <p class="mt-1 text-sm text-gray-600">Which bird, when, and what kind of event it was.</p>

            <div class="mt-6 grid gap-5 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label for="broodcock_id" class="label">Bird <span class="text-rose-600">*</span></label>
                    <select id="broodcock_id" wire:model.live="broodcock_id"
                            class="input mt-1 @error('broodcock_id') input-error @enderror">
                        <option value="">Choose a bird</option>
                        @foreach ($this->birdOptions as $bird)
                            <option value="{{ $bird->id }}">{{ $bird->displayName() }}</option>
                        @endforeach
                    </select>
                    @error('broodcock_id') <p class="error">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="event_date" class="label">Date of the Event <span class="text-rose-600">*</span></label>
                    <input id="event_date" type="date" wire:model.live.blur="event_date"
                           max="{{ now()->toDateString() }}"
                           class="input mt-1 @error('event_date') input-error @enderror">
                    <p class="help">The day the event actually took place. It cannot be a future date.</p>
                    @error('event_date') <p class="error">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="event_type" class="label">Type of Event <span class="text-rose-600">*</span></label>
                    <select id="event_type" wire:model.live="event_type"
                            class="input mt-1 @error('event_type') input-error @enderror">
                        @foreach ($this->eventTypeOptions() as $option)
                            <option value="{{ $option->value }}">{{ $option->label() }}</option>
                        @endforeach
                    </select>
                    @error('event_type') <p class="error">{{ $message }}</p> @enderror
                </div>
            </div>
        </section>

        {{-- Outcome --}}
        <section class="card p-6">
            <h2 class="text-base font-semibold text-gray-900">How the Bird Did</h2>
            <p class="mt-1 text-sm text-gray-600">Measurements taken and, for a contest, the outcome.</p>

            <div class="mt-6 grid gap-5 sm:grid-cols-2">
                {{-- A weigh-in or conditioning session has no winner, so the
                     selector is not merely disabled - it is absent, and the
                     server forces "Not applicable" regardless of what is sent. --}}
                @if ($this->resultApplies())
                    <div class="sm:col-span-2">
                        <label for="result" class="label">Result <span class="text-rose-600">*</span></label>
                        <select id="result" wire:model.live.blur="result"
                                class="input mt-1 @error('result') input-error @enderror">
                            <option value="">Choose the result</option>
                            @foreach ($this->resultOptions() as $option)
                                <option value="{{ $option->value }}">{{ $option->label() }}</option>
                            @endforeach
                        </select>
                        @error('result') <p class="error">{{ $message }}</p> @enderror
                    </div>
                @else
                    <div class="sm:col-span-2 rounded-lg bg-gray-50 p-4 ring-1 ring-gray-200">
                        <p class="text-sm text-gray-700">
                            A
                            <strong>{{ $this->eventTypeLabel() }}</strong>
                            has no winner or loser, so no result is recorded for it.
                            It will not count towards this bird's win rate.
                        </p>
                    </div>
                @endif

                <div>
                    <label for="weight" class="label">Weight (kg)</label>
                    <input id="weight" type="number" step="0.01" min="0" inputmode="decimal"
                           wire:model.live.blur="weight" placeholder="e.g. 2.10"
                           class="input mt-1 @error('weight') input-error @enderror">
                    <p class="help">Leave empty if the bird was not weighed at this event.</p>
                    @error('weight') <p class="error">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="duration_seconds" class="label">Duration (seconds)</label>
                    <input id="duration_seconds" type="number" step="1" min="0" inputmode="numeric"
                           wire:model.live.blur="duration_seconds" placeholder="e.g. 180"
                           class="input mt-1 @error('duration_seconds') input-error @enderror">
                    <p class="help">How long it lasted, in seconds. 180 seconds is 3 minutes.</p>
                    @error('duration_seconds') <p class="error">{{ $message }}</p> @enderror
                </div>

                <div class="sm:col-span-2">
                    <span class="label" id="rating-label">Rating</span>
                    <p class="help">
                        How well the bird performed, from 1 star (poor) to 5 stars (excellent).
                        Tap a star again to clear the rating.
                    </p>

                    <div class="mt-2 flex items-center gap-2" role="group" aria-labelledby="rating-label">
                        @for ($star = 1; $star <= 5; $star++)
                            <button type="button"
                                    wire:click="setRating({{ $star }})"
                                    aria-pressed="{{ (int) $rating === $star ? 'true' : 'false' }}"
                                    class="rounded-lg p-2 transition hover:bg-amber-50 focus-visible:ring-2 focus-visible:ring-brand-600">
                                <span class="sr-only">{{ $star }} {{ Str::plural('star', $star) }}</span>
                                <svg class="h-8 w-8 {{ $rating !== null && $star <= (int) $rating ? 'text-amber-400' : 'text-gray-300' }}"
                                     fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                                    <path d="M10 1.5l2.6 5.27 5.82.85-4.21 4.1.99 5.79L10 14.78l-5.2 2.73.99-5.79-4.21-4.1 5.82-.85L10 1.5z"/>
                                </svg>
                            </button>
                        @endfor

                        <span class="ml-2 text-sm text-gray-600">
                            {{ $rating !== null && $rating !== '' ? $rating.' of 5' : 'Not rated' }}
                        </span>
                    </div>

                    @error('rating') <p class="error">{{ $message }}</p> @enderror
                </div>
            </div>
        </section>

        {{-- Internal notes. Customers can read performance history but must
             never see remarks - the policy is the gate, this is the courtesy. --}}
        @if (auth()->user()?->isInternal())
            <section class="card p-6">
                <h2 class="text-base font-semibold text-gray-900">Remarks</h2>
                <p class="mt-1 text-sm text-gray-600">
                    Notes for farm staff only. Customers never see these.
                </p>

                <div class="mt-6">
                    <label for="remarks" class="label">Remarks</label>
                    <textarea id="remarks" rows="4" wire:model.live.blur="remarks"
                              placeholder="e.g. Tired in the last minute, needs more conditioning."
                              class="input mt-1 @error('remarks') input-error @enderror"></textarea>
                    @error('remarks') <p class="error">{{ $message }}</p> @enderror
                </div>
            </section>
        @endif

        <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
            <a href="{{ route('performance.index') }}" class="btn-secondary">Cancel</a>
            <button type="submit" class="btn-primary" wire:loading.attr="disabled" wire:target="save">
                <span wire:loading.remove wire:target="save">
                    {{ $this->isEditing() ? 'Save Changes' : 'Save Performance Record' }}
                </span>
                <span wire:loading wire:target="save">Saving…</span>
            </button>
        </div>
    </form>
</div>
