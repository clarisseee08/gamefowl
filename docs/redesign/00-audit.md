# 00 — UI audit (read-only inventory)

Audited 2026-09-14 on branch `redesign/ui-system`. Facts only, no proposals.
Every claim carries `file:line`. Nothing in the application was edited.

> **READ FIRST.** `.claude/skills/gamefowl-design-system/SKILL.md` is declared the
> binding design law by `CLAUDE.md`, and **almost every token name in it is
> stale**. It documents the superseded "field ledger" direction; `app.css` and
> `config/gfms-brand.php` implement a different, later one. This is not a
> documentation nit — an agent following the skill in good faith shipped **7 dead
> utility classes into `livewire/appointments/index.blade.php` today**. Full table
> in §4.1; the live bug is offender #1 in §8.

---

## 1. Stack and styling approach

| Thing | Reality | Evidence |
|---|---|---|
| Framework | Laravel 12, PHP 8.2 | `composer.json` |
| UI layer | Livewire 4, class-based components | `config/livewire.php:97` `class_namespace = App\Livewire` |
| Templating | Blade. 56 `.blade.php` files | `resources/views/**` |
| CSS | Tailwind v4, CSS-first. No `tailwind.config.js`, no PostCSS | `resources/css/app.css:8` |
| Content set | **Explicitly scoped**, auto-detection disabled | `app.css:8` `source(none)`, `app.css:38-40` three `@source` lines |
| Tokens | `@theme { }` block, lines 66–230 | `app.css:66` |
| Colour truth | `config/gfms-brand.php` — mirrored into `@theme`, asserted by `tests/Unit/BrandTokensAreMirroredTest.php` | `app.css:51-54` |
| Build | Vite 7 + `@tailwindcss/vite` | `package.json` |
| Bundle on disk | `public/build/assets/app-CG9T0-Qd.css` (54.3 KB) | committed; `tests/Unit/PublicAssetsAreCommittedTest.php` |

**Fonts — three faces, not the two the skill names.** `app.css:16-36` loads
**Poppins** (600/700, headings only), **Inter** (400/500/600 + latin-ext),
**JetBrains Mono** (400/500/600, via `.datum`). Declared at `app.css:76-78`.
Poppins is bound to `h1–h4` at `app.css:266-271` and is stated to be the *only*
place `--font-display` appears.
`@fontsource/fira-sans` and `@fontsource/fira-code` are **still installed**
(`package.json` dependencies) but **no longer imported by `app.css`** — dead
dependencies.

**View composition.** Three layouts (§3), consumed via `<x-layouts::app>` /
`<x-layouts::catalog>` / `<x-layouts::guest>`. Livewire components that set no
layout inherit `layouts::app` from `config/livewire.php:47`. Three components
choose their shell at runtime from the viewer's role via
`App\Livewire\Concerns\ChoosesShellByViewer` (`app/Livewire/Concerns/ChoosesShellByViewer.php:25-30`);
that file warns at lines 18-21 that `#[Layout]` silently overrides `render()`,
so the two must not be combined.

---

## 2. Route inventory

32 application routes in `routes/web.php`, plus the Fortify-registered auth paths
(`/login`, `/logout`, `/forgot-password`, `/reset-password`,
`/user/confirm-password` — see the header comment at `routes/web.php:28-31`;
self-registration is disabled).

`Route::livewire()` is the idiom (`routes/web.php:38`), so `php artisan
route:list` does not show most of these. Layout column: *default* = inherited
from `config/livewire.php:47`.

### Public (`active` middleware only)

| URL | Component | View | Layout | Traffic |
|---|---|---|---|---|
| `/` `web.php:56` | `Landing\Index` | `livewire/landing/index.blade.php` | `layouts::catalog` (`Landing/Index.php:56`) | **highest — new today** |
| `/catalog` `web.php:83` | `Catalog\Index` | `livewire/catalog/index.blade.php` (13 lines; nests `catalog.browse`) | viewer-chosen (`Catalog/Index.php:53`) | **highest** |
| `/broodcocks/{broodcock}` `web.php:242` | `Broodcocks\Show` | `livewire/broodcocks/show.blade.php` | viewer-chosen (`Show.php:96`) | **highest** |
| `/broodcocks/{broodcock}/pedigree` `web.php:241` | `Broodcocks\Pedigree` | `livewire/broodcocks/pedigree.blade.php` | viewer-chosen (`Pedigree.php:171`) | high — stated selling point (`web.php:235-238`) |
| `/photos/{photo}` `web.php:94` | `BroodcockPhotoController` | — (streams a private bucket object) | — | high |

