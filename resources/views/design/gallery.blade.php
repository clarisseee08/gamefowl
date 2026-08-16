@php
    use App\Enums\BroodcockClass;
    use App\Enums\BroodcockStatus;
    use App\Enums\HealthRecordType;
    use App\Enums\PerformanceEventType;
    use App\Enums\PerformanceResult;
    use App\Http\Controllers\DesignGalleryController as G;
    use App\Support\BandTag;
@endphp

<x-layouts::app title="Design System">
    <div class="space-y-14 pb-16">

        <header>
            <h1 class="text-[30px] font-semibold leading-[1.15] text-foreground">Design System</h1>
            <p class="mt-2 max-w-[68ch] text-[15px] leading-relaxed text-muted-foreground">
                Every component in every state. This page is the reference the whole
                interface is built against — if something here looks wrong, it is wrong
                everywhere.
            </p>
        </header>

        {{-- 1 ─ ELEVATION. The biggest single lever in the direction change. --}}
        <section>
            <h2 class="text-[19px] font-semibold text-foreground">Elevation</h2>
            <p class="mt-1.5 max-w-[68ch] text-[14px] text-muted-foreground">
                Three steps, and they mean something: elevation indicates interactivity or
                layering, never decoration. Nothing lifts on its own. The previous direction
                forbade shadows entirely, which is exactly what made it read as paper.
            </p>

            <div class="mt-5 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ([
                    ['elev-0', 'Page chrome, table rows', ''],
                    ['elev-1', 'Cards, panels, stat tiles', 'elev-1'],
                    ['elev-2', 'Hover on interactive cards, dropdowns', 'elev-2'],
                    ['elev-3', 'Modals, palette, popovers', 'elev-3'],
                ] as [$name, $use, $cls])
                    <div class="rounded-[var(--radius-md)] border border-border bg-card p-5 {{ $cls }}">
                        <p class="datum text-[13px] font-medium text-foreground">{{ $name }}</p>
                        <p class="mt-1 text-[12px] leading-snug text-muted-foreground">{{ $use }}</p>
                    </div>
                @endforeach
            </div>

            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                <div class="card card-interactive p-5">
                    <p class="text-[14px] font-medium text-foreground">Interactive card</p>
                    <p class="mt-1 text-[13px] text-muted-foreground">
                        Hover me — lifts elev-1 → elev-2 over 200ms and rises 1px.
                    </p>
                </div>
                <div class="card p-5">
                    <p class="text-[14px] font-medium text-foreground">Static card</p>
                    <p class="mt-1 text-[13px] text-muted-foreground">
                        Does not lift. Only things you can act on move.
                    </p>
                </div>
            </div>
        </section>

        {{-- 2 ─ COLOUR, with measured contrast --}}
        <section>
            <h2 class="text-[19px] font-semibold text-foreground">Colour</h2>
            <p class="mt-1.5 max-w-[68ch] text-[14px] text-muted-foreground">
                Ratios are computed at render time, not typed in. Console text must clear
                <span class="datum">7:1</span> because the interface is used outdoors in
                daylight; the catalogue floor is <span class="datum">4.5:1</span>.
            </p>

            <h3 class="mt-6 text-[11px] font-semibold uppercase tracking-[0.07em] text-muted-foreground">Text on surface</h3>
            <div class="mt-3 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($inkPairs as [$name, $fg, $bgName, $bg])
                    @php $r = G::ratio($fg, $bg); @endphp
                    <div class="card p-4">
                        <div class="flex h-14 items-center justify-center rounded-[var(--radius-sm)] border border-border"
                             style="background-color: {{ $bg }}; color: {{ $fg }}">
                            <span class="text-[15px] font-medium">Aa 0123</span>
                        </div>
                        <p class="mt-2.5 text-[13px] font-medium text-foreground">{{ $name }}</p>
                        <p class="datum text-[11px] text-muted-foreground">on {{ $bgName }} · {{ $fg }}</p>
                        <p class="mt-1 text-[12px] {{ $r >= 7 ? 'text-success' : ($r >= 4.5 ? 'text-warning' : 'text-destructive') }}">
                            <span class="datum">{{ number_format($r, 2) }}:1</span>
                            {{ $r >= 7 ? '— clears Console' : ($r >= 4.5 ? '— catalogue only' : '— FAILS') }}
                        </p>
                    </div>
                @endforeach
            </div>

            <h3 class="mt-7 text-[11px] font-semibold uppercase tracking-[0.07em] text-muted-foreground">
                Semantic pairs — where "one accent only" is abandoned
            </h3>
            <div class="mt-3 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($statusPairs as [$name, $fg, $bgName, $bg])
                    @php $r = G::ratio($fg, $bg); @endphp
                    <div class="card p-4">
                        <div class="flex h-12 items-center justify-center rounded-[var(--radius-sm)] border border-border"
                             style="background-color: {{ $bg }}; color: {{ $fg }}">
                            <span class="text-[13px] font-medium">{{ $name }}</span>
                        </div>
                        <p class="datum mt-2 text-[11px] text-muted-foreground">{{ $fg }}</p>
                        <p class="text-[12px] {{ $r >= 4.5 ? 'text-success' : 'text-destructive' }}">
                            <span class="datum">{{ number_format($r, 2) }}:1</span>
                        </p>
                    </div>
                @endforeach
            </div>

            <h3 class="mt-7 text-[11px] font-semibold uppercase tracking-[0.07em] text-muted-foreground">Brand scale</h3>
            <div class="mt-3 flex flex-wrap gap-2">
                @foreach (['primary_50','primary_100','primary_200','primary_400','primary','primary_600','primary_700','primary_900'] as $step)
                    <div class="w-[104px] overflow-hidden rounded-[var(--radius-sm)] border border-border">
                        <div class="h-11" style="background-color: {{ $brand[$step] }}"></div>
                        <p class="datum px-2 py-1 text-[10.5px] text-muted-foreground">{{ $step === 'primary' ? '500' : str_replace('primary_', '', $step) }}</p>
                    </div>
                @endforeach
            </div>
        </section>

        {{-- 3 ─ THE BAND TAG --}}
        <section>
            <h2 class="text-[19px] font-semibold text-foreground">The band tag</h2>
            <p class="mt-1.5 max-w-[68ch] text-[14px] text-muted-foreground">
                The identity, carried through the direction change unchanged in concept.
                Modelled on the anodised ring a gamefowl actually wears.
                <strong class="font-medium text-foreground">Bloodline is free text, not an enum</strong> —
                colour resolves through a curated map, then a deterministic hash, so a
                bloodline nobody anticipated still renders. Colour is never the only
                channel: every tag carries a two-letter code and its name.
            </p>

            <div class="card mt-5 divide-y divide-border">
                @foreach (['Sweater', 'Hatch', 'Kelso', 'Roundhead', 'Grey', 'Claret'] as $i => $bl)
                    @php
                        $hex = BandTag::hex($bl);
                        $fg = BandTag::foreground($bl);
                        $ratio = BandTag::contrast($hex, $fg);
                    @endphp
                    <div class="flex flex-wrap items-center gap-4 p-3.5">
                        <x-band-tag :bloodline="$bl" :band="'SW-40'.str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT)" />
                        <x-band-tag :bloodline="$bl" :band="'SW-40'.str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT)" size="xs" />
                        <span class="text-[14px] text-foreground">{{ $bl }}</span>
                        <span class="datum ml-auto text-[11px] text-muted-foreground">
                            {{ $hex }} · {{ $fg === config('gfms-brand.band_foreground_light') ? 'white' : 'ink' }} ·
                            {{ number_format($ratio, 2) }}:1
                        </span>
                    </div>
                @endforeach

                <div class="flex flex-wrap items-center gap-4 p-3.5">
                    <x-band-tag bloodline="Whitehackle" band="WH-9001" />
                    <span class="text-[14px] text-foreground">Whitehackle</span>
                    <span class="text-[12px] text-muted-foreground">— unknown bloodline, hashed to a stable slot</span>
                </div>

                <div class="flex flex-wrap items-center gap-4 p-3.5">
                    <x-band-tag bloodline="Sweater" :band="null" />
                    <span class="text-[12px] text-muted-foreground">— banded at an age, not at hatch. Not an error.</span>
                </div>
            </div>

            <div class="card mt-3 p-4">
                <p class="text-[13px] font-medium text-foreground">Why the foreground is computed</p>
                <p class="mt-1.5 max-w-[68ch] text-[13px] leading-relaxed text-muted-foreground">
                    Three of the six bands cannot carry white text — amber measures
                    <span class="datum">2.08:1</span> against white, which is unreadable.
                    Darkening them until white worked would have walked amber back to the
                    muted gold this direction replaced. So each tag picks the foreground that
                    passes, which also covers whatever colour the hash hands a bloodline
                    nobody has typed yet.
                </p>
            </div>
        </section>

        {{-- 4 ─ CONTROLS + MOTION --}}
        <section>
            <h2 class="text-[19px] font-semibold text-foreground">Controls</h2>
            <p class="mt-1.5 max-w-[68ch] text-[14px] text-muted-foreground">
                Every control carries the motion system: 120ms on colour and focus, 200ms on
                elevation, a 2% scale on press. <code class="datum text-[13px]">prefers-reduced-motion</code>
                strips travel and transform while keeping the state change — a blanket kill
                removes useful feedback and is itself a defect.
            </p>

            <div class="card mt-5 space-y-6 p-6">
                <div class="flex flex-wrap items-center gap-3">
                    <button type="button" class="btn-primary">Save record</button>
                    <button type="button" class="btn-secondary">Cancel</button>
                    <button type="button" class="btn-danger">Delete</button>
                    <button type="button" class="btn-quiet">Clear filters</button>
                    <button type="button" class="btn-primary" disabled>Disabled</button>
                </div>

                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <label for="g-a" class="label">Band Number</label>
                        <input id="g-a" type="text" class="input mt-1" placeholder="e.g. SW-1024">
                        <p class="help">Leave empty if the bird has not been banded yet.</p>
                    </div>
                    <div>
                        <label for="g-b" class="label">Weight (kg)</label>
                        <input id="g-b" type="text" class="input input-error mt-1" value="-2">
                        <p class="error">Weight cannot be a negative number.</p>
                    </div>
                </div>
            </div>
        </section>

        {{-- 5 ─ ENUM BADGES: the lockstep self-check --}}
        <section>
            <h2 class="text-[19px] font-semibold text-foreground">Status pills</h2>
            <p class="mt-1.5 max-w-[68ch] text-[14px] text-muted-foreground">
                Rendered straight from the PHP enums. If a class is renamed on one side and
                not the other, an unstyled pill appears here immediately — which is the whole
                point of putting them on one page.
            </p>
            <div class="card mt-5 divide-y divide-border">
                @foreach ([
                    'Broodcock status' => BroodcockStatus::cases(),
                    'Class' => BroodcockClass::cases(),
                    'Health record type' => HealthRecordType::cases(),
                    'Performance event' => PerformanceEventType::cases(),
                    'Performance result' => PerformanceResult::cases(),
                ] as $group => $cases)
                    <div class="flex flex-wrap items-center gap-2.5 p-3.5">
                        <span class="datum w-40 shrink-0 text-[11px] text-muted-foreground">{{ $group }}</span>
                        @foreach ($cases as $case)
                            <span class="badge {{ $case->badgeClasses() }}">{{ $case->label() }}</span>
                        @endforeach
                    </div>
                @endforeach
            </div>
        </section>

        {{-- 6 ─ PATTERNS --}}
        <section>
            <h2 class="text-[19px] font-semibold text-foreground">Patterns</h2>

            <div class="card mt-5 overflow-hidden">
                <table class="table-hairline min-w-full">
                    <thead class="bg-muted">
                        <tr>
                            @foreach (['Band', 'Name', 'Bloodline', 'Weight', 'Status', ''] as $h)
                                <th class="px-3 py-2.5 text-left text-[11px] font-semibold uppercase tracking-[0.07em] text-muted-foreground">{{ $h }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ([['Sweater','SW-4001','Haring Agila','2.85'],['Hatch','HA-3120','Bantay','3.07'],['Kelso','KE-2088','Tandang','2.64']] as [$bl,$bn,$nm,$wt])
                            <tr class="row-hover">
                                <td class="px-3 py-2.5"><x-band-tag :bloodline="$bl" :band="$bn" size="xs" /></td>
                                <td class="px-3 py-2.5 text-[14px] font-medium text-foreground">{{ $nm }}</td>
                                <td class="px-3 py-2.5 text-[14px] text-muted-foreground">{{ $bl }}</td>
                                <td class="datum px-3 py-2.5 text-[14px] text-foreground">{{ $wt }} kg</td>
                                <td class="px-3 py-2.5"><span class="badge badge-ok">Active</span></td>
                                <td class="px-3 py-2.5 text-right">
                                    {{-- Revealed on hover. A permanent column of three buttons
                                         per row is the clearest tell of a scaffolded CRUD table. --}}
                                    <span class="row-actions inline-flex gap-1">
                                        <button type="button" class="btn-quiet h-8 min-h-0 px-2 text-[13px]">Edit</button>
                                        <button type="button" class="btn-quiet h-8 min-h-0 px-2 text-[13px] text-destructive">Delete</button>
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-4 grid gap-4 lg:grid-cols-3">
                <div class="card p-5">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.07em] text-muted-foreground">Vaccination compliance</p>
                    <p class="datum mt-1.5 text-[28px] font-semibold leading-none text-foreground">81.3%</p>
                    <div class="meter mt-3"><span class="bg-success" style="width: 81.3%"></span></div>
                    <p class="mt-2 text-[12px] text-muted-foreground">A bar reads faster than a number alone.</p>
                </div>

                <div class="card p-10 text-center">
                    <svg class="mx-auto h-7 w-7 text-muted-foreground" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 0 1-2.247 2.118H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z"/>
                    </svg>
                    <p class="mt-3 text-[14px] font-medium text-foreground">No health records yet</p>
                    <p class="mt-1 text-[13px] text-muted-foreground">Add the first vaccination or checkup.</p>
                    <button type="button" class="btn-primary mt-4">Add health record</button>
                </div>

                <div class="card p-5">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.07em] text-muted-foreground">Skeleton</p>
                    <div class="mt-3 space-y-2.5">
                        <div class="skeleton h-3 w-3/4"></div>
                        <div class="skeleton h-3 w-1/2"></div>
                        <div class="skeleton h-3 w-2/3"></div>
                    </div>
                    <p class="mt-3 text-[12px] text-muted-foreground">
                        Matches the real layout, so the page does not reflow when data lands.
                    </p>
                </div>
            </div>
        </section>

        {{-- 7 ─ RATIONALE --}}
        <section>
            <h2 class="text-[19px] font-semibold text-foreground">Why it looks like this</h2>
            <div class="card mt-5 space-y-3.5 p-6 text-[14px] leading-relaxed text-muted-foreground">
                <p><strong class="font-medium text-foreground">A registry, running as software.</strong>
                    The subject has not changed — provenance, bloodlines, papers. What changed
                    is that it presents as a live product with depth and state rather than a
                    printed document rendered in a browser.</p>
                <p><strong class="font-medium text-foreground">Colour still means bloodline.</strong>
                    The semantic pairs carry status and the brand scale carries navigation, but
                    the six band colours mean one thing and are never spent on anything else.</p>
                <p><strong class="font-medium text-foreground">Registry data is monospaced.</strong>
                    Proportional digits do not align in a column, and a weight column that does
                    not align is the fastest way to look amateur.</p>
                <p><strong class="font-medium text-foreground">Nothing depends on colour alone.</strong>
                    Every band tag carries a two-letter code and its bloodline name, so the
                    encoding survives colour-vision deficiency and a photocopied report.</p>
            </div>
        </section>
    </div>
</x-layouts::app>
