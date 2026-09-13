# 00 — UI audit (read-only inventory)

Audited 2026-09-14, branch `redesign/ui-system`. Facts only, no proposals.
`file:line` on every claim. No application file was edited.

> **READ FIRST.** `.claude/skills/gamefowl-design-system/SKILL.md` is declared the
> binding design law by `CLAUDE.md`, and **nearly every token name in it is
> stale** — it documents the superseded "field ledger" direction while `app.css`
> and `config/gfms-brand.php` implement a later one. An agent following the skill
> in good faith shipped **7 dead utility classes into the appointments queue
> today**. Drift table in §4.1; the live bug is offender #1 in §8.
>
> **Snapshot warning.** `resources/css/app.css` was **being rewritten while this
> audit ran** — 643 → 846 lines mid-pass (+217/−14 vs HEAD). Every `app.css`
> citation below is re-anchored to the **846-line state**, verified stable
> (identical md5 across two samples). If it has moved again, re-anchor by
> selector name rather than trusting these numbers.

---

## 1. Stack and styling approach

| Thing | Reality | Evidence |
|---|---|---|
| Framework / UI | Laravel 12, PHP 8.2; Livewire 4 class-based components | `composer.json`, `config/livewire.php:97` |
| Templating | Blade, 56 `.blade.php` files | `resources/views/**` |
| CSS | Tailwind v4 CSS-first. No `tailwind.config.js`, no PostCSS. Auto-detection **disabled**; three explicit `@source` lines | `app.css:8`, `:38-40` |
| Tokens | `@theme { }` | `app.css:66-232` |
| Colour truth | `config/gfms-brand.php`, mirrored into `@theme`; `tests/Unit/BrandTokensAreMirroredTest.php` fails on drift | `app.css:51-54` |
| Build | Vite 7 + `@tailwindcss/vite`; bundle `public/build/assets/app-CG9T0-Qd.css` (54.3 KB) committed | `package.json`, `tests/Unit/PublicAssetsAreCommittedTest.php` |

**Fonts — three faces, not the two the skill names.** `app.css:16-36` loads
**Poppins** 600/700 (headings only), **Inter** 400/500/600 + latin-ext (body/UI),
**JetBrains Mono** 400/500/600 (via `.datum`). Declared `app.css:76-78`; Poppins
bound to `h1–h4` at `app.css:267-268` and stated to be the only use of
`--font-display`. `@fontsource/fira-sans` and `@fontsource/fira-code` remain in
`package.json` but are **no longer imported** — dead packages.

**View composition.** Three layouts (§3), used as `<x-layouts::app>` /
`<x-layouts::catalog>` / `<x-layouts::guest>`. Livewire components setting no
layout inherit `layouts::app` from `config/livewire.php:47`. Three pick their
shell at runtime from the viewer's role via `ChoosesShellByViewer`
(`app/Livewire/Concerns/ChoosesShellByViewer.php:25-30`); its lines 18-21 warn
that `#[Layout]` silently overrides `render()`, so the two must not be combined.

---

## 2. Route inventory

32 application routes in `routes/web.php`, plus Fortify's auth paths — `/login`,
`/logout`, `/forgot-password`, `/reset-password`, `/user/confirm-password`
(`web.php:28-31`; self-registration disabled), which render `auth/*.blade.php` in
`layouts::guest`. `Route::livewire()` is the idiom (`web.php:38`), so
`route:list` shows almost none of these. *default* layout = inherited from
`config/livewire.php:47` (`layouts::app`).

### Public (`active` middleware only)