The last two bird routes are **declared last on purpose** (`web.php:225-233`):
moving them above the auth group makes `/broodcocks/create` resolve to a bird
whose id is the string "create".

### Behind `auth` + `active`

| URL | Component | View | Layout | Traffic |
|---|---|---|---|---|
| `/dashboard` `web.php:98` | `DashboardController` | `dashboard.blade.php` → nests `livewire/dashboard/overview.blade.php` | `layouts::app` | high |
| `/broodcocks` `web.php:102` | `Broodcocks\Index` | `livewire/broodcocks/index.blade.php` (416 lines — largest view) | default | **highest** |
| `/broodcocks/create` `web.php:103` · `/{}/edit` `web.php:104` | `Broodcocks\Form` | `livewire/broodcocks/form.blade.php` | default | **highest** |
| `/health` `web.php:109` | `Health\Index` | `livewire/health/index.blade.php` | default | med |
| `/health/schedule` `web.php:110` | `Health\Schedule` | `livewire/health/schedule.blade.php` | default | med |
| `/health/create` `web.php:111` · `/{}/edit` `web.php:112` | `Health\Form` | `livewire/health/form.blade.php` | default | med |
| `/performance` `web.php:117` | `Performance\Index` | `livewire/performance/index.blade.php` (384) | default | med |
| `/performance/create` `web.php:118` · `/{}/edit` `web.php:119` | `Performance\Form` | `livewire/performance/form.blade.php` | default | med |
| `/breeding` `web.php:124` | `Breeding\Index` | `livewire/breeding/index.blade.php` | default | med |
| `/breeding/create` `web.php:125` · `/{}/edit` `web.php:126` | `Breeding\Form` | `livewire/breeding/form.blade.php` | default | med |
| `/breeding/{record}` `web.php:127` | `Breeding\Show` | `livewire/breeding/show.blade.php` | default | med |
| `/mortality` `web.php:132` | `Mortality\Index` | `livewire/mortality/index.blade.php` (338) | default | low |
| `/mortality/create/{broodcock?}` `web.php:135` | `Mortality\Form` | `livewire/mortality/form.blade.php` | default | low |
| `/reports` `web.php:146` | `Reports\Index` | `livewire/reports/index.blade.php` | default | med |
| `/reports/{report}/csv` `web.php:147` · `/pdf` `web.php:148` | `ReportController` | `reports/pdf/*.blade.php` | `reports/pdf/_layout.blade.php` | med — **dompdf, outside the design system** |
| `/appointments` `web.php:159` | `Appointments\Index` | `livewire/appointments/index.blade.php` | `layouts::app` explicit (`Index.php:109`) | **new today — carries offender #1** |
| `/users` `web.php:168` · `/create` `:169` · `/{}/edit` `:170` | `Users\Index`, `Users\Form` | `livewire/users/{index,form}.blade.php` | default | low — owner only |
| `/profile` `web.php:196` | `Profile\Edit` | `livewire/profile/edit.blade.php` | default | low |
| `/design` `web.php:204` | `DesignGalleryController` | `design/gallery.blade.php` (321) | `layouts::app` | **the component reference** |
| `/diagnostics` `web.php:217` | `DiagnosticsController` | `diagnostics.blade.php` | `layouts::app` | low — owner only |

**Pens have no screens, deliberately** — `web.php:174-189` records the removal
and its consequence. Do not add them back during a reskin.

### Nested Livewire components (no route of their own)

`Appointments\RequestForm` → `livewire/appointments/request-form.blade.php`,
mounted at `livewire/landing/index.blade.php:107` · `Catalog\Browse` (mounted at
`landing/index.blade.php:86` and by `/catalog`) · `Dashboard\Overview` ·
`Health\BroodcockHealthHistory` · `Performance\BroodcockTimeline` ·
`Photos\Gallery` · `Photos\Upload`.

