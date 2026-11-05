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

        @include('design.ems')

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
                <div class="d-card d-card-border bg-base-100 elev-1 card-interactive p-5">
                    <p class="text-[14px] font-medium text-foreground">Interactive card</p>
                    <p class="mt-1 text-[13px] text-muted-foreground">
                        Hover me — lifts elev-1 → elev-2 over 200ms and rises 1px.
                    </p>
                </div>
                <div class="d-card d-card-border bg-base-100 elev-1 p-5">
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
                    <div class="d-card d-card-border bg-base-100 elev-1 p-4">
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
                    <div class="d-card d-card-border bg-base-100 elev-1 p-4">
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
                        <p class="datum px-2 py-1 text-[11px] text-muted-foreground">{{ $step === 'primary' ? '500' : str_replace('primary_', '', $step) }}</p>
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

            <div class="d-card d-card-border bg-base-100 elev-1 mt-5 divide-y divide-border">
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

            <div class="d-card d-card-border bg-base-100 elev-1 mt-3 p-4">
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

            <div class="d-card d-card-border bg-base-100 elev-1 mt-5 space-y-8 p-6">
                {{-- Buttons: d-btn at the default size is 45px tall - the console
                     touch floor - so no size modifier appears on a primary action. --}}
                <div>
                    <h3 class="text-[11px] font-semibold uppercase tracking-[0.07em] text-muted-foreground">Buttons</h3>
                    <div class="mt-3 flex flex-wrap items-center gap-3">
                        <button type="button" class="d-btn d-btn-primary">Save record</button>
                        <button type="button" class="d-btn d-btn-outline">Cancel</button>
                        <button type="button" class="d-btn d-btn-error">Delete</button>
                        <button type="button" class="d-btn d-btn-ghost">Clear filters</button>
                        <button type="button" class="d-btn d-btn-primary" disabled>Disabled</button>
                        <button type="button" class="d-btn d-btn-primary" data-loading>
                            <span class="d-loading d-loading-spinner d-loading-sm" aria-hidden="true"></span>
                            Saving
                        </button>
                    </div>
                </div>

                {{-- Fields: legend above, help (d-label) or error below. --}}
                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <label for="g-a" class="d-fieldset-legend">Band Number</label>
                        <input id="g-a" type="text" class="d-input w-full" placeholder="e.g. SW-1024" aria-describedby="g-a-help">
                        <p id="g-a-help" class="d-label mt-1">Leave empty if the bird has not been banded yet.</p>
                    </div>
                    <div>
                        <label for="g-b" class="d-fieldset-legend">Weight (kg)</label>
                        <input id="g-b" type="text" class="d-input d-input-error datum w-full" value="-2" aria-invalid="true" aria-describedby="g-b-error">
                        <p id="g-b-error" class="mt-1.5 text-[13px] font-medium text-error">Weight cannot be a negative number.</p>
                    </div>
                    <div>
                        <label for="g-c" class="d-fieldset-legend">Bloodline</label>
                        <select id="g-c" class="d-select w-full">
                            <option>Sweater</option>
                            <option>Hatch</option>
                            <option>Kelso</option>
                        </select>
                        <p class="d-label mt-1">A native select, for the rare field that needs no search.</p>
                    </div>
                    <div>
                        <label for="g-d" class="d-fieldset-legend">Sex</label>
                        <select id="g-d" class="d-select d-select-error w-full" aria-invalid="true" aria-describedby="g-d-error">
                            <option value="">Choose one</option>
                            <option>Cock</option>
                            <option>Hen</option>
                        </select>
                        <p id="g-d-error" class="mt-1.5 text-[13px] font-medium text-error">Choose the bird's sex.</p>
                    </div>
                    <div class="sm:col-span-2">
                        <label for="g-e" class="d-fieldset-legend">Notes</label>
                        <textarea id="g-e" rows="3" class="d-textarea w-full" placeholder="Temperament, markings, anything a buyer should know"></textarea>
                        <p class="d-label mt-1">Shown to staff only.</p>
                    </div>
                </div>

                {{-- Choices: checkbox, toggle, radio. Each sits in a 44px row so
                     the whole line is the target, not the 24px control. --}}
                <div class="grid gap-6 sm:grid-cols-3">
                    <div>
                        <h3 class="text-[11px] font-semibold uppercase tracking-[0.07em] text-muted-foreground">Checkbox</h3>
                        <label class="mt-2 flex min-h-11 cursor-pointer items-center gap-3 text-[15px] text-foreground">
                            <input type="checkbox" class="d-checkbox d-checkbox-primary" checked>
                            Listed for sale
                        </label>
                        <label class="flex min-h-11 cursor-pointer items-center gap-3 text-[15px] text-foreground">
                            <input type="checkbox" class="d-checkbox d-checkbox-primary">
                            Show outside birds
                        </label>
                    </div>
                    <div>
                        <h3 class="text-[11px] font-semibold uppercase tracking-[0.07em] text-muted-foreground">Toggle</h3>
                        <label class="mt-2 flex min-h-11 cursor-pointer items-center gap-3 text-[15px] text-foreground">
                            <input type="checkbox" class="d-toggle d-toggle-primary" checked>
                            Email me overdue vaccinations
                        </label>
                        <label class="flex min-h-11 cursor-not-allowed items-center gap-3 text-[15px] text-muted-foreground">
                            <input type="checkbox" class="d-toggle d-toggle-primary" disabled>
                            Disabled
                        </label>
                    </div>
                    <fieldset>
                        <legend class="text-[11px] font-semibold uppercase tracking-[0.07em] text-muted-foreground">Radio</legend>
                        <label class="mt-2 flex min-h-11 cursor-pointer items-center gap-3 text-[15px] text-foreground">
                            <input type="radio" name="g-sex" class="d-radio d-radio-primary" checked>
                            Cock
                        </label>
                        <label class="flex min-h-11 cursor-pointer items-center gap-3 text-[15px] text-foreground">
                            <input type="radio" name="g-sex" class="d-radio d-radio-primary">
                            Hen
                        </label>
                    </fieldset>
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
            <div class="d-card d-card-border bg-base-100 elev-1 mt-5 divide-y divide-border">
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
                            <span class="d-badge d-badge-soft {{ $case->badgeClasses() }}">{{ $case->label() }}</span>
                        @endforeach
                    </div>
                @endforeach

                {{-- The five colours the enums may choose from, written out in
                     full so each class is a literal Tailwind can see. --}}
                <div class="flex flex-wrap items-center gap-2.5 p-3.5">
                    <span class="datum w-40 shrink-0 text-[11px] text-muted-foreground">d-badge-soft</span>
                    <span class="d-badge d-badge-soft d-badge-success">success</span>
                    <span class="d-badge d-badge-soft d-badge-warning">warning</span>
                    <span class="d-badge d-badge-soft d-badge-error">error</span>
                    <span class="d-badge d-badge-soft d-badge-info">info</span>
                    <span class="d-badge d-badge-soft d-badge-neutral">neutral</span>
                </div>
            </div>
        </section>

        {{-- 6 ─ PATTERNS --}}
        <section>
            <h2 class="text-[19px] font-semibold text-foreground">Patterns</h2>

            <div class="d-card d-card-border bg-base-100 elev-1 mt-5 overflow-x-auto">
                <table class="d-table min-w-full [&_td]:border-border [&_th]:border-border">
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
                                <td class="px-3 py-2.5"><span class="d-badge d-badge-soft d-badge-success">Active</span></td>
                                <td class="px-3 py-2.5 text-right">
                                    {{-- Revealed on hover. A permanent column of three buttons
                                         per row is the clearest tell of a scaffolded CRUD table. --}}
                                    <span class="row-actions inline-flex gap-1">
                                        <button type="button" class="d-btn d-btn-ghost d-btn-sm">Edit</button>
                                        <button type="button" class="d-btn d-btn-ghost d-btn-sm text-error">Delete</button>
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-4 grid gap-4 lg:grid-cols-3">
                {{-- A stat tile: d-stat, with title and desc reset to the console
                     ink (daisyUI draws both at 60%, under 7:1) and the value at
                     semibold rather than daisyUI's 800. --}}
                <div class="d-card d-card-border bg-base-100 elev-1">
                    <div class="d-stat p-5">
                        <p class="d-stat-title whitespace-normal text-[11px] font-semibold uppercase tracking-[0.07em] text-muted-foreground">Vaccination compliance</p>
                        <p class="d-stat-value datum mt-1.5 text-[28px] font-semibold leading-none text-foreground">81.3%</p>
                        <progress class="d-progress d-progress-success mt-3 h-1.5" value="81.3" max="100" aria-label="Vaccination compliance">81.3%</progress>
                        <p class="d-stat-desc mt-2 whitespace-normal text-[12px] text-muted-foreground">A bar reads faster than a number alone.</p>
                    </div>
                </div>

                <div class="d-card d-card-border bg-base-100 elev-1 items-center p-10 text-center">
                    <svg class="mx-auto h-7 w-7 text-muted-foreground" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 0 1-2.247 2.118H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z"/>
                    </svg>
                    <p class="mt-3 text-[14px] font-medium text-foreground">No health records yet</p>
                    <p class="mt-1 text-[13px] text-muted-foreground">Add the first vaccination or checkup.</p>
                    <button type="button" class="d-btn d-btn-primary mt-4">Add health record</button>
                </div>

                <div class="d-card d-card-border bg-base-100 elev-1 p-5">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.07em] text-muted-foreground">Skeleton</p>
                    <div class="mt-3 space-y-2.5">
                        <div class="d-skeleton h-3 w-3/4"></div>
                        <div class="d-skeleton h-3 w-1/2"></div>
                        <div class="d-skeleton h-3 w-2/3"></div>
                    </div>
                    <p class="mt-3 text-[12px] text-muted-foreground">
                        Matches the real layout, so the page does not reflow when data lands.
                    </p>
                </div>
            </div>
        </section>

        {{-- 6b ─ THE daisyUI COMPONENTS IN USE. Every one is prefixed d- (the
             unprefixed names are this stylesheet's own vocabulary) and each is
             drawn in the state a screen actually puts it in. --}}
        <section>
            <h2 class="text-[19px] font-semibold text-foreground">Components</h2>
            <p class="mt-1.5 max-w-[68ch] text-[14px] text-muted-foreground">
                daisyUI 5, prefixed <code class="datum text-[13px]">d-</code>, with its colour names
                pointed at the tokens above. Nothing here carries a colour of its own.
            </p>

            {{-- Alerts: the x-alert component, which renders d-alert d-alert-soft. --}}
            <h3 class="mt-6 text-[11px] font-semibold uppercase tracking-[0.07em] text-muted-foreground">Alert</h3>
            <div class="mt-3 grid gap-3 lg:grid-cols-2">
                <x-alert variant="info" title="Pedigree incomplete">Three birds have no sire recorded.</x-alert>
                <x-alert variant="success" title="Broodcock saved">The bird is now on the farm register.</x-alert>
                <x-alert variant="warning" title="Vaccination due soon">Two birds are due within the week.</x-alert>
                <x-alert variant="danger" title="Upload failed">The photo was larger than the upload limit.</x-alert>
            </div>

            <div class="mt-6 grid gap-4 lg:grid-cols-2">
                {{-- Card: body, title, actions. --}}
                <div class="d-card d-card-border bg-base-100 elev-1">
                    <div class="d-card-body">
                        <h3 class="d-card-title font-display text-[17px] text-foreground">Card</h3>
                        <p class="text-[15px] leading-relaxed text-muted-foreground">
                            d-card with d-card-body, d-card-title and d-card-actions. The border and
                            elev-1 make it a surface; the padding is the body's.
                        </p>
                        <div class="d-card-actions mt-2 justify-end">
                            <button type="button" class="d-btn d-btn-outline">Cancel</button>
                            <button type="button" class="d-btn d-btn-primary">Save record</button>
                        </div>
                    </div>
                </div>

                {{-- Stats: side-by-side figures share one d-stats group. --}}
                <div class="d-card d-card-border bg-base-100 elev-1 overflow-x-auto">
                    <div class="d-stats d-stats-vertical sm:d-stats-horizontal w-full">
                        <div class="d-stat">
                            <p class="d-stat-title text-[13px] font-semibold text-muted-foreground">Birds on the farm</p>
                            <p class="d-stat-value datum font-display font-semibold text-foreground">142</p>
                            <p class="d-stat-desc text-[13px] text-muted-foreground"><span class="datum">12</span> breeding</p>
                        </div>
                        <div class="d-stat">
                            <p class="d-stat-title text-[13px] font-semibold text-muted-foreground">Fertility</p>
                            <p class="d-stat-value datum font-display font-semibold text-foreground">81.3%</p>
                            <p class="d-stat-desc text-[13px] text-muted-foreground">Last <span class="datum">6</span> months</p>
                        </div>
                        <div class="d-stat">
                            <p class="d-stat-title text-[13px] font-semibold text-muted-foreground">Overdue</p>
                            <p class="d-stat-value datum font-display font-semibold text-error">3</p>
                            <p class="d-stat-desc text-[13px] text-muted-foreground">Needs attention</p>
                        </div>
                    </div>
                </div>

                {{-- Tabs: border for page sections, box for a segmented filter. --}}
                <div class="d-card d-card-border bg-base-100 elev-1 p-5">
                    <h3 class="text-[11px] font-semibold uppercase tracking-[0.07em] text-muted-foreground">Tabs</h3>
                    <div role="tablist" aria-label="Bird record" class="d-tabs d-tabs-border mt-3">
                        <button type="button" role="tab" aria-selected="true" class="d-tab d-tab-active">Details</button>
                        <button type="button" role="tab" aria-selected="false" class="d-tab">Health</button>
                        <button type="button" role="tab" aria-selected="false" class="d-tab">Performance</button>
                    </div>
                    <div role="tablist" aria-label="Account status" class="d-tabs d-tabs-box mt-5 w-fit">
                        <button type="button" role="tab" aria-selected="true" class="d-tab d-tab-active gap-2">
                            Everyone <span class="d-badge d-badge-sm datum">24</span>
                        </button>
                        <button type="button" role="tab" aria-selected="false" class="d-tab gap-2">
                            Active <span class="d-badge d-badge-sm datum">21</span>
                        </button>
                        <button type="button" role="tab" aria-selected="false" class="d-tab gap-2">
                            Deactivated <span class="d-badge d-badge-sm datum">3</span>
                        </button>
                    </div>
                </div>

                {{-- Menu: the sidebar and dropdown vocabulary. --}}
                <div class="d-card d-card-border bg-base-100 elev-1 p-5">
                    <h3 class="text-[11px] font-semibold uppercase tracking-[0.07em] text-muted-foreground">Menu</h3>
                    <ul class="d-menu mt-3 w-full rounded-box border border-base-300 bg-base-100 text-[15px]">
                        <li class="d-menu-title">Records</li>
                        <li><button type="button" class="d-menu-active" aria-current="page">Broodcocks</button></li>
                        <li><button type="button">Breeding</button></li>
                        <li><button type="button">Health</button></li>
                        <li class="d-menu-disabled"><button type="button" disabled>Disabled</button></li>
                    </ul>
                </div>

                {{-- Navigation: breadcrumbs and join pagination. --}}
                <div class="d-card d-card-border bg-base-100 elev-1 p-5">
                    <h3 class="text-[11px] font-semibold uppercase tracking-[0.07em] text-muted-foreground">Breadcrumbs</h3>
                    <div class="d-breadcrumbs mt-2 text-sm">
                        <ul>
                            <li><a href="{{ route('broodcocks.index') }}">Broodcocks</a></li>
                            <li><span aria-current="page">Tanikala</span></li>
                        </ul>
                    </div>

                    <h3 class="mt-5 text-[11px] font-semibold uppercase tracking-[0.07em] text-muted-foreground">Pagination</h3>
                    <nav class="mt-3" aria-label="Pagination">
                        <div class="d-join">
                            <button type="button" class="d-join-item d-btn" aria-label="Previous page">&lsaquo;</button>
                            <button type="button" class="d-join-item d-btn datum">1</button>
                            <button type="button" class="d-join-item d-btn d-btn-active datum" aria-current="page">2</button>
                            <button type="button" class="d-join-item d-btn datum">3</button>
                            <button type="button" class="d-join-item d-btn" aria-label="Next page">&rsaquo;</button>
                        </div>
                    </nav>
                </div>

                {{-- Progress and loading. Every tone class is a literal, so the
                     production build keeps it. --}}
                <div class="d-card d-card-border bg-base-100 elev-1 p-5">
                    <h3 class="text-[11px] font-semibold uppercase tracking-[0.07em] text-muted-foreground">Progress</h3>
                    <div class="mt-3 space-y-3">
                        @foreach ([
                            ['d-progress-primary', 64, 'Pedigree recorded'],
                            ['d-progress-success', 92, 'Vaccinated on time'],
                            ['d-progress-warning', 71, 'Hatch rate'],
                            ['d-progress-error', 18, 'Mortality this year'],
                        ] as [$tone, $value, $label])
                            <div>
                                <div class="flex items-baseline justify-between text-[13px]">
                                    <span class="text-foreground">{{ $label }}</span>
                                    <span class="datum text-muted-foreground">{{ $value }}%</span>
                                </div>
                                <progress class="d-progress {{ $tone }} mt-1 h-1.5" value="{{ $value }}" max="100" aria-label="{{ $label }}">{{ $value }}%</progress>
                            </div>
                        @endforeach
                    </div>

                    <h3 class="mt-5 text-[11px] font-semibold uppercase tracking-[0.07em] text-muted-foreground">Loading</h3>
                    <div class="mt-3 flex items-center gap-4 text-foreground" role="status" aria-label="Loading">
                        <span class="d-loading d-loading-spinner d-loading-xs"></span>
                        <span class="d-loading d-loading-spinner d-loading-sm"></span>
                        <span class="d-loading d-loading-spinner d-loading-md"></span>
                        <span class="d-loading d-loading-dots d-loading-md"></span>
                    </div>
                </div>

                {{-- Avatar, kbd, tooltip. --}}
                <div class="d-card d-card-border bg-base-100 elev-1 p-5">
                    <h3 class="text-[11px] font-semibold uppercase tracking-[0.07em] text-muted-foreground">Avatar</h3>
                    <div class="mt-3 flex items-center gap-3">
                        <div class="d-avatar d-avatar-placeholder">
                            <div class="w-9 rounded-full bg-base-300 text-foreground">
                                <span class="text-[13px] font-semibold">SG</span>
                            </div>
                        </div>
                        <div class="d-avatar d-avatar-placeholder">
                            <div class="w-11 rounded-full bg-base-300 text-foreground">
                                <span class="text-[15px] font-semibold">SG</span>
                            </div>
                        </div>
                        <div class="d-avatar d-avatar-placeholder">
                            <div class="w-14 rounded-full bg-neutral text-neutral-content">
                                <span class="text-[17px] font-semibold">SG</span>
                            </div>
                        </div>
                    </div>

                    <h3 class="mt-5 text-[11px] font-semibold uppercase tracking-[0.07em] text-muted-foreground">Kbd</h3>
                    <p class="mt-2 flex items-center gap-1.5 text-[14px] text-muted-foreground">
                        <kbd class="d-kbd d-kbd-sm">Ctrl</kbd>
                        <span aria-hidden="true">+</span>
                        <kbd class="d-kbd d-kbd-sm">K</kbd>
                        <span class="ml-1">opens the command palette</span>
                    </p>

                    <h3 class="mt-5 text-[11px] font-semibold uppercase tracking-[0.07em] text-muted-foreground">Tooltip</h3>
                    <div class="mt-3">
                        <div class="d-tooltip" data-tip="The number on the leg ring">
                            <button type="button" class="d-btn d-btn-outline">Band number</button>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Modal. Drawn static inside a bordered frame so it does not cover
                 the page. No d-modal-open: daisyUI locks the root scroll while
                 one exists. Only the box, with its closed-state opacity and
                 scale undone. On a screen the box sits in d-modal d-modal-open. --}}
            <h3 class="mt-7 text-[11px] font-semibold uppercase tracking-[0.07em] text-muted-foreground">Modal</h3>
            <div class="mt-3 grid place-items-center rounded-box border border-border bg-foreground/40 px-4 py-10">
                <div class="d-modal-box elev-3 w-full max-w-md scale-100 opacity-100">
                    <h4 class="font-display text-[22px] font-semibold leading-[1.2] tracking-[-0.015em] text-foreground">
                        Deactivate Ana Reyes?
                    </h4>
                    <p class="mt-3 text-[15px] leading-relaxed text-muted-foreground">
                        She will be signed out immediately. Everything she has recorded is kept.
                    </p>
                    <div class="d-modal-action flex-col-reverse gap-3 sm:flex-row">
                        <button type="button" class="d-btn d-btn-outline">Cancel</button>
                        <button type="button" class="d-btn d-btn-error">Yes, deactivate Ana</button>
                    </div>
                </div>
            </div>
        </section>

        {{-- 7 ─ RATIONALE --}}
        {{-- ---------------------------------------------------------------
             THE PRIMITIVES.

             These eight were built as a layer and then referenced by nothing -
             not one view, and not this gallery either, which is the page
             CLAUDE.md describes as "every component in every state on one page".
             A primitive nobody can see is indistinguishable from one that does
             not work, and the next person to need a progress bar writes a
             ninth one.

             So they are exercised here rather than deleted. The alternative was
             removing a deliberate layer because the reference page had not
             caught up with it.
        --------------------------------------------------------------- --}}
        <section>
            <h2 class="text-[19px] font-semibold text-foreground">Primitives</h2>
            <p class="mt-2 max-w-[68ch] text-[14px] leading-relaxed text-muted-foreground">
                Available to any view. Each one already resolves its own tokens, contrast
                and ARIA, so reaching for it is cheaper than rebuilding it.
            </p>

            <div class="mt-5 grid gap-4 lg:grid-cols-2">
                <x-info-card title="Info card" description="A titled panel for content that is not a single figure.">
                    <p class="text-[15px] leading-relaxed text-muted-foreground">
                        The container this row of examples is sitting in.
                    </p>
                </x-info-card>

                <x-info-card title="Avatar" description="A photo when there is one, initials when there is not.">
                    <div class="flex items-center gap-4">
                        <x-avatar name="Salvador Guadalupe" size="sm" />
                        <x-avatar name="Salvador Guadalupe" size="md" />
                        <x-avatar name="Salvador Guadalupe" size="lg" />
                    </div>
                </x-info-card>

                <x-info-card title="Progress" description="A proportion, with the figure stated in text beside it.">
                    <div class="space-y-4">
                        <x-progress :value="14" label="Pedigree recorded" />
                        <x-progress :value="81" label="Fertility rate" />
                    </div>
                </x-info-card>

                <x-info-card title="Switch" description="A setting that applies immediately, with no Save.">
                    <x-switch id="design-switch" label="Only show birds that are for sale"
                              help="Applies as soon as it is flipped." />
                </x-info-card>

                <x-info-card title="Breadcrumb" description="Where this page sits. The last entry is the current page.">
                    <x-breadcrumb :items="[
                        ['label' => 'Broodcocks', 'href' => route('broodcocks.index')],
                        ['label' => 'Tanikala', 'href' => '#'],
                    ]" />
                </x-info-card>

                <x-info-card title="Separator" description="A hairline rule - elevation here is a rule, never a shadow.">
                    <div class="flex items-center gap-4 text-[14px] text-muted-foreground">
                        <span>Before</span>
                        <x-separator orientation="vertical" class="h-5" />
                        <span>After</span>
                    </div>
                    <x-separator class="mt-4" />
                </x-info-card>
            </div>

            <h3 class="mt-7 text-[11px] font-semibold uppercase tracking-[0.07em] text-muted-foreground">
                Sort control
            </h3>
            <div class="d-card d-card-border bg-base-100 elev-1 mt-3 overflow-x-auto">
                <table class="d-table w-full text-left">
                    <thead class="bg-muted">
                        <tr>
                            <x-sort-control field="band_number" label="Band Number" current="band_number" direction="asc" />
                            <x-sort-control field="name" label="Name" current="band_number" />
                            <x-sort-control field="weight" label="Weight" current="band_number" align="right" />
                        </tr>
                    </thead>
                    <tbody class="bg-card">
                        <tr>
                            <td class="datum px-4 py-3 text-[14px] text-foreground">KL-4003</td>
                            <td class="px-4 py-3 text-[14px] text-foreground">Tanikala</td>
                            <td class="datum px-4 py-3 text-right text-[14px] text-foreground">2.10 kg</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <h3 class="mt-7 text-[11px] font-semibold uppercase tracking-[0.07em] text-muted-foreground">
                Empty state
            </h3>
            <div class="d-card d-card-border bg-base-100 elev-1 mt-3">
                <x-empty-state title="No deaths have been recorded yet"
                               description="That is worth noting. If a bird has died, click Record a Death." />
            </div>
        </section>

        <section>
            <h2 class="text-[19px] font-semibold text-foreground">Why it looks like this</h2>
            <div class="d-card d-card-border bg-base-100 elev-1 mt-5 space-y-3.5 p-6 text-[14px] leading-relaxed text-muted-foreground">
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
