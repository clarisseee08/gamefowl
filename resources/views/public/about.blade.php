<x-layouts::catalog title="About the farm">
    @php
        $farm = config('gfms.farm');
        $bloodlines = \App\Support\PublicFarm::bloodlines();
        $stockCount = \App\Support\PublicFarm::stockCount();

        /*
         * The farm's own photographs lead the slideshow, in a stable order so
         * the page does not reshuffle between visits. Each carries its band
         * and links to the bird. The stock photographs after them are
         * atmosphere (public/photos/CREDITS.md) and carry no caption, so
         * nothing could read one as a bird the farm holds.
         */
        $pictured = \App\Support\PublicFarm::photographedBirds(4);

        /* How many birds carry each bloodline, from the farm's own records. */
        $counts = \App\Support\PublicFarm::bloodlineCounts();

        $slides = $pictured->map(fn ($bird) => [
            'src' => route('photos.show', $bird->primaryPhoto),
            'alt' => ($bird->band_number ?: $bird->name).', '.($bird->bloodline ?: 'gamefowl'),
            'href' => route('broodcocks.show', $bird),
            'caption' => $bird->band_number ?: $bird->name,
            'meta' => $bird->bloodline,
            'dot' => \App\Support\BandTag::hex($bird->bloodline),
        ])->all();

        $slides = [
            ...$slides,
            ['src' => asset('photos/rooster-portrait.webp'), 'alt' => 'A rooster in low light, close up', 'position' => '50% 35%'],
            ['src' => asset('photos/hens-fence.webp'), 'alt' => 'Free-range hens on grass behind a wire fence', 'position' => '55% 50%'],
            ['src' => asset('photos/rooster-walking.webp'), 'alt' => 'A red rooster with green tail feathers walking on bare ground', 'position' => '35% 50%'],
        ];
    @endphp

    {{--
        ABOUT - the farm's story, told editorially.

        Text and photographs in an asymmetric arrangement, every statement one
        the farm already makes. There are no founding years, awards or quotes
        here, because the records hold none - a stud book is worth exactly as
        much as its claims can be checked, and the page holds itself to that.
    --}}
    <div class="sb-wrap pb-16 pt-10 lg:pb-24 lg:pt-14">
        {{-- SPREAD ONE: who the farm is, and the claim that answers "are
             these your birds", beside the slideshow. --}}
        <section class="grid items-center gap-10 lg:grid-cols-[1fr_1fr] lg:gap-14" aria-labelledby="bred-heading">
            <div>
                <p class="sb-eyebrow sb-line" style="--line: 0ms">About {{ $farm['name'] }}</p>
                <h1 class="sb-hero-title sb-line mt-3" style="--line: 60ms">Bred here, and written down</h1>
                <p class="sb-lead sb-line mt-4 max-w-[50ch]" style="--line: 130ms">Broodcocks bred and raised on the farm, with the pedigree of every bird kept from the day it hatched.</p>

                <div class="sb-line mt-8" style="--line: 200ms">
                    <h2 id="bred-heading" class="text-lg font-bold text-foreground">Bred on the farm</h2>
                    <p class="mt-2 max-w-[48ch] text-[16px] leading-relaxed text-muted-foreground">
                        Every bird listed was hatched and raised here, or brought in and recorded on
                        arrival. Nothing is listed that the farm does not hold.
                    </p>
                </div>

                @if ($stockCount > 0 || count($counts) > 0)
                    <div class="sb-line mt-6" style="--line: 260ms">
                        <dl class="grid max-w-sm grid-cols-2 gap-2">
                            <div class="rounded-md border border-border bg-card px-3 py-2.5">
                                <dt class="text-[11px] font-medium text-muted-foreground">On the farm today</dt>
                                <dd class="mt-0.5 text-xl font-bold text-foreground"><span class="datum">{{ $stockCount }}</span> <span class="text-sm font-medium text-muted-foreground">{{ \Illuminate\Support\Str::plural('bird', $stockCount) }}</span></dd>
                            </div>
                            <div class="rounded-md border border-border bg-card px-3 py-2.5">
                                <dt class="text-[11px] font-medium text-muted-foreground">Bloodlines kept</dt>
                                <dd class="datum mt-0.5 text-xl font-bold text-foreground">{{ count($counts) }}</dd>
                            </div>
                        </dl>
                    </div>
                @endif
            </div>

            <div class="sb-photo-in">
                <x-ui.slideshow :slides="$slides" priority label="Birds and the farm" class="aspect-[4/5] rounded-xl bg-muted shadow-sm sm:aspect-[4/3] lg:aspect-[5/6]" />
            </div>
        </section>

        {{-- The two other claims, side by side as cards. --}}
        <section class="mt-16 grid gap-3 md:grid-cols-2 md:gap-4 lg:mt-20" aria-label="How the farm works">
            <article class="rounded-md border border-border bg-card p-6" data-reveal>
                <span class="inline-flex size-9 items-center justify-center rounded-full bg-muted text-foreground"><x-ui.icon name="file-text" class="size-4" /></span>
                <h2 id="pedigree-heading" class="mt-4 text-lg font-bold text-foreground">Pedigree kept</h2>
                <p class="mt-2 max-w-[52ch] text-[15px] leading-relaxed text-muted-foreground">
                    A bird's sire and dam are written down wherever they are known, so its family
                    tree can be followed back through the farm's own records rather than taken on trust.
                </p>
            </article>
            <article class="flex flex-col rounded-md border border-border bg-card p-6" data-reveal style="--reveal-delay: 90ms">
                <span class="inline-flex size-9 items-center justify-center rounded-full bg-muted text-foreground"><x-ui.icon name="map-pin" class="size-4" /></span>
                <h2 class="mt-4 text-lg font-bold text-foreground">Seen in person</h2>
                <p class="mt-2 max-w-[52ch] text-[15px] leading-relaxed text-muted-foreground">
                    A photograph only shows so much. Arrange a visit and look at the birds on the
                    farm before deciding anything.
                </p>
                <a href="{{ route('public.visit') }}" wire:navigate class="d-link d-link-primary mt-3 inline-flex min-h-9 items-center gap-1.5 text-sm font-semibold">Arrange a visit <span aria-hidden="true">&rarr;</span></a>
            </article>
        </section>

        {{-- THE BLOODLINES - the evidence under the claim: each bloodline's
             band dot and how many birds carry it, read from the records. --}}
        @if (count($counts) > 0)
            <section class="mt-16 grid gap-8 lg:mt-20 lg:grid-cols-[5fr_6fr] lg:gap-14" aria-labelledby="lines-heading">
                <div data-reveal>
                    <h2 id="lines-heading" class="sb-h2">Bloodlines kept here</h2>
                    <p class="mt-3 max-w-[40ch] text-[16px] leading-relaxed text-muted-foreground">
                        Each carries its own band colour through the whole site, so a bird's band
                        tells you its bloodline at a glance.
                    </p>
                </div>

                <ul class="divide-y divide-border rounded-md border border-border bg-card" data-reveal style="--reveal-delay: 100ms">
                    @foreach ($counts as $bloodline => $count)
                        <li>
                            <a href="{{ route('catalog.index', ['bloodline' => $bloodline]) }}" wire:navigate
                               class="flex min-h-12 items-center gap-3 px-4 py-3 text-[15px] text-foreground transition-colors hover:bg-muted/50">
                                <x-band-dot :bloodline="$bloodline" />
                                <span class="font-medium">{{ $bloodline }}</span>
                                <span class="datum ml-auto text-sm text-muted-foreground">{{ $count }} {{ \Illuminate\Support\Str::plural('bird', $count) }}</span>
                                <x-ui.icon name="chevron-right" class="size-4 text-muted-foreground" />
                            </a>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif

        <div class="mt-16 flex flex-wrap gap-3 lg:mt-20">
            <a href="{{ route('catalog.index') }}" wire:navigate class="d-btn d-btn-primary">See the {{ $stockCount > 0 ? $stockCount.' birds' : 'birds' }}</a>
            <a href="{{ route('public.visit') }}" wire:navigate class="d-btn d-btn-outline">Arrange a visit</a>
        </div>
    </div>
</x-layouts::catalog>