---

## 3. Shared components and layouts

### `resources/views/layouts/` (3)

| File | Purpose | Consumers |
|---|---|---|
| `app.blade.php` (105) | Console shell. Fixed-height body, `<main>` is the **only** scroll container (`app.blade.php:13-15`, `:78`). Sidebar + off-canvas sheet + topbar + command palette. | every internal screen |
| `catalog.blade.php` (185) | Public shell. Slim sticky header, normal page scroll, conditional footer (`:92-100`). | `/`, `/catalog`, public bird pages |
| `guest.blade.php` (71) | Auth split-panel. Brand panel + form panel. | 4 `auth/*` views |

### `resources/views/components/` (10)

| Component | Purpose | Views consuming |
|---|---|---|
| `band-tag.blade.php` | **The signature.** Bloodline capsule; colour is an inline hex from PHP (`:47`) because bloodline is free text; unbanded is an honest state, never a dash (`:56-64`). | **12** |
| `brand-mark.blade.php` | Farm badge `<img>`; picks the smallest generated PNG ≥ 2× render size (`:24-26`). | 4 |
| `brand-lockup.blade.php` | Mark + wordmark, `sm`/`md`/`lg`, `brand`/`onDark` tone. | 1 (`guest.blade.php:32`) |
| `partials/head-meta.blade.php` | Shared `<head>`: title, OG, Twitter, favicon set. | 3 (all layouts) |
| `app-sidebar.blade.php` (218) | Console rail; nav built server-side from role (`:15-60`), collapsible, icons as inline path data (`:11-13`). | 1 |
| `app-topbar.blade.php` (100) | 56px bar inside the content column; route-derived breadcrumbs (`:4-11`); command-palette triggers. | 1 |
| `command-palette.blade.php` (151) | Ctrl/⌘-K palette. | 1 |
| `photo-thumb.blade.php` | Photo with `?size=thumb` and a placeholder fallback (`:22-28`). | 3 |
| `sparkline.blade.php` (109) | Inline trend SVG. | 1 |
| `icon/user.blade.php` | The **only** icon component. | 1 |

---

## 4. Palette

**46 `--color-*` tokens**, all in `app.css:66-230`, all mirrored in
`config/gfms-brand.php`. Naming is shadcn-style semantic pairs (`app.css:56-59`).

### Neutrals / ink / paper / rule
`--color-background` `#fbfbfa` (`:83`) · `--color-card` `#ffffff` (`:84`) ·
`--color-muted` `#f4f5f3` (`:85`) · `--color-popover` `#ffffff` (`:86`) ·
`--color-border` `#e4e6e2` (`:87`) · `--color-input` `#dfe2dd` (`:88`) ·
`--color-foreground` `#161c19` (`:90`) · `--color-muted-foreground` `#4e5550`
(`:91`, 7.40:1 — the Console floor) · `--color-card-foreground`,
`--color-popover-foreground` `#161c19` (`:92-93`).

### Primary — the interactive colour (deep red)
`-50 #faf0ef` · `-100 #f1dad8` · `-200 #e0b4b0` · `-400 #b5453f` ·
`--color-primary #8b2626` · `-600 #74201f` · `-700 #5c1a19` · `-900 #331010` ·
`-foreground #ffffff` (`app.css:100-108`). Buttons, links, active nav, focus
ring. Red holds this role because it is 8.74:1 on card and the green is 6.07:1
(`config/gfms-brand.php:86-88`).

### Brand — chrome only (green)
`--color-brand #486c2f` (`:113`) · `-deep #243619` · `-deeper #1a2711` ·
`-foreground #edf3ea` · `-muted-fg #bfcfbb` (`:114-117`). Sidebar, mark, 3px top
rail, auth panel, PDF chrome. Never interactive — enforced by
`DesignSystemGuardTest::test_brand_red_is_never_used_on_an_interactive_element:197`.

