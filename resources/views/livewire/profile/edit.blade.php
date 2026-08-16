<div>
    <div class="mb-5">
        <h1 class="page-title-marked text-[26px] font-semibold leading-[1.2] text-foreground">Your Profile</h1>
        <p class="mt-1 max-w-[68ch] text-[14px] text-muted-foreground">
            Your own details and photo. Your role and whether your account is active
            are set by the farm owner, not here.
        </p>
    </div>

    @if ($status !== '')
        <div class="mb-5 rounded-[var(--radius-md)] bg-success-bg px-4 py-3 text-[15px] text-success" role="status">
            {{ $status }}
        </div>
    @endif

    {{-- Two columns on desktop: the photo is a distinct task from the details,
         and stacking them buries it under six fields. Left-aligned in the
         canvas, never centred. --}}
    <div class="grid max-w-[68rem] gap-5 lg:grid-cols-[20rem_1fr]">

        {{-- Photo --}}
        <div class="card p-6">
            <h2 class="text-[15px] font-semibold text-foreground">Photo</h2>
            <p class="mt-1 text-[13px] text-muted-foreground">
                Shown beside your name in the sidebar. JPG or PNG, up to
                <span class="datum">4 MB</span>.
            </p>

            <div class="mt-5 flex flex-col items-center">
                <div class="h-32 w-32 overflow-hidden rounded-full border border-border bg-muted">
                    @if ($photo && $photo->isPreviewable())
                        {{-- isPreviewable() is load-bearing, not defensive.
                             temporaryUrl() THROWS on a non-image, so selecting a
                             PDF crashed the page instead of showing the
                             validation message that was already waiting for it. --}}
                        <img src="{{ $photo->temporaryUrl() }}" alt="Selected photo preview"
                             class="h-full w-full object-cover">
                    @elseif ($user->hasProfilePhoto() && $user->profilePhotoUrl())
                        <img src="{{ $user->profilePhotoUrl() }}" alt="Your current photo"
                             class="h-full w-full object-cover">
                    @else
                        <span class="flex h-full w-full items-center justify-center">
                            <svg class="h-12 w-12 text-muted-foreground" fill="none" stroke="currentColor"
                                 stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                      d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/>
                                <circle cx="12" cy="7" r="4"/>
                            </svg>
                        </span>
                    @endif
                </div>

                <div wire:loading wire:target="photo" class="mt-3 text-[13px] text-muted-foreground">
                    Uploading…
                </div>

                <label class="btn-secondary mt-4 cursor-pointer">
                    {{ $user->hasProfilePhoto() ? 'Change photo' : 'Choose a photo' }}
                    <input type="file" wire:model="photo" accept="image/*" class="sr-only">
                </label>

                @error('photo')
                    <p class="error text-center">{{ $message }}</p>
                @enderror

                @if ($user->hasProfilePhoto())
                    <button type="button" wire:click="removePhoto"
                            wire:confirm="Remove your profile photo?"
                            class="btn-quiet mt-1 text-destructive">
                        Remove photo
                    </button>
                @endif
            </div>
        </div>

        <div class="space-y-5">
            {{-- Details --}}
            <form wire:submit="save" class="card p-6">
                <h2 class="text-[15px] font-semibold text-foreground">Your details</h2>

                <div class="mt-5 grid gap-5 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label for="full_name" class="label">Full Name</label>
                        <input id="full_name" type="text" wire:model="full_name" class="input mt-1 @error('full_name') input-error @enderror">
                        @error('full_name') <p class="error">{{ $message }}</p> @enderror
                    </div>

                    <div class="sm:col-span-2">
                        <label for="email" class="label">Email Address</label>
                        <input id="email" type="email" wire:model="email" class="input datum mt-1 @error('email') input-error @enderror">
                        @error('email') <p class="error">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="contact_number" class="label">Contact Number</label>
                        <input id="contact_number" type="text" wire:model="contact_number" class="input datum mt-1 @error('contact_number') input-error @enderror">
                        @error('contact_number') <p class="error">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="position" class="label">Position</label>
                        <input id="position" type="text" wire:model="position" class="input mt-1 @error('position') input-error @enderror">
                        <p class="help">What you do on the farm.</p>
                        @error('position') <p class="error">{{ $message }}</p> @enderror
                    </div>

                    <div class="sm:col-span-2">
                        <label for="address" class="label">Address</label>
                        <input id="address" type="text" wire:model="address" class="input mt-1 @error('address') input-error @enderror">
                        @error('address') <p class="error">{{ $message }}</p> @enderror
                    </div>
                </div>

                {{-- Role and account status are read-only here on purpose: they
                     belong to the owner, and showing them as disabled fields is
                     clearer than leaving a user to wonder where they went. --}}
                <div class="mt-5 flex flex-wrap items-center gap-3 border-t border-border pt-4">
                    <span class="text-[13px] text-muted-foreground">Role</span>
                    <span class="badge badge-info">{{ $user->role->label() }}</span>
                    <span class="text-[13px] text-muted-foreground">
                        Set by the farm owner.
                    </span>
                </div>

                <div class="mt-5 flex justify-end border-t border-border pt-4">
                    <button type="submit" class="btn-primary" wire:loading.attr="disabled" wire:target="save">
                        <span wire:loading.remove wire:target="save">Save changes</span>
                        <span wire:loading wire:target="save">Saving…</span>
                    </button>
                </div>
            </form>

            {{-- Password --}}
            <form wire:submit="updatePassword" class="card p-6">
                <h2 class="text-[15px] font-semibold text-foreground">Change password</h2>
                <p class="mt-1 text-[13px] text-muted-foreground">
                    You need your current password to set a new one.
                </p>

                <div class="mt-5 grid gap-5 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label for="current_password" class="label">Current Password</label>
                        <input id="current_password" type="password" wire:model="current_password"
                               autocomplete="current-password"
                               class="input mt-1 @error('current_password') input-error @enderror">
                        @error('current_password') <p class="error">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="password" class="label">New Password</label>
                        <input id="password" type="password" wire:model="password"
                               autocomplete="new-password"
                               class="input mt-1 @error('password') input-error @enderror">
                        <p class="help">At least 8 characters.</p>
                        @error('password') <p class="error">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="password_confirmation" class="label">Confirm New Password</label>
                        <input id="password_confirmation" type="password" wire:model="password_confirmation"
                               autocomplete="new-password" class="input mt-1">
                    </div>
                </div>

                <div class="mt-5 flex justify-end border-t border-border pt-4">
                    <button type="submit" class="btn-primary">Change password</button>
                </div>
            </form>
        </div>
    </div>
</div>
