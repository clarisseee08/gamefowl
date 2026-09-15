{{--
    THE FARM'S PUBLIC IDENTITY — a console screen.

    15px dense text, 44px touch targets, 16px inputs, 7:1 contrast. No colour:
    nothing on this page is a bloodline, and the only wash is the success
    status after a save.

    The preview beside the form is not decoration. Every field here is read by
    a page the owner is not looking at while they type - the catalogue footer,
    the PDF footer, the public Visit section - and "where does this end up?" is
    the question a settings screen usually refuses to answer.
--}}
<div>
    <div class="mb-5">
        <h1 class="page-title-marked text-[26px] font-semibold leading-[1.2] text-foreground">Farm Profile</h1>
        <p class="mt-1 max-w-[68ch] text-[14px] leading-relaxed text-muted-foreground">
            The farm's name and how the public is told to reach it. These appear on the
            front page, in the catalogue footer and at the foot of every report.
        </p>
    </div>

    @if ($saved)
        <div class="mb-5 rounded-[var(--radius-md)] bg-success-bg px-4 py-3 text-[15px] text-foreground" role="status">
            Saved. The public pages show the new details now.
        </div>
    @endif

    <div class="grid max-w-[68rem] gap-5 lg:grid-cols-[1fr_20rem]">

        <form wire:submit="save" class="card p-6">
            <div class="grid gap-5 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label for="farm_name" class="label">Farm name</label>
                    <input id="farm_name" type="text" wire:model.blur="farm_name"
                           class="input mt-1 @error('farm_name') input-error @enderror">
                    <p class="help">Shown in the sidebar, the browser tab and the footer of every PDF.</p>
                    @error('farm_name') <p class="error">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="phone" class="label">Phone</label>
                    {{-- .datum: a phone number is registry data and belongs in
                         tabular figures, the same as it was in the visit queue
                         whose contact details this screen replaced. --}}
                    <input id="phone" type="tel" wire:model.blur="phone"
                           class="input datum mt-1 @error('phone') input-error @enderror">
                    <p class="help">The number the public is told to ring.</p>
                    @error('phone') <p class="error">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="email" class="label">Email</label>
                    <input id="email" type="email" wire:model.blur="email"
                           class="input mt-1 @error('email') input-error @enderror">
                    @error('email') <p class="error">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="address" class="label">Where to find us</label>
                    <input id="address" type="text" wire:model.blur="address"
                           class="input mt-1 @error('address') input-error @enderror">
                    @error('address') <p class="error">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="hours" class="label">Visiting hours</label>
                    <input id="hours" type="text" wire:model.blur="hours"
                           class="input mt-1 @error('hours') input-error @enderror">
                    @error('hours') <p class="error">{{ $message }}</p> @enderror
                </div>

                <div class="sm:col-span-2">
                    <label for="visitor_note" class="label">
                        Note to visitors <span class="text-muted-foreground">(optional)</span>
                    </label>
                    <textarea id="visitor_note" rows="3" wire:model.blur="visitor_note"
                              class="input mt-1 @error('visitor_note') input-error @enderror"></textarea>
                    <p class="help">
                        Anything else the public should know before coming &mdash; ring ahead on
                        Sundays, where to park. Leave it blank and nothing is shown.
                    </p>
                    @error('visitor_note') <p class="error">{{ $message }}</p> @enderror
                </div>
            </div>

            {{-- Any field left blank simply disappears from the public page
                 rather than rendering a heading with nothing under it. Said
                 here because it is the one non-obvious thing about this form:
                 clearing a field is how you remove it, and there is no delete
                 control to go looking for. --}}
            <p class="mt-6 max-w-[62ch] text-[13px] leading-relaxed text-muted-foreground">
                Clear a field to stop showing it. The farm name is the exception &mdash; it
                appears on every page and cannot be left empty.
            </p>

            <div class="mt-5 flex items-center gap-3">
                <button type="submit" class="btn-primary" wire:loading.attr="disabled">
                    Save changes
                </button>
                <span wire:loading wire:target="save" class="text-[14px] text-muted-foreground">Saving&hellip;</span>
            </div>
        </form>

        {{-- What the public sees. Bound to the same properties as the form, so
             it follows the fields on blur rather than waiting for a save. --}}
        <aside class="card h-fit p-6">
            <h2 class="text-[13px] font-medium uppercase tracking-[0.07em] text-muted-foreground">
                On the public page
            </h2>

            <p class="mt-4 text-[17px] font-semibold text-foreground">{{ $farm_name ?: 'Unnamed farm' }}</p>

            <dl class="mt-4 space-y-4">
                @if ($phone)
                    <div>
                        <dt class="text-[12px] font-medium uppercase tracking-[0.07em] text-muted-foreground">Phone</dt>
                        <dd class="datum mt-1 text-[15px] text-foreground">{{ $phone }}</dd>
                    </div>
                @endif
                @if ($email)
                    <div>
                        <dt class="text-[12px] font-medium uppercase tracking-[0.07em] text-muted-foreground">Email</dt>
                        <dd class="mt-1 break-words text-[15px] text-foreground">{{ $email }}</dd>
                    </div>
                @endif
                @if ($address)
                    <div>
                        <dt class="text-[12px] font-medium uppercase tracking-[0.07em] text-muted-foreground">Where to find us</dt>
                        <dd class="mt-1 text-[15px] leading-relaxed text-foreground">{{ $address }}</dd>
                    </div>
                @endif
                @if ($hours)
                    <div>
                        <dt class="text-[12px] font-medium uppercase tracking-[0.07em] text-muted-foreground">Visiting hours</dt>
                        <dd class="mt-1 text-[15px] leading-relaxed text-foreground">{{ $hours }}</dd>
                    </div>
                @endif
                @if ($visitor_note)
                    <div>
                        <dt class="text-[12px] font-medium uppercase tracking-[0.07em] text-muted-foreground">Note</dt>
                        <dd class="mt-1 text-[15px] leading-relaxed text-foreground">{{ $visitor_note }}</dd>
                    </div>
                @endif
            </dl>

            @unless ($phone || $email || $address || $hours)
                <p class="mt-4 text-[13px] leading-relaxed text-muted-foreground">
                    With all four blank the front page shows no way to contact the farm at all.
                </p>
            @endunless
        </aside>
    </div>
</div>