### Semantic (five pairs)
`success #1f7a4d` / `success-bg #e8f4ee` · `warning #755006` / `warning-bg
#f1e5a1` · `destructive #d32f2f` / `destructive-bg #fef5f4` /
`destructive-foreground #ffffff` · `info #3c4a8a` / `info-bg #eceef8`
(`app.css:155-163`). `accent #ef6905` / `accent-foreground #161c19` (`:167-168`)
— **fill only**, 3.14:1 on white.

### Band / bloodline slots — the only chroma that means bloodline
`--color-band-ember #e8552e` · `band-amber #f2a413` · `band-jade #1f9e6b` ·
`band-cobalt #1d5fd0` · `band-plum #8e44ad` · `band-rose #d6336c`
(`app.css:173-178`). Foregrounds `band-fg-light #ffffff` / `band-fg-dark
#10201b` (`:183-184`), resolved per band in `App\Support\BandTag::foreground()`.
Curated bloodline→slot map at `config/gfms-brand.php:198-205`; anything else
falls to a `crc32` hash (`app/Support/BandTag.php:71-80`).

### Print-only
`print-rule #e4e6e2` · `print-rule-strong #c7cbc5` · `print-zebra #f7f8f6`
(`app.css:187-189`).

### 4.1 SKILL.md ↔ `app.css` token drift — **complete list**

Every left-hand name produces **no CSS and no warning**. This is the audit's
most important table.

| SKILL.md says | Line | `app.css` actually defines | Status |
|---|---|---|---|
| `bg-parchment` | SKILL.md:33 | `bg-background` (`app.css:83`) | **dead** |
| `bg-canvas` | :34 | `bg-card` (`:84`) | **dead** |
| `bg-pearl` | :35 | `bg-muted` (`:85`) | **dead — shipped, see §8 #1** |
| `text-ink` | :36 | `text-foreground` (`:90`) | **dead** |
| `text-ink-80` | :37 | `text-muted-foreground` (`:91`) | **dead — shipped, see §8 #1** |
| `text-ink-48` | :38, :39-40 | no equivalent; the two-step ink scale collapsed to one (`docs/redesign-progress.md:51-53`) | **dead** |
| `border-hairline` | :41 | `border-border` (`:87`) | **dead** (already known) |
| `border-rule-strong` | :42 | `border-print-rule-strong` — print only (`:188`) | **dead** |
| `text-action` / `bg-action` `#16324f` | :43 | `text-primary` / `bg-primary` `#8b2626` (`:104`) | **dead + wrong hex** |
| `text-ok` / `bg-ok-wash` (+ `warn`/`alert`/`note`/`quiet`) | :45 | `text-success`/`bg-success-bg`, `warning`, `destructive`, `info` (`:155-163`) | **dead** |
| band slots `crimson` `forest` `slate` | :47-48 | `band-ember` `band-jade` `band-rose` (`:173-178`) | **renamed** |
| band slots `cobalt` `amber` `plum` | :47-48 | exist, but prefixed `band-` | **dead as written** |
| `.frosted` in the vocabulary | :58 | **not defined anywhere**, and used nowhere | **phantom** |
| Fira Sans / Fira Code | :73, :89 | Poppins + Inter + JetBrains Mono (`:76-78`) | **superseded** |
| "no shadows" / "elevation is a hairline" | CLAUDE.md, SKILL.md:10 | `--shadow-e1/e2/e3` (`:197-199`), `.card` has `box-shadow` (`:387`), `.card-interactive` lifts (`:396-399`) | **reversed on purpose** (`app.css:380-384`) |
| "five PHP enums return `badgeClasses()`" | :77-79 | **six** — `AppointmentStatus` added today (`app/Enums/AppointmentStatus.php:45`) | **stale count** |

Two more docs carry the same stale direction: `docs/design-brief.md` — which
SKILL.md:12 cites as the full rationale — still lists `--color-paper`,
`--color-ink`, `--color-action #16324F` and the six old band hexes
(`design-brief.md:105-155`) **and asserts at `:6-8` that "where it and the code
disagree, the code is wrong."** That statement is now false.
`docs/redesign-progress.md:1-12` is the document that records the actual current
direction.

