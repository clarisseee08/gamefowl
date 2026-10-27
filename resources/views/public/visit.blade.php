<x-layouts::catalog title="Visit the farm">
    @php
        $farm = config('gfms.farm');
        $telHref = $farm['phone'] ? preg_replace('/[^0-9+]/', '', $farm['phone']) : '';

        /*
         * THE MAP IS A LINK, NOT AN EMBED. An embedded Google map loads
         * Google's scripts and sets its cookies on every visit to this page,
         * and the privacy notice tells visitors - truthfully - that the site
         * runs no third-party trackers. A link keeps that sentence true and
         * opens the map in the app the visitor already uses for directions.
         */
        $mapHref = $farm['address']
            ? 'https://www.google.com/maps/search/?api=1&query='.rawurlencode($farm['name'].', '.$farm['address'])
            : null;

        /* The visitor note is not contact information on its own. */
        $hasContact = $farm['address'] || $farm['phone'] || $farm['email'] || $farm['hours'];
    @endphp

    {{--
        VISITING.

        THERE IS NO BOOKING FORM, and the farm asked for that: a visit used to
        be requested through a form that queued it for somebody to ring back
        about - two steps and a wait, to reach a farm that answers its phone.
        Booking a visit is ringing or writing, so the phone and email ARE the
        booking actions here, as real tel: and mailto: links.
    --}}
    <div class="sb-wrap pb-16 pt-10 lg:pb-28 lg:pt-14">
        <div class="grid items-start gap-12 lg:grid-cols-[1fr_1fr] lg:gap-20">
            <div>
                <x-page-header eyebrow="Visit us" title="Come and see the birds"
                               lead="Visitors are welcome by arrangement. Ring the farm or send an email and someone will settle a time with you — nothing is arranged until you have spoken to somebody.">
                    @if ($farm['phone'] || $farm['email'])
                        <x-slot:actions>
                            @if ($farm['phone'])
                                <a href="tel:{{ $telHref }}" class="btn-primary">Ring the farm</a>
                            @endif
                            @if ($farm['email'])
                                <a href="mailto:{{ $farm['email'] }}?subject={{ rawurlencode('Visiting '.$farm['name']) }}" class="btn-secondary">Send an email</a>
                            @endif
                        </x-slot:actions>
                    @endif
                </x-page-header>

                @if ($hasContact)
                    {{-- Every row is conditional - the farm can clear any of
                         them at /settings, and a "Phone" label with nothing
                         beside it looks broken where an absent row does not. --}}
                    <dl class="mt-12 border-t border-border" data-reveal>
                        @if ($farm['address'])
                            <x-field-row label="Address">
                                {{ $farm['address'] }}
                                <a href="{{ $mapHref }}" target="_blank" rel="noopener"
                                   class="mt-1 flex min-h-11 items-center gap-1.5 text-[15px] font-semibold text-primary underline decoration-primary/30 underline-offset-4 hover:decoration-primary">
                                    Open in Google Maps <span aria-hidden="true">&nearr;</span>
                                </a>
                            </x-field-row>
                        @endif
                        @if ($farm['hours'])
                            <x-field-row label="Visiting hours">{{ $farm['hours'] }}</x-field-row>
                        @endif
                        @if ($farm['phone'])
                            <x-field-row label="Phone">
                                <a href="tel:{{ $telHref }}" class="datum font-semibold text-primary underline decoration-primary/30 underline-offset-4 hover:decoration-primary">{{ $farm['phone'] }}</a>
                            </x-field-row>
                        @endif
                        @if ($farm['email'])
                            <x-field-row label="Email">
                                <a href="mailto:{{ $farm['email'] }}" class="break-words font-semibold text-primary underline decoration-primary/30 underline-offset-4 hover:decoration-primary">{{ $farm['email'] }}</a>
                            </x-field-row>
                        @endif
                    </dl>

                    @if ($farm['note'])
                        {{-- The owner's own words - the one thing here they wrote
                             rather than filled in. --}}
                        <p class="mt-8 max-w-[58ch] text-[16px] leading-relaxed text-foreground" data-reveal>{{ $farm['note'] }}</p>
                    @endif
                @else
                    <p class="mt-12 text-[16px] text-muted-foreground">The farm has not published its contact details yet.</p>
                @endif
            </div>

            {{-- The farm at the hour the birds are let out. Licensed stock,
                 credited in public/photos/CREDITS.md - atmosphere, never
                 presented as a bird the farm owns. --}}
            <figure class="sb-photo sb-photo-in aspect-[4/5] lg:sticky lg:top-24">
                <img src="{{ asset('photos/hero-farm.webp') }}" alt="Gamefowl on grass beside a shed in evening light"
                     width="2000" height="1240" fetchpriority="high" decoding="async" class="object-[38%_50%]">
            </figure>
        </div>

        {{-- How a visit goes. Numbered, because here the order really does
             matter: nothing happens until the second step. --}}
        <section class="mt-20 border-t border-border pt-12 lg:mt-28 lg:pt-16" aria-labelledby="how-heading" data-reveal>
            <h2 id="how-heading" class="sb-h2">How a visit works</h2>
            <ol class="mt-8 grid gap-8 md:grid-cols-3 md:gap-10">
                <li>
                    <p class="font-display text-[15px] font-semibold text-primary">First</p>
                    <p class="mt-2 max-w-[34ch] text-[16px] leading-relaxed text-foreground">Ring or email the farm and say which birds you would like to see.</p>
                </li>
                <li>
                    <p class="font-display text-[15px] font-semibold text-primary">Then</p>
                    <p class="mt-2 max-w-[34ch] text-[16px] leading-relaxed text-foreground">Someone will settle a day and time with you{{ $farm['hours'] ? ', within the visiting hours' : '' }}.</p>
                </li>
                <li>
                    <p class="font-display text-[15px] font-semibold text-primary">On the day</p>
                    <p class="mt-2 max-w-[34ch] text-[16px] leading-relaxed text-foreground">Come to the farm and see the birds, and their records, in person.</p>
                </li>
            </ol>
        </section>
    </div>
</x-layouts::catalog>