| URL | Component | View | Layout | Traffic |
|---|---|---|---|---|
| `/` `:56` | `Landing\Index` | `livewire/landing/index.blade.php` | `layouts::catalog` (`Landing/Index.php:56`) | **highest — new today** |
| `/catalog` `:83` | `Catalog\Index` | `livewire/catalog/index.blade.php` (13 lines, nests `catalog.browse`) | viewer-chosen (`Catalog/Index.php:53`) | **highest** |
| `/broodcocks/{broodcock}` `:242` | `Broodcocks\Show` | `livewire/broodcocks/show.blade.php` | viewer-chosen (`Show.php:96`) | **highest** |
| `/broodcocks/{broodcock}/pedigree` `:241` | `Broodcocks\Pedigree` | `livewire/broodcocks/pedigree.blade.php` | viewer-chosen (`Pedigree.php:171`) | high — the stated selling point (`web.php:235-238`) |
| `/photos/{photo}` `:94` | `BroodcockPhotoController` | — streams a private bucket object | — | high |

The two bird routes are **declared last deliberately** (`web.php:225-233`):
moved above the auth group, `/broodcocks/create` resolves as a bird with id
`"create"`.

### Behind `auth` + `active`

| URL(s) | Component | View | Layout | Traffic |
|---|---|---|---|---|
| `/dashboard` `:98` | `DashboardController` | `dashboard.blade.php` → nests `livewire/dashboard/overview.blade.php` | `layouts::app` | high |
| `/broodcocks` `:102` | `Broodcocks\Index` | `livewire/broodcocks/index.blade.php` (416 — largest view) | default | **highest** |
| `/broodcocks/create` `:103`, `/{}/edit` `:104` | `Broodcocks\Form` | `livewire/broodcocks/form.blade.php` | default | **highest** |
| `/health` `:109`, `/schedule` `:110`, `/create` `:111`, `/{}/edit` `:112` | `Health\{Index,Schedule,Form}` | `livewire/health/*.blade.php` | default | med |
| `/performance` `:117`, `/create` `:118`, `/{}/edit` `:119` | `Performance\{Index,Form}` | `livewire/performance/*.blade.php` | default | med |
| `/breeding` `:124`, `/create` `:125`, `/{}/edit` `:126`, `/{record}` `:127` | `Breeding\{Index,Form,Show}` | `livewire/breeding/*.blade.php` | default | med |
| `/mortality` `:132`, `/create/{broodcock?}` `:135` | `Mortality\{Index,Form}` | `livewire/mortality/*.blade.php` | default | low |
| `/reports` `:146` | `Reports\Index` | `livewire/reports/index.blade.php` | default | med |
| `/reports/{report}/csv` `:147`, `/pdf` `:148` | `ReportController` | `reports/pdf/*.blade.php` | `reports/pdf/_layout.blade.php` | med — **dompdf, outside the design system** |
| `/appointments` `:159` | `Appointments\Index` | `livewire/appointments/index.blade.php` | `layouts::app` explicit (`Index.php:109`) | **new today — carries offender #1** |
| `/users` `:168`, `/create` `:169`, `/{}/edit` `:170` | `Users\{Index,Form}` | `livewire/users/*.blade.php` | default | low — owner only |
| `/profile` `:196` | `Profile\Edit` | `livewire/profile/edit.blade.php` | default | low |
| `/design` `:204` | `DesignGalleryController` | `design/gallery.blade.php` (321) | `layouts::app` | **the component reference** |
| `/diagnostics` `:217` | `DiagnosticsController` | `diagnostics.blade.php` | `layouts::app` | low — owner only |

**Pens have no screens, deliberately** — `web.php:174-189` records the removal
and its consequence. Do not restore them during a reskin.
**Nested components, no route of their own:** `Appointments\RequestForm` (mounted
`landing/index.blade.php:107`) · `Catalog\Browse` (`landing/index.blade.php:86`
and `/catalog`) · `Dashboard\Overview` · `Health\BroodcockHealthHistory` ·
`Performance\BroodcockTimeline` · `Photos\Gallery` · `Photos\Upload`.

---

## 3. Shared components and layouts