Correct names *do* survive: `.badge-info`/`.badge-neutral` (SKILL.md:64-68),
the `.table-hairline` descendant rule (SKILL.md:70-71), the band-tag contract
(SKILL.md:93-107), `.datum` (SKILL.md:82-87) and the two-surface table
(SKILL.md:111-118) are all accurate.

---

## 5. Logo and brand assets — **FROZEN, do not touch**

| Asset | Path |
|---|---|
| Source artwork (1080px, 1.9 MB, never served) | `resources/brand/logo-source.png` |
| Generated marks | `public/images/brand/logo-{32,64,128,192,512}.png` |
| Favicon | `public/favicon.ico` |
| Apple touch icon | `public/apple-touch-icon.png` |

Every reference: `components/brand-mark.blade.php:30` (the `asset()` call) ·
`components/partials/head-meta.blade.php:33` (ico), `:34` (192 png), `:35`
(apple-touch), `:36` (og:image), `:37` (`theme-color` from
`gfms-brand.brand_deep`) · `components/brand-lockup.blade.php:30` ·
`components/app-sidebar.blade.php:109` · `layouts/catalog.blade.php:38` and
`:107` · `layouts/guest.blade.php:32` and `:36`.

Names come from `config/gfms.system.*` and `config/gfms.farm.*`; nothing is
written literally (`head-meta.blade.php:8`).
`tests/Unit/PublicAssetsAreCommittedTest.php` guards the generated files.

---

## 6. Existing motion

**44 sites: 27 lines in Blade views, 17 rules in `app.css`.**

### The token set (`app.css:201-229`)
Four durations — `--dur-instant: 100ms` (`:216`), `--dur-fast: 150ms` (`:217`),
`--dur-base: 200ms` (`:218`), `--dur-slow: 300ms` (`:219`). Three easings —
`--ease-out cubic-bezier(0.16, 1, 0.3, 1)` (`:221`), `--ease-in
cubic-bezier(0.4, 0, 1, 1)` (`:222`), `--ease-in-out cubic-bezier(0.4, 0, 0.2,
1)` (`:223`). Legacy aliases `--ease-out-quart` → `--ease-in-out` and
`--ease-enter` → `--ease-out` (`:228-229`), `--t-fast`/`--t-base`/`--t-enter`
(`:238-240`), all marked "must not be used in new work."

`app.css:202-203` states the rule outright: *"Nothing outside it may be used, and
a raw duration or easing anywhere in a view is a defect."*

### Rules in `app.css`
| Line | What | Duration / easing | Property |
|---|---|---|---|
| 346-350 | `@utility btn` | `--t-fast` (150ms) `--ease-out-quart` | bg, border, color, **box-shadow**, transform |
| 356 | `.btn:active` | instant | `transform: scale(0.98)` |
| 369-370 | `.input` | 150ms | **border-color**, **box-shadow** |
| 392-394 | `.card-interactive` | `--t-base` (200ms) | **box-shadow**, **border-color**, transform |
| 396-399 | `.card-interactive:hover` | — | `translateY(-1px)` + `--shadow-e2` |
| 448 | `.row-hover` | 150ms | **background-color** |
| 455 | `.row-actions` | 150ms | opacity |
| 460-468 | `.skeleton` + `@keyframes gfms-pulse` | **1.6s infinite** | opacity |
| 475-479 | `.enter-pop` + `@keyframes gfms-pop` | `--t-enter` (200ms) `--ease-enter` | opacity + translateY(4px) |
| 494-495 | `.nav-item` | 150ms | **background-color**, **color** |
| 310-316, 323-327 | two `prefers-reduced-motion` blocks | 0.01ms blanket kill + `animation-iteration-count: 1` | — |
| 594-596 | `.meter` — a deliberate **absence** of width animation | — | — |

