@php
    use App\Enums\BroodcockClass;
    use App\Enums\BroodcockStatus;
    use App\Enums\HealthRecordType;
    use App\Enums\PerformanceEventType;
    use App\Enums\PerformanceResult;
    use App\Http\Controllers\DesignGalleryController as G;
@endphp

<x-layouts::app title="Design System">
    <div class="space-y-16">

        <header>
            <h1 class="text-[32px] font-semibold leading-[1.15] tracking-[-0.02em] text-foreground">
                Design System
            </h1>
            <p class="mt-3 max-w-[65ch] text-[17px] leading-relaxed text-muted-foreground">
                Every component in every state. This page is the reference the whole
                interface is built against — if something here looks wrong, it is wrong
                everywhere. Full rationale in <span class="datum">docs/design-brief.md</span>.
            </p>
        </header>

        {{-- 1 ─ TOKENS, with measured contrast --}}
        <section>
            <h2 class="text-[22px] font-semibold tracking-[-0.01em] text-foreground">Colour</h2>
            <p class="mt-2 max-w-[65ch] text-[15px] text-muted-foreground">
                Ratios below are computed at render time, not typed in. Console body text
                must clear <span class="datum">7:1</span> because the interface is used
                outdoors in daylight; Catalog text must clear <span class="datum">4.5:1</span>.
            </p>

            <h3 class="mt-8 text-[13px] font-medium uppercase tracking-[0.06em] text-muted-foreground">Ink on paper</h3>
            <div class="mt-3 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($inkPairs as [$name, $fg, $bgName, $bg])
                    @php $r = G::ratio($fg, $bg); @endphp
                    <div class="card p-4">
                        {{-- The plate is filled with the INK, not the paper: paper is
                             within a hair of white, so a paper-filled swatch reads as an
                             empty box and shows nothing. --}}
                        <div class="flex h-16 items-center justify-center rounded-[4px]"
                             style="background-color: {{ $fg }}; color: {{ $bg }}">
                            <span class="text-[15px] font-medium">Aa 0123</span>
                        </div>
                        <div class="mt-2 flex h-9 items-center justify-center rounded-[4px] border border-border"
                             style="background-color: {{ $bg }}; color: {{ $fg }}">
                            <span class="text-[13px]">on {{ $bgName }}</span>
                        </div>
                        <p class="mt-3 text-[13px] font-medium text-foreground">{{ $name }}</p>
                        <p class="datum text-[12px] text-muted-foreground">{{ $fg }}</p>
                        <p class="mt-1 text-[12px] {{ $r >= 7 ? 'text-success' : ($r >= 4.5 ? 'text-warning' : 'text-destructive') }}">
                            <span class="datum">{{ number_format($r, 2) }}:1</span>
                            {{ $r >= 7 ? '— passes Console 7:1' : ($r >= 4.5 ? '— Catalog only' : '— FAILS') }}
                        </p>
                    </div>
                @endforeach
            </div>

            <h3 class="mt-8 text-[13px] font-medium uppercase tracking-[0.06em] text-muted-foreground">Status on wash</h3>
            <div class="mt-3 grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
                @foreach ($statusPairs as [$name, $fg, $washName, $bg])
                    @php $r = G::ratio($fg, $bg); @endphp
                    <div class="card p-4">
                        <div class="flex h-12 items-center justify-center rounded-[4px] border border-border"
                             style="background-color: {{ $bg }}; color: {{ $fg }}">
                            <span class="text-[13px] font-medium">{{ $name }}</span>
                        </div>
                        <p class="datum mt-2 text-[12px] text-muted-foreground">{{ $fg }}</p>
                        <p class="text-[12px] {{ $r >= 4.5 ? 'text-success' : 'text-destructive' }}">
                            <span class="datum">{{ number_format($r, 2) }}:1</span>
                        </p>
                    </div>
                @endforeach
            </div>
        </section>

        {{-- 2 ─ THE BAND TAG --}}
        <section>
            <h2 class="text-[22px] font-semibold tracking-[-0.01em] text-foreground">The band tag</h2>
            <p class="mt-2 max-w-[65ch] text-[15px] text-muted-foreground">
                The signature, and the only place colour is spent. Modelled on the anodised
                aluminium ring a gamefowl actually wears. <strong>Bloodline is free text,
                not an enum</strong> — colour resolves through a curated map for known stock,
                then a deterministic hash, so a bloodline nobody anticipated still renders.
                Colour is never the only channel: every tag carries a two-letter code and its
                bloodline name.
            </p>

            <div class="card mt-6 divide-y divide-border">
                @foreach (['Sweater', 'Hatch', 'Kelso', 'Roundhead', 'Grey', 'Claret'] as $i => $bl)
                    <div class="flex flex-wrap items-center gap-4 p-4">
                        <x-band-tag :bloodline="$bl" :band="'SW-40'.str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT)" />
                        <x-band-tag :bloodline="$bl" :band="'SW-40'.str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT)" size="xs" />
                        <span class="text-[15px] text-foreground">{{ $bl }}</span>
                        <span class="datum ml-auto text-[12px] text-muted-foreground">{{ App\Support\BandTag::hex($bl) }}</span>
                    </div>
                @endforeach

                <div class="flex flex-wrap items-center gap-4 p-4">
                    <x-band-tag bloodline="Whitehackle" band="WH-9001" />
                    <span class="text-[15px] text-foreground">Whitehackle</span>
                    <span class="text-[13px] text-muted-foreground">— unknown bloodline, hashed to a stable slot</span>
                </div>

                <div class="flex flex-wrap items-center gap-4 p-4">
                    <x-band-tag bloodline="Sweater" :band="null" />
                    <span class="text-[15px] text-foreground">Sweater</span>
                    <span class="text-[13px] text-muted-foreground">— banded at an age, not at hatch. Not an error.</span>
                </div>

                <div class="flex flex-wrap items-center gap-4 p-4">
                    <x-band-tag :bloodline="null" :band="null" />
                    <span class="text-[15px] text-muted-foreground">no bloodline recorded</span>
                </div>
            </div>
        </section>

        {{-- 3 ─ TYPE --}}
        <section>
            <h2 class="text-[22px] font-semibold tracking-[-0.01em] text-foreground">Type</h2>
            <p class="mt-2 max-w-[65ch] text-[15px] text-muted-foreground">
                Fira Sans for interface, Fira Code for registry data — self-hosted, no CDN.
                Weight ladder is 400 / 500 / 600; there is no 700.
            </p>
            <div class="card mt-6 divide-y divide-border">
                @foreach ([
                    ['display', 'text-[32px] font-semibold tracking-[-0.02em]', 'Vaccination Schedule'],
                    ['title', 'text-[22px] font-semibold tracking-[-0.01em]', 'Breeding Success Trend'],
                    ['subtitle', 'text-[18px] font-medium', 'Vaccinations Needing Attention'],
                    ['body (Catalog)', 'text-[17px]', 'Browse the birds currently on the farm.'],
                    ['body (Console)', 'text-[15px]', 'Delete pen P-04 - Breeding Pen D?'],
                    ['label', 'text-[13px] font-medium tracking-[0.01em]', 'Band Number'],
                    ['caption', 'text-[12px] text-muted-foreground', 'Leave empty if the bird has not been banded yet.'],
                    ['micro', 'text-[11px] font-medium uppercase tracking-[0.06em] text-muted-foreground', 'Overdue Vaccinations'],
                ] as [$name, $cls, $sample])
                    <div class="flex flex-wrap items-baseline gap-4 p-4">
                        <span class="datum w-32 shrink-0 text-[12px] text-muted-foreground">{{ $name }}</span>
                        <span class="{{ $cls }} text-foreground">{{ $sample }}</span>
                    </div>
                @endforeach
                <div class="flex flex-wrap items-baseline gap-4 p-4">
                    <span class="datum w-32 shrink-0 text-[12px] text-muted-foreground">datum</span>
                    <span class="datum text-[15px] text-foreground">SW-4001 · 2.85 kg · 81.3% · 14 Jan 2026</span>
                </div>
            </div>

            <div class="card mt-4 p-4">
                <p class="text-[13px] font-medium text-foreground">Why registry data is monospaced</p>
                <div class="mt-3 grid gap-6 sm:grid-cols-2">
                    <div>
                        <p class="text-[11px] font-medium uppercase tracking-[0.06em] text-destructive">Proportional — misaligned</p>
                        <ul class="mt-2 space-y-1 text-[15px] text-foreground">
                            <li>2.85 kg</li><li>11.40 kg</li><li>3.07 kg</li><li>19.98 kg</li>
                        </ul>
                    </div>
                    <div>
                        <p class="text-[11px] font-medium uppercase tracking-[0.06em] text-success">Mono + tabular-nums</p>
                        <ul class="datum mt-2 space-y-1 text-[15px] text-foreground">
                            <li>2.85 kg</li><li>11.40 kg</li><li>3.07 kg</li><li>19.98 kg</li>
                        </ul>
                    </div>
                </div>
            </div>
        </section>

        {{-- 4 ─ CONTROLS --}}
        <section>
            <h2 class="text-[22px] font-semibold tracking-[-0.01em] text-foreground">Controls</h2>
            <div class="card mt-6 space-y-6 p-6">
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
            <h2 class="text-[22px] font-semibold tracking-[-0.01em] text-foreground">Status badges</h2>
            <p class="mt-2 max-w-[65ch] text-[15px] text-muted-foreground">
                Rendered straight from the PHP enums. If a class is renamed on one side and
                not the other, an unstyled pill appears here immediately — which is the whole
                point of putting them on one page.
            </p>
            <div class="card mt-6 divide-y divide-border">
                @foreach ([
                    'Broodcock status' => BroodcockStatus::cases(),
                    'Class' => BroodcockClass::cases(),
                    'Health record type' => HealthRecordType::cases(),
                    'Performance event' => PerformanceEventType::cases(),
                    'Performance result' => PerformanceResult::cases(),
                ] as $group => $cases)
                    <div class="flex flex-wrap items-center gap-3 p-4">
                        <span class="datum w-40 shrink-0 text-[12px] text-muted-foreground">{{ $group }}</span>
                        @foreach ($cases as $case)
                            <span class="badge {{ $case->badgeClasses() }}">{{ $case->label() }}</span>
                        @endforeach
                    </div>
                @endforeach
            </div>
        </section>

        {{-- 6 ─ COMPOSITES --}}
        <section>
            <h2 class="text-[22px] font-semibold tracking-[-0.01em] text-foreground">Patterns</h2>

            <div class="card mt-6 overflow-hidden">
                {{-- .table-hairline is a DESCENDANT selector (.table-hairline tbody tr+tr),
                     so it belongs on the table. It was on the tbody here, which meant the
                     reference gallery's own table had no row rules - the one table in the
                     app that exists to show what a table should look like. --}}
                <table class="table-hairline min-w-full">
                    <thead class="bg-muted">
                        <tr>
                            @foreach (['Band', 'Name', 'Bloodline', 'Weight', 'Status'] as $h)
                                <th class="px-3 py-2.5 text-left text-[11px] font-medium uppercase tracking-[0.06em] text-muted-foreground">{{ $h }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ([['Sweater','SW-4001','Haring Agila','2.85'],['Hatch','HA-3120','Bantay','3.07'],['Kelso','KE-2088','Tandang','2.64']] as [$bl,$bn,$nm,$wt])
                            <tr>
                                <td class="px-3 py-2.5"><x-band-tag :bloodline="$bl" :band="$bn" size="xs" /></td>
                                <td class="px-3 py-2.5 text-[15px] text-foreground">{{ $nm }}</td>
                                <td class="px-3 py-2.5 text-[15px] text-muted-foreground">{{ $bl }}</td>
                                <td class="datum px-3 py-2.5 text-[15px] text-foreground">{{ $wt }} kg</td>
                                <td class="px-3 py-2.5"><span class="badge badge-ok">Active</span></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-4 grid gap-4 lg:grid-cols-2">
                <div class="card p-10 text-center">
                    <p class="text-[15px] font-medium text-foreground">No health records yet</p>
                    <p class="mt-1 text-[13px] text-muted-foreground">Add the first vaccination or checkup.</p>
                    <button type="button" class="btn-primary mt-5">Add health record</button>
                </div>
                <div class="card p-4">
                    <div class="rounded-[4px] bg-success-bg px-4 py-3 text-[15px] text-success">Vaccination record saved.</div>
                    <div class="mt-3 rounded-[4px] bg-destructive-bg px-4 py-3 text-[15px] text-destructive">
                        A death has already been recorded for this bird.
                    </div>
                    <div class="mt-3 space-y-2">
                        <div class="h-3 w-3/4 animate-pulse rounded-[2px] bg-muted"></div>
                        <div class="h-3 w-1/2 animate-pulse rounded-[2px] bg-muted"></div>
                    </div>
                </div>
            </div>
        </section>

        {{-- 7 ─ RATIONALE --}}
        <section>
            <h2 class="text-[22px] font-semibold tracking-[-0.01em] text-foreground">Why it looks like this</h2>
            <div class="card mt-6 space-y-4 p-6 text-[15px] leading-relaxed text-muted-foreground">
                <p><strong class="text-foreground">A field ledger, not an app.</strong> Dark ink, pale paper,
                    hairline rules, no shadows and no gradients. The Console is used one-handed
                    outdoors in Philippine daylight, so contrast is a legibility requirement.</p>
                <p><strong class="text-foreground">Colour is spent once.</strong> A gamefowl's identity is not
                    a row id — it is a numbered ring on its leg. The six band colours are the
                    colours poultry leg bands are actually sold in, so the encoding is the
                    physical object. Everything else is ink and rule.</p>
                <p><strong class="text-foreground">Registry data is monospaced.</strong> Proportional digits
                    do not align in a column, and a weight column that does not align is the
                    fastest way to look amateur.</p>
                <p><strong class="text-foreground">Nothing depends on colour alone.</strong> Every band tag
                    carries a two-letter code and its bloodline name, so the encoding survives
                    colour-vision deficiency and a photocopied report.</p>
            </div>
        </section>
    </div>
</x-layouts::app>
