@php
    /*
     * config('gfms.farm.*') is the farm_settings row the owner edits at
     * /settings; App\Support\FarmProfile pushes it into config at boot.
     */
    $farm = config('gfms.farm');
    $telHref = $farm['phone'] ? preg_replace('/[^0-9+]/', '', $farm['phone']) : '';

    /*
     * The kicker is the farm's town and province - the last two parts of its
     * address ("Brgy. Igcadlum, San Joaquin, Iloilo" -> "San Joaquin, Iloilo").
     * The barangay is for someone driving there; the town is for someone
     * deciding whether to.
     */
    $place = $farm['address']
        ? implode(', ', array_slice(array_map('trim', explode(',', $farm['address'])), -2))
        : null;

    $hero = $this->heroBird;
    $birds = $this->gridBirds;
    $total = $this->stockCount;
    $lines = count($this->bloodlines);

    $photoUrl = fn ($photo, bool $full = false): ?string => $photo
        ? route('photos.show', $full ? $photo : ['photo' => $photo, 'size' => 'thumb'])
        : null;

    /* A bird's name is shown beside its band only when it is a real name -
       "Spark" - rather than the band digits again, which most of them are. */
    $realName = fn ($bird): ?string => preg_match('/\p{L}/u', (string) $bird->name) ? $bird->name : null;

    $pedigree = $this->pedigreeBird;
@endphp

