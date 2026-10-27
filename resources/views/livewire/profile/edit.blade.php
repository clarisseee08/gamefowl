@php
    // Resolved once: profilePhotoUrl() asks the storage disk whether the file
    // exists, which in production is a network round trip.
    $currentPhotoUrl = $user->hasProfilePhoto() ? $user->profilePhotoUrl() : null;
@endphp

<div>
    <x-anchor-block eyebrow="Account" title="Your Profile">
        Your own details and photo. Your role and whether your account is active
        are set by the farm owner, not here.
    </x-anchor-block>

    {{-- Details and photo are one save: the photo is stored when the form is. --}}
    <form wire:submit="save" class="mt-8">
        <div class="max-w-[40rem] space-y-12">
            {{-- Photo --}}
            <section aria-labelledby="photo-heading" x-data="{ confirmRemove: false }">
                <h2 id="photo-heading" class="font-display text-[19px] font-semibold tracking-[-0.015em] text-foreground">Photo</h2>
                <p class="mt-1 text-[15px] leading-relaxed text-muted-foreground">
                    Shown beside your name in the sidebar. JPG or PNG, up to
                    <span class="datum">4 MB</span>.
                </p>

                <div class="mt-4 flex flex-wrap items-center gap-x-6 gap-y-4 border-t border-border pt-6">
                    <div class="h-24 w-24 shrink-0 overflow-hidden rounded-full border border-border bg-muted">
                        @if ($photo && $photo->isPreviewable())
                            {{-- isPreviewable() is load-bearing, not defensive.
                                 temporaryUrl() THROWS on a non-image, so selecting
                                 a PDF crashed the page instead of showing the
                                 validation message already waiting for it. --}}
                            <img src="{{ $photo->temporaryUrl() }}" alt="Selected photo preview"
                                 class="h-full w-full object-cover">
                        @elseif ($currentPhotoUrl)
                            <img src="{{ $currentPhotoUrl }}" alt="Your current photo"
                                 class="h-full w-full object-cover">
                        @else
                            <span class="flex h-full w-full items-center justify-center">
                                <svg class="h-10 w-10 text-muted-foreground" fill="none" stroke="currentColor"
                                     stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                          d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/>
                                    <circle cx="12" cy="7" r="4"/>
                                </svg>
                            </span>
                        @endif
                    </div>

                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-3">
                            {{-- `relative` contains the sr-only input, which is
                                 position:absolute. Without a positioned ancestor
                                 it lands against the DOCUMENT, and focusing it
                                 scrolls the WINDOW - which on this fixed-height,
                                 overflow-hidden shell carries the whole layout
                                 off screen. --}}
                            <label class="btn-secondary relative cursor-pointer focus-within:outline focus-within:outline-2 focus-within:outline-offset-2 focus-within:outline-primary">
                                {{ $user->hasProfilePhoto() ? 'Change photo' : 'Choose a photo' }}
                                <input type="file" wire:model="photo" accept="image/*" class="sr-only"
                                       @error('photo') aria-invalid="true" aria-describedby="photo-error" @enderror>
                            </label>

                            @if ($user->hasProfilePhoto())
                                <button type="button" x-on:click="confirmRemove = true" class="btn-quiet text-destructive">
                                    Remove photo
                                </button>
                            @endif
                        </div>

                        <p wire:loading wire:target="photo" class="help" role="status">Uploading&hellip;</p>
                        @if ($photo && $photo->isPreviewable())
                            <p class="help" wire:loading.remove wire:target="photo">New photo selected. Save changes to use it.</p>
                        @endif
                        @error('photo') <p id="photo-error" class="error" role="alert">{{ $message }}</p> @enderror
                    </div>
                </div>

                {{-- Removing the photo deletes the stored file, so it is
                     confirmed - in a dialog, not the browser's confirm(). --}}
                @if ($user->hasProfilePhoto())
                    <div x-show="confirmRemove" x-cloak
                         class="dialog-backdrop fixed inset-0 z-50 flex items-center justify-center bg-foreground/60 p-4"
                         x-trap.noscroll="confirmRemove" x-on:keydown.escape.window="confirmRemove = false"
                         role="dialog" aria-modal="true" aria-labelledby="remove-photo-title" aria-describedby="remove-photo-body">
                        <div class="dialog-panel elev-3 w-full max-w-md rounded-[12px] bg-card p-6" x-on:click.outside="confirmRemove = false">
                            <h2 id="remove-photo-title" class="font-display text-[22px] font-semibold leading-[1.2] tracking-[-0.015em] text-foreground">
                                Remove your profile photo?
                            </h2>
                            <p id="remove-photo-body" class="mt-3 text-[15px] leading-relaxed text-muted-foreground">
                                The photo is deleted, and your initials are shown beside your name in the
                                sidebar instead. You can upload a new one at any time.
                            </p>
                            <div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                                <button type="button" x-on:click="confirmRemove = false" class="btn-secondary">Cancel</button>
                                <button type="button" wire:click="removePhoto" x-on:click="confirmRemove = false" class="btn-danger">
                                    Yes, remove my photo
                                </button>
                            </div>
                        </div>
                    </div>
                @endif
            </section>

            {{-- Details --}}
            <section aria-labelledby="details-heading">
                <h2 id="details-heading" class="font-display text-[19px] font-semibold tracking-[-0.015em] text-foreground">Your details</h2>

                <div class="mt-4 space-y-6 border-t border-border pt-6">
                    <div>
                        <label for="full_name" class="label">Full Name</label>
                        <input id="full_name" type="text" wire:model="full_name" autocomplete="name"
                               @error('full_name') aria-invalid="true" aria-describedby="full_name-error" @enderror
                               class="input @error('full_name') input-error @enderror">
                        @error('full_name') <p id="full_name-error" class="error" role="alert">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="email" class="label">Email Address</label>
                        <input id="email" type="email" wire:model="email" autocomplete="email"
                               @error('email') aria-invalid="true" aria-describedby="email-error" @enderror
                               class="input @error('email') input-error @enderror">
                        @error('email') <p id="email-error" class="error" role="alert">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="contact_number" class="label">Contact Number</label>
                        <input id="contact_number" type="tel" inputmode="tel" wire:model="contact_number" autocomplete="tel"
                               @error('contact_number') aria-invalid="true" aria-describedby="contact_number-error" @enderror
                               class="input datum @error('contact_number') input-error @enderror">
                        @error('contact_number') <p id="contact_number-error" class="error" role="alert">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="position" class="label">Position</label>
                        <input id="position" type="text" wire:model="position"
                               aria-describedby="position-help @error('position') position-error @enderror"
                               @error('position') aria-invalid="true" @enderror
                               class="input @error('position') input-error @enderror">
                        <p id="position-help" class="help">What you do on the farm.</p>
                        @error('position') <p id="position-error" class="error" role="alert">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="address" class="label">Address</label>
                        <input id="address" type="text" wire:model="address" autocomplete="street-address"
                               @error('address') aria-invalid="true" aria-describedby="address-error" @enderror
                               class="input @error('address') input-error @enderror">
                        @error('address') <p id="address-error" class="error" role="alert">{{ $message }}</p> @enderror
                    </div>

                    {{-- Role is read-only here on purpose: it belongs to the
                         owner, and showing it is clearer than leaving a user to
                         wonder where it went. --}}
                    <div class="flex flex-wrap items-center gap-3 border-t border-border pt-5">
                        <span class="text-[14px] font-semibold text-foreground">Role</span>
                        <span class="badge badge-info">{{ $user->role->label() }}</span>
                        <span class="text-[14px] text-muted-foreground">Set by the farm owner.</span>
                    </div>
                </div>
            </section>
        </div>

        <div class="sticky bottom-0 z-20 -mx-4 mt-12 flex justify-end gap-3 border-t border-border bg-background/95 px-4 py-3 sm:-mx-6 sm:px-6 lg:-mx-8 lg:px-8">
            <button type="submit" class="btn-primary" wire:loading.attr="disabled" wire:target="save,photo">
                <span class="btn-spinner" wire:loading wire:target="save"></span>
                Save changes
            </button>
        </div>
    </form>

    {{-- Password: its own form and its own button, because it is checked
         against the current password and saved on its own. --}}
    <form wire:submit="updatePassword" class="mt-12 max-w-[40rem]">
        <section aria-labelledby="password-heading">
            <h2 id="password-heading" class="font-display text-[19px] font-semibold tracking-[-0.015em] text-foreground">Change password</h2>
            <p class="mt-1 text-[15px] leading-relaxed text-muted-foreground">
                You need your current password to set a new one.
            </p>

            <div class="mt-4 space-y-6 border-t border-border pt-6">
                <div>
                    <label for="current_password" class="label">Current Password</label>
                    <input id="current_password" type="password" wire:model="current_password"
                           autocomplete="current-password"
                           @error('current_password') aria-invalid="true" aria-describedby="current_password-error" @enderror
                           class="input @error('current_password') input-error @enderror">
                    @error('current_password') <p id="current_password-error" class="error" role="alert">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="password" class="label">New Password</label>
                    <input id="password" type="password" wire:model="password"
                           autocomplete="new-password"
                           aria-describedby="password-help @error('password') password-error @enderror"
                           @error('password') aria-invalid="true" @enderror
                           class="input @error('password') input-error @enderror">
                    <p id="password-help" class="help">At least 8 characters.</p>
                    @error('password') <p id="password-error" class="error" role="alert">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="password_confirmation" class="label">Confirm New Password</label>
                    <input id="password_confirmation" type="password" wire:model="password_confirmation"
                           autocomplete="new-password" class="input">
                </div>
            </div>

            <div class="mt-6 flex justify-end">
                <button type="submit" class="btn-secondary" wire:loading.attr="disabled" wire:target="updatePassword">
                    <span class="btn-spinner" wire:loading wire:target="updatePassword"></span>
                    Change password
                </button>
            </div>
        </section>
    </form>
</div>
