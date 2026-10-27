<x-layouts::catalog title="Visit the farm">
    @php
        $farm = config('gfms.farm');

        /*
         * The visitor note is NOT part of this test, deliberately. It is prose
         * about a visit - "ring ahead on Sundays" - and on its own it is not a
         * way to reach anybody. A page whose only contact content was a note
         * would promise a conversation it gives no means to start.
         */
        $hasContact = $farm['address'] || $farm['phone'] || $farm['email'] || $farm['hours'];

        /*
         * A phone number on a phone should be dialable. Presentation only - the
         * displayed string stays exactly as the farm entered it, and only the
         * href is reduced to digits and a leading plus.
         */
        $telHref = $farm['phone'] ? preg_replace('/[^0-9+]/', '', $farm['phone']) : '';

        $bleed = '-mx-4 sm:-mx-6 lg:-mx-8';
    @endphp

    {{--
        VISITING — its own page.

        THERE IS NO FORM HERE AND THE FARM ASKED FOR THAT. A visit used to be
        arranged through a request form that wrote an appointments row and
        queued it for somebody to ring back about: two steps and a wait, to
        reach a farm that answers its phone. The contact details were a narrow
        rail beside the form, framed as the alternative to it. They are the
        whole page now, and the wording no longer apologises for them.
    --}}
    <div>
        <div class="{{ $bleed }} -mt-6 relative isolate flex min-h-[220px] items-end overflow-hidden bg-brand-deep sm:min-h-[280px]">
            {{-- NOT DUOTONED, and the first version was. The reasoning for a
                 duotone was that the photo would fight an oxblood palette;
                 what it actually did was drain a warm sunrise field to a flat
                 grey-green band that read as a failed image load. The frame is
                 already the farm's own colours - green and low gold - so it
                 needs a crop, not a correction. Licensed stock, credited in
                 public/photos/CREDITS.md. --}}
            <img src="{{ asset('photos/visit-band.webp') }}" alt="" aria-hidden="true"
                 width="1600" height="560" fetchpriority="high" decoding="async"
                 class="absolute inset-0 -z-10 h-full w-full object-cover object-center" />

            {{-- A solid caption band rather than a gradient scrim. A gradient
                 is the default fix and always reads as a stock photo with text
                 dropped on it; a flat panel of the farm's own brand-deep reads
                 as the caption printed under a plate. --}}
            <div class="w-full bg-brand-deep/[0.88] px-4 py-6 sm:px-6 sm:py-7 lg:px-8">
                <p class="text-[13px] font-semibold text-brand-muted-fg">Come and see them</p>
                <h1 class="mt-2 text-[2.2rem] font-semibold leading-[1.08] tracking-[-0.03em] text-brand-foreground sm:text-[2.8rem]">
                    Visit the farm
                </h1>
            </div>
        </div>

        <p class="mt-9 max-w-[58ch] text-[18px] leading-relaxed text-muted-foreground">
            Visitors are welcome by arrangement. Ring the farm or send an email and
            someone will settle a time with you &mdash; nothing is arranged until you
            have spoken to somebody.
        </p>

        @if ($hasContact)
            {{-- Each row is conditional. A farm that has cleared its email
                 address at /settings shows no email, not an "Email" heading
                 with nothing under it.

                 FOUR ACROSS ON A WIDE SCREEN rather than a column. As a stacked
                 list these read as small print; ruled off and given a quarter of
                 the width each, they read as the four things the farm wants a
                 visitor to leave with. Below lg they fall to two and then to
                 one, in that order, because phone and email belong together and
                 address and hours belong together. --}}
            <dl class="mt-10 grid gap-x-10 gap-y-8 border-t border-border pt-8 sm:grid-cols-2 lg:grid-cols-4">
                @if ($farm['phone'])
                    <div>
                        <dt class="text-[13px] font-semibold text-muted-foreground">Phone</dt>
                        {{-- The number leads because it is the fastest way to
                             reach the farm, and it leads on WEIGHT rather than
                             size: the four headings sit on one baseline and
                             setting this one larger would break the row for
                             emphasis a medium does as well. --}}
                        <dd class="mt-2 text-[18px] font-medium leading-snug">
                            @if ($telHref)
                                <a href="tel:{{ $telHref }}" class="datum text-primary hover:underline">{{ $farm['phone'] }}</a>
                            @else
                                <span class="datum text-foreground">{{ $farm['phone'] }}</span>
                            @endif
                        </dd>
                    </div>
                @endif
                @if ($farm['email'])
                    <div>
                        <dt class="text-[13px] font-semibold text-muted-foreground">Email</dt>
                        {{-- break-words, because an address is one unbroken
                             token and a quarter-width column is narrower than
                             several real ones. --}}
                        <dd class="mt-2 break-words text-[17px] leading-snug">
                            <a href="mailto:{{ $farm['email'] }}" class="text-primary hover:underline">{{ $farm['email'] }}</a>
                        </dd>
                    </div>
                @endif
                @if ($farm['address'])
                    <div>
                        <dt class="text-[13px] font-semibold text-muted-foreground">Where to find us</dt>
                        <dd class="mt-2 max-w-[28ch] text-[17px] leading-relaxed text-foreground">{{ $farm['address'] }}</dd>
                    </div>
                @endif
                @if ($farm['hours'])
                    <div>
                        <dt class="text-[13px] font-semibold text-muted-foreground">Visiting hours</dt>
                        <dd class="mt-2 max-w-[28ch] text-[17px] leading-relaxed text-foreground">{{ $farm['hours'] }}</dd>
                    </div>
                @endif
            </dl>

            @if ($farm['note'])
                {{-- The owner's own words, ruled off from the four fixed rows
                     because it is the one thing here they wrote rather than
                     filled in. --}}
                <p class="mt-8 max-w-[58ch] border-t border-border pt-6 text-[17px] leading-relaxed text-foreground">
                    {{ $farm['note'] }}
                </p>
            @endif
        @else
            {{-- Reachable only on a database whose farm_settings row is missing
                 or wholly blank. Better to say so plainly than to leave a
                 heading promising a visit above nothing at all. --}}
            <p class="mt-8 border-t border-border pt-8 text-[17px] leading-relaxed text-muted-foreground">
                The farm has not published its contact details yet.
            </p>
        @endif

        <div class="mt-12 flex flex-wrap items-center gap-3 border-t border-border pt-8">
            <a href="{{ route('catalog.index') }}" wire:navigate class="btn-primary">See our stock</a>
            <a href="{{ route('public.about') }}" wire:navigate class="btn-secondary">About the farm</a>
        </div>
    </div>
</x-layouts::catalog>
