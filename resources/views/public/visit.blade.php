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
    <div class="sb-wrap pb-16 pt-10 lg:pb-24 lg:pt-14">
        <div class="grid items-start gap-10 lg:grid-cols-[1fr_1fr] lg:gap-14">
            <div>
                <p class="sb-eyebrow sb-line" style="--line: 0ms">Visit us</p>
                <h1 class="sb-hero-title sb-line mt-3" style="--line: 60ms">Come and see the birds</h1>
                <p class="sb-lead sb-line mt-4 max-w-[52ch]" style="--line: 130ms">Visitors are welcome by arrangement. Ring the farm or send an email and someone will settle a time with you — nothing is arranged until you have spoken to somebody.</p>

                @if ($farm['phone'] || $farm['email'])
                    <div class="sb-line mt-6" style="--line: 200ms">
                        <div class="flex flex-wrap gap-3">
                            @if ($farm['phone'])
                                <a href="tel:{{ $telHref }}" class="d-btn d-btn-primary"><x-ui.icon name="phone" class="size-4" /> Ring the farm</a>
                            @endif
                            @if ($farm['email'])
                                <a href="mailto:{{ $farm['email'] }}?subject={{ rawurlencode('Visiting '.$farm['name']) }}" class="d-btn d-btn-outline"><x-ui.icon name="mail" class="size-4" /> Send an email</a>
                            @endif
                        </div>
                    </div>
                @endif

                @if ($hasContact)
                    {{-- Every row is conditional - the farm can clear any of
                         them at /settings, and a "Phone" label with nothing
                         beside it looks broken where an absent row does not. --}}
                    <ul class="mt-8 divide-y divide-border rounded-md border border-border bg-card" data-reveal>
                        @if ($farm['address'])
                            <li class="flex items-start gap-3 px-4 py-3.5">
                                <span class="inline-flex size-8 shrink-0 items-center justify-center rounded-full bg-muted text-muted-foreground"><x-ui.icon name="map-pin" class="size-4" /></span>
                                <div class="min-w-0">
                                    <p class="text-xs font-medium text-muted-foreground">Address</p>
                                    <p class="text-[15px] text-foreground">{{ $farm['address'] }}</p>
                                    <a href="{{ $mapHref }}" target="_blank" rel="noopener"
                                       class="d-link d-link-primary mt-0.5 inline-flex min-h-9 items-center gap-1.5 text-sm font-semibold">
                                        Open in Google Maps <x-ui.icon name="external-link" class="size-3.5" />
                                    </a>
                                </div>
                            </li>
                        @endif
                        @if ($farm['hours'])
                            <li class="flex items-start gap-3 px-4 py-3.5">
                                <span class="inline-flex size-8 shrink-0 items-center justify-center rounded-full bg-muted text-muted-foreground"><x-ui.icon name="clock" class="size-4" /></span>
                                <div class="min-w-0">
                                    <p class="text-xs font-medium text-muted-foreground">Visiting hours</p>
                                    <p class="text-[15px] text-foreground">{{ $farm['hours'] }}</p>
                                </div>
                            </li>
                        @endif
                        @if ($farm['phone'])
                            <li class="flex items-start gap-3 px-4 py-3.5">
                                <span class="inline-flex size-8 shrink-0 items-center justify-center rounded-full bg-muted text-muted-foreground"><x-ui.icon name="phone" class="size-4" /></span>
                                <div class="min-w-0">
                                    <p class="text-xs font-medium text-muted-foreground">Phone</p>
                                    <a href="tel:{{ $telHref }}" class="d-link d-link-primary datum text-[15px] font-semibold">{{ $farm['phone'] }}</a>
                                </div>
                            </li>
                        @endif
                        @if ($farm['email'])
                            <li class="flex items-start gap-3 px-4 py-3.5">
                                <span class="inline-flex size-8 shrink-0 items-center justify-center rounded-full bg-muted text-muted-foreground"><x-ui.icon name="mail" class="size-4" /></span>
                                <div class="min-w-0">
                                    <p class="text-xs font-medium text-muted-foreground">Email</p>
                                    <a href="mailto:{{ $farm['email'] }}" class="d-link d-link-primary break-words text-[15px] font-semibold">{{ $farm['email'] }}</a>
                                </div>
                            </li>
                        @endif
                    </ul>

                    @if ($farm['note'])
                        {{-- The owner's own words - the one thing here they wrote
                             rather than filled in. --}}
                        <div class="mt-4 flex gap-3 rounded-md border border-border bg-muted/50 px-4 py-3.5" data-reveal>
                            <x-ui.icon name="message-square" class="mt-0.5 size-4 shrink-0 text-muted-foreground" />
                            <p class="max-w-[58ch] text-[15px] leading-relaxed text-foreground">{{ $farm['note'] }}</p>
                        </div>
                    @endif
                @else
                    <p class="mt-8 text-[16px] text-muted-foreground">The farm has not published its contact details yet.</p>
                @endif
            </div>

            {{-- The farm, as a slideshow: the farm's photographs, dealt per
                 visit so no page repeats another's (App\Support\FarmPhotos).
                 No slide carries a caption, so none could be read as a
                 particular bird. --}}
            <div class="sb-photo-in lg:sticky lg:top-24">
                <x-ui.slideshow priority label="The farm" class="aspect-[4/5] rounded-xl bg-muted shadow-sm lg:aspect-[5/6]"
                                :slides="\App\Support\FarmPhotos::slides('visit')" />
            </div>
        </div>

        {{-- How a visit goes. Numbered, because here the order really does
             matter: nothing happens until the second step. --}}
        <section class="mt-16 lg:mt-20" aria-labelledby="how-heading" data-reveal>
            <h2 id="how-heading" class="sb-h2">How a visit works</h2>
            <ol class="mt-6 grid gap-3 md:grid-cols-3 md:gap-4">
                @foreach ([
                    ['First', 'phone', 'Ring or email the farm and say which birds you would like to see.'],
                    ['Then', 'clock', 'Someone will settle a day and time with you'.($farm['hours'] ? ', within the visiting hours' : '').'.'],
                    ['On the day', 'map-pin', 'Come to the farm and see the birds, and their records, in person.'],
                ] as $index => [$step, $icon, $text])
                    <li class="rounded-md border border-border bg-card p-5">
                        <div class="flex items-center gap-3">
                            <span class="datum inline-flex size-8 items-center justify-center rounded-full bg-primary text-sm font-bold text-primary-content">{{ $index + 1 }}</span>
                            <p class="text-[15px] font-semibold text-foreground">{{ $step }}</p>
                            <x-ui.icon :name="$icon" class="ml-auto size-4 text-muted-foreground" />
                        </div>
                        <p class="mt-3 text-[15px] leading-relaxed text-muted-foreground">{{ $text }}</p>
                    </li>
                @endforeach
            </ol>
        </section>
    </div>
</x-layouts::catalog>
