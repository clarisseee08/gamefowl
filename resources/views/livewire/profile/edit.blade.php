@php
    // Resolved once: profilePhotoUrl() asks the storage disk whether the file
    // exists, which in production is a network round trip.
    $currentPhotoUrl = $user->hasProfilePhoto() ? $user->profilePhotoUrl() : null;
    $previewUrl = ($photo && $photo->isPreviewable()) ? $photo->temporaryUrl() : $currentPhotoUrl;

    $field = fn (string $name, string $extra = ''): string => trim('d-input w-full '.$extra.' '.($errors->has($name) ? 'd-input-error' : ''));
    $error = fn (string $name): string => $errors->has($name)
        ? '<p id="'.$name.'-error" class="mt-1.5 text-[13px] font-medium text-error lg:text-[11px]" role="alert">'.e($errors->first($name)).'</p>'
        : '';

    $rowClass = 'grid gap-3 border-t border-border px-5 py-5 md:grid-cols-[11rem_minmax(0,1fr)] md:gap-6';
    $rowTitle = 'text-sm font-semibold text-foreground lg:text-xs';
    $rowHelp = 'mt-0.5 text-[13px] leading-relaxed text-muted-foreground lg:text-xs';
    $iconInput = 'pointer-events-none absolute top-1/2 left-3 z-10 size-4 -translate-y-1/2 text-muted-foreground lg:size-3.5';
@endphp