### `resources/views/layouts/` (3)
| File | Purpose | Consumers |
|---|---|---|
| `app.blade.php` (105) | Console shell. Fixed-height body; `<main>` is the **only** scroll container (`:13-15`, `:78`). Sidebar + off-canvas sheet + topbar + palette. | every internal screen |
| `catalog.blade.php` (185) | Public shell. Slim sticky header, normal page scroll, all-conditional footer (`:92-100`). | `/`, `/catalog`, public bird pages |
| `guest.blade.php` (71) | Auth split-panel: brand panel + form panel. | 4 `auth/*` views |

### `resources/views/components/` (10)
| Component | Purpose | Views using |
|---|---|---|
| `band-tag.blade.php` | **The signature.** Bloodline capsule; colour is an inline hex from PHP (`:47`) because bloodline is free text; unbanded is an honest state, never a dash (`:56-64`). | **12** |
| `brand-mark.blade.php` | Farm badge `<img>`; picks smallest generated PNG ≥ 2× render size (`:24-26`). | 4 |
| `partials/head-meta.blade.php` | Shared `<head>`: title, OG, Twitter, favicons. | 3 (all layouts) |
| `photo-thumb.blade.php` | Photo with `?size=thumb` + placeholder fallback (`:22-28`). | 3 |
| `app-sidebar.blade.php` (218) | Console rail; nav built server-side from role (`:15-60`); icons as inline path data (`:11-13`). | 1 |
| `app-topbar.blade.php` (100) | 56px bar inside the content column; route-derived breadcrumbs (`:4-11`); palette triggers. | 1 |
| `command-palette.blade.php` (151) | Ctrl/⌘-K palette. | 1 |
| `brand-lockup.blade.php` | Mark + wordmark; `sm`/`md`/`lg`, `brand`/`onDark`. | 1 |
| `sparkline.blade.php` (109) | Inline trend SVG. | 1 |
| `icon/user.blade.php` | The **only** icon component. | 1 |

---

## 4. Palette

**61 `--color-*` tokens** in `app.css:66-232`, shadcn-style semantic pairs
(`app.css:56-59`). The 46 originals are mirrored in `config/gfms-brand.php`; the
**15 new ramp steps arrived in this branch's in-progress edit** (see the snapshot
warning) and are not in that config.

| Group | Tokens | Role |
|---|---|---|
| **Neutrals** `:83-93` | `background #fbfbfa` · `card #ffffff` · `muted #f4f5f3` · `popover #ffffff` · `border #e4e6e2` · `input #dfe2dd` · `foreground #161c19` · `muted-foreground #4e5550` (7.40:1 — the Console floor) · `card-foreground`, `popover-foreground` `#161c19` | ink / paper / rule |
| **Ink ramp — NEW** `:110-120` | `ink-50 #f7f8f6` → `ink-950 #0d110f`, 10 steps | **not in `config/gfms-brand.php`** |
| **Primary** `:131-143` | `primary-50 #faf0ef` … `primary #8b2626` `:136` … `primary-950 #1e0909`, + `-foreground`. Steps 300/500/800/950 are new | **the interactive colour** — buttons, links, active nav, focus ring. Red not green because 8.74:1 vs 6.07:1 on card (`config/gfms-brand.php:86-88`) |
| **Brand** `:148-152` | `brand #486c2f` · `-deep #243619` · `-deeper #1a2711` · `-foreground #edf3ea` · `-muted-fg #bfcfbb` | **chrome only** — sidebar, mark, rail, auth panel, PDF. Never interactive; `DesignSystemGuardTest.php:197` |
| **Semantic** `:156-169` | `success #1f7a4d`/`success-bg #e8f4ee` · `warning #755006`/`warning-bg #f1e5a1` · `destructive #d32f2f`/`-bg #fef5f4`/`-foreground #ffffff` · `info #3c4a8a`/`info-bg #eceef8` · `accent #ef6905` (**fill only**, 3.14:1 on white) | status |
| **Band / bloodline** `:174-184` | `band-ember #e8552e` · `band-amber #f2a413` · `band-jade #1f9e6b` · `band-cobalt #1d5fd0` · `band-plum #8e44ad` · `band-rose #d6336c`; foregrounds `band-fg-light #ffffff` / `band-fg-dark #10201b` | **the only chroma that means bloodline** |
| **Print** `:188-190` | `print-rule #e4e6e2` · `print-rule-strong` · `print-zebra` | dompdf |