### Hits in Blade views
| File:line | Declares | Note |
|---|---|---|
| `components/app-sidebar.blade.php:78` | `transition-[width] duration-200` | **animates `width` — layout property** |
| `components/app-sidebar.blade.php:180` | `transition-colors` | |
| `components/command-palette.blade.php:100-103` | `transition-opacity ease-out duration-200` / `ease-in duration-150` | scrim |
| `components/command-palette.blade.php:109-111` | `transition ease-out duration-260` | **260ms is off-scale** |
| `layouts/app.blade.php:48-51` | `transition-opacity ease-out duration-200` / `ease-in duration-150` | scrim |
| `layouts/app.blade.php:56-59` | `transition ease-out duration-260` / `ease-in duration-150` | **260ms off-scale**; translate-x |
| `livewire/broodcocks/form.blade.php:293` | `transition-colors` | |
| `livewire/broodcocks/pedigree.blade.php:79` | `transition` (bare) | |
| `livewire/broodcocks/show.blade.php:95` | `transition` (bare) | tab underline |
| `livewire/performance/form.blade.php:124` | `transition-colors` | |
| `livewire/photos/gallery.blade.php:73` | `transition-opacity duration-150` | |
| `livewire/photos/gallery.blade.php:156` | `x-transition.opacity` | |
| `livewire/photos/upload.blade.php:64` | `transition-colors duration-150` | |
| `livewire/photos/upload.blade.php:107` | `transition-all duration-150` on a `:style="width: …%"` | **animates `width`** — directly contradicts `app.css:594-596` |
| `livewire/users/form.blade.php:69` | `transition-colors` | |
| `livewire/users/index.blade.php:139` | `transition-colors duration-100` | |

**Animating something other than `transform`/`opacity`:** `app-sidebar:78`
(width), `photos/upload:107` (width, via `transition-all`), and every
`transition-colors` / `.row-hover` / `.nav-item` / `.input` / `.btn` rule
(background-color, border-color, color, box-shadow). Colour and shadow are cheap;
the two **width** animations are the genuine layout-thrash cases.

**Every `duration-*` and `ease-*` in a view is a raw value**, not a token —
12 view lines violate `app.css:202-203` as written.

---

## 7. The CSS class contract — restyle, never rename

### Classes returned by a PHP enum `badgeClasses()`
Six enums, ~22 call sites.

| Enum | Returns |
|---|---|
| `AppointmentStatus` `:48-51` | `badge badge-info`, `badge badge-ok`, `badge badge-alert`, `badge badge-neutral` |
| `BroodcockClass` `:38-40` | `badge-ok`, `badge-info`, `badge-neutral` |
| `BroodcockStatus` `:74-79` | `badge-ok`, `badge-info`, `badge-neutral`, `badge-warn`, `badge-alert` |
| `HealthRecordType` `:41-45` | `badge-ok`, `badge-info`, `badge-neutral`, `badge-warn` |
| `PerformanceEventType` `:40-43` | `badge-info`, `badge-ok`, `badge-neutral`, `badge-warn` |
| `PerformanceResult` `:33-36` | `badge-ok`, `badge-alert`, `badge-warn`, `badge-neutral` |

**Gap:** `BadgeVocabularyTest::enums()` (`tests/Unit/BadgeVocabularyTest.php:31-40`)
covers only **five** — `AppointmentStatus` is not in the data provider. Its
badges are currently unguarded.

### Enforced by `tests/Unit/BadgeVocabularyTest.php`
- `:43` every class an enum emits must exist in `app.css`.
- `:65` no enum may emit a `band-` class (colour means bloodline, never status).
- `:83` every class in a Blade view matching a closed-vocabulary prefix must
  exist. Prefixes, `:91`: `badge-`, `btn-`, `input-`, `band-tag`, `ped-`,
  `table-hairline`, `datum`.
  **`bg-` / `text-` / `border-` colour tokens are NOT in that list — which is
  exactly the hole `bg-pearl` and `text-ink-80` fell through.**
- PDF templates are excluded (`:126-128`).

### Enforced by `tests/Unit/DesignSystemGuardTest.php` (9 checks)
`:72` no stock Tailwind palette class in a view · `:91` none in the compiled
bundle (skips when absent) · `:123` no hardcoded hex outside
`config/gfms-brand.php` and `app.css` · `:162` PDF templates carry no hardcoded
colour · `:197` brand green never on `btn-primary`/`btn-secondary`/`btn-danger`/
`btn-quiet`/`input`/`badge` · `:240` no console view re-introduces a centred
max-width column (`max-w-7xl…4xl`, `container mx-auto`, `mx-auto` + px/rem
width; `ch` widths allowed) · `:303` every view balances its `<div>`s · `:348`
an `sr-only` file input needs a positioned wrapper · `:402` no `x-data`
expression contains a double quote.

