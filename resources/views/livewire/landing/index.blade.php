@php
    /*
     * config('gfms.farm.*') is the farm_settings row the owner edits at
     * /settings; App\Support\FarmProfile pushes it into config at boot.
     */
    $farm = config('gfms.farm');
    $telHref = $farm['phone'] ? preg_replace('/[^0-9+]/', '', $farm['phone']) : '';

    $birdCount = $this->stockCount;
    $lineCount = count($this->bloodlines);

    $photoUrl = fn ($photo, bool $full = false): ?string => $photo
        ? route('photos.show', $full ? $photo : ['photo' => $photo, 'size' => 'thumb'])
        : null;
@endphp

{{--
    THE FRONT PAGE — a board.

    The page is tiles fitted edge to edge with 8px gaps: the farm's name, its
    birds, its figures, one action. The previous front page was a sequence of
    sections divided by hairlines with a great deal of empty ground between
    them, and it read as thin. A board cannot be thin - every cell holds one
    true thing - and it is the honest shape for a farm whose story is ten
    birds, eight bloodlines and a phone number.

    THE IDEA: the farm's own banded birds are the tiles of the board, each one
    carrying its bloodline's band colour. Pick a bloodline and the board lights
    that bloodline's birds and dims the rest.

    It is a front page, not the whole site. The catalogue, the farm's story and
    the visiting details are pages of their own, and every tile here that
    points at one is a real link to it.
--}}
<div class="board-grid">

    {{-- ---------------------------------------------------------------
         THE NAME. The largest type on the site, and the page's one h1.
         One line wherever it fits; at phone widths it may take two, which is
         still inside the two-line rule - it is never cropped, because the
         farm's name is the thing a visitor must be able to read.
    --------------------------------------------------------------- --}}
    <div class="board-tile col-span-12 bg-tile px-5 pb-5 pt-7 sm:px-7 sm:pb-6 sm:pt-9">
        <h1 class="board-reveal font-display text-[13vw] font-semibold leading-[0.92] tracking-[-0.045em] text-board-ink sm:text-[9.6vw] xl:text-[min(8.9vw,150px)]">
            {{ $farm['name'] }}
        </h1>
    </div>

    {{-- ---------------------------------------------------------------
         THE FIRST SCREEN'S THREE COLUMNS.

         Left: one of the farm's own birds, tall. Centre: the farm at the hour
         the birds are let out, with the one sentence that says what this is
         laid along its foot. Right: the two figures that describe the farm
         and the action.

         The centre is a licensed stock photograph, used as atmosphere and
         credited in public/photos/CREDITS.md; the bird on the left is the
         farm's own, and links to that bird.
    --------------------------------------------------------------- --}}
    {{-- Its own grid, so the ORDER utilities below rearrange these three
         columns only. Order on the page's grid would also have pulled every
         later section ahead of them. On a phone the farm photograph and its
         sentence come first; on a desktop the bird stands on the left. --}}
    <div class="col-span-12 board-grid">
        <div class="board-tile order-1 col-span-12 flex flex-col overflow-hidden bg-tile lg:order-2 lg:col-span-6 lg:min-h-[540px]">
            <div class="relative aspect-[4/3] lg:aspect-auto lg:flex-1">
                <img src="{{ asset('photos/hero-farm.webp') }}"
                     alt="Gamefowl on grass beside a shed in evening light"
                     width="1800" height="1000" fetchpriority="high" decoding="async"
                     class="board-photo board-drift">
            </div>

            {{-- A statement, not a headline: a paragraph, three lines at most,
                 so it never competes with the name above. On a desktop it is a
                 tile laid along the foot of the photograph; on a phone it sits
                 under it, because an overlay at 390px covers the picture. --}}
            <div class="bg-tile p-5 sm:p-7 lg:absolute lg:bottom-4 lg:left-4 lg:right-4 lg:rounded-[var(--radius-tile)] lg:p-6">
                <p class="board-reveal max-w-[34ch] text-[22px] leading-[1.3] text-board-ink sm:text-[26px] lg:text-[24px] xl:text-[27px]" style="--reveal-delay: 120ms">
                    Broodcocks bred and raised in San Joaquin, Iloilo, with the sire and dam of every bird written down.
                </p>
            </div>
        </div>

        <a href="{{ $this->heroBird ? route('broodcocks.show', $this->heroBird) : route('catalog.index') }}" wire:navigate
           class="board-tile group order-2 col-span-6 aspect-[4/5] overflow-hidden bg-tile lg:order-1 lg:col-span-3 lg:aspect-auto">
            @if ($this->heroBird)
                <img src="{{ $photoUrl($this->heroBird->primaryPhoto, true) }}"
                     alt="{{ $this->heroBird->name }}, a {{ $this->heroBird->bloodline ?: 'gamefowl' }} on the farm"
                     width="800" height="1000" decoding="async"
                     class="board-photo">
                {{-- The bird's own band, on a filled tile across the TOP of the
                     photograph - the same object a visitor meets on every plate
                     below. At the foot it sat directly over the next section's
                     heading at tablet width and read as a label for it. --}}
                <span class="absolute left-3 right-3 top-3 flex flex-wrap items-center justify-between gap-x-3 gap-y-1.5 rounded-[var(--radius-tile)] bg-board/85 px-3 py-2.5">
                    <span class="whitespace-nowrap text-[15px] font-semibold text-board-ink">{{ $this->heroBird->name }}</span>
                    <x-band-tag :bloodline="$this->heroBird->bloodline" :band="$this->heroBird->band_number" size="xs" class="shrink-0" />
                </span>
            @else
                <span class="flex h-full items-end p-5 text-[17px] font-semibold text-board-ink">See the birds</span>
            @endif
        </a>

        <div class="order-3 col-span-6 grid gap-2 lg:col-span-3">
            <a href="{{ route('catalog.index') }}" wire:navigate
               class="board-tile flex flex-col justify-between gap-5 bg-hackle p-5 text-hackle-ink sm:p-6">
                <span class="board-figure board-reveal" style="--reveal-delay: 160ms">{{ $birdCount }}</span>
                <span class="text-[15px] font-semibold">{{ \Illuminate\Support\Str::plural('bird', $birdCount) }} on the farm today</span>
            </a>

            <a href="#bloodlines"
               class="board-tile flex flex-col justify-between gap-5 bg-brand-deep p-5 text-board-ink sm:p-6">
                <span class="board-figure board-reveal" style="--reveal-delay: 220ms">{{ $lineCount }}</span>
                <span class="text-[15px] font-semibold">{{ \Illuminate\Support\Str::plural('bloodline', $lineCount) }}, each with its own band colour</span>
            </a>

            <div class="board-tile flex flex-col gap-2 bg-tile p-4 sm:p-5">
                <a href="{{ route('catalog.index') }}" wire:navigate class="board-btn w-full">See the birds</a>
                <a href="{{ route('public.visit') }}" wire:navigate class="board-btn-tile w-full">Arrange a visit</a>
            </div>
        </div>
    </div>

    {{-- ---------------------------------------------------------------
         THE BIRDS — and the one live thing on the page.

         Pick a bloodline and the board keeps that bloodline's birds lit and
         dims the rest, on the page, with no reload. Each chip is a REAL LINK to
         the catalogue filtered to that bloodline, so with scripting off it
         still goes somewhere useful; Alpine only intercepts the click.

         Every bird tile carries its bloodline's band colour as a filled strip
         along its foot - the colour a visitor learns from the chip above is the
         one they then find on the bird.
    --------------------------------------------------------------- --}}
    @if ($this->featuredBirds->isNotEmpty())
        <section id="bloodlines" class="col-span-12 mt-2 scroll-mt-24"
                 x-data="{ line: null }"
                 aria-labelledby="birds-heading">
            <div class="board-grid">
                <div class="board-tile col-span-12 bg-tile p-5 sm:p-7 lg:col-span-4">
                    <h2 id="birds-heading" class="board-reveal font-display text-[28px] font-semibold leading-[1.1] tracking-[-0.02em] text-board-ink sm:text-[34px]">
                        Currently on the farm
                    </h2>
                    <p class="board-reveal mt-3 max-w-[36ch] text-[16px] leading-relaxed text-board-muted" style="--reveal-delay: 60ms">
                        Pick a bloodline to see which birds carry it.
                    </p>
                </div>

                <div class="board-tile col-span-12 bg-tile p-3 sm:p-4 lg:col-span-8">
                    <ul class="board-reveal flex flex-wrap gap-2" style="--reveal-delay: 80ms" aria-label="Bloodlines">
                        <li>
                            <a href="{{ route('catalog.index') }}"
                               x-on:click.prevent="line = null"
                               :class="line === null ? 'board-chip-on' : ''"
                               class="board-chip board-chip-on">All birds</a>
                        </li>
                        @foreach ($this->bloodlines as $bloodline)
                            <li>
                                <a href="{{ route('catalog.index', ['bloodline' => $bloodline]) }}"
                                   x-on:click.prevent="line = (line === @js($bloodline) ? null : @js($bloodline))"
                                   :class="line === @js($bloodline) ? 'board-chip-on' : ''"
                                   :aria-pressed="(line === @js($bloodline)).toString()"
                                   class="board-chip">
                                    <span class="h-3 w-3 shrink-0 rounded-[2px]" style="background-color: {{ \App\Support\BandTag::hex($bloodline) }}" aria-hidden="true"></span>
                                    {{ $bloodline }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>

                @foreach ($this->featuredBirds as $bird)
                    @php $hex = \App\Support\BandTag::hex($bird->bloodline); @endphp
                    <a href="{{ route('broodcocks.show', $bird) }}" wire:navigate
                       class="board-tile board-bird group col-span-6 overflow-hidden bg-tile md:col-span-4 lg:col-span-3"
                       :class="line !== null && line !== @js($bird->bloodline) ? 'board-bird-dim' : ''">
                        {{-- The entrance runs on this inner wrapper and the
                             dimming on the link outside it. Both animate opacity,
                             and on one element the entrance's stagger delay would
                             also delay the dimming - a chip press that answered
                             a third of a second late. --}}
                        <span class="board-reveal flex h-full flex-col" style="--reveal-delay: {{ ($loop->index % 4) * 70 }}ms">
                            <span class="relative block aspect-[4/5] overflow-hidden">
                                @if ($bird->primaryPhoto)
                                    <img src="{{ $photoUrl($bird->primaryPhoto) }}" alt="{{ $bird->name }}"
                                         width="400" height="500" loading="lazy" decoding="async"
                                         class="board-photo">
                                @else
                                    <span class="absolute inset-0 flex items-center justify-center text-[40px] font-semibold"
                                          style="color: {{ $hex }}" aria-hidden="true">{{ \App\Support\BandTag::code($bird->bloodline) }}</span>
                                @endif
                            </span>

                            {{-- The band strip: the bloodline's own colour, FILLED,
                                 along the foot of the photograph. A field, not a rule. --}}
                            <span class="h-1.5 w-full shrink-0" style="background-color: {{ $hex }}" aria-hidden="true"></span>

                            <span class="flex flex-1 flex-col gap-1 p-4">
                                <span class="truncate font-display text-[18px] font-semibold tracking-[-0.015em] text-board-ink">{{ $bird->name }}</span>
                                <span class="truncate text-[15px] text-board-muted">
                                    {{ $bird->bloodline ?: 'Bloodline not recorded' }}@if ($bird->ageLabel())<span aria-hidden="true"> &middot; </span><span class="datum">{{ $bird->ageLabel() }}</span>@endif
                                </span>
                            </span>
                        </span>
                    </a>
                @endforeach

                <a href="{{ route('catalog.index') }}" wire:navigate
                   class="board-tile col-span-12 flex items-center justify-between gap-4 bg-comb p-5 text-white sm:p-6">
                    <span class="font-display text-[22px] font-semibold tracking-[-0.02em] sm:text-[26px]">
                        See all {{ $birdCount }} {{ \Illuminate\Support\Str::plural('bird', $birdCount) }}
                    </span>
                    <span class="font-display text-[22px] font-semibold sm:text-[26px]" aria-hidden="true">&rarr;</span>
                </a>
            </div>
        </section>
    @endif

    {{-- ---------------------------------------------------------------
         THE MARQUEE — the page's one loud moment.

         The bloodlines the farm keeps, sliding across the full width as the
         page is scrolled past them (see .board-marquee - it never moves on its
         own), each followed by a mark in its own band colour. It used to
         carry the farm's name, which put that name on the page three times
         at display size - heading, marquee, footer - and three times is a
         stammer, not emphasis. The bloodlines are real words only this farm
         would run, and they rehearse the colours a visitor has just used to
         filter the board.
    --------------------------------------------------------------- --}}
    @if ($lineCount > 0)
        <div class="board-tile col-span-12 mt-2 overflow-hidden bg-brand-deep py-6" aria-hidden="true">
            <div class="board-marquee">
                @for ($i = 0; $i < 2; $i++)
                    <span class="board-marquee-run">
                        @foreach ($this->bloodlines as $bloodline)
                            {{ $bloodline }}<span class="mx-[0.35em] inline-block h-[0.42em] w-[0.42em] rounded-[3px] align-middle"
                                                   style="background-color: {{ \App\Support\BandTag::hex($bloodline) }}"></span>
                        @endforeach
                    </span>
                @endfor
            </div>
        </div>
    @endif

    {{-- ---------------------------------------------------------------
         HOW TO SEE THEM.

         Three filled cells: ring, write, or come. Each is the action itself -
         a tel: link, a mailto: link, the visiting page - rather than a label
         with the detail somewhere else. Conditional, because the farm can
         clear any of these at /settings.
    --------------------------------------------------------------- --}}
    <section class="col-span-12 mt-2" aria-labelledby="visit-heading">
        <div class="board-grid">
            <div class="board-tile col-span-12 overflow-hidden bg-tile lg:col-span-5">
                <div class="relative aspect-[16/9] lg:absolute lg:inset-0 lg:aspect-auto">
                    <img src="{{ asset('photos/visit-band.webp') }}" alt="Pasture at first light"
                         width="1600" height="560" loading="lazy" decoding="async"
                         class="board-photo">
                </div>
            </div>

            <div class="board-tile col-span-12 bg-tile p-5 sm:p-7 lg:col-span-7">
                <h2 id="visit-heading" class="board-reveal font-display text-[28px] font-semibold leading-[1.1] tracking-[-0.02em] text-board-ink sm:text-[34px]">
                    Come and see them
                </h2>
                <p class="board-reveal mt-3 max-w-[46ch] text-[16px] leading-relaxed text-board-ink" style="--reveal-delay: 60ms">
                    Visitors are welcome by arrangement. Ring or write first and the farm will settle a time with you.
                </p>

                <div class="board-reveal mt-6 grid gap-2 sm:grid-cols-3" style="--reveal-delay: 120ms">
                    @if ($farm['phone'])
                        <a href="tel:{{ $telHref }}" class="board-cell">
                            <span class="text-[13px] font-semibold text-board-muted">Ring the farm</span>
                            <span class="datum text-[17px] font-semibold text-board-ink">{{ $farm['phone'] }}</span>
                        </a>
                    @endif
                    @if ($farm['email'])
                        <a href="mailto:{{ $farm['email'] }}" class="board-cell">
                            <span class="text-[13px] font-semibold text-board-muted">Write to us</span>
                            <span class="break-all text-[15px] font-semibold text-board-ink">{{ $farm['email'] }}</span>
                        </a>
                    @endif
                    <a href="{{ route('public.visit') }}" wire:navigate class="board-cell board-cell-accent">
                        <span class="text-[13px] font-semibold">{{ $farm['hours'] ?: 'By arrangement' }}</span>
                        <span class="text-[17px] font-semibold">Plan a visit</span>
                    </a>
                </div>
            </div>
        </div>
    </section>
</div>
