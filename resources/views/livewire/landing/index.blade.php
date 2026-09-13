@php
    $farm = config('gfms.farm');
    $hasContact = $farm['address'] || $farm['phone'] || $farm['email'] || $farm['hours'];
@endphp

{{--
    THE FRONT PAGE.

    Ink, rule and paper. There is no coloured banner here and there should
    never be one: colour in this system means bloodline, and the only coloured
    things on this page are the band tags inside the catalogue below. A hero
    that reaches for a wash of green to feel less plain would be the single
    fastest way to break the rule the whole design rests on.

    Weight, space and a hairline do the work instead.
--}}
<div>
    {{-- ---------------------------------------------------------------
         Hero.

         Two anchors, and the second one matters structurally: the
         catalogue sits between here and the form, so without a way to
         jump straight down, arranging a visit would mean scrolling past
         every bird on the farm.
    --------------------------------------------------------------- --}}
    <section class="border-b border-border pb-12 pt-6 sm:pt-10">
        <p class="text-[13px] font-semibold uppercase tracking-[0.09em] text-muted-foreground">
            {{ $farm['address'] ?: 'Gamefowl breeding farm' }}
        </p>

        <h1 class="mt-4 max-w-[16ch] text-[40px] font-semibold leading-[1.08] tracking-[-0.02em] text-foreground sm:text-[56px]">
            {{ $farm['name'] }}
        </h1>

        <p class="mt-6 max-w-[58ch] text-[19px] leading-relaxed text-muted-foreground">
            Broodcocks bred and raised here, with the pedigree of every bird kept from
            the day it hatched. Browse what is on the farm, and come and see them.
        </p>

        <div class="mt-8 flex flex-wrap items-center gap-3">
            <a href="#stock" class="btn-primary">See our stock</a>
            <a href="#visit" class="btn-secondary">Arrange a visit</a>
        </div>
    </section>

    {{-- ---------------------------------------------------------------
         What the farm is.

         Three short statements rather than a paragraph nobody reads. Each
         one is a fact about how the farm works, not a claim about it.
    --------------------------------------------------------------- --}}
    <section class="border-b border-border py-12">
        <div class="grid gap-8 sm:grid-cols-3">
            <div>
                <h2 class="text-[17px] font-semibold text-foreground">Bred on the farm</h2>
                <p class="mt-2 max-w-[38ch] text-[17px] leading-relaxed text-muted-foreground">
                    Every bird listed was hatched and raised here, or brought in and
                    recorded on arrival. Nothing is listed that the farm does not hold.
                </p>
            </div>
            <div>
                <h2 class="text-[17px] font-semibold text-foreground">Pedigree kept</h2>
                <p class="mt-2 max-w-[38ch] text-[17px] leading-relaxed text-muted-foreground">
                    Sire and dam are recorded for each bird, so a family tree can be
                    followed back through the farm's own records rather than taken on trust.
                </p>
            </div>
            <div>
                <h2 class="text-[17px] font-semibold text-foreground">Seen in person</h2>
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
         /catalog renders, so the two can never drift apart.
    --------------------------------------------------------------- --}}
    <section id="stock" class="scroll-mt-20 border-b border-border py-12">
        <livewire:catalog.browse />
    </section>

    {{-- ---------------------------------------------------------------
         Visiting.

         The form arrives here in the next step. Until it does, this
         section carries the farm's own contact details, which is a
         complete answer to "how do I come and see them" on its own.
    --------------------------------------------------------------- --}}
    <section id="visit" class="scroll-mt-20 py-12">
        <h2 class="text-[28px] font-semibold leading-[1.15] tracking-[-0.01em] text-foreground">
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
                {{-- The form is the easy path, not the only one. A customer who
                     would rather telephone should not have to fill in a form to
                     find the number. --}}
                <div class="lg:border-l lg:border-border lg:pl-10">
                    <h3 class="text-[13px] font-semibold uppercase tracking-[0.07em] text-muted-foreground">
                        Or get in touch directly
                    </h3>
                    <dl class="mt-5 space-y-5">
                        @if ($farm['phone'])
                            <div>
                                <dt class="text-[15px] text-muted-foreground">Phone</dt>
                                <dd class="datum mt-1 text-[19px] text-foreground">{{ $farm['phone'] }}</dd>
                            </div>
                        @endif
                        @if ($farm['email'])
                            <div>
                                <dt class="text-[15px] text-muted-foreground">Email</dt>
                                <dd class="mt-1 text-[17px] text-foreground">
                                    <a href="mailto:{{ $farm['email'] }}" class="hover:underline">{{ $farm['email'] }}</a>
                                </dd>
                            </div>
                        @endif
                        @if ($farm['address'])
                            <div>
                                <dt class="text-[15px] text-muted-foreground">Where to find us</dt>
                                <dd class="mt-1 max-w-[34ch] text-[17px] leading-relaxed text-foreground">{{ $farm['address'] }}</dd>
                            </div>
                        @endif
                        @if ($farm['hours'])
                            <div>
                                <dt class="text-[15px] text-muted-foreground">Visiting hours</dt>
                                <dd class="mt-1 max-w-[34ch] text-[17px] leading-relaxed text-foreground">{{ $farm['hours'] }}</dd>
                            </div>
                        @endif
                    </dl>
                </div>
            @endif
        </div>
    </section>
</div>