Band foreground is resolved per band by `App\Support\BandTag::foreground()`;
curated bloodline→slot map at `config/gfms-brand.php:198-205`, anything else
falls to a `crc32` hash (`app/Support/BandTag.php:71-80`).

### 4.1 SKILL.md ↔ `app.css` token drift — complete list

Every left-hand name compiles to **no CSS and no warning**.

| SKILL.md says | Line | `app.css` actually defines | Status |
|---|---|---|---|
| `bg-parchment` | 33 | `bg-background` `:83` | **dead** |
| `bg-canvas` | 34 | `bg-card` `:84` | **dead** |
| `bg-pearl` | 35 | `bg-muted` `:85` | **dead — shipped, §8 #1** |
| `text-ink` | 36 | `text-foreground` `:90` | **dead** |
| `text-ink-80` | 37 | `text-muted-foreground` `:91` | **dead — shipped, §8 #1** |
| `text-ink-48` | 38, 39-40 | none; the two-step ink scale collapsed (`docs/redesign-progress.md:51-53`) | **dead** |
| `border-hairline` | 41 | `border-border` `:87` | **dead** (already known) |
| `border-rule-strong` | 42 | `border-print-rule-strong` — print only | **dead** |
| `text-action`/`bg-action` `#16324f` | 43 | `text-primary`/`bg-primary` `#8b2626` `:136` | **dead + wrong hex** |
| `text-ok`/`bg-ok-wash`, `warn`, `alert`, `note`, `quiet` | 45 | `success`/`success-bg`, `warning`, `destructive`, `info` `:156-166` | **dead** |
| bands `crimson` `forest` `slate` | 47-48 | `band-ember` `band-jade` `band-rose` `:174-179` | **renamed** |
| bands `cobalt` `amber` `plum` | 47-48 | exist but prefixed `band-` | **dead as written** |
| `.frosted` in the vocabulary | 58 | **not defined, not used anywhere** | **phantom** |
| Fira Sans / Fira Code | 73, 89 | Poppins + Inter + JetBrains Mono `:76-78` | **superseded** |
| "no shadows; elevation is a hairline" | 10 + `CLAUDE.md` | `--shadow-e1/e2/e3` `:198-200`; `.card` has `box-shadow` `:406-409`; `.card-interactive` lifts `:412-421` | **reversed on purpose** (`app.css:401-405`) |
| "**five** PHP enums return `badgeClasses()`" | 77-79 | **six** — `AppointmentStatus` added today (`app/Enums/AppointmentStatus.php:45`) | **stale count** |

**Note:** `text-ink-80` is *not* rescued by the new ink ramp — that ramp is
`ink-50…ink-950` (`:110-120`) and `ink-80` is not a step in it. Verified absent
from the 61-token list and from the compiled bundle.

`docs/design-brief.md` — which SKILL.md:12 cites as the full rationale — carries
the same stale direction (`:105-155`) **and asserts at `:6-8` that "where it and
the code disagree, the code is wrong."** That is now false;
`docs/redesign-progress.md:1-12` records the actual current direction.

Accurate in SKILL.md and worth keeping: `.badge-info`/`.badge-neutral` (`:64-68`),
the `.table-hairline` descendant rule (`:70-71`), the band-tag contract
(`:93-107`), `.datum` (`:82-87`), the two-surface table (`:111-118`).

---

## 5. Logo and brand assets — FROZEN, do not touch

**Files.** `resources/brand/logo-source.png` (1080px, 1.9 MB, never served) ·
`public/images/brand/logo-{32,64,128,192,512}.png` (generated) ·
`public/favicon.ico` · `public/apple-touch-icon.png`.

