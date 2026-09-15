@php
    /*
     * config('gfms.farm.*') is the farm_settings row, which the owner edits at
     * /settings - App\Support\FarmProfile pushes it into config in
     * AppServiceProvider::boot(), so this page reads it exactly as it did when
     * these were environment variables.
     */
    $farm = config('gfms.farm');

    /*
     * The visitor note is NOT part of this test, deliberately. It is prose
     * about a visit - "ring ahead on Sundays" - and on its own it is not a way
     * to reach anybody. A page whose only contact content was a note would
     * promise a conversation it gives no means to start.
     */
    $hasContact = $farm['address'] || $farm['phone'] || $farm['email'] || $farm['hours'];

    /*
     * A phone number on a phone should be dialable. Presentation only - the
     * displayed string stays exactly as the farm entered it, and only the href
     * is reduced to digits and a leading plus.
     */
    $telHref = $farm['phone'] ? preg_replace('/[^0-9+]/', '', $farm['phone']) : '';
@endphp

{{--
    THE FRONT PAGE.

    Ink, rule and paper. There is no coloured banner here and there should
    never be one: colour in this system means bloodline, and the only coloured
    things on this page are the band tags inside the catalogue below. A hero
    that reaches for a wash of green to feel less plain would be the single
    fastest way to break the rule the whole design rests on.

    Weight, space and a hairline do the work instead - which is why every
    structural move on this page is a rule: the masthead splits across one,
    the three statements are divided by two more, and the contact details at
    the foot are ruled off from the catalogue above them.
--}}
<div>
    {{-- ---------------------------------------------------------------
         Hero - the masthead.

         TWO COLUMNS FROM lg, and the rule between them is the point. In one
         column this was a 58ch ribbon of text in the top-left corner of a
         full-bleed shell with most of a wide monitor empty beside it, which
         is the single thing that reads as a web page pasted into an
         application. Identity to the left of the hairline; what the farm
         offers and how to act on it to the right.

         Two anchors, and the second one matters structurally: the catalogue
         sits between here and the farm's contact details, so without a way to
         jump straight down, finding the phone number would mean scrolling past
         every bird on the farm.
    --------------------------------------------------------------- --}}
    <section class="border-b border-border pb-12 pt-8 sm:pb-16 sm:pt-14">
        <div class="grid gap-8 lg:grid-cols-2 lg:gap-12">
            {{-- ---------------------------------------------------------
                 THE NAMEPLATE.

                 This column read flat for a reason that had nothing to do with
                 how much was on it, and everything to do with how evenly it was
                 distributed. It ran six stacked blocks - an address eyebrow, the
                 name, a pair of big figures, the chips, a sentence, two buttons -
                 each one quiet, none of them a peak. A masthead with no peak is
                 a stack, and a stack always reads as a template.

                 So one move, made completely: the farm's name at full strength,
                 breaking across the column the way a flag does on the front of a
                 record book. Everything else is turned DOWN to make that legible,
                 not up to keep pace with it.

                 TWO THINGS WERE DELETED, and both were the same mistake twice.

                 The eyebrow - the full postal address set in small caps ABOVE the
                 name. A label above a heading is the one device this craft floor
                 bans outright: the heading carries its own weight. The address
                 was not lost; it moves below the name as a dateline, which is
                 where a masthead has always put its locality, and it is still
                 printed in full in the footer.

                 The figure pair - ON THE FARM 26 / BLOODLINES 3, set as two big
                 numerals either side of a divider. That is the stock dashboard
                 template, and it was also saying what the column beside it and
                 the chips below it already said. The figures are kept, because
                 they are the farm's own records and they are true; they are set
                 as a census line instead, which is what they always were.
            --------------------------------------------------------- --}}
            <div>
                {{-- Tracking tightens as the size climbs. Poppins is geometric and
                     runs wide, and the -0.02em that suits a page title is far too
                     loose at ninety-six points. The measure is deliberately NOT
                     capped: the old max-w-[16ch] held this to a single line across
                     half an empty column, which is exactly what made a sixty-point
                     headline look timid. --}}
                <h1 class="text-balance text-[44px] font-semibold leading-[1] tracking-[-0.03em] text-foreground sm:text-[64px] sm:leading-[0.96] sm:tracking-[-0.035em] lg:text-[84px] xl:text-[96px]">
                    {{ $farm['name'] }}
                </h1>

                {{-- ---------------------------------------------------------
                     THE DATELINE — what this book holds, and where it is kept.

                     One ruled line under the flag. The two figures are the farm's
                     own records, read through the same farmStock()->onFarm()
                     filter the catalogue below uses, so the masthead cannot claim
                     a bird the list does not contain. Both are .datum - they are
                     counts, and every count in this application is monospaced.

                     Pluralised, because a farm that holds one bird should not be
                     told it holds "1 birds" on its own front page.

                     EVERY SEPARATOR LEADS ITS OWN SEGMENT and is tied to the
                     first word after it with a non-breaking space. As separate
                     children the dots could end a wrapped line, and at 390px one
                     duly did: the address dropped to the next line and left a
                     lone interpunct hanging off the end of the one above.
                --------------------------------------------------------- --}}
                @if ($this->stockCount > 0 || $farm['address'])
                    <p class="mt-7 flex flex-wrap items-baseline gap-x-2 gap-y-1 border-t border-border pt-5 text-[15px] text-muted-foreground">
                        @if ($this->stockCount > 0)
                            <span>
                                <span class="datum text-foreground">{{ $this->stockCount }}</span>
                                {{ \Illuminate\Support\Str::plural('bird', $this->stockCount) }}
                            </span>

                            @if (count($this->bloodlines) > 0)
                                <span>
                                    <span aria-hidden="true">&middot;</span>&nbsp;<span class="datum text-foreground">{{ count($this->bloodlines) }}</span>
                                    {{ \Illuminate\Support\Str::plural('bloodline', count($this->bloodlines)) }}
                                </span>
                            @endif
                        @endif

                        @if ($farm['address'])
                            <span>
                                @if ($this->stockCount > 0)
                                    <span aria-hidden="true">&middot;</span>&nbsp;@endif{{ $farm['address'] }}
                            </span>
                        @endif
                    </p>
                @endif

                {{-- ---------------------------------------------------------
                     THE BLOODLINES — the only colour the masthead is allowed.

                     Colour in this system means bloodline and nothing else, so a
                     hero that wants to be more than ink on paper has exactly one
                     honest route to it: show the bloodlines themselves. The
                     colours come from the same App\Support\BandTag the catalogue
                     uses, so the ones a visitor meets here are the ones they then
                     see on the birds below. A decorative palette would have had
                     to invent colours that mean nothing; these already mean
                     something.

                     At md rather than xs. They were three specks under a headline
                     they were meant to answer - the system's own signature object,
                     opted out of at the one place it would carry the most. Same
                     component, same colours, no new primitive: just run at the
                     size the system already defines for it.

                     x-bloodline-chip, NOT x-band-tag, and the difference is not
                     cosmetic. x-band-tag describes one BIRD - its slot is a band
                     number, and with none it correctly renders "Not yet banded",
                     which is a true statement about a bird and a meaningless one
                     about a bloodline. It read "Not yet banded" three times here
                     before the swap. The chip carries the two-letter code and the
                     name in text, so colour is never the only channel.
                --------------------------------------------------------- --}}
                @if (count($this->bloodlines) > 0)
                    <div class="mt-5">
                        <p class="sr-only">Bloodlines kept on this farm</p>
                        <ul class="flex flex-wrap items-center gap-2">
                            @foreach ($this->bloodlines as $bloodline)
                                <li>
                                    <x-bloodline-chip :bloodline="$bloodline" size="md" />
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                {{-- The sentence and the actions sit with the identity, not beside
                     the register. Each column carries one job - who this farm is
                     and what to do about it on the left, what it currently holds
                     on the right. --}}
                <p class="mt-8 max-w-[46ch] text-[19px] leading-relaxed text-muted-foreground">
                    Broodcocks bred and raised here, with the pedigree of every bird kept
                    from the day it hatched.
                </p>

                <div class="mt-7 flex flex-wrap items-center gap-3">
                    <a href="#stock" class="btn-primary">See our stock</a>
                    <a href="#visit" class="btn-secondary">Arrange a visit</a>
                </div>
            </div>

            {{-- ---------------------------------------------------------
                 THE REGISTER — the masthead's right-hand page.

                 This was a paragraph and two buttons floating in a column,
                 which is the arrangement every landing page has. It is now an
                 extract from the farm's own book: four birds, ruled, each
                 carrying its band, its bloodline and its age.

                 The whole design is "a printed record book crossed with the
                 physical anodised leg band", and until now that concept only
                 appeared at the bottom of the page in the catalogue. A
                 masthead that IS a ledger spread states it on arrival.

                 PHOTO-LED WHEN THERE ARE PHOTOS. Each row leads with the
                 bird's picture, and falls back to its bloodline's plate -
                 the same two-letter code on the same tinted ground the
                 catalogue cards use. That matters here: the farm currently
                 has no photographs at all, so a hero that assumed them would
                 have shipped three empty boxes.
            --------------------------------------------------------- --}}
            <div class="lg:border-l lg:border-border lg:pl-12">
                {{-- A heading, so the register is not three birds arriving with
                     no explanation of why these three. --}}
                <p class="text-[12px] font-medium uppercase tracking-[0.07em] text-muted-foreground">
                    Currently on the farm
                </p>

                @if ($this->featuredBirds->isNotEmpty())
                    <ul class="mt-4 border-t border-border">
                        @foreach ($this->featuredBirds as $bird)
                            @php $hex = \App\Support\BandTag::hex($bird->bloodline); @endphp
                            <li class="border-b border-border">
                                <a href="{{ route('broodcocks.show', $bird) }}" wire:navigate
                                   class="group flex items-center gap-4 py-3">
                                    {{-- 56px square. The plate is the bloodline's own
                                         colour at 12% - a tint, not a fill, so it reads
                                         as stationery rather than as a coloured block. --}}
                                    <span class="flex h-14 w-14 shrink-0 items-center justify-center overflow-hidden rounded-[var(--radius-sm)]"
                                          style="background-color: {{ $hex }}1f">
                                        @if ($bird->primaryPhoto)
                                            <x-photo-thumb :photo="$bird->primaryPhoto" :alt="'Photo of '.$bird->name"
                                                           class="h-14 w-14" />
                                        @else
                                            <span class="datum text-[15px] font-semibold" style="color: {{ $hex }}">
                                                {{ \App\Support\BandTag::code($bird->bloodline) }}
                                            </span>
                                        @endif
                                    </span>

                                    <span class="min-w-0 flex-1">
                                        <span class="block truncate text-[17px] font-medium text-foreground group-hover:underline">
                                            {{ $bird->name }}
                                        </span>
                                        <span class="mt-0.5 block truncate text-[14px] text-muted-foreground">
                                            {{ $bird->bloodline ?: 'Bloodline not recorded' }}
                                            @if ($bird->ageLabel())
                                                <span aria-hidden="true"> · </span><span class="datum">{{ $bird->ageLabel() }}</span>
                                            @endif
                                        </span>
                                    </span>

                                    <x-band-tag :bloodline="$bird->bloodline" :band="$bird->band_number" size="xs"
                                                class="shrink-0" />
                                </a>
                            </li>
                        @endforeach
                    </ul>

                    <a href="#stock"
                       class="mt-4 inline-flex min-h-11 items-center text-[15px] font-medium text-primary hover:underline">
                        See all {{ $this->stockCount }} birds
                    </a>
                @endif
            </div>
        </div>
    </section>

    {{-- ---------------------------------------------------------------
         What the farm is.

         Three short statements rather than a paragraph nobody reads. Each
         one is a fact about how the farm works, not a claim about it.

         Ruled rather than spaced: three columns floating in a gap read as
         three unrelated blocks, and three columns divided by a hairline read
         as three entries in one register. The ordinals are set in .datum for
         the same reason every other figure in this application is - an index
         is registry data, and it aligns with the figures in the catalogue
         directly below it.
    --------------------------------------------------------------- --}}
    <section class="border-b border-border py-12 sm:py-16">
        <div class="grid divide-y divide-border sm:grid-cols-3 sm:divide-x sm:divide-y-0">
            <div class="pb-8 sm:pb-0 sm:pr-8">
                <p class="datum text-[13px] leading-none text-muted-foreground">01</p>
                <h2 class="mt-3 text-[19px] font-semibold text-foreground">Bred on the farm</h2>
                <p class="mt-2 max-w-[38ch] text-[17px] leading-relaxed text-muted-foreground">
                    Every bird listed was hatched and raised here, or brought in and
                    recorded on arrival. Nothing is listed that the farm does not hold.
                </p>
            </div>
            <div class="py-8 sm:px-8 sm:py-0">
                <p class="datum text-[13px] leading-none text-muted-foreground">02</p>
                <h2 class="mt-3 text-[19px] font-semibold text-foreground">Pedigree kept</h2>
                <p class="mt-2 max-w-[38ch] text-[17px] leading-relaxed text-muted-foreground">
                    Sire and dam are recorded for each bird, so a family tree can be
                    followed back through the farm's own records rather than taken on trust.
                </p>
            </div>
            <div class="pt-8 sm:pl-8 sm:pt-0">
                <p class="datum text-[13px] leading-none text-muted-foreground">03</p>
                <h2 class="mt-3 text-[19px] font-semibold text-foreground">Seen in person</h2>
                <p class="mt-2 max-w-[38ch] text-[17px] leading-relaxed text-muted-foreground">
                    A photograph only shows so much. Arrange a visit and look at the birds
                    on the farm before deciding anything.
                </p>
            </div>
        </div>
    </section>

    {{-- ---------------------------------------------------------------
         The stock.

         The whole catalogue, filters and all, nested rather than
         reimplemented - App\Livewire\Catalog\Browse is the same component
         /catalog renders, so the two can never drift apart. It brings its own
         heading and its own photo grid, so nothing is wrapped around it here.
    --------------------------------------------------------------- --}}
    <section id="stock" class="scroll-mt-20 border-b border-border py-12 sm:py-16">
        {{-- h2, because the farm's nameplate above is this page's h1. Routed on
             its own at /catalog it keeps the h1 - the same component, correctly
             levelled for where it finds itself. --}}
        <livewire:catalog.browse heading-level="h2" />
    </section>

    {{-- ---------------------------------------------------------------
         Visiting.

         THERE IS NO FORM HERE ANY MORE, and the farm asked for that. A visit
         was arranged through a request form that wrote an appointments row and
         queued it for somebody to ring back about - two steps and a wait, to
         reach a farm that answers its phone. The contact details were a
         narrow rail beside the form, framed as the alternative to it
         ("Or get in touch directly"). They are now the whole section, and the
         wording no longer apologises for them.

         FOUR ACROSS ON A WIDE SCREEN rather than a column. As a stacked list
         at the foot of a long page these read as small print; ruled off and
         given a quarter of the width each, they read as the four things the
         farm wants a visitor to leave with. Below lg they fall to two and then
         to one, in that order, because phone and email belong together and
         address and hours belong together.
    --------------------------------------------------------------- --}}
    <section id="visit" class="scroll-mt-20 py-12 sm:py-16">
        <h2 class="text-[28px] font-semibold leading-[1.15] tracking-[-0.01em] text-foreground sm:text-[32px]">
            Visit the farm
        </h2>
        <p class="mt-3 max-w-[58ch] text-[17px] leading-relaxed text-muted-foreground">
            Visitors are welcome by arrangement. Ring the farm or send an email and
            someone will settle a time with you &mdash; nothing is arranged until you
            have spoken to somebody.
        </p>

        @if ($hasContact)
            {{-- Each row is conditional. A farm that has cleared its email
                 address at /settings shows no email, not an "Email" heading
                 with nothing under it. --}}
            <dl class="mt-9 grid gap-x-10 gap-y-8 border-t border-border pt-8 sm:grid-cols-2 lg:grid-cols-4">
                @if ($farm['phone'])
                    <div>
                        <dt class="text-[13px] font-semibold uppercase tracking-[0.07em] text-muted-foreground">Phone</dt>
                        {{-- The number leads because it is now the fastest way
                             to reach the farm, and it leads on WEIGHT rather
                             than size: the four headings sit on one baseline
                             and setting this one larger would break the row
                             for the sake of emphasis a medium does as well. --}}
                        <dd class="mt-2 text-[17px] font-medium leading-snug">
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
                        <dt class="text-[13px] font-semibold uppercase tracking-[0.07em] text-muted-foreground">Email</dt>
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
                        <dt class="text-[13px] font-semibold uppercase tracking-[0.07em] text-muted-foreground">Where to find us</dt>
                        <dd class="mt-2 max-w-[28ch] text-[17px] leading-relaxed text-foreground">{{ $farm['address'] }}</dd>
                    </div>
                @endif
                @if ($farm['hours'])
                    <div>
                        <dt class="text-[13px] font-semibold uppercase tracking-[0.07em] text-muted-foreground">Visiting hours</dt>
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
    </section>
</div>
