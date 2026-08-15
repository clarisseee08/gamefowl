# GFMS Design Brief

The design law for the Digital Broodcock Farm Record Management System.
Every visual decision in this codebase is measured against this document.

> **Status:** this brief was written *after* the application was built, from the
> product's own requirements and data. It is the source of truth; where it and
> the code disagree, the code is wrong.

---

## 1. Who this is for, and where they are standing

Two audiences, one codebase, and they are not in the same place.

**Console** — the owner and the record keeper. They are entering and reviewing
records: broodcock tables, vaccination schedules, egg counts, mortality, pens.
They are frequently **outdoors, in Philippine daylight, holding a phone in one
hand** with the other hand occupied by a bird or a feed scoop. Glare is the
default viewing condition. They have low technical literacy and high domain
literacy — they know exactly what a hatch rate is, and they do not know what a
"UID" is.

**Catalog** — customers browsing available stock. Photo-led, unhurried, usually
indoors on a phone. They are deciding whether a bird is worth enquiring about.

The panel evaluating this system is a third audience, and they will be looking
at a projector.

---

## 2. The two surfaces

These are genuinely different products sharing a database. Designing them
identically is the single most common way a system like this reads as
"admin panel bolted onto a shop."

| | **Catalog** (public) | **Console** (staff) |
|---|---|---|
| The visitor's job | decide whether to enquire | complete a record |
| Density | spacious — one idea at a time | dense — maximum rows in view |
| Images | fixed **4:5**, `object-cover` | 40px thumbnail, or none |
| Body type | 17px | 15px |
| Input floor | 16px | **16px** (never smaller — iOS zooms the page below 16) |
| Row height | generous | compact but ≥44px tappable |
| Text contrast | ≥4.5:1 | **≥7:1** (daylight) |
| Colour | band tag + photography | band tag only |

Everything else — tokens, components, type ramp — is shared. The surfaces differ
in **density and contrast**, not in vocabulary.

---

## 3. Typography

**Fira Sans** for interface. **Fira Code** for registry data. One superfamily,
two voices; both SIL OFL, both **self-hosted as subset woff2**.

> No CDN. A demo in a provincial venue cannot depend on Google Fonts resolving,
> and the previous stack declared `SF Pro` — which ships only on Apple platforms,
> meaning every Windows machine silently rendered Segoe UI instead.

### Where mono is mandatory

**Registry data is always Fira Code with `font-variant-numeric: tabular-nums`.**
Proportional digits do not align in a column, and a weight column that does not
align is the fastest way to look amateur at a glance.

Mono applies to: band numbers, dates, weights, egg counts (set/fertile/hatched),
fertility and hatch rates, percentages, durations, ratings, row counts, IDs.

Mono does **not** apply to: names, breeds, bloodlines, causes of death, remarks,
labels, or any prose.

### Ramp

| Token | Size / weight / tracking | Use |
|---|---|---|
| `display` | 32 / 600 / -0.02em | page title |
| `title` | 22 / 600 / -0.01em | card and section heads |
| `subtitle` | 18 / 500 / 0 | sub-heads |
| `body` | 17 / 400 / 0 | Catalog body |
| `body-console` | 15 / 400 / 0 | Console body, table cells |
| `label` | 13 / 500 / 0.01em | form labels |
| `caption` | 12 / 400 / 0 | help text, meta |
| `micro` | 11 / 500 / 0.06em uppercase | column heads, eyebrows |

Weight ladder is **400 / 500 / 600 only**. No 700 — bold is a blunt instrument
and 600 carries every heading here.

---

## 4. Tokens

*This is the section everything else cites.* Every colour in the application
comes from this table. A stock Tailwind palette class (`bg-gray-100`,
`text-blue-600`, `border-slate-200`) anywhere in `resources/views/` is a defect.

### 4.1 Ground and ink

The reference is a **field ledger**: dark ink, pale paper, and colour reserved
for the tags. High contrast is not a style choice here — it is the daylight
requirement.

| Token | Hex | Use |
|---|---|---|
| `--color-paper` | `#FAF9F7` | app background |
| `--color-card` | `#FFFFFF` | raised surface |
| `--color-sunk` | `#F1EFEA` | table heads, wells, inset |
| `--color-ink` | `#1A1917` | primary text |
| `--color-ink-muted` | `#55534D` | secondary text (**7.4:1 on paper**) |
| `--color-ink-faint` | `#726F66` | tertiary, placeholders (**4.77:1** — Catalog only, never Console body) |
| `--color-rule` | `#E3E0DA` | hairlines, borders |
| `--color-rule-strong` | `#CFCBC2` | table dividers under load |

### 4.2 The one interactive colour

| Token | Hex | Use |
|---|---|---|
| `--color-action` | `#16324F` | links, primary buttons, focus ring |
| `--color-action-hover` | `#1E4468` | hover |
| `--color-action-wash` | `#E9EEF4` | selected rows, active nav |

**Blue-black ink**, not a bright UI blue. It is the ledger's own colour, it sits
at ~11:1 on paper so it survives glare, and — critically — it does **not**
compete with the band colours, which are the only chroma in the system.

### 4.3 Semantic status

Deliberately separate from `--color-action`, so a red badge never reads as a
link, and separate from the band palette, so status never reads as bloodline.