### The full vocabulary `app.css` defines (41 names)
`.btn` (`@utility`, `:342`) `.btn-primary` `.btn-secondary` `.btn-danger`
`.btn-quiet` (`:358-361`) · `.input` `.input-error` `.label` `.help` `.error`
(`:365-378`) · `.card` (`:385`) `.card-interactive` (`:391`) `.elev-1/2/3`
(`:401-403`) · `.badge` `.badge-ok` `.badge-warn` `.badge-alert` `.badge-info`
`.badge-neutral` (`:410-418`) · `.band-tag` `.band-code` `.band-number`
`.band-tag-none` (`:426-443`) · `.datum` (`@utility`, `:335`) ·
`.table-hairline` (`:445` — **descendant selector `tbody tr + tr`; goes on
`<table>`**) · `.row-hover` `.row-actions` (`:448-457`) · `.skeleton` (`:460`) ·
`.popover` `.enter-pop` (`:471-475`) · `.nav-item` `.nav-item-active`
`.nav-section` (`:491-509`) · `.scroll-slim` `.scroll-slim-dark` (`:529`,
`:555`) · `.brand-rail` (`:579`) · `.page-title-marked` (`:583`) · `.meter`
(`:593`) · `.ped-branch` `.ped-node` `.ped-empty` (`:605-638`).

Names defined but **absent from SKILL.md**: `.card-interactive`, `.elev-1/2/3`,
`.row-hover`, `.row-actions`, `.skeleton`, `.popover`, `.enter-pop`,
`.nav-item*`, `.nav-section`, `.scroll-slim*`, `.brand-rail`,
`.page-title-marked`, `.meter`, `.band-code`, `.band-number`, `.btn`.

---

## 8. The 10 worst offenders

**1. Seven dead utility classes in the new appointments queue.**
`livewire/appointments/index.blade.php:43` `bg-pearl`; `:45 :46 :47 :48 :49 :50`
`text-ink-80`. Neither token exists (§4.1); confirmed absent from
`public/build/assets/app-CG9T0-Qd.css`. The table head renders with **no ground
and no colour rule**, so it does not read as a head at all. The file's own header
comment at `:4` promises "7:1 contrast". Direct consequence of the stale
SKILL.md. Nothing in the suite catches it.

**2. `.table-hairline` on a `<tbody>`, where it matches nothing.**
`livewire/users/index.blade.php:137`. The rule is
`.table-hairline tbody tr + tr` (`app.css:445`) — it needs a `tbody`
*descendant*, so on the `tbody` itself it selects nothing and the users table
ships with **no row separators**. SKILL.md:70-71 calls this out by name. The
three correct usages are `mortality/index:234`, `performance/index:195`,
`appointments/index:42`.

**3. `docs/design-brief.md` claims primacy while being a direction out of date.**
`design-brief.md:6-8`: *"It is the source of truth; where it and the code
disagree, the code is wrong."* Its palette (`:105-155`) is the field-ledger set
— `--color-paper`, `--color-ink`, `--color-action #16324F`, bands `crimson`
`forest` `slate`. SKILL.md:12 sends every agent to it. Anyone obeying it writes
offender #1 again.

**4. Radius tokens exist and are mostly ignored.** `--radius-sm: 6px`,
`--radius-md: 10px`, `--radius-lg: 14px` (`app.css:191-193`). Views use
`rounded-[var(--radius-*)]` 20 times and hardcode `rounded-[4px]` / `[3px]` /
`[2px]` / `[6px]` / `[1px]` **40 times across 17 files** — and `4px`, the most
common, is not one of the three steps. Worst concentrations:
`photos/upload.blade.php` (6), `performance/broodcock-timeline.blade.php` (4),
`mortality/index.blade.php` (5), `performance/index.blade.php` (3).