{{--
    YOUR PROFILE (EMS).

    An identity banner - your photo, name and role, and the photo controls -
    then your details as settings rows beside two side cards: the password,
    which is checked and saved on its own, and the account facts the owner
    sets. Details and photo are one save: a chosen photo is stored when the
    details are.
--}}
<div>
    <x-ui.page-header title="Your Profile"
                      description="Your own details and photo. Your role and whether your account is active are set by the farm owner." />

    {{-- THE IDENTITY BANNER --}}
    <x-ui.card class="mt-5" :flush="true">
        <div class="flex flex-col gap-5 p-5 sm:flex-row sm:items-center">
            {{-- The photo is also the way to change it: a pencil over it on
                 hover or focus, and a click opens the same picker as the button. --}}
            <label for="profile-photo" class="group relative inline-flex size-20 shrink-0 cursor-pointer overflow-hidden rounded-full bg-muted ring-4 ring-muted/60"
                   title="{{ $user->hasProfilePhoto() ? 'Change your photo' : 'Add a photo' }}">
                @if ($previewUrl)
                    {{-- isPreviewable() is load-bearing: temporaryUrl() THROWS
                         on a non-image, so choosing a PDF would crash the page
                         instead of showing its validation message. --}}
                    <img src="{{ $previewUrl }}" alt="{{ $photo ? 'Selected photo preview' : 'Your current photo' }}" class="h-full w-full object-cover">
                @else
                    <x-ui.avatar :name="$user->full_name" size="hero" tone="primary" class="size-20! text-xl" />
                @endif
                <span class="absolute inset-0 flex items-center justify-center bg-foreground/45 text-background opacity-0 transition-opacity duration-150 group-hover:opacity-100" aria-hidden="true">
                    <x-ui.icon name="pencil" class="size-5" />
                </span>
                <span wire:loading.flex wire:target="photo" class="absolute inset-0 hidden items-center justify-center bg-card/70">
                    <x-ui.icon name="loader-circle" class="size-5 animate-spin text-primary" />
                </span>
            </label>

            <div class="min-w-0 flex-1">
                <p class="truncate text-lg font-bold text-foreground">{{ $user->full_name }}</p>
                <p class="mt-0.5 truncate text-[13px] text-muted-foreground lg:text-xs">{{ $user->email }}</p>
                <div class="mt-2 flex flex-wrap items-center gap-2">
                    <x-ui.status-badge>{{ $user->role->label() }}</x-ui.status-badge>
                    @if ($user->position)
                        <span class="text-[13px] text-muted-foreground lg:text-xs">{{ $user->position }}</span>
                    @endif
                </div>
            </div>

            <div class="flex flex-col gap-2 sm:items-end">
                <div class="flex flex-wrap gap-2">
                    {{-- `relative` contains the sr-only input, which is
                         position:absolute: without a positioned ancestor,
                         focusing it scrolls the fixed-height shell off screen. --}}
                    <label class="d-btn d-btn-outline relative cursor-pointer focus-within:outline focus-within:outline-2 focus-within:outline-offset-2 focus-within:outline-primary">
                        <x-ui.icon name="plus" class="size-4 lg:size-3.5" />
                        {{ $user->hasProfilePhoto() ? 'Change Photo' : 'Add a Photo' }}
                        <input id="profile-photo" type="file" wire:model="photo" accept="image/*" class="sr-only"
                               @error('photo') aria-invalid="true" aria-describedby="photo-error" @enderror>
                    </label>

                    {{-- Removing deletes the stored file, so it is confirmed -
                         in the app's confirm dialog, not the browser's. --}}
                    @if ($user->hasProfilePhoto())
                        <x-ui.button variant="ghost" class="text-destructive"
                                     x-on:click="$dispatch('ems-confirm', {
                                         title: 'Remove your profile photo?',
                                         description: 'The photo is deleted, and your initials are shown beside your name instead. You can upload a new one at any time.',
                                         confirmLabel: 'Remove Photo',
                                         tone: 'danger',
                                         run: () => $wire.removePhoto(),
                                     })">
                            Remove
                        </x-ui.button>
                    @endif
                </div>
                <p class="text-[11px] text-muted-foreground">JPG or PNG, up to <span class="datum">4 MB</span></p>
                {!! $error('photo') !!}
            </div>
        </div>
    </x-ui.card>

    <div class="mt-4 grid gap-4 lg:grid-cols-3">
        {{-- YOUR DETAILS, as settings rows. --}}
        {{-- data-unsaved-guard: leaving the page with a field here changed and
             not saved asks first (app.js). The flag is the footer's
             "Unsaved changes", which wire:dirty shows. --}}
        <form wire:submit="save" class="lg:col-span-2" data-unsaved-guard>
            <x-ui.card :flush="true">
                <div class="px-5 py-5">
                    <h2 class="text-sm font-semibold text-foreground">Personal Details</h2>
                    <p class="mt-0.5 text-[13px] text-muted-foreground lg:text-xs">How you appear to everyone who works in the system.</p>
                </div>

                <div class="{{ $rowClass }}">
                    <div>
                        <p class="{{ $rowTitle }}">Name</p>
                        <p class="{{ $rowHelp }}">As it appears beside every record you make.</p>
                    </div>
                    <div>
                        <label for="full_name" class="sr-only">Full name</label>
                        <div class="relative">
                            <x-ui.icon name="user" class="{{ $iconInput }}" />
                            <input id="full_name" type="text" wire:model="full_name" autocomplete="name"
                                   @error('full_name') aria-invalid="true" aria-describedby="full_name-error" @enderror
                                   class="{{ $field('full_name', 'pl-9') }}">
                        </div>
                        {!! $error('full_name') !!}
                    </div>
                </div>

                <div class="{{ $rowClass }}">
                    <div>
                        <p class="{{ $rowTitle }}">Contact</p>
                        <p class="{{ $rowHelp }}">Your sign-in email and a number to reach you.</p>
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="email" class="d-fieldset-legend">Email address</label>
                            <div class="relative">
                                <x-ui.icon name="mail" class="{{ $iconInput }}" />
                                <input id="email" type="email" wire:model="email" autocomplete="email"
                                       @error('email') aria-invalid="true" aria-describedby="email-error" @enderror
                                       class="{{ $field('email', 'pl-9') }}">
                            </div>
                            {!! $error('email') !!}
                        </div>
                        <div>
                            <label for="contact_number" class="d-fieldset-legend">Contact number</label>
                            <div class="relative">
                                <x-ui.icon name="phone" class="{{ $iconInput }}" />
                                <input id="contact_number" type="tel" inputmode="tel" wire:model="contact_number" autocomplete="tel"
                                       @error('contact_number') aria-invalid="true" aria-describedby="contact_number-error" @enderror
                                       class="{{ $field('contact_number', 'datum pl-9') }}">
                            </div>
                            {!! $error('contact_number') !!}
                        </div>
                    </div>
                </div>

                <div class="{{ $rowClass }}">
                    <div>
                        <p class="{{ $rowTitle }}">Work</p>
                        <p class="{{ $rowHelp }}">What you do on the farm, and where you live.</p>
                    </div>
                    <div class="space-y-4">
                        <div x-data>
                            <x-form-select id="position_choice" label="Position" wire:model="position_choice" error="position">
                                <option value="">-- Choose a position --</option>
                                @foreach (\App\Support\FarmPositions::all() as $listed)
                                    <option value="{{ $listed }}">{{ $listed }}</option>
                                @endforeach
                                <option value="{{ \App\Support\FarmPositions::OTHER }}">Other (type your own)</option>
                            </x-form-select>

                            {{-- Shown in the browser the moment Other is picked: the
                                 choice is only sent with Save, like every field here. --}}
                            <div x-show="$wire.position_choice === @js(\App\Support\FarmPositions::OTHER)" x-cloak>
                                <label for="position_other" class="sr-only">Your position</label>
                                <textarea id="position_other" rows="2" wire:model="position_other" maxlength="120"
                                          placeholder="Type your position"
                                          @error('position_other') aria-invalid="true" aria-describedby="position_other-error" @enderror
                                          class="d-textarea mt-2 w-full {{ $errors->has('position_other') ? 'd-textarea-error' : '' }}"></textarea>
                                {!! $error('position_other') !!}
                            </div>
                        </div>
                        <div>
                            <label for="address" class="d-fieldset-legend">Address</label>
                            <div class="relative">
                                <x-ui.icon name="map-pin" class="{{ $iconInput }}" />
                                <input id="address" type="text" wire:model="address" autocomplete="street-address"
                                       @error('address') aria-invalid="true" aria-describedby="address-error" @enderror
                                       class="{{ $field('address', 'pl-9') }}">
                            </div>
                            {!! $error('address') !!}
                        </div>
                    </div>
                </div>

                <div class="flex flex-wrap items-center justify-end gap-3 border-t border-border bg-muted/40 px-5 py-3">
                    {{-- wire:dirty sets the display of the element it is on, so it
                         sits on a wrapper and the row inside keeps its flex. --}}
                    <div wire:dirty wire:target="full_name,email,contact_number,address,position_choice,position_other" data-unsaved-flag class="mr-auto">
                        <p class="flex items-center gap-1.5 text-[13px] font-medium text-foreground lg:text-xs">
                            <x-ui.icon name="circle-alert" class="size-4 text-muted-foreground lg:size-3.5" /> Unsaved changes
                        </p>
                    </div>
                    <x-ui.button type="submit" wire:loading.attr="disabled" wire:target="save,photo">Save Changes</x-ui.button>
                </div>
            </x-ui.card>
        </form>

        <div class="flex flex-col gap-4">
            {{-- PASSWORD: its own form and its own button, because it is
                 checked against the current password and saved on its own. --}}
            <form wire:submit="updatePassword" data-unsaved-guard>
                <span wire:dirty wire:target="current_password,password,password_confirmation" data-unsaved-flag class="sr-only">A password has been typed and not saved.</span>
                <x-ui.card>
                    <x-ui.card.header>
                        <x-ui.card.title>Change Password</x-ui.card.title>
                        <x-ui.card.description>You need your current password to set a new one.</x-ui.card.description>
                    </x-ui.card.header>
                    <x-ui.card.content class="space-y-4">
                        <div>
                            <label for="current_password" class="d-fieldset-legend">Current password</label>
                            <div class="relative">
                                <x-ui.icon name="shield-check" class="{{ $iconInput }}" />
                                <input id="current_password" type="password" wire:model="current_password" autocomplete="current-password"
                                       @error('current_password') aria-invalid="true" aria-describedby="current_password-error" @enderror
                                       class="{{ $field('current_password', 'pl-9 pr-12 lg:pr-9') }}">
                                <x-password-reveal for="current_password" />
                            </div>
                            {!! $error('current_password') !!}
                        </div>
                        {{-- The two checks below read what is typed as it is typed
                             ($wire, before anything is sent), and clear themselves
                             when the password is saved and the fields are emptied. --}}
                        <div x-data>
                            <label for="password" class="d-fieldset-legend">New password</label>
                            <div class="relative">
                                <x-ui.icon name="shield-check" class="{{ $iconInput }}" />
                                <input id="password" type="password" wire:model="password" autocomplete="new-password"
                                       aria-describedby="password-help @error('password') password-error @enderror"
                                       @error('password') aria-invalid="true" @enderror
                                       class="{{ $field('password', 'pl-9 pr-12 lg:pr-9') }}">
                                <x-password-reveal for="password" />
                            </div>
                            <p id="password-help" class="d-label mt-1 flex items-center gap-1.5"
                               x-bind:class="$wire.password.length >= 8 ? 'text-success' : ''">
                                <x-ui.icon name="check" class="size-3.5" x-show="$wire.password.length >= 8" x-cloak />
                                At least 8 characters.
                            </p>
                            {!! $error('password') !!}
                        </div>
                        <div x-data>
                            <label for="password_confirmation" class="d-fieldset-legend">Confirm new password</label>
                            <div class="relative">
                                <x-ui.icon name="shield-check" class="{{ $iconInput }}" />
                                <input id="password_confirmation" type="password" wire:model="password_confirmation" autocomplete="new-password"
                                       aria-describedby="password_confirmation-help"
                                       class="d-input w-full pl-9 pr-12 lg:pr-9">
                                <x-password-reveal for="password_confirmation" />
                            </div>
                            <p id="password_confirmation-help" class="d-label mt-1 flex items-center gap-1.5" aria-live="polite"
                               x-show="$wire.password_confirmation !== ''" x-cloak
                               x-bind:class="$wire.password_confirmation === $wire.password ? 'text-success' : ''">
                                <x-ui.icon name="check" class="size-3.5" x-show="$wire.password_confirmation === $wire.password" />
                                <span x-text="$wire.password_confirmation === $wire.password ? 'The passwords match.' : 'The passwords do not match yet.'"></span>
                            </p>
                        </div>
                    </x-ui.card.content>
                    <x-ui.card.footer class="flex justify-end border-t border-border pt-4">
                        <x-ui.button type="submit" variant="outline" wire:loading.attr="disabled" wire:target="updatePassword">
                            <x-ui.icon name="shield-check" /> Change Password
                        </x-ui.button>
                    </x-ui.card.footer>
                </x-ui.card>
            </form>

            {{-- ACCOUNT: what the owner sets, shown so nobody wonders where it went. --}}
            <x-ui.card>
                <x-ui.card.header>
                    <x-ui.card.title>Account</x-ui.card.title>
                    <x-ui.card.description>Set by the farm owner</x-ui.card.description>
                </x-ui.card.header>
                <x-ui.card.content>
                    <dl class="space-y-3 text-[13px] lg:text-xs">
                        <div class="flex items-center justify-between gap-3">
                            <dt class="text-muted-foreground">Role</dt>
                            <dd><x-ui.status-badge>{{ $user->role->label() }}</x-ui.status-badge></dd>
                        </div>
                        <div class="flex items-center justify-between gap-3">
                            <dt class="text-muted-foreground">Status</dt>
                            <dd><x-ui.status-badge :tone="$user->is_active ? 'success' : 'neutral'">{{ $user->is_active ? 'Active' : 'Deactivated' }}</x-ui.status-badge></dd>
                        </div>
                        @if ($user->created_at)
                            <div class="flex items-center justify-between gap-3">
                                <dt class="text-muted-foreground">Member since</dt>
                                <dd class="datum font-medium text-foreground">{{ $user->created_at->format('j M Y') }}</dd>
                            </div>
                        @endif
                    </dl>
                </x-ui.card.content>
            </x-ui.card>
        </div>
    </div>
</div>
