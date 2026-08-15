<div>
    <div class="mb-8">
        <a href="{{ route('users.index') }}"
           class="inline-flex min-h-11 items-center text-[15px] font-medium text-action hover:underline">
            &larr; Back to user accounts
        </a>
        <h1 class="mt-1 text-[32px] font-semibold leading-[1.15] tracking-[-0.02em] text-ink">
            {{ $this->isEditing() ? 'Edit '.$full_name : 'Add a User' }}
        </h1>
        <p class="mt-2 max-w-[60ch] text-[15px] leading-relaxed text-ink-80">
            Accounts are created here — people cannot sign themselves up.
        </p>
    </div>

    <form wire:submit="save" class="space-y-6">
        <section class="card p-6">
            <h2 class="border-b border-hairline pb-4 text-[22px] font-semibold leading-[1.2] tracking-[-0.01em] text-ink">
                Person
            </h2>

            <div class="mt-6 grid gap-5 sm:grid-cols-2">
                <div>
                    <label for="full_name" class="label">Full Name <span class="text-alert">*</span></label>
                    <input id="full_name" type="text" wire:model.blur="full_name"
                           class="input mt-1 @error('full_name') input-error @enderror">
                    @error('full_name') <p class="error">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="email" class="label">Email Address <span class="text-alert">*</span></label>
                    <input id="email" type="email" wire:model.blur="email" autocomplete="off"
                           class="input datum mt-1 @error('email') input-error @enderror">
                    <p class="help">They will use this to sign in.</p>
                    @error('email') <p class="error">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="contact_number" class="label">Contact Number</label>
                    <input id="contact_number" type="tel" inputmode="tel" wire:model.blur="contact_number"
                           class="input datum mt-1 @error('contact_number') input-error @enderror">
                    @error('contact_number') <p class="error">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="position" class="label">Position</label>
                    <input id="position" type="text" wire:model.blur="position" placeholder="e.g. Farm Caretaker"
                           class="input mt-1 @error('position') input-error @enderror">
                    @error('position') <p class="error">{{ $message }}</p> @enderror
                </div>

                <div class="sm:col-span-2">
                    <label for="address" class="label">Address</label>
                    <input id="address" type="text" wire:model.blur="address"
                           class="input mt-1 @error('address') input-error @enderror">
                    @error('address') <p class="error">{{ $message }}</p> @enderror
                </div>
            </div>
        </section>

        <section class="card p-6">
            <h2 class="border-b border-hairline pb-4 text-[22px] font-semibold leading-[1.2] tracking-[-0.01em] text-ink">
                What they are allowed to do
            </h2>

            {{-- Selected uses the action wash, never a status wash: green here
                 would read as "this role is OK" rather than "this one is chosen". --}}
            <div class="mt-6 space-y-2">
                @foreach ($this->roleOptions() as $option)
                    <label class="flex cursor-pointer items-start gap-3 rounded-[4px] border p-4 transition-colors
                                  {{ $role === $option->value ? 'border-action bg-action-wash' : 'border-hairline hover:bg-pearl' }}">
                        <input type="radio" name="role" value="{{ $option->value }}" wire:model.live="role"
                               class="mt-0.5 h-5 w-5 shrink-0 accent-action">
                        <span>
                            <span class="block text-[15px] font-medium text-ink">{{ $option->label() }}</span>
                            <span class="mt-0.5 block text-[15px] leading-snug text-ink-80">{{ $option->description() }}</span>
                        </span>
                    </label>
                @endforeach
                @error('role') <p class="error">{{ $message }}</p> @enderror
            </div>

            <div class="mt-6 border-t border-hairline pt-5">
                <label class="flex cursor-pointer items-start gap-3">
                    <input type="checkbox" wire:model.live="is_active"
                           class="mt-0.5 h-5 w-5 shrink-0 rounded-[2px] accent-action">
                    <span>
                        <span class="block text-[15px] font-medium text-ink">This person can sign in</span>
                        <span class="mt-0.5 block text-[15px] leading-snug text-ink-80">
                            Untick to deactivate the account. Their records are always kept.
                        </span>
                    </span>
                </label>
                @error('is_active') <p class="error">{{ $message }}</p> @enderror
            </div>
        </section>

        <section class="card p-6">
            <h2 class="border-b border-hairline pb-4 text-[22px] font-semibold leading-[1.2] tracking-[-0.01em] text-ink">
                Password
            </h2>
            <p class="mt-4 max-w-[60ch] text-[15px] leading-relaxed text-ink-80">
                @if ($this->isEditing())
                    Leave both boxes empty to keep the current password unchanged.
                @else
                    At least 8 characters. Give it to the person directly — it is not emailed.
                @endif
            </p>

            <div class="mt-6 grid gap-5 sm:grid-cols-2">
                <div>
                    <label for="password" class="label">
                        Password @unless ($this->isEditing())<span class="text-alert">*</span>@endunless
                    </label>
                    <input id="password" type="password" wire:model.blur="password" autocomplete="new-password"
                           class="input mt-1 @error('password') input-error @enderror">
                    @error('password') <p class="error">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="password_confirmation" class="label">Confirm Password</label>
                    <input id="password_confirmation" type="password" wire:model.blur="password_confirmation"
                           autocomplete="new-password" class="input mt-1">
                </div>
            </div>
        </section>

        <div class="flex flex-col-reverse gap-3 border-t border-hairline pt-6 sm:flex-row sm:justify-end">
            <a href="{{ route('users.index') }}" class="btn-secondary">Cancel</a>
            <button type="submit" class="btn-primary" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="save">
                    {{ $this->isEditing() ? 'Save Changes' : 'Create Account' }}
                </span>
                <span wire:loading wire:target="save">Saving…</span>
            </button>
        </div>
    </form>
</div>