**Every reference.** `brand-mark.blade.php:30` (the `asset()` call) ·
`partials/head-meta.blade.php:33` (ico), `:34` (192 png), `:35` (apple-touch),
`:36` (og:image), `:37` (`theme-color` from `gfms-brand.brand_deep`) ·
`brand-lockup.blade.php:30` · `app-sidebar.blade.php:109` ·
`layouts/catalog.blade.php:38`, `:107` · `layouts/guest.blade.php:32`, `:36`.

Names come from `config/gfms.system.*` / `config/gfms.farm.*`; nothing literal
(`head-meta.blade.php:8`). `tests/Unit/PublicAssetsAreCommittedTest.php` guards
the generated files.

---

## 6. Existing motion

### Token set (`app.css:202-230`)
Durations `--dur-instant: 100ms` `:217` · `--dur-fast: 150ms` · `--dur-base:
200ms` · `--dur-slow: 300ms` `:220`. Easings `--ease-out
cubic-bezier(0.16,1,0.3,1)` `:222` · `--ease-in cubic-bezier(0.4,0,1,1)` ·
`--ease-in-out cubic-bezier(0.4,0,0.2,1)` `:224`. Legacy aliases
`--ease-out-quart`→`--ease-in-out` `:229`, `--ease-enter`→`--ease-out`,
`--t-fast/-base/-enter` `:239-241`, all marked "must not be used in new work."
`:203-204` states: *"Nothing outside it may be used, and a raw duration or easing
anywhere in a view is a defect."*

### Rules in `app.css` — original set
`@utility btn` `:347-351` `--t-fast` on bg, border, color, **box-shadow**,
transform · `.btn:active` `:357` `scale(0.98)` · `.input` `:390-391` `--t-fast`
on **border-color**, **box-shadow** · `.card-interactive` `:413-415` `--t-base`
on **box-shadow**, **border-color**, transform, with `translateY(-1px)` +
`--shadow-e2` on hover `:417-421` · `.row-hover` `:469` `--t-fast`
**background-color** · `.row-actions` `:476` `--t-fast` opacity · `.skeleton` +
`@keyframes gfms-pulse` `:481-490` opacity, **1.6s infinite** · `.enter-pop` +
`@keyframes gfms-pop` `:496-501` `--t-enter`, opacity + translateY(4px) ·
`.nav-item` `:515-516` `--t-fast` **background-color**, **color**. Two
`prefers-reduced-motion` blocks `:311-317` and `:324-328` — 0.01ms blanket kill
plus `animation-iteration-count: 1`. `.meter` `:615-617` is a deliberate
**absence** of width animation.

### Rules added by the in-progress redesign (`app.css:666-846`)
Header `:666-686` states two governing rules — transform/opacity only, nothing
over 300ms — and names **three deliberate exceptions**. **None of these classes
is used by any view yet** (checked all 12; zero hits).
`.focus-ring` `:693-708` opacity `--dur-fast` · `.tooltip` `:715-729` opacity +
scale, 400ms enter delay / 0ms leave · `.toast-enter-active` `:741-746` /
`.toast-leave-active` `:747-752` translateY + opacity, `--dur-base` in,
`--dur-fast` out · `.modal-backdrop` `:759-763` and `.modal-panel` `:766-777`
opacity + `scale(0.97)` `--dur-slow` · `.drawer` `:781-786` `translateX`
`--dur-slow` · `.tab` `:791-794` colour `--dur-instant` · `.tab-indicator`
`:796-800` transform **+ width** — *exception 1* · `.accordion-content`
`:805-811` **grid-template-rows 0fr→1fr** — *exception 2* · `.empty-state`
`:816-821` no motion, by design · `.breadcrumb-link` `:831-834` and `.page-link`
`:839-844` colour/background `--dur-instant`.

`app.css:671` cites **`docs/redesign/01-system.md`** as the motion whitelist.
**That file does not exist** — `docs/redesign/` contains only this audit.

