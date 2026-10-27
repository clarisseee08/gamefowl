<x-layouts::catalog title="About the farm">
    @php
        $farm = config('gfms.farm');
        $bloodlines = \App\Support\PublicFarm::bloodlines();
        $stockCount = \App\Support\PublicFarm::stockCount();
    @endphp

    {{--
        WHAT THE FARM IS — its own page now.

        These three statements used to be a band halfway down the front page,
        between the masthead and the catalogue, where nobody arriving to look
        at birds would stop to read them. Given a page they can be what they
        actually are: the farm's answer to "are these your birds, and can you
        prove it".

        The bloodline list comes with them, because it belongs to the same
        question. On the front page it was a column beside a register; here it
        is the evidence under the claim it supports.
    --}}
    <article class="py-6">
        <header class="max-w-[52ch]">
            <p class="text-[13px] font-medium text-muted-foreground">
                About {{ $farm['name'] }}
            </p>
            <h1 class="mt-3 text-[38px] font-semibold leading-[1.08] text-foreground sm:text-[48px]">
                Bred here, and written down
            </h1>
            <p class="mt-5 text-[19px] leading-relaxed text-muted-foreground">
                Broodcocks bred and raised on the farm, with the pedigree of every
                bird kept from the day it hatched.
            </p>
        </header>

        {{-- ASYMMETRIC, not three equal columns. The first statement answers
             the question a visitor actually arrives with, so it runs wider and
             larger; the other two sit beside it, stacked and ruled. There are
             no ordinals - three statements do not need an index, and numbering
             them implied an order they do not have. --}}
        <div class="mt-14 grid gap-x-12 gap-y-10 border-t border-border pt-10 lg:grid-cols-12">
            <div class="lg:col-span-5">
                <h2 class="text-[26px] font-semibold leading-[1.2] text-foreground sm:text-[30px]">
                    Bred on the farm
                </h2>
                <p class="mt-4 max-w-[42ch] text-[18px] leading-relaxed text-muted-foreground">
                    Every bird listed was hatched and raised here, or brought in and
                    recorded on arrival. Nothing is listed that the farm does not hold.
                </p>
            </div>

            <div class="lg:col-span-6 lg:col-start-7">
                <div class="border-t border-border pt-6">
                    <h2 class="text-[19px] font-semibold text-foreground">Pedigree kept</h2>
                    <p class="mt-2 max-w-[48ch] text-[17px] leading-relaxed text-muted-foreground">
                        Sire and dam are recorded for each bird, so a family tree can be
                        followed back through the farm's own records rather than taken on trust.
                    </p>
                </div>
                <div class="mt-7 border-t border-border pt-6">
                    <h2 class="text-[19px] font-semibold text-foreground">Seen in person</h2>
                    <p class="mt-2 max-w-[48ch] text-[17px] leading-relaxed text-muted-foreground">
                        A photograph only shows so much. Arrange a visit and look at the birds
                        on the farm before deciding anything.
                    </p>
                </div>
            </div>
        </div>

        @if (count($bloodlines) > 0)
            {{-- THE BLOODLINES, AS A RULED LIST WITH A COLOUR EDGE.

                 They used to be filled chips under the front-page headline -
                 seven saturated pills in unrelated hues, which read as a tag
                 cloud and was the single most template-looking thing on the
                 site. Colour still means bloodline, which is the rule this
                 whole system rests on; it is carried by a 3px edge and the
                 two-letter code rather than by a filled capsule. Same
                 resolver, same colours, a fraction of the volume - and colour
                 is never the only channel, because the code and the name are
                 both text. --}}
            <section class="mt-16 border-t border-border pt-10">
                <h2 class="text-[21px] font-semibold text-foreground">Bloodlines kept here</h2>
                <p class="mt-2 max-w-[56ch] text-[17px] leading-relaxed text-muted-foreground">
                    Each carries its own colour through the whole site, so a bird's band
                    tells you its bloodline at a glance.
                </p>

                <ul class="mt-7 grid gap-x-10 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($bloodlines as $bloodline)
                        @php $hex = \App\Support\BandTag::hex($bloodline); @endphp
                        <li class="flex items-center gap-3 border-b border-border py-3 pl-3"
                            style="box-shadow: inset 3px 0 0 0 {{ $hex }}">
                            <span class="datum w-7 shrink-0 text-[13px] font-semibold" style="color: {{ $hex }}">
                                {{ \App\Support\BandTag::code($bloodline) }}
                            </span>
                            <span class="min-w-0 flex-1 truncate text-[16px] text-foreground">{{ $bloodline }}</span>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif

        <div class="mt-14 flex flex-wrap items-center gap-3 border-t border-border pt-8">
            <a href="{{ route('catalog.index') }}" wire:navigate class="btn-primary">
                See the {{ $stockCount > 0 ? $stockCount.' birds' : 'stock' }}
            </a>
            <a href="{{ route('public.visit') }}" wire:navigate class="btn-secondary">Arrange a visit</a>
        </div>
    </article>
</x-layouts::catalog>
