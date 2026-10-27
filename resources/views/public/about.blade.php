<x-layouts::catalog title="About the farm">
    @php
        $farm = config('gfms.farm');
        $bloodlines = \App\Support\PublicFarm::bloodlines();
        $stockCount = \App\Support\PublicFarm::stockCount();

        /*
         * Three of the farm's own photographs, for the editorial spreads.
         *
         * Different birds from the front page's hero by design - each page
         * uses its photographs once - and drawn in a stable order so the page
         * does not reshuffle between visits. Real birds, real photographs:
         * nothing on this page is stock.
         */
        $pictured = \App\Support\PublicFarm::photographedBirds(3);

        /* How many birds carry each bloodline, from the farm's own records. */
        $counts = \App\Support\PublicFarm::bloodlineCounts();

        $photo = fn ($bird) => route('photos.show', $bird->primaryPhoto);
    @endphp

    {{--
        ABOUT - the farm's story, told editorially.

        Text and photographs in an asymmetric arrangement, every statement one
        the farm already makes. There are no founding years, awards or quotes
        here, because the records hold none - a stud book is worth exactly as
        much as its claims can be checked, and the page holds itself to that.
    --}}
    <div class="sb-wrap pb-16 pt-10 lg:pb-28 lg:pt-14">
        <x-page-header eyebrow="About {{ $farm['name'] }}" title="Bred here, and written down"
                       lead="Broodcocks bred and raised on the farm, with the pedigree of every bird kept from the day it hatched." />

        {{-- SPREAD ONE: the claim that answers "are these your birds", beside
             the farm's largest photograph. Text left, picture right. --}}
        <section class="mt-16 grid items-center gap-10 lg:mt-24 lg:grid-cols-[5fr_6fr] lg:gap-20" aria-labelledby="bred-heading">
            <div data-reveal>
                <h2 id="bred-heading" class="sb-h2">Bred on the farm</h2>
                <p class="mt-4 max-w-[44ch] text-[17px] leading-[1.7] text-foreground">
                    Every bird listed was hatched and raised here, or brought in and recorded on
                    arrival. Nothing is listed that the farm does not hold.
                </p>
                @if ($stockCount > 0)
                    <p class="mt-6 text-[15px] text-muted-foreground">
                        <span class="datum font-semibold text-foreground">{{ $stockCount }}</span>
                        {{ \Illuminate\Support\Str::plural('bird', $stockCount) }} on the farm today.
                    </p>
                @endif
            </div>

            @if ($pictured->get(0))
                <figure class="sb-photo aspect-[4/5]" data-reveal style="--reveal-delay: 100ms">
                    <img src="{{ $photo($pictured[0]) }}" alt="{{ $pictured[0]->band_number ?: $pictured[0]->name }}, {{ $pictured[0]->bloodline ?: 'gamefowl' }}"
                         width="800" height="1000" loading="lazy" decoding="async">
                    <figcaption class="sb-chip">
                        <x-band-dot :bloodline="$pictured[0]->bloodline" />
                        <span class="datum">{{ $pictured[0]->band_number ?: $pictured[0]->name }}</span>
                    </figcaption>
                </figure>
            @endif
        </section>

        {{-- SPREAD TWO, mirrored: two smaller photographs on the left, offset,
             and the pedigree claim on the right. The offset is the asymmetry
             - the second picture starts lower than the first. --}}
        <section class="mt-20 grid items-center gap-10 lg:mt-32 lg:grid-cols-[6fr_5fr] lg:gap-20" aria-labelledby="pedigree-heading">
            <div class="order-2 grid grid-cols-2 gap-4 lg:order-1 lg:gap-6">
                @foreach ($pictured->slice(1, 2) as $bird)
                    <figure @class(['sb-photo aspect-[4/5]', 'mt-12 lg:mt-20' => $loop->index === 1])
                            data-reveal style="--reveal-delay: {{ $loop->index * 90 }}ms">
                        <img src="{{ $photo($bird) }}" alt="{{ $bird->band_number ?: $bird->name }}, {{ $bird->bloodline ?: 'gamefowl' }}"
                             width="600" height="750" loading="lazy" decoding="async">
                    </figure>
                @endforeach
            </div>

            <div class="order-1 lg:order-2" data-reveal>
                <h2 id="pedigree-heading" class="sb-h2">Pedigree kept</h2>
                <p class="mt-4 max-w-[44ch] text-[17px] leading-[1.7] text-foreground">
                    A bird's sire and dam are written down wherever they are known, so its family
                    tree can be followed back through the farm's own records rather than taken on trust.
                </p>

                <h2 class="sb-h2 mt-12">Seen in person</h2>
                <p class="mt-4 max-w-[44ch] text-[17px] leading-[1.7] text-foreground">
                    A photograph only shows so much. Arrange a visit and look at the birds on the
                    farm before deciding anything.
                </p>
                <a href="{{ route('public.visit') }}" wire:navigate class="sb-link mt-4">Arrange a visit <span aria-hidden="true">&rarr;</span></a>
            </div>
        </section>

        {{-- THE BLOODLINES - the evidence under the claim. A label/value list
             with each bloodline's band dot and how many birds carry it, read
             from the records. --}}
        @if (count($counts) > 0)
            <section class="mt-20 border-t border-border pt-12 lg:mt-32 lg:pt-16" aria-labelledby="lines-heading">
                <div class="grid gap-10 lg:grid-cols-[5fr_6fr] lg:gap-20">
                    <div data-reveal>
                        <h2 id="lines-heading" class="sb-h2">Bloodlines kept here</h2>
                        <p class="mt-4 max-w-[40ch] text-[16px] leading-relaxed text-muted-foreground">
                            Each carries its own band colour through the whole site, so a bird's band
                            tells you its bloodline at a glance.
                        </p>
                    </div>

                    <dl class="border-t border-border" data-reveal style="--reveal-delay: 100ms">
                        @foreach ($counts as $bloodline => $count)
                            <div class="flex items-center justify-between gap-6 border-b border-border py-3.5">
                                <dt class="flex items-center gap-3 text-[16px] text-foreground">
                                    <x-band-dot :bloodline="$bloodline" />
                                    <a href="{{ route('catalog.index', ['bloodline' => $bloodline]) }}" wire:navigate
                                       class="hover:underline hover:underline-offset-4">{{ $bloodline }}</a>
                                </dt>
                                <dd class="datum text-[15px] text-muted-foreground">
                                    {{ $count }} {{ \Illuminate\Support\Str::plural('bird', $count) }}
                                </dd>
                            </div>
                        @endforeach
                    </dl>
                </div>
            </section>
        @endif

        <div class="mt-16 flex flex-wrap gap-3 lg:mt-20">
            <a href="{{ route('catalog.index') }}" wire:navigate class="btn-primary">See the {{ $stockCount > 0 ? $stockCount.' birds' : 'birds' }}</a>
            <a href="{{ route('public.visit') }}" wire:navigate class="btn-secondary">Arrange a visit</a>
        </div>
    </div>
</x-layouts::catalog>