### Hits in Blade views (16 lines)
- `app-sidebar.blade.php:78` `transition-[width] duration-200` — **animates `width`**; `:180` `transition-colors`
- `command-palette.blade.php:100-103` `transition-opacity ease-out duration-200` / `ease-in duration-150`; `:109-111` `transition ease-out duration-260` — **260ms off-scale**
- `layouts/app.blade.php:48-51` same scrim pair; `:56-59` `ease-out duration-260` / `ease-in duration-150` — **off-scale**, translate-x
- `photos/upload.blade.php:107` `transition-all duration-150` on a `:style="width: …%"` — **animates `width`**; `:64` `transition-colors duration-150`
- `photos/gallery.blade.php:73` `transition-opacity duration-150`; `:156` `x-transition.opacity`
- `broodcocks/pedigree.blade.php:79` and `broodcocks/show.blade.php:95` bare `transition`
- `transition-colors` only: `broodcocks/form.blade.php:293`, `performance/form.blade.php:124`, `users/form.blade.php:69`, `users/index.blade.php:139` (`duration-100`)

`x-transition` (Alpine) appears only at `layouts/app.blade.php:48-59`,
`command-palette.blade.php:100-111`, `photos/gallery.blade.php:156` — the
off-canvas sheet, the palette, and the lightbox.

**Animating a property other than `transform`/`opacity`:** the two **width**
cases (`app-sidebar:78`, `photos/upload:107`) are the genuine layout-thrash
offenders. Colour/shadow transitions (`transition-colors`, `.row-hover`,
`.nav-item`, `.input`, `.btn`) are cheap but are still outside the
transform/opacity rule the new block declares at `:677-680`.
**Every `duration-*` / `ease-*` in a view is a raw value, not a token** — 12 view
lines violate `app.css:203-204` as written.

---

## 7. The CSS class contract — restyle, never rename

### Classes an enum's `badgeClasses()` returns — six enums, ~22 call sites
The whole emitted set is `badge`, `badge-ok`, `badge-warn`, `badge-alert`,
`badge-info`, `badge-neutral`. Sources: `AppointmentStatus:48-51` (the only one
that also emits the base `badge`), `BroodcockClass:38-40`,
`BroodcockStatus:74-79`, `HealthRecordType:41-45`, `PerformanceEventType:40-43`,
`PerformanceResult:33-36`.

**Gap:** `BadgeVocabularyTest::enums()` (`tests/Unit/BadgeVocabularyTest.php:31-40`)
lists only **five** — `AppointmentStatus` is absent, so its badges are unguarded.

### Enforced by `tests/Unit/BadgeVocabularyTest.php`
`:43` every class an enum emits must exist in `app.css` · `:65` no enum may emit
a `band-` class · `:83` every closed-vocabulary class in a view must exist, over
prefixes at `:91` — `badge-`, `btn-`, `input-`, `band-tag`, `ped-`,
`table-hairline`, `datum`. **`bg-` / `text-` / `border-` colour tokens are not in
that list — the exact hole `bg-pearl` and `text-ink-80` fell through.** PDF
templates excluded (`:126-128`).

### Enforced by `tests/Unit/DesignSystemGuardTest.php` (9 checks)
`:72` no stock Tailwind palette class in a view · `:91` none in the compiled
bundle (skips when absent; a bundle is present) · `:123` no hardcoded hex outside
`config/gfms-brand.php` and `app.css` · `:162` PDF templates carry no hardcoded
colour · `:197` brand green never on `btn-primary`/`-secondary`/`-danger`/
`-quiet`/`input`/`badge` · `:240` no console view re-introduces a centred
max-width column (`max-w-7xl…4xl`, `container mx-auto`, `mx-auto` + px/rem;
`ch` widths allowed) · `:303` every view balances its `<div>`s · `:348` an
`sr-only` file input needs a positioned wrapper · `:402` no `x-data` expression
contains a double quote.