| Token | Hex | Wash | Meaning |
|---|---|---|---|
| `--color-ok` | `#1B6B44` | `#E6F1EB` | active, compliant, win |
| `--color-warn` | `#8A5A12` | `#F8EFDF` | due soon, resting, draw |
| `--color-alert` | `#A32219` | `#F7E8E7` | overdue, deceased, loss |
| `--color-note` | `#2A4E7A` | `#E9EEF4` | informational |
| `--color-quiet` | `#5E5B55` | `#EFEDE9` | neutral, not applicable |

### 4.4 Band palette — the only chroma in the system

Six colours, taken from the anodised aluminium leg bands actually sold for
poultry. This is not a decorative palette; it is the physical object.

| Token | Hex | White text |
|---|---|---|
| `--band-1` crimson | `#B3202C` | ✓ |
| `--band-2` cobalt | `#1B4F9C` | ✓ |
| `--band-3` forest | `#1E6B45` | ✓ |
| `--band-4` amber | `#9A5B08` | ✓ |
| `--band-5` plum | `#6A3080` | ✓ |
| `--band-6` slate | `#41525E` | ✓ |

All six carry white text at ≥4.5:1, which is what makes the tag legible
regardless of which bloodline it lands on.

### 4.5 Radius and space

`--radius-tag: 999px` · `--radius-control: 8px` · `--radius-card: 12px`

Space scale: `4 · 8 · 12 · 16 · 24 · 32 · 48 · 64`.

---

## 5. The band tag — the signature element

Every gamefowl wears a numbered leg band. In this system `band_number` is the
bird's primary human identifier and `bloodline` is its primary organising fact.
The band tag renders both in one object, and it is modelled on the real thing:
a coloured anodised ring with an ID stamped into it.

**This is the one place colour is spent.** Everything else is ink, rule and
paper.

### Anatomy

A filled capsule in the bloodline's band colour, with the band number knocked
out in **white Fira Code**, `tabular-nums`.

### Colour assignment — this is the part that must not be naive

`bloodline` is a **free-text `varchar(120) nullable` column, not an enum.** Seed
data contains three values; the factory defines five; a keeper can type anything
at all. A fixed name→colour map therefore fails the moment someone adds a
bloodline.

The rule is:

1. **Curated map** for known bloodlines, so the farm's real stock is stable and
   recognisable: Sweater → cobalt, Hatch → crimson, Kelso → forest,
   Roundhead → amber, Grey → slate.
2. **Stable hash fallback** for anything else: `crc32(strtolower(trim(bloodline)))
   % 6`. Deterministic, so the same bloodline always gets the same colour on
   every screen and every session.
3. **No bloodline recorded** → `--color-quiet`, never a random colour.

### The unbanded state

A bird with `band_number = null` is **not an error** — birds are banded at an
age, not at hatch. It renders as a **dashed outline capsule** in
`--color-ink-faint` reading **"Not yet banded"**. Never a blank cell, never a
dash, never "N/A".

### Accessibility

**Colour is never the only channel.** The bloodline name appears as text
adjacent to every band tag. A keeper with colour-vision deficiency, or reading
a monochrome printout of a report, loses nothing.

---

## 6. Anti-patterns

These are defects in this codebase, not preferences.

- **Stock Tailwind palette classes.** `bg-gray-50`, `text-blue-600`,
  `border-slate-200`. Every colour comes from §4.
- **Proportional digits in a data column.** Registry numerics are mono +
  `tabular-nums`, always.
- **A second accent colour.** Interactive means `--color-action`. Nothing else.
- **Band colour used for anything that is not a bloodline.** It is an identity
  channel; borrowing it for status destroys the encoding.
- **Blank empty states.** Every empty list says what it is and what to do next:
  "No health records yet. Add the first vaccination or checkup."
- **Generic copy.** Not "Submit" / "Success!" / "No data" — say what happened:
  "Save record", "Vaccination record saved".
- **Spinners as loading states.** Skeletons that match the real layout.
- **Unconstrained images.** Mixed aspect ratios destroy a grid. Catalog is 4:5
  `object-cover`, fixed.
- **Uniform spacing everywhere.** Equal padding at every level reads as a
  wireframe. Sections breathe; rows do not.
- **Emoji as icons.** Drawn SVG only, one consistent stroke weight.
- **Tailwind tokens in a PDF template.** See §8.
- **`font-weight: 700`.** The ladder is 400 / 500 / 600.

---

## 7. Accessibility floor

Not polish — these are pass/fail.

- Console text **≥7:1**; Catalog text ≥4.5:1. Measured, not estimated.
- Touch targets **≥44×44px**, unconditionally — not behind
  `@media (pointer: coarse)`, which does not match a desktop browser at a narrow
  viewport.
- Inputs **≥16px** or iOS zooms the page on focus.
- Visible focus on every interactive element; never remove a focus ring without
  replacing it.
- Every modal traps focus and closes on Escape.
- `prefers-reduced-motion` **removes travel while preserving state change** — a
  global `0.01ms` kill destroys useful feedback and is itself a defect.
- Labels bound to inputs; meaningful `alt` on every image.

---

## 8. PDF reports are a separate world

`resources/views/reports/pdf/` is rendered by **dompdf, a CSS 2.1 engine**. It
cannot parse CSS custom properties, `oklch()`, flexbox or grid, so it can never
import the app's stylesheet.

The reports therefore carry a **plain-hex mirror** of §4 — the same values,
written literally, in their own `<style>` block, with table-based layout. They
must look like they belong to the same system without sharing a line of code
with it.

The band tag appears in reports too, as a bordered cell with the hex fill. A
report that looks like the application is worth a visible amount at defense.
