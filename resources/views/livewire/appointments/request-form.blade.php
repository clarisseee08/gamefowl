{{--
    The visit request form.

    Catalog surface, not console: 17px text, generous spacing, and the
    photo-led page's manners rather than the data-entry screen's density. The
    inputs still take the console's 16px floor because anything smaller makes
    iOS Safari zoom on focus, which throws a visitor out of the form.
--}}
<div>
    @if ($submitted)
        {{-- Deliberately the same message a honeypot submission receives. --}}
        <div class="max-w-2xl rounded-[var(--radius-md)] border border-border bg-card p-6" role="status">
            <h3 class="text-[19px] font-semibold text-foreground">Thank you &mdash; the request is in.</h3>
            <p class="mt-2 max-w-[52ch] text-[17px] leading-relaxed text-muted-foreground">
                The farm will ring you on the number you left to confirm a time. Nothing is
                booked until you have spoken to someone.
            </p>
            <button type="button" wire:click="$set('submitted', false)" class="btn-secondary mt-5">
                Ask about another visit
            </button>
        </div>
    @else
        <form wire:submit="submit" class="max-w-2xl">
            <div class="grid gap-5 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label for="visit_name" class="label">Your name</label>
                    <input id="visit_name" type="text" wire:model.blur="name"
                           class="input mt-1 @error('name') input-error @enderror">
                    @error('name') <p class="error">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="visit_contact" class="label">Contact number</label>
                    <input id="visit_contact" type="tel" wire:model.blur="contact_number"
                           class="input datum mt-1 @error('contact_number') input-error @enderror">
                    <p class="help">The farm will ring this number to confirm.</p>
                    @error('contact_number') <p class="error">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="visit_email" class="label">Email <span class="text-muted-foreground">(optional)</span></label>
                    <input id="visit_email" type="email" wire:model.blur="email"
                           class="input mt-1 @error('email') input-error @enderror">
                    @error('email') <p class="error">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="visit_date" class="label">Preferred date</label>
                    <input id="visit_date" type="date" wire:model.blur="preferred_date"
                           min="{{ today()->toDateString() }}"
                           class="input datum mt-1 @error('preferred_date') input-error @enderror">
                    @error('preferred_date') <p class="error">{{ $message }}</p> @enderror
                </div>

                <div>
                    <x-form-select id="visit_time" label="Time of day" error="preferred_time"
                                   wire:model.blur="preferred_time"
                                   help="The exact hour is settled on the phone.">
                        <option value="morning">Morning</option>
                        <option value="afternoon">Afternoon</option>
                    </x-form-select>
                </div>

                <div>
                    <label for="visit_party" class="label">How many coming</label>
                    <input id="visit_party" type="number" min="1" max="50" wire:model.blur="party_size"
                           class="input datum mt-1 @error('party_size') input-error @enderror">
                    @error('party_size') <p class="error">{{ $message }}</p> @enderror
                </div>

                <div class="sm:col-span-2">
                    <label for="visit_message" class="label">Anything the farm should know <span class="text-muted-foreground">(optional)</span></label>
                    <textarea id="visit_message" rows="3" wire:model.blur="message"
                              class="input mt-1 @error('message') input-error @enderror"></textarea>
                    <p class="help">Which birds you are interested in, or anything else.</p>
                    @error('message') <p class="error">{{ $message }}</p> @enderror
                </div>
            </div>

            {{--
                THE HONEYPOT. Hidden from people, offered to bots.

                aria-hidden and tabindex="-1" keep it away from screen readers
                and keyboard navigation, so it is invisible to everyone using
                the form legitimately and present in the markup to anything
                filling fields by name. autocomplete="off" stops a browser
                helpfully populating it and locking a real visitor out.
            --}}
            <div class="hidden" aria-hidden="true">
                <label for="visit_website">Website</label>
                <input id="visit_website" type="text" tabindex="-1" autocomplete="off" wire:model="website">
            </div>

            <div class="mt-7 flex items-center gap-3">
                <button type="submit" class="btn-primary" wire:loading.attr="disabled">
                    Request a visit
                </button>
                <span wire:loading class="text-[15px] text-muted-foreground">Sending&hellip;</span>
            </div>
        </form>
    @endif
</div>