{{--
    THE FRONT PAGE — a stud book, not a template.

    Calm, photo-led, with the pedigree as the proof. Warm paper and near-black
    ink, one red from the logo, and whitespace between sections rather than a
    box around each one. The one dark block on the page is the pedigree,
    because recording parentage is the farm's real difference.

    Each photograph is used once: the hero bird is left out of the grid.
--}}
<div>
    {{-- ---------------------------------------------------------------
         1. THE HERO — asymmetric, 45 / 55.

         The headline says what the farm does, in its own voice; the farm's
         name is already in the header and does not need to be shouted again.
         The text rises in line by line, which is why each line is its own
         block - the breaks are written, not left to the browser, and every
         line is short enough to hold at 390px.
    --------------------------------------------------------------- --}}
    <section class="sb-wrap pb-16 pt-10 lg:pb-28 lg:pt-16">
        <div class="grid items-center gap-10 lg:grid-cols-[45fr_55fr] lg:gap-16">
            <div>
                @if ($place)
                    <p class="sb-eyebrow sb-line" style="--line: 0ms">{{ $place }}</p>
                @endif

                <h1 class="sb-hero-title mt-4">
                    <span class="sb-line" style="--line: 60ms">Broodcocks with</span>
                    <span class="sb-line" style="--line: 130ms">their pedigree</span>
                    <span class="sb-line" style="--line: 200ms">on record.</span>
                </h1>

                <p class="sb-lead sb-line mt-6" style="--line: 280ms">
                    Every bird here was hatched on the farm or recorded when it arrived,
                    banded, and written down with its sire and dam wherever they are known.
                </p>

                {{-- .sb-line is display:block, so the flex row is the inner
                     element - on one element the block display won and the
                     two buttons sat touching. --}}
                <div class="sb-line mt-8" style="--line: 340ms">
                    <div class="flex flex-wrap gap-3">
                        <a href="{{ route('catalog.index') }}" wire:navigate class="sb-cta">See the birds</a>
                        <a href="{{ route('public.visit') }}" wire:navigate class="sb-ghost">Arrange a visit</a>
                    </div>
                </div>

                {{-- The proof line. Every figure is counted, not claimed: the
                     brief's draft said "sire and dam recorded for each", and
                     the records do not support that, so it says how many. --}}
                <p class="sb-line mt-7 text-[14px] text-muted-foreground" style="--line: 400ms">
                    <span class="datum font-semibold text-foreground">{{ $total }}</span> {{ \Illuminate\Support\Str::plural('bird', $total) }}
                    <span aria-hidden="true">&middot;</span>
                    <span class="datum font-semibold text-foreground">{{ $lines }}</span> {{ \Illuminate\Support\Str::plural('bloodline', $lines) }}
                    @if ($this->parentageCount > 0)
                        <span aria-hidden="true">&middot;</span>
                        parentage on record for <span class="datum font-semibold text-foreground">{{ $this->parentageCount }}</span>
                    @endif
                </p>
            </div>

            @if ($hero)
                {{-- One bird, large. 4:5 on a phone and tablet; on a wide
                     screen the height is capped so the photograph never pushes
                     the rest of the page entirely below the fold, and the crop
                     gives a little at the sides rather than the bird's feet. --}}
                <a href="{{ route('broodcocks.show', $hero) }}" wire:navigate class="group block">
                    <figure class="sb-photo sb-photo-in aspect-[4/5] lg:aspect-auto lg:h-[min(82svh,860px)]">
                        <img src="{{ $photoUrl($hero->primaryPhoto, true) }}"
                             alt="{{ $realName($hero) ?? 'Broodcock '.($hero->band_number ?: $hero->name) }}, {{ $hero->bloodline ?: 'gamefowl' }}"
                             width="800" height="1000" fetchpriority="high" decoding="async">
                        <figcaption class="sb-chip">
                            <span class="sb-dot" style="background-color: {{ \App\Support\BandTag::hex($hero->bloodline) }}" aria-hidden="true"></span>
                            <span class="datum">{{ $hero->band_number ?: $hero->name }}</span>
                            @if ($hero->bloodline)
                                <span class="font-medium text-muted-foreground">{{ $hero->bloodline }}</span>
                            @endif
                        </figcaption>
                    </figure>
                </a>
            @endif
        </div>
    </section>

    {{-- ---------------------------------------------------------------
         2. ON THE FARM NOW — the main section, and the one live thing.

         Pick a bloodline and the grid keeps only those birds. Each pill is a
         REAL LINK to the catalogue filtered to that bloodline, so it still
         works with scripting off; Alpine intercepts the click. Where the
         browser supports view transitions the cards fade and slide into their
         new places; elsewhere they simply change.

         Pills come from the birds in THIS grid, not every bloodline on the
         farm: a pill whose only bird is the hero would empty the grid.
    --------------------------------------------------------------- --}}
    @if ($birds->isNotEmpty())
        <section class="sb-wrap sb-section border-t border-border" aria-labelledby="farm-now"
                 x-data="{
                     line: null,
                     pick(value) {
                         const swap = () => { this.line = value };
                         if (document.startViewTransition && ! matchMedia('(prefers-reduced-motion: reduce)').matches) {
                             document.startViewTransition(swap);
                         } else {
                             swap();
                         }
                     },
                 }">
            <div>
                <div data-reveal>
                    <h2 id="farm-now" class="sb-h2">On the farm now</h2>
                    <p class="mt-2 text-[16px] text-muted-foreground">
                        Banded, photographed and recorded. Choose a bloodline to narrow the list.
                    </p>
                </div>

                {{-- One row under the heading, not beside it: beside it the
                     eight pills wrapped and left one stranded on a second line. --}}
                <ul class="sb-pills mt-7" aria-label="Filter by bloodline" data-reveal style="--reveal-delay: 80ms">
                    <li>
                        <a href="{{ route('catalog.index') }}" class="sb-pill"
                           x-on:click.prevent="pick(null)"
                           :aria-pressed="(line === null).toString()" aria-pressed="true">All</a>
                    </li>
                    @foreach ($this->gridBloodlines as $bloodline)
                        <li>
                            <a href="{{ route('catalog.index', ['bloodline' => $bloodline]) }}" class="sb-pill"
                               x-on:click.prevent="pick(line === @js($bloodline) ? null : @js($bloodline))"
                               :aria-pressed="(line === @js($bloodline)).toString()" aria-pressed="false">
                                <span class="sb-dot" style="background-color: {{ \App\Support\BandTag::hex($bloodline) }}" aria-hidden="true"></span>
                                {{ $bloodline }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>

            <ul class="mt-10 grid grid-cols-2 gap-x-4 gap-y-10 md:grid-cols-3 lg:mt-12 lg:grid-cols-4 lg:gap-x-6 lg:gap-y-12">
                @foreach ($birds as $bird)
                    <li x-show="line === null || line === @js($bird->bloodline)"
                        style="view-transition-name: bird-{{ $bird->id }}">
                        <a href="{{ route('broodcocks.show', $bird) }}" wire:navigate class="group block"
                           data-reveal style="--reveal-delay: {{ ($loop->index % 4) * 70 }}ms">
                            <div class="sb-photo aspect-[4/5]">
                                @if ($bird->primaryPhoto)
                                    <img src="{{ $photoUrl($bird->primaryPhoto) }}"
                                         alt="{{ $realName($bird) ?? 'Broodcock '.($bird->band_number ?: $bird->name) }}, {{ $bird->bloodline ?: 'bloodline not recorded' }}"
                                         width="400" height="500" loading="lazy" decoding="async">
                                @else
                                    <span class="flex h-full items-center justify-center text-[14px] text-muted-foreground">No photo yet</span>
                                @endif
                            </div>

                            <div class="mt-3.5 flex items-baseline justify-between gap-3">
                                <h3 class="min-w-0 truncate text-[17px] font-semibold tracking-[-0.015em] text-foreground group-hover:underline group-hover:decoration-1 group-hover:underline-offset-4">
                                    {{ $bird->band_number ?: $bird->name }}@if ($realName($bird) && $bird->band_number)<span class="font-sans text-[15px] font-medium text-muted-foreground"> &nbsp;{{ $realName($bird) }}</span>@endif
                                </h3>
                                @if ($bird->ageLabel())
                                    <span class="datum shrink-0 text-[14px] text-muted-foreground">{{ $bird->ageLabel() }}</span>
                                @endif
                            </div>
                            <p class="mt-1 flex items-center gap-2 text-[14px] text-muted-foreground">
                                <span class="sb-dot" style="background-color: {{ \App\Support\BandTag::hex($bird->bloodline) }}" aria-hidden="true"></span>
                                {{ $bird->bloodline ?: 'Bloodline not recorded' }}
                            </p>
                        </a>
                    </li>
                @endforeach
            </ul>

            <div class="mt-12 lg:mt-14">
                <a href="{{ route('catalog.index') }}" wire:navigate class="sb-link text-[16px]">
                    See all {{ $total }} {{ \Illuminate\Support\Str::plural('bird', $total) }} <span aria-hidden="true">&rarr;</span>
                </a>
            </div>
        </section>
    @endif

    {{-- ---------------------------------------------------------------
         3. WHY THE PEDIGREE MATTERS — the one dark block on the page.

         Allowed because it is the farm's real difference. On one side a
         short paragraph; on the other, the real pedigree of one real bird,
         drawn as a small tree: the bird, then its sire and its dam. The bird
         is chosen by a rule from the records (see pedigreeBird()), and when
         no bird qualifies the block shows the bloodline counts instead.
         Nothing here is invented.

         Bloodline colour appears only as a thin line beside each name.
    --------------------------------------------------------------- --}}
    <section class="sb-dark" aria-labelledby="pedigree-heading">
        <div class="sb-wrap sb-section grid gap-12 lg:grid-cols-[5fr_7fr] lg:gap-20">
            <div data-reveal>
                <h2 id="pedigree-heading" class="sb-h2 text-[color:var(--sb-dark-ink)]">Why the pedigree matters</h2>
                <p class="mt-5 max-w-[46ch] text-[17px] leading-[1.65] text-[color:var(--sb-dark-muted)]">
                    A bloodline written on a card is a claim. A sire and a dam written down
                    the day a bird hatches is a record you can follow. Every bird the farm
                    raises goes into the book with its parents, so what you are told about
                    a bird can be traced back rather than taken on trust.
                </p>
                <a href="{{ route('public.about') }}" wire:navigate
                   class="mt-7 inline-flex min-h-11 items-center gap-1.5 text-[15px] font-semibold text-[color:var(--sb-dark-ink)] underline decoration-[color:var(--sb-dark-line)] underline-offset-[5px] hover:decoration-[color:var(--sb-dark-ink)]">
                    How the farm keeps its records <span aria-hidden="true">&rarr;</span>
                </a>
            </div>

            <div data-reveal style="--reveal-delay: 120ms">
                @if ($pedigree)
                    @php
                        $parents = [
                            ['role' => 'Sire', 'bird' => $pedigree->sire],
                            ['role' => 'Dam', 'bird' => $pedigree->dam],
                        ];
                    @endphp
                    <figure>
                        <figcaption class="text-[14px] text-[color:var(--sb-dark-muted)]">
                            From the records: <span class="text-[color:var(--sb-dark-ink)]">{{ $pedigree->band_number ?: $pedigree->name }}</span> and its parents
                        </figcaption>

                        {{-- The tree: the bird on the left, a bracket, and its two
                             parents on the right - drawn with hairlines, which is
                             what a pedigree chart has always been. --}}
                        <div class="mt-8 grid grid-cols-[auto_auto_1fr] items-center gap-x-3 sm:gap-x-5">
                            <a href="{{ route('broodcocks.pedigree', $pedigree) }}" wire:navigate class="group block min-w-0 border-l-2 py-1 pl-4 pr-1"
                               style="border-color: {{ \App\Support\BandTag::hex($pedigree->bloodline) }}">
                                <span class="block truncate font-display text-[22px] font-semibold tracking-[-0.015em] text-[color:var(--sb-dark-ink)] group-hover:underline group-hover:underline-offset-4 sm:text-[26px]">
                                    {{ $pedigree->band_number ?: $pedigree->name }}
                                </span>
                                <span class="mt-1 block text-[14px] text-[color:var(--sb-dark-muted)]">{{ $pedigree->bloodline ?: 'Bloodline not recorded' }}</span>
                            </a>

                            {{-- The bracket: a vertical stroke joining the two
                                 parents, and a short arm reaching back to the bird. --}}
                            <div class="flex h-28 w-10 items-center sm:w-16" aria-hidden="true">
                                <span class="h-px w-1/2 bg-[color:var(--sb-dark-line)]"></span>
                                <span class="h-full w-px bg-[color:var(--sb-dark-line)]"></span>
                                <span class="flex h-full w-1/2 flex-col justify-between">
                                    <span class="h-px w-full bg-[color:var(--sb-dark-line)]"></span>
                                    <span class="h-px w-full bg-[color:var(--sb-dark-line)]"></span>
                                </span>
                            </div>

                            <ul class="flex h-44 min-w-0 flex-col justify-between">
                                @foreach ($parents as $parent)
                                    <li class="min-w-0 border-l-2 py-1 pl-4" style="border-color: {{ \App\Support\BandTag::hex($parent['bird']->bloodline) }}">
                                        <span class="block text-[13px] font-semibold text-[color:var(--sb-dark-muted)]">{{ $parent['role'] }}</span>
                                        <span class="block truncate font-display text-[18px] font-semibold tracking-[-0.015em] text-[color:var(--sb-dark-ink)] sm:text-[20px]">
                                            {{ $parent['bird']->band_number ?: $parent['bird']->name }}
                                        </span>
                                        <span class="block text-[14px] text-[color:var(--sb-dark-muted)]">
                                            {{ $parent['bird']->bloodline ?: 'Bloodline not recorded' }}@if ($parent['bird']->is_external) &middot; outside bird @endif
                                        </span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </figure>
                @elseif (count($this->bloodlineCounts) > 0)
                    {{-- No bird qualifies yet, so the block shows what IS on
                         record: how many birds carry each bloodline. --}}
                    <p class="text-[14px] text-[color:var(--sb-dark-muted)]">The bloodlines on the farm today</p>
                    <dl class="mt-5">
                        @foreach ($this->bloodlineCounts as $bloodline => $count)
                            <div class="flex items-center justify-between gap-6 border-b border-[color:var(--sb-dark-line)] py-3">
                                <dt class="flex items-center gap-3 text-[16px] text-[color:var(--sb-dark-ink)]">
                                    <span class="sb-dot" style="background-color: {{ \App\Support\BandTag::hex($bloodline) }}" aria-hidden="true"></span>
                                    {{ $bloodline }}
                                </dt>
                                <dd class="datum text-[16px] text-[color:var(--sb-dark-muted)]">{{ $count }}</dd>
                            </div>
                        @endforeach
                    </dl>
                @endif
            </div>
        </div>
    </section>

    {{-- ---------------------------------------------------------------
         4. VISIT THE FARM.

         The field photograph on one side; on the other, the four things a
         visitor needs as plain label/value rows divided by hairlines. The
         phone and email are real tel: and mailto: links. Every row is
         conditional - the farm can clear any of them at /settings.
    --------------------------------------------------------------- --}}
    <section class="sb-wrap sb-section" aria-labelledby="visit-heading">
        <div class="grid items-center gap-10 lg:grid-cols-2 lg:gap-20">
            <div class="sb-photo aspect-[4/5] lg:order-2" data-reveal>
                <img src="{{ asset('photos/visit-field.webp') }}" alt="Pasture at first light"
                     width="880" height="1100" loading="lazy" decoding="async">
            </div>

            <div class="lg:order-1" data-reveal style="--reveal-delay: 100ms">
                <h2 id="visit-heading" class="sb-h2">Visit the farm</h2>
                <p class="mt-3 max-w-[46ch] text-[16px] text-muted-foreground">
                    Visitors are welcome by arrangement. Ring or write first, and the farm will settle a time with you.
                </p>

                <dl class="mt-8 border-t border-border">
                    @if ($farm['address'])
                        <div class="grid gap-1 border-b border-border py-4 sm:grid-cols-[140px_1fr] sm:gap-6">
                            <dt class="text-[14px] font-medium text-muted-foreground">Address</dt>
                            <dd class="text-[16px] text-foreground">{{ $farm['address'] }}</dd>
                        </div>
                    @endif
                    @if ($farm['hours'])
                        <div class="grid gap-1 border-b border-border py-4 sm:grid-cols-[140px_1fr] sm:gap-6">
                            <dt class="text-[14px] font-medium text-muted-foreground">Visiting hours</dt>
                            <dd class="text-[16px] text-foreground">{{ $farm['hours'] }}</dd>
                        </div>
                    @endif
                    @if ($farm['phone'])
                        <div class="grid gap-1 border-b border-border py-4 sm:grid-cols-[140px_1fr] sm:gap-6">
                            <dt class="text-[14px] font-medium text-muted-foreground">Phone</dt>
                            <dd class="text-[16px]">
                                <a href="tel:{{ $telHref }}" class="datum font-semibold text-primary underline decoration-primary/30 underline-offset-4 hover:decoration-primary">{{ $farm['phone'] }}</a>
                            </dd>
                        </div>
                    @endif
                    @if ($farm['email'])
                        <div class="grid gap-1 border-b border-border py-4 sm:grid-cols-[140px_1fr] sm:gap-6">
                            <dt class="text-[14px] font-medium text-muted-foreground">Email</dt>
                            <dd class="break-words text-[16px]">
                                <a href="mailto:{{ $farm['email'] }}" class="font-semibold text-primary underline decoration-primary/30 underline-offset-4 hover:decoration-primary">{{ $farm['email'] }}</a>
                            </dd>
                        </div>
                    @endif
                </dl>

                <a href="{{ route('public.visit') }}" wire:navigate class="sb-cta mt-8">Plan a visit</a>
            </div>
        </div>
    </section>
</div>
