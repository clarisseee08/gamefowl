{{-- One measured column, left-aligned, grouped under small headings with a
     hairline. The save bar sits outside the column so it spans the canvas. --}}
<div>
    <a href="{{ route('users.index') }}" wire:navigate
       class="-mt-2 inline-flex min-h-11 items-center gap-1.5 text-[14px] font-medium text-primary hover:underline">
        <span aria-hidden="true">&larr;</span> Back to user accounts
    </a>

    <x-anchor-block class="mt-2" eyebrow="Admin" :title="$this->isEditing() ? 'Edit '.$full_name : 'Add a User'">
        Accounts are created here &mdash; people cannot sign themselves up.
    </x-anchor-block>

    <form wire:submit="save" class="mt-8">
        <div class="max-w-[40rem] space-y-12">
            <section aria-labelledby="person-heading">
                <h2 id="person-heading" class="font-display text-[19px] font-semibold tracking-[-0.015em] text-foreground">Person</h2>

                <div class="mt-4 space-y-6 border-t border-border pt-6">
                    <div>
                        <label for="full_name" class="label">Full Name <span class="text-destructive">*</span></label>
                        <input id="full_name" type="text" wire:model.blur="full_name" autocomplete="off"
                               @error('full_name') aria-invalid="true" aria-describedby="full_name-error" @enderror
                               class="input @error('full_name') input-error @enderror">
                        @error('full_name') <p id="full_name-error" class="error" role="alert">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="email" class="label">Email Address <span class="text-destructive">*</span></label>
                        <input id="email" type="email" wire:model.blur="email" autocomplete="off"
                               aria-describedby="email-help @error('email') email-error @enderror"
                               @error('email') aria-invalid="true" @enderror
                               class="input @error('email') input-error @enderror">
                        <p id="email-help" class="help">They will use this to sign in.</p>
                        @error('email') <p id="email-error" class="error" role="alert">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="contact_number" class="label">Contact Number</label>
                        <input id="contact_number" type="tel" inputmode="tel" wire:model.blur="contact_number"
                               @error('contact_number') aria-invalid="true" aria-describedby="contact_number-error" @enderror
                               class="input datum @error('contact_number') input-error @enderror">
                        @error('contact_number') <p id="contact_number-error" class="error" role="alert">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="position" class="label">Position</label>
                        <input id="position" type="text" wire:model.blur="position" placeholder="e.g. Farm Caretaker"
                               @error('position') aria-invalid="true" aria-describedby="position-error" @enderror
                               class="input @error('position') input-error @enderror">
                        @error('position') <p id="position-error" class="error" role="alert">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="address" class="label">Address</label>
                        <input id="address" type="text" wire:model.blur="address"
                               @error('address') aria-invalid="true" aria-describedby="address-error" @enderror
                               class="input @error('address') input-error @enderror">
                        @error('address') <p id="address-error" class="error" role="alert">{{ $message }}</p> @enderror
                    </div>
                </div>
            </section>

            <section aria-labelledby="access-heading">
                <h2 id="access-heading" class="font-display text-[19px] font-semibold tracking-[-0.015em] text-foreground">What they are allowed to do</h2>

                <div class="mt-4 border-t border-border pt-6">
                    {{-- Selected uses the action wash, never a status wash: green
                         here would read as "this role is OK" rather than "this
                         one is chosen". --}}
                    <fieldset @error('role') aria-describedby="role-error" @enderror>
                        <legend class="label">Role <span class="text-destructive">*</span></legend>
                        <div class="space-y-2">
                            @foreach ($this->roleOptions() as $option)
                                <label class="flex min-h-11 cursor-pointer items-start gap-3 rounded-[8px] border p-4 transition-colors
                                              {{ $role === $option->value ? 'border-primary bg-primary-50' : 'border-border hover:bg-muted/60' }}">
                                    <input type="radio" name="role" value="{{ $option->value }}" wire:model.live="role"
                                           class="mt-0.5 h-5 w-5 shrink-0 accent-primary">
                                    <span>
                                        <span class="block text-[15px] font-medium text-foreground">{{ $option->label() }}</span>
                                        <span class="mt-0.5 block text-[15px] leading-snug text-muted-foreground">{{ $option->description() }}</span>
                                    </span>
                                </label>
                            @endforeach
                        </div>
                        @error('role') <p id="role-error" class="error" role="alert">{{ $message }}</p> @enderror
                    </fieldset>

                    <div class="mt-6 border-t border-border pt-5">
                        <label class="flex min-h-11 cursor-pointer items-start gap-3">
                            <input type="checkbox" wire:model.live="is_active"
                                   @error('is_active') aria-invalid="true" aria-describedby="is_active-error" @enderror
                                   class="mt-0.5 h-5 w-5 shrink-0 rounded-[2px] accent-primary">
                            <span>
                                <span class="block text-[15px] font-medium text-foreground">This person can sign in</span>
                                <span class="mt-0.5 block text-[15px] leading-snug text-muted-foreground">
                                    Untick to deactivate the account. Their records are always kept.
                                </span>
                            </span>
                        </label>
                        @error('is_active') <p id="is_active-error" class="error" role="alert">{{ $message }}</p> @enderror
                    </div>
                </div>
            </section>

            <section aria-labelledby="password-heading">
                <h2 id="password-heading" class="font-display text-[19px] font-semibold tracking-[-0.015em] text-foreground">Password</h2>
                <p class="mt-1 text-[15px] leading-relaxed text-muted-foreground">
                    @if ($this->isEditing())
                        Leave both boxes empty to keep the current password unchanged.
                    @else
                        At least 8 characters. Give it to the person directly &mdash; it is not emailed.
                    @endif
                </p>

                <div class="mt-4 space-y-6 border-t border-border pt-6">
                    <div>
                        <label for="password" class="label">
                            Password @unless ($this->isEditing())<span class="text-destructive">*</span>@endunless
                        </label>
                        <input id="password" type="password" wire:model.blur="password" autocomplete="new-password"
                               @error('password') aria-invalid="true" aria-describedby="password-error" @enderror
                               class="input @error('password') input-error @enderror">
                        @error('password') <p id="password-error" class="error" role="alert">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="password_confirmation" class="label">Confirm Password</label>
                        <input id="password_confirmation" type="password" wire:model.blur="password_confirmation"
                               autocomplete="new-password" class="input">
                    </div>
                </div>
            </section>
        </div>

        <div class="sticky bottom-0 z-20 -mx-4 mt-12 flex justify-end gap-3 border-t border-border bg-background/95 px-4 py-3 sm:-mx-6 sm:px-6 lg:-mx-8 lg:px-8">
            <a href="{{ route('users.index') }}" wire:navigate class="btn-secondary">Cancel</a>
            <button type="submit" class="btn-primary" wire:loading.attr="disabled" wire:target="save">
                <span class="btn-spinner" wire:loading wire:target="save"></span>
                {{ $this->isEditing() ? 'Save Changes' : 'Create Account' }}
            </button>
        </div>
    </form>
</div>
