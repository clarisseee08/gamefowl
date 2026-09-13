# 01 — The design system. FROZEN.

You consume this. You never extend it.

No new token. No new primitive. No hex value in a view. No raw duration or
easing. No animation absent from the whitelist below. No edit to a logo asset.

If a screen needs something this document does not contain, use the closest
primitive here and append one line to `docs/redesign/gaps.md`. Do not improvise
a pattern — a pattern invented once per route is how thirty routes end up
looking like thirty products.

---

## 0. The one rule above all others

**Colour means bloodline and nothing else.**

Every band tag is coloured. Nothing else on the page is. If you are reaching for
colour to make something feel less plain, you are about to break the rule the
whole system rests on — reach for **weight, space, or a rule** instead.

The five status washes (`success`, `warning`, `destructive`, `info`, plus
`neutral`) are the single exception, and they are desaturated on purpose so they
never compete with a band.

`DesignSystemGuardTest` enforces this. It greps every view **and the compiled
bundle**.

---

## 1. Tokens

All tokens live in `@theme { }` in `resources/css/app.css`. Colour is mirrored
into `config/gfms-brand.php` because dompdf cannot read a stylesheet;
`BrandTokensAreMirroredTest` fails if they drift.

### 1.1 Names that DO NOT EXIST

These appear in `.claude/skills/gamefowl-design-system/SKILL.md` and in
`docs/design-brief.md`. **Every one produces no CSS and no warning.** Tailwind
emits nothing for a utility it does not know and fails no build, so using one
renders as nothing at all and no test catches it.

| Do not write | Write instead |
|---|---|
| `bg-parchment` | `bg-background` |
| `bg-canvas` | `bg-card` |
| `bg-pearl` | `bg-muted` |
| `text-ink` | `text-foreground` |
| `text-ink-80` | `text-muted-foreground` |
| `text-ink-48` | `text-muted-foreground` (the two-step ink scale collapsed to one) |
| `border-hairline` | `border-border` |
| `border-rule-strong` | `border-border`, or `border-print-rule-strong` in PDF only |
| `text-action` / `bg-action` | `text-primary` / `bg-primary` |
| `text-ok` / `bg-ok-wash` | `text-success` / `bg-success-bg` |
| `band-crimson` | `band-ember` |
| `band-forest` | `band-jade` |
| `band-slate` | `band-rose` |
| `.frosted` | nothing — it is defined nowhere and used nowhere |

`docs/design-brief.md` states that "where it and the code disagree, the code is
wrong." **That sentence is false.** The stylesheet is the source of truth.

### 1.2 Surfaces and text

```
bg-background          #fbfbfa   the page
bg-card                #ffffff   any raised surface
bg-muted               #f4f5f3   sunk wells, table heads
border-border          #e4e6e2   every hairline rule
border-input           #dfe2dd   form control borders

text-foreground        #161c19   primary text
text-muted-foreground  #4e5550   secondary text — 7.40:1, the CONSOLE FLOOR
```

`text-muted-foreground` is the **lightest text the console surface may use.**
Anything lighter fails the 7:1 requirement.

### 1.3 The ink ramp — neutrals, no hue

`ink-50 100 200 300 400 500 600 700 800 900 950`

Prefer the semantic names above. Reach for the ramp only for a step that has no
semantic name. `ink-700` equals `text-muted-foreground`; **`ink-500` and
`ink-600` are mid-greys that fail the console's 7:1 floor** — legal on a caption
over paper, never on a console screen.

### 1.4 Primary — the interactive colour

`primary-50 100 200 300 400 500 600 700 800 900 950`, base `primary` = `#8b2626`.

Buttons, links, active nav, focus rings. It carries this role rather than the
green because it measures 8.74:1 on card against the green's 6.07:1.

### 1.5 Brand green — CHROME ONLY

`brand`, `brand-deep`, `brand-deeper`, `brand-foreground`, `brand-muted-fg`.

Sidebar, brand mark, top rail, auth panel, PDF chrome. **Never a button, a link,
an input or a status pill.** `test_brand_red_is_never_used_on_an_interactive_element`
enforces the matching rule for red.

### 1.6 Semantic pairs

```
text-success / bg-success-bg          text-warning / bg-warning-bg
text-destructive / bg-destructive-bg  text-info / bg-info-bg
```

### 1.7 Band slots — the only chroma that means anything

`band-ember  band-amber  band-jade  band-cobalt  band-plum  band-rose`

**Never write these yourself.** Use the component (§2.12). Colour is resolved in
PHP by `App\Support\BandTag` and emitted as an inline `style`, because a dynamic
Tailwind class survives `npm run dev` and vanishes from the production bundle.