### Vocabulary `app.css` defines
**Original (41):** `.btn` (`@utility` `:343`) `.btn-primary/-secondary/-danger/
-quiet` `:373-376` · `.input` `:383` `.input-error` `:394` `.label` `.help`
`.error` `:397-399` · `.card` `:406` `.card-interactive` `:412` `.elev-1/2/3`
`:422-424` · `.badge` `:431` `.badge-ok/-warn/-alert/-info/-neutral` `:435-439` ·
`.band-tag` `:447` `.band-code` `.band-number` `.band-tag-none` `:461` ·
`.datum` (`@utility` `:336`) · `.table-hairline` `:466` (**descendant selector
`tbody tr + tr` — goes on `<table>`**) · `.row-hover` `:469` `.row-actions`
`:476` · `.skeleton` `:481` · `.popover` `:492` `.enter-pop` `:496` ·
`.nav-item` `:512` `.nav-item-active` `:522` `.nav-section` `:528` ·
`.scroll-slim` `:550` `.scroll-slim-dark` `:576` · `.brand-rail` `:600` ·
`.page-title-marked` `:604` · `.meter` `:614` · `.ped-branch` `:626`
`.ped-node` `:641` `.ped-empty` `:659`.

**Added by the in-progress redesign (26), none yet used** — `:693-846`, listed
in §6.

Defined but **absent from SKILL.md**: all 26 of those, plus `.card-interactive`,
`.elev-1/2/3`, `.row-hover`, `.row-actions`, `.skeleton`, `.popover`,
`.enter-pop`, `.nav-item*`, `.nav-section`, `.scroll-slim*`, `.brand-rail`,
`.page-title-marked`, `.meter`, `.band-code`, `.band-number`, `.btn`.

---

## 8. The 10 worst offenders

**1. Seven dead utility classes in the new appointments queue.**
`livewire/appointments/index.blade.php:43` `bg-pearl`; `:45 :46 :47 :48 :49 :50`
`text-ink-80`. Neither token exists in the 61-token set; both confirmed absent
from `public/build/assets/app-CG9T0-Qd.css`. The table head renders with **no
ground and no colour rule** and does not read as a head, while the file's own
comment at `:4` promises "7:1 contrast". Direct consequence of the stale
SKILL.md, and nothing in the suite catches it (§7).

**2. `.table-hairline` on a `<tbody>`, where it matches nothing.**
`livewire/users/index.blade.php:137`. The rule is `.table-hairline tbody tr + tr`
(`app.css:466`) — it needs a `tbody` *descendant*, so on the `tbody` itself it
selects nothing and the users table ships with **no row separators**.
SKILL.md:70-71 calls this out by name. Correct usages: `mortality/index:234`,
`performance/index:195`, `appointments/index:42`, `design/gallery:240`.

**3. `docs/design-brief.md` claims primacy while being out of date.** `:6-8` —
*"It is the source of truth; where it and the code disagree, the code is wrong."*
Its palette (`:105-155`) is the field-ledger set: `--color-paper`, `--color-ink`,
`--color-action #16324F`, bands `crimson`/`forest`/`slate`. SKILL.md:12 sends
every agent to it. Anyone obeying it writes offender #1 again.

**4. `app.css:671` points at a whitelist that does not exist.** The new
primitives block says each rule "implements exactly one row of the Motion
whitelist in `docs/redesign/01-system.md`" and calls it "the contract".
`docs/redesign/` contains only this audit. 26 new classes are therefore governed
by a document nobody can read, and none is used by a view yet.

**5. Radius tokens exist and are mostly ignored.** `--radius-sm: 6px`,
`--radius-md: 10px`, `--radius-lg: 14px` (`app.css:192-194`). Views use
`rounded-[var(--radius-*)]` 20 times and hardcode `rounded-[4px]`/`[3px]`/`[2px]`
/`[6px]`/`[1px]` **40 times across 17 files** — and `4px`, by far the most
common, is not one of the three steps. Heaviest: `photos/upload.blade.php` (6),
`mortality/index.blade.php` (5), `performance/broodcock-timeline.blade.php` (4).