**5. No type scale in the token layer.** `@theme` defines fonts, colour, radius,
shadow, duration and easing — **no `--text-*`**. Every size is an arbitrary
value, and 21 distinct ones are in use: `text-[15px]` ×153, `[11px]` ×103,
`[13px]` ×84, `[14px]` ×50, `[12px]` ×46, `[17px]` ×42, `[21px]` ×24, `[22px]`
×19, `[32px]` ×18, `[34px]` ×14, `[18px]` ×13, `[19px]` ×10, `[26px]` ×7,
`[24px]` ×5, `[28px]` ×4, `[44px]` ×2, `[30px]` ×2, `[16px]` ×2, `[56px]`,
`[40px]`, `[10px]`. 21px/22px and 18px/19px are neighbours that no system needs.

**6. Stale colour names in comments, describing a swap that already happened.**
`app.css:482` "The console sidebar, on the deep **comb red**" — the sidebar is
`bg-brand-deep` `#243619`, a dark **green**. `app.css:487` "brand **red**".
`layouts/guest.blade.php:17` "the only place **comb red** is allowed";
`guest.blade.php:18` "every control on it stays **peacock**";
`DesignSystemGuardTest.php:195` "**Peacock** stays the interactive colour" — and
the test method itself is named `test_brand_red_is_never_used_on_an_interactive_element:197`.
Brand is green, primary is red, and no comment says so.

**7. Two `width` animations, one of which the stylesheet explicitly forbids.**
`livewire/photos/upload.blade.php:107` — `transition-all duration-150` on an
element whose `:style` binds `width`. `app.css:594-596` states the rule for
exactly this case: *"No width transition. Animating width is layout thrash."*
Also `components/app-sidebar.blade.php:78` `transition-[width] duration-200`.

**8. 38 inline `<svg>` blocks, 1 icon component.** Only
`components/icon/user.blade.php` exists. The search glyph is duplicated verbatim
three times (`app-topbar.blade.php:83-85`, `:96-98`,
`command-palette.blade.php:116-117`). Six SVGs each in
`performance/index.blade.php`, `performance/broodcock-timeline.blade.php`,
`broodcocks/index.blade.php`; four in `photos/upload.blade.php` and
`app-sidebar.blade.php`. Nine more nav icons live as raw path strings in a PHP
array at `app-sidebar.blade.php:15-46`.

**9. Type below the 11px floor.** `components/photo-thumb.blade.php:37` uses
`text-[10px]`. SKILL.md:127 forbids anything under 11px, and this component is
consumed by 3 views. (Clean elsewhere: **zero** `font-bold`, **zero**
`backdrop-blur`, **zero** gradients, **zero** stock palette classes.)

**10. Shipped comments that describe code that is no longer there.**
`livewire/landing/index.blade.php:92-94` — "The form **arrives here in the next
step**. Until it does, this section carries the farm's own contact details" —
the form is mounted 15 lines below at `:107`.
`components/partials/head-meta.blade.php:31` — "The **SVG** is the mark itself,
so it stays sharp at any density" — there is no SVG favicon; the set is
ICO + PNG (`:33-35`).
`broodcocks/pedigree.blade.php:91-94` explains an `ink-80`/`ink-48` choice using
tokens that no longer exist.
`livewire/users/index.blade.php:26` describes a "**pearl** ground" on an element
classed `bg-muted`.

### Also noted, below the top ten
- `components/brand-mark.blade.php:37` sets `style="width:…;height:…"`
  duplicating the `width`/`height` attributes two lines above.
- `app-topbar.blade.php:17-28` `$sectionLabels` has no key for `appointments` or
  `profile`; the sidebar calls the same section "Visits"
  (`app-sidebar.blade.php:35`). Latent only — `$actionLabels` has no `index`
  entry (`:30-36`), so no breadcrumb renders on that screen.
- `@fontsource/fira-sans` and `@fontsource/fira-code` are installed but no longer
  imported — dead dependencies in `package.json`.
- `tests/Unit/DesignSystemGuardTest.php:91` (stock palette in the bundle) only
  runs when `public/build/assets/*.css` exists. It does today.