### 1.8 Radii, elevation, spacing

```
--radius-sm 6px   --radius-md 10px   --radius-lg 14px
--shadow-e1  resting card
--shadow-e2  popover, toast, dropdown
--shadow-e3  modal
```

Elevation means **interactive or layered**. Nothing lifts on its own. Do not
write an ad-hoc `box-shadow` — there are three steps and they are the whole set.

Spacing is Tailwind's default 4px scale. Stay on it: `gap-2 gap-3 gap-4 gap-6
gap-8`, `p-4 p-6 p-8`. Off-scale values (`gap-[7px]`) are a defect.

### 1.9 Type

```
--font-display  Poppins         HEADINGS ONLY, 600/700
--font-sans     Inter           body, UI, forms, tables — 400/500/600
--font-mono     JetBrains Mono  every number, through .datum
```

`h1`–`h4` already carry `--font-display`. **Never apply it to body text.**
Nothing below 11px, anywhere.

---

## 2. Primitives

### 2.1 Button

```blade
<button type="submit" class="btn-primary">Save</button>
<button type="button" class="btn-secondary">Cancel</button>
<button type="button" class="btn-danger">Delete</button>
<button type="button" class="btn-quiet">Dismiss</button>
```

44px min height is already in the base rule. Hover changes background only;
press is `scale(0.98)`. Disabled: `disabled` attribute — the opacity is handled.

### 2.2 Input, textarea, select

```blade
<label for="name" class="label">Bird name</label>
<input id="name" type="text" wire:model.blur="name"
       class="input mt-1 @error('name') input-error @enderror">
<p class="help">Optional. Shown in the catalogue.</p>
@error('name') <p class="error">{{ $message }}</p> @enderror
```

Always `id` + `for`. 16px is already set — anything smaller zooms iOS Safari on
focus and throws the user out of the form.

### 2.3 Card

```blade
<div class="card p-6"> … </div>
<a href="…" class="card card-interactive p-6"> … </a>
```

### 2.4 Badge

**Never write a badge class literally.** Six PHP enums return them:

```blade
<span class="{{ $record->status->badgeClasses() }}">{{ $record->status->label() }}</span>
```

Vocabulary: `badge-ok badge-warn badge-alert badge-info badge-neutral`. Note the
last two are **`info` and `neutral`**, not `note` and `quiet` — `BadgeVocabularyTest`
scans every view and fails on any `badge-*` that `app.css` does not define.

### 2.5 Table

```blade
<div class="card overflow-hidden">
  <div class="overflow-x-auto">
    <table class="table-hairline w-full text-left">
      <thead class="bg-muted"> … </thead>
      <tbody> … </tbody>
    </table>
  </div>
</div>
```

`.table-hairline` is a **descendant** selector. It goes on `<table>`, never on
`<tbody>`. The `overflow-x-auto` wrapper is required: a table may scroll
sideways, the page may not.

### 2.6 Empty state

```blade
<div class="empty-state">
  <p class="empty-state-title">No visit requests to show.</p>
  <p class="empty-state-body">Requests made from the front page appear here.</p>
</div>
```

Required wherever a list can be empty. No motion — see the whitelist.

### 2.7 Skeleton

```blade
<div class="skeleton h-4 w-40"></div>
```

Required wherever data is fetched.

### 2.8 Modal

```blade
<div class="modal-backdrop modal-backdrop-open"></div>
<div class="modal-panel modal-panel-open max-w-lg p-6" role="dialog" aria-modal="true" aria-labelledby="t">
  <h2 id="t">Confirm</h2> …
</div>
```

`role="dialog"`, `aria-modal`, `aria-labelledby`, and a focus trap. Escape closes.

### 2.9 Tooltip

```blade
<span class="tooltip-trigger relative inline-flex">
  <button type="button" class="btn-quiet" aria-describedby="tip">?</button>
  <span id="tip" role="tooltip" class="tooltip bottom-full left-1/2 mb-2 -translate-x-1/2">Text</span>