**6. No type scale in the token layer.** `@theme` defines fonts, colour, radius,
shadow, duration and easing — **no `--text-*`**. Every size is an arbitrary
value, and 21 distinct ones are in use: 15px ×153, 11px ×103, 13px ×84, 14px
×50, 12px ×46, 17px ×42, 21px ×24, 22px ×19, 32px ×18, 34px ×14, 18px ×13, 19px
×10, 26px ×7, 24px ×5, 28px ×4, 44px ×2, 30px ×2, 16px ×2, 56px, 40px, 10px.
21/22px and 18/19px are neighbours no system needs.

**7. Stale colour names in comments, describing a swap that already happened.**
`app.css:503` "The console sidebar, on the deep **comb red**" — the sidebar is
`bg-brand-deep` `#243619`, a dark **green**; `app.css:508` "brand **red**".
`layouts/guest.blade.php:17` "the only place **comb red** is allowed";
`guest.blade.php:18` "every control on it stays **peacock**";
`DesignSystemGuardTest.php:195` "**Peacock** stays the interactive colour", under
a method named `test_brand_red_is_never_used_on_an_interactive_element:197`.
Brand is green, primary is red, and no comment says so.

**8. Two `width` animations, one of which the stylesheet explicitly forbids.**
`livewire/photos/upload.blade.php:107` — `transition-all duration-150` on an
element whose `:style` binds `width`; `app.css:615-617` states the rule for
exactly this case: *"No width transition. Animating width is layout thrash."*
Also `components/app-sidebar.blade.php:78` `transition-[width] duration-200`.
Both contradict the new block's own rule at `app.css:677-680`.

**9. 38 inline `<svg>` blocks, 1 icon component.** Only `icon/user.blade.php`
exists. The search glyph is duplicated verbatim three times
(`app-topbar.blade.php:83-85`, `:96-98`, `command-palette.blade.php:116-117`).
Six SVGs each in `performance/index.blade.php`,
`performance/broodcock-timeline.blade.php`, `broodcocks/index.blade.php`; four
each in `photos/upload.blade.php` and `app-sidebar.blade.php`. Nine more nav
icons are raw path strings in a PHP array at `app-sidebar.blade.php:15-46`.

**10. Shipped comments describing code that is no longer there.**
`landing/index.blade.php:92-94` — "The form **arrives here in the next step**.
Until it does, this section carries the farm's own contact details" — the form is
mounted 15 lines below at `:107`. `partials/head-meta.blade.php:31` — "The
**SVG** is the mark itself" — there is no SVG favicon; the set is ICO + PNG
(`:33-35`). `broodcocks/pedigree.blade.php:91-94` explains an `ink-80`/`ink-48`
choice using tokens that do not exist. `users/index.blade.php:26` describes a
"**pearl** ground" on an element classed `bg-muted`.

### Below the top ten
- `photo-thumb.blade.php:37` uses `text-[10px]`; SKILL.md:127 bans anything under
  11px, and 3 views consume it. Otherwise clean: **zero** `font-bold`,
  `backdrop-blur`, gradients, or stock palette classes anywhere.
- `brand-mark.blade.php:37` sets `style="width:…;height:…"`, duplicating the
  `width`/`height` attributes two lines above.
- `app-topbar.blade.php:17-28` `$sectionLabels` has no key for `appointments` or
  `profile`; the sidebar calls that section "Visits" (`app-sidebar.blade.php:35`).
  Latent only — `$actionLabels` has no `index` entry (`:30-36`), so no breadcrumb
  renders there.
- The 15 new ink/primary ramp steps are in `app.css` but **not** in
  `config/gfms-brand.php`. `BrandTokensAreMirroredTest` asserts config→css, so it
  will not flag the one-way addition; the PDF templates cannot use them.
