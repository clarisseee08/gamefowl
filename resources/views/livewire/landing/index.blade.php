@php
    $farm = config('gfms.farm');
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
    the three statements are divided by two more, and the contact details are
    ruled off from the form beside them.
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
         sits between here and the form, so without a way to jump straight
         down, arranging a visit would mean scrolling past every bird on the
         farm.
    --------------------------------------------------------------- --}}
    <section class="border-b border-border pb-12 pt-6 sm:pb-16 sm:pt-10">
        <div class="grid gap-8 lg:grid-cols-2 lg:gap-12">
            <div>
                <p class="text-[13px] font-semibold uppercase tracking-[0.07em] text-muted-foreground">
                    {{ $farm['address'] ?: 'Gamefowl breeding farm' }}
                </p>

                {{-- Tracking tightens as the size climbs. Poppins is geometric and
                     runs wide, and the base -0.02em is set for a page title rather
                     than for sixty-point type. --}}
                <h1 class="mt-4 max-w-[16ch] text-[40px] font-semibold leading-[1.05] tracking-[-0.02em] text-foreground sm:text-[52px] sm:tracking-[-0.03em] lg:text-[60px]">
                    {{ $farm['name'] }}
                </h1>

                {{-- ---------------------------------------------------------
                     THE REGISTRY LINE — what this farm actually holds, today.

                     A masthead of type alone says nothing a template could not
                     say. These two figures are the farm's own records, read
                     through the same farmStock()->onFarm() filter the
                     catalogue below uses, so the masthead cannot claim a bird
                     the list does not contain.

                     Both are .datum. They are counts, and every count in this
                     application is monospaced.
                --------------------------------------------------------- --}}
                @if ($this->stockCount > 0)
                    <dl class="mt-8 flex items-stretch gap-8 border-t border-border pt-6">
                        <div>
                            <dt class="text-[12px] font-medium uppercase tracking-[0.07em] text-muted-foreground">On the farm</dt>
                            <dd class="datum mt-1.5 text-[30px] font-semibold leading-none text-foreground">{{ $this->stockCount }}</dd>
                        </div>

                        @if (count($this->bloodlines) > 0)
                            <div class="border-l border-border pl-8">
                                <dt class="text-[12px] font-medium uppercase tracking-[0.07em] text-muted-foreground">Bloodlines</dt>
                                <dd class="datum mt-1.5 text-[30px] font-semibold leading-none text-foreground">{{ count($this->bloodlines) }}</dd>
                            </div>
                        @endif
                    </dl>
                @endif

                {{-- ---------------------------------------------------------
                     THE BLOODLINES — the only colour the masthead is allowed.

                     Colour in this system means bloodline and nothing else, so
                     a hero that wants to be more than ink on paper has exactly
                     one honest route to it: show the bloodlines themselves.

                     The colours come from the same App\Support\BandTag the
                     catalogue uses, so the ones a visitor meets here are the
                     ones they then see on the birds below. A decorative
                     palette would have had to invent colours that mean
                     nothing; these already mean something.

                     x-bloodline-chip, NOT x-band-tag, and the difference is
                     not cosmetic. x-band-tag describes one BIRD - its slot is
                     a band number, and with none it correctly renders "Not yet
                     banded", which is a true statement about a bird and a
                     meaningless one about a bloodline. It read "Not yet
                     banded" three times here before the swap.

                     The chip carries the two-letter code and the name in text,
                     so colour is never the only channel.
                --------------------------------------------------------- --}}
                @if (count($this->bloodlines) > 0)
                    <div class="mt-6">
                        <p class="sr-only">Bloodlines kept on this farm</p>
                        <ul class="flex flex-wrap items-center gap-2">
                            @foreach ($this->bloodlines as $bloodline)
                                <li>
                                    <x-bloodline-chip :bloodline="$bloodline" size="xs" />
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>

            <div class="lg:border-l lg:border-border lg:pl-12">
                <p class="max-w-[58ch] text-[19px] leading-relaxed text-muted-foreground">
                    Broodcocks bred and raised here, with the pedigree of every bird kept from
                    the day it hatched. Browse what is on the farm, and come and see them.
                </p>

                <div class="mt-8 flex flex-wrap items-center gap-3">
                    <a href="#stock" class="btn-primary">See our stock</a>
                    <a href="#visit" class="btn-secondary">Arrange a visit</a>
                </div>
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
        <livewire:catalog.browse />
    </section>

    {{-- ---------------------------------------------------------------
         Visiting.

         The form is the easy path, not the only one, so the farm's own
         contact details sit beside it rather than after it. Below lg the two
         stack and the rule between them turns from a left edge into a top
         one - without that the details butt straight onto the end of the form
         and read as another part of it.
    --------------------------------------------------------------- --}}
    <section id="visit" class="scroll-mt-20 py-12 sm:py-16">
        <h2 class="text-[28px] font-semibold leading-[1.15] tracking-[-0.01em] text-foreground sm:text-[32px]">
            Visit the farm
        </h2>
        <p class="mt-3 max-w-[58ch] text-[17px] leading-relaxed text-muted-foreground">
            Visitors are welcome by arrangement. Leave your details and the farm will ring
            you back to settle a time &mdash; nothing is booked until you have spoken to
            someone.
        </p>

        <div class="mt-8 grid gap-10 lg:grid-cols-[minmax(0,2fr)_minmax(0,1fr)]">
            <livewire:appointments.request-form />

            @if ($hasContact)
                {{-- A customer who would rather telephone should not have to fill
                     in a form to find the number - and on the phone they are
                     holding, the number itself is the control. --}}
                <div class="border-t border-border pt-10 lg:border-l lg:border-t-0 lg:border-border lg:pl-10 lg:pt-0">
                    <h3 class="text-[17px] font-semibold text-foreground">
                        Or get in touch directly
                    </h3>
                    <dl class="mt-5 space-y-5">
                        @if ($farm['phone'])
                            <div>
                                <dt class="text-[13px] font-semibold uppercase tracking-[0.07em] text-muted-foreground">Phone</dt>
                                <dd class="mt-1.5 text-[19px]">
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
                                <dd class="mt-1.5 text-[17px]">
                                    <a href="mailto:{{ $farm['email'] }}" class="text-primary hover:underline">{{ $farm['email'] }}</a>
                                </dd>
                            </div>
                        @endif
                        @if ($farm['address'])
                            <div>
                                <dt class="text-[13px] font-semibold uppercase tracking-[0.07em] text-muted-foreground">Where to find us</dt>
                                <dd class="mt-1.5 max-w-[34ch] text-[17px] leading-relaxed text-foreground">{{ $farm['address'] }}</dd>
                            </div>
                        @endif
                        @if ($farm['hours'])
                            <div>
                                <dt class="text-[13px] font-semibold uppercase tracking-[0.07em] text-muted-foreground">Visiting hours</dt>
                                <dd class="mt-1.5 max-w-[34ch] text-[17px] leading-relaxed text-foreground">{{ $farm['hours'] }}</dd>
                            </div>
                        @endif
                    </dl>
                </div>
            @endif
        </div>
    </section>
</div>