</span>
```

### 2.10 Toast, drawer, tabs, accordion, avatar, breadcrumb, pagination

Classes: `.toast` (+ `-enter/-enter-active/-leave-active`), `.drawer` (+ `-open`),
`.tab` / `.tab-active` / `.tab-indicator`, `.accordion-content` (+ `.accordion-open`),
`.avatar`, `.breadcrumb` / `.breadcrumb-link` / `.breadcrumb-current`,
`.page-link` / `.page-link-current`.

### 2.11 `.datum` — every number

```blade
<span class="datum">{{ $bird->band_number }}</span>
<td class="datum">{{ $record->weight }}</td>
```

Band numbers, dates, weights, egg counts, rates, percentages, phone numbers, ids.
Proportional digits do not align in a column, and a weight column that does not
align is the fastest way to look amateur. **This is the single clearest
amateur→professional tell in the application.**

### 2.12 Band tag

```blade
<x-band-tag :bloodline="$bird->bloodline" :band="$bird->band_number" />
<x-band-tag :bloodline="$b->bloodline" :band="$b->band_number" size="xs" />
```

`bloodline` is free text `varchar(120)` — a keeper can type anything, so never
hard-code a name→colour map. `band_number` is legitimately nullable (birds are
banded at an age, not at hatch); the component renders "Not yet banded". Never a
blank cell, never a dash, never an error style. Colour is never the only channel:
the tag carries a two-letter code and the bloodline name appears as text nearby.

### 2.13 Focus ring

```blade
<button class="btn-primary focus-ring">Save</button>
```

Opacity only, on a ring already at full size. Never a ring that grows.

---

## 3. The two surfaces

| | **Catalog** (public) | **Console** (staff) |
|---|---|---|
| Screens | landing, catalogue, bird pages | everything behind `auth` |
| Body text | 17px, generous | 15px, dense |
| Contrast floor | 4.5:1 | **7:1** — used outdoors in daylight |
| Inputs | — | **16px minimum** |
| Touch targets | — | **44px minimum** |

---

## Motion whitelist

| Element              | Motion                                          |
|----------------------|-------------------------------------------------|
| Button hover         | bg color, --dur-instant                          |
| Button press         | scale(0.98), --dur-instant                       |
| Focus ring           | opacity only, --dur-fast, never a growing ring   |
| Input focus          | border color, --dur-fast                         |
| Dropdown / popover   | opacity 0→1 + translateY(-4px→0), --dur-base     |
| Tooltip              | opacity + scale(0.96→1), --dur-fast, 400ms delay |
| Modal backdrop       | opacity, --dur-slow                              |
| Modal panel          | opacity + scale(0.97→1), --dur-slow, --ease-out  |
| Drawer               | translateX, --dur-slow                           |
| Toast                | translateY + opacity, --dur-base                 |
| Accordion / collapse | grid-template-rows 0fr→1fr, --dur-base           |
| Tab indicator        | translateX + width, --dur-base, --ease-in-out    |
| Skeleton             | opacity pulse 1.5s, or nothing                   |
| Table row hover      | bg color, --dur-instant                          |
| Sidebar collapse     | width, --dur-base (the one width exception)      |

BANNED — never implement, regardless of how good it looks:
- Scroll-triggered reveals, fade-in-on-scroll, staggered list entrances
- Parallax, marquees, animated gradients, floating or pulsing decoration
- Page-load animations on dashboard content; counter / number roll-ups
- Bounce, elastic, or spring easing anywhere
- Any animation over 300ms
- Animating any property other than transform and opacity
- Anything not in the table above

### The tokens, and the only ones permitted

```
--dur-instant 100ms   --dur-fast 150ms   --dur-base 200ms   --dur-slow 300ms
--ease-out    cubic-bezier(0.16, 1, 0.3, 1)    entrances
--ease-in     cubic-bezier(0.4, 0, 1, 1)       exits
--ease-in-out cubic-bezier(0.4, 0, 0.2, 1)     moves, resizes
```

A raw `300ms`, a `duration-200`, or a literal `cubic-bezier()` in a view is a
defect. `--t-fast`, `--t-base`, `--t-enter`, `--ease-out-quart` and
`--ease-enter` are **legacy aliases** kept so existing rules compile; never use
them in new work.

Exits run one step faster than entrances.

---

## 4. Traps that fail a build or ship a silent bug

1. **A double quote inside `x-data`** truncates the HTML attribute and Alpine
   dies with no error. Write `[role=option]`, never `[role="option"]`.
2. **Unbalanced `<div>`s** — a guard test parses every view.
3. **`resources/views/reports/pdf/` is dompdf**, a CSS 2.1 engine. No custom
   properties, no `oklch()`, no flex, no grid. **Do not touch those templates.**
4. **Copy is the test API.** ~126 assertions target visible copy and data; zero
   target class names. Restyling is safe; **rewording breaks tests.** Change copy
   and its assertion in the same commit.
5. **Never rename a vocabulary class.** Six enums return them at ~22 call sites.
   Restyle what a class resolves to; never rename it.
6. **Verify a class exists in the built bundle** before believing it works.
   `npm run build`, then grep `public/build/assets/app-*.css`.
7. **Icons are inline SVG path data**, one family, uniform stroke. There is no
   icon package and no React in this codebase.
