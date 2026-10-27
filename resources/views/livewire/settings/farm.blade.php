{{--
    THE FARM'S PUBLIC IDENTITY - a console screen.

    One measured column of fields, grouped under small headings, with the
    public preview beside it on a wide screen and below it on a phone. The
    preview is not decoration: every field here is read by a page the owner is
    not looking at while they type - the catalogue footer, the PDF footer, the
    public Visit section - and "where does this end up?" is the question a
    settings screen usually refuses to answer.
--}}
<div>
    <x-anchor-block eyebrow="Settings" title="Farm Profile">
        The farm's name and how the public is told to reach it. These appear on the
        front page, in the catalogue footer and at the foot of every report.
    </x-anchor-block>

    <form wire:submit="save" class="mt-8">
        <div class="grid gap-x-16 gap-y-12 lg:grid-cols-[minmax(0,40rem)_18rem]">
            <div class="space-y-12">
                <section aria-labelledby="identity-heading">
                    <h2 id="identity-heading" class="font-display text-[19px] font-semibold tracking-[-0.015em] text-foreground">Name</h2>

                    <div class="mt-4 border-t border-border pt-6">
                        <label for="farm_name" class="label">Farm name <span class="text-destructive">*</span></label>
                        <input id="farm_name" type="text" wire:model.blur="farm_name"
                               aria-describedby="farm_name-help @error('farm_name') farm_name-error @enderror"
                               @error('farm_name') aria-invalid="true" @enderror
                               class="input @error('farm_name') input-error @enderror">
                        <p id="farm_name-help" class="help">Shown in the sidebar, the browser tab and the footer of every PDF.</p>
                        @error('farm_name') <p id="farm_name-error" class="error" role="alert">{{ $message }}</p> @enderror
                    </div>
                </section>

                <section aria-labelledby="contact-heading">
                    <h2 id="contact-heading" class="font-display text-[19px] font-semibold tracking-[-0.015em] text-foreground">How the public reaches you</h2>
                    {{-- Any field left blank simply disappears from the public
                         page rather than rendering a heading with nothing under
                         it. Said here because it is the one non-obvious thing
                         about this form: clearing a field is how you remove it. --}}
                    <p class="mt-1 text-[15px] leading-relaxed text-muted-foreground">
                        Clear a field to stop showing it. The farm name is the exception &mdash; it
                        appears on every page and cannot be left empty.
                    </p>

                    <div class="mt-4 space-y-6 border-t border-border pt-6">
                        <div>
                            <label for="phone" class="label">Phone</label>
                            {{-- .datum: a phone number is registry data and
                                 belongs in tabular figures. --}}
                            <input id="phone" type="tel" wire:model.blur="phone"
                                   aria-describedby="phone-help @error('phone') phone-error @enderror"
                                   @error('phone') aria-invalid="true" @enderror
                                   class="input datum @error('phone') input-error @enderror">
                            <p id="phone-help" class="help">The number the public is told to ring.</p>
                            @error('phone') <p id="phone-error" class="error" role="alert">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="email" class="label">Email</label>
                            <input id="email" type="email" wire:model.blur="email"
                                   @error('email') aria-invalid="true" aria-describedby="email-error" @enderror
                                   class="input @error('email') input-error @enderror">
                            @error('email') <p id="email-error" class="error" role="alert">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="address" class="label">Where to find us</label>
                            <input id="address" type="text" wire:model.blur="address"
                                   @error('address') aria-invalid="true" aria-describedby="address-error" @enderror
                                   class="input @error('address') input-error @enderror">
                            @error('address') <p id="address-error" class="error" role="alert">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="hours" class="label">Visiting hours</label>
                            <input id="hours" type="text" wire:model.blur="hours"
                                   @error('hours') aria-invalid="true" aria-describedby="hours-error" @enderror
                                   class="input @error('hours') input-error @enderror">
                            @error('hours') <p id="hours-error" class="error" role="alert">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </section>

                <section aria-labelledby="note-heading">
                    <h2 id="note-heading" class="font-display text-[19px] font-semibold tracking-[-0.015em] text-foreground">For visitors</h2>

                    <div class="mt-4 border-t border-border pt-6">
                        <label for="visitor_note" class="label">
                            Note to visitors <span class="font-normal text-muted-foreground">(optional)</span>
                        </label>
                        <textarea id="visitor_note" rows="3" wire:model.blur="visitor_note"
                                  aria-describedby="visitor_note-help @error('visitor_note') visitor_note-error @enderror"
                                  @error('visitor_note') aria-invalid="true" @enderror
                                  class="input @error('visitor_note') input-error @enderror"></textarea>
                        <p id="visitor_note-help" class="help">
                            Anything else the public should know before coming &mdash; ring ahead on
                            Sundays, where to park. Leave it blank and nothing is shown.
                        </p>
                        @error('visitor_note') <p id="visitor_note-error" class="error" role="alert">{{ $message }}</p> @enderror
                    </div>
                </section>
            </div>

            {{-- What the public sees. Bound to the same properties as the form,
                 so it follows the fields on blur rather than waiting for a save.
                 A card because it is a genuinely separate object: a picture of
                 another page, not part of this one. --}}
            <aside aria-labelledby="preview-heading" class="h-fit lg:sticky lg:top-6">
                <h2 id="preview-heading" class="font-display text-[19px] font-semibold tracking-[-0.015em] text-foreground">On the public page</h2>

                <div class="elev-1 mt-4 rounded-[12px] bg-card p-6">
                    <p class="font-display text-[18px] font-semibold tracking-[-0.01em] text-foreground">{{ $farm_name ?: 'Unnamed farm' }}</p>

                    <dl class="mt-4 space-y-4 border-t border-border pt-4">
                        @if ($phone)
                            <div>
                                <dt class="text-[13px] font-semibold text-muted-foreground">Phone</dt>
                                <dd class="datum mt-0.5 text-[15px] text-foreground">{{ $phone }}</dd>
                            </div>
                        @endif
                        @if ($email)
                            <div>
                                <dt class="text-[13px] font-semibold text-muted-foreground">Email</dt>
                                <dd class="mt-0.5 break-words text-[15px] text-foreground">{{ $email }}</dd>
                            </div>
                        @endif
                        @if ($address)
                            <div>
                                <dt class="text-[13px] font-semibold text-muted-foreground">Where to find us</dt>
                                <dd class="mt-0.5 text-[15px] leading-relaxed text-foreground">{{ $address }}</dd>
                            </div>
                        @endif
                        @if ($hours)
                            <div>
                                <dt class="text-[13px] font-semibold text-muted-foreground">Visiting hours</dt>
                                <dd class="mt-0.5 text-[15px] leading-relaxed text-foreground">{{ $hours }}</dd>
                            </div>
                        @endif
                        @if ($visitor_note)
                            <div>
                                <dt class="text-[13px] font-semibold text-muted-foreground">Note</dt>
                                <dd class="mt-0.5 text-[15px] leading-relaxed text-foreground">{{ $visitor_note }}</dd>
                            </div>
                        @endif
                    </dl>

                    @unless ($phone || $email || $address || $hours)
                        <p class="text-[14px] leading-relaxed text-muted-foreground">
                            With all four blank the front page shows no way to contact the farm at all.
                        </p>
                    @endunless
                </div>
            </aside>
        </div>

        <div class="sticky bottom-0 z-20 -mx-4 mt-12 flex items-center justify-end gap-3 border-t border-border bg-background/95 px-4 py-3 sm:-mx-6 sm:px-6 lg:-mx-8 lg:px-8">
            {{-- Quiet confirmation beside the button, cleared by the next edit
                 - the toast says it once, this keeps saying it until it stops
                 being true. --}}
            @if ($saved)
                <p class="mr-auto text-[14px] text-muted-foreground" wire:loading.remove wire:target="save">All changes saved.</p>
            @endif
            <button type="submit" class="btn-primary" wire:loading.attr="disabled" wire:target="save">
                <span class="btn-spinner" wire:loading wire:target="save"></span>
                Save changes
            </button>
        </div>
    </form>
</div>
