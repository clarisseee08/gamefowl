# Redesign progress — "Registry, running as software"

> **Identity pass (name / mark / colour) is complete.** See the section at the
> end of this file. Rollback for that pass: `git tag design/pre-identity`.

**Rollback:** `git tag design/field-ledger-v1` (`a6b640a`). Earlier world at
`design/apple-v1` (`3938e56`).

**Direction:** replaced the field-ledger's no-elevation / single-accent / paper
rules with a product UI that has depth, state and motion. Kept the band tag, the
bloodline resolution over free text, mono registry data, the pedigree connectors,
and the rule that colour means bloodline.

---

## Steps

- [x] 1. Tokens into `config/gfms-brand.php` + Tailwind theme
- [x] 2. App shell — full-bleed, full-height, sidebar, scroll containment (§3.5)
- [x] 3. Rebuild `/design` against new tokens
- [x] 4. Command palette (⌘K / Ctrl+K)
- [x] 5. Card / table / form / badge components
- [x] 6. Broodcock table with the §3.7 behaviour set
- [x] 7. Dashboard — stat tiles, sparkline, compliance meter
- [x] 8. Catalogue — full-bleed on its own shell
- [x] 9. Pedigree — elevation and hover, connectors kept
- [~] 10. Propagate §3.7 — **partial**, see below
- [x] 11. PDF templates read the config
- [ ] 12. Dark mode — **not done, deliberately**, see below
- [x] §8 guard tests (five)

---

## Decisions taken

**1. Band foregrounds are resolved, not fixed.** §3.1's band hexes are brighter
than the previous anodised set, and three of the six cannot carry white text —
amber measures **2.08:1**. Darkening them to fit white would have walked amber
straight back to the muted gold this direction replaced. So the hexes stay
exactly as specified and `BandTag::foreground()` picks white or deep ink per
band. This also covers the hash fallback, where the colour is not known ahead of
time.

**2. `muted-foreground` darkened `#667069` → `#4E5550`.** The spec value measures
4.96:1 on background and 4.70:1 on muted — both clear AA, but the same document
keeps the Console 7:1 floor. Same hue, lower lightness: 7.40 / 7.67 / 7.01.

**3. `warning` darkened `#A87409` → `#906308`.** At the specified value it was
3.68:1 on its own tinted background — the one semantic pair that failed.

**4. The two-step ink scale collapsed to one.** `ink-80` and `ink-48` both map to
`muted-foreground`; the new value already clears 7:1, so a second lighter step
would only have reintroduced the failure the old `ink-48` had on pearl.

**5. Bulk delete was implemented, not refused.** §9 forbids new features, but
§3.7 explicitly requires bulk selection with "available actions" and states the
behaviours need no schema change. The more specific instruction wins. It is a
loop over the **same** authorize-then-delete path a single row already uses — not
a `whereIn(...)->delete()`, which would bypass the Policy and the model's own
delete handling.

**6. Column visibility is client-side.** It is a per-person viewing preference,
not shared state. localStorage keeps it across sessions without a table.

**7. Catalogue got its own shell.** §3.5 says full-bleed but no console sidebar.
A dense app rail spends 240px of a grid that wants width, and shows a customer
navigation for screens they cannot open.

**8. Catalogue cards went 4:5 → 4:3.** The portrait crop was chosen when the grid
was a 1120px centred column; full-bleed it made cards ~730px tall and showed one
row — the change meant to show more stock was showing less.

---

## Could not do

**Sparklines on three of the four headline tiles.** Total / on-farm / breeding
have no existing time series; drawing twelve points for each needs a new
aggregate per tile, which §9 forbids. They carry a proportion bar derived from
figures already on the page. Only fertility has a real series
(`breedingTrend()`, one query, already computed for the chart below it).

**§3.7 is partial (step 10).** Landed: bulk selection with a floating action bar,
row-hover actions, filter chips, column visibility, saved views via the query
string (already present through `#[Url]`), and the row-hover pattern propagated
to all six tables. **Not landed:** side drawers replacing page navigations,
inline edit on single fields, toasts replacing flash banners, and the `?`
keyboard-shortcut overlay. Each is a substantial behavioural change across
several components, and they were the lowest-certainty items against the
remaining budget.

**Dark mode (step 12).** §4 gates it on steps 1–11 being complete. Step 10 is
partial, so it is not started. The token layer is ready — every surface declares
its own foreground — and enabling it later is a `:root[data-theme="dark"]` block
plus a toggle, with no component changes. Recorded in the README.

**Sticky page-header stack.** §3.5 asks for breadcrumbs (56px), page header
(64px) and tab rail (44px) all sticky. The breadcrumb bar is in the shell and is
sticky; page headers are still owned by individual views and scroll with the
content.

---

## Measured, after

Debugbar disabled. All 14 routes fetched and inspected; dense screens measured in
the browser at 390px.

| | Result |
|---|---|
| Stock Tailwind palette classes, all 14 routes | **0** |
| Page-scale centred columns, all 14 routes | **0** |
| Contrast failures (dashboard, broodcocks, incl. composited band chips) | **0** |
| Touch targets under 44px | **0** |
| Horizontal scroll at 390px | **none** |
| Body scrolls in console | **no** — main is the only scroll container |
| Dead margin at 1920px | **0px** (content 1665px of 1920) |
| Tests | **472 passing** |

Two defects were found only by measuring the rendered page, not by reading
tokens: the band code chip at 3.84:1, and three toolbar controls at 40px. Both
are fixed and both now have guards.

---

## Flux UI / Mary UI — evaluated, not adopted

Per the brief, both were assessed as a source of the primitives this app lacks
(combobox, command palette, sheet, data table) and neither was adopted.

**Flux UI** is the official Livewire component library from the Livewire authors,
so version alignment is a non-issue and its `wire:model` integration is native.
The free tier covers the basics; the components this project actually wanted —
command palette, data table, date picker — are largely in the paid tier, which
§0 rules out. Its visual language is also strongly opinionated toward a
neutral-grey SaaS look, so theming it to the band-tag identity would mean
overriding most of what you installed it for.

**Mary UI** is free, broader, and built on daisyUI — which is the problem: it
brings a second theming system with its own token names and semantics, on top of
a Tailwind v4 theme layer this project already owns. Two sources of truth for
colour is precisely what `config/gfms-brand.php` and its mirror test exist to
prevent.

**Recommendation for future work:** revisit Flux if the project ever takes a paid
tier, and only for genuinely complex primitives — a combobox with async search, a
date-range picker. The components built here by hand (command palette, popover,
sheet, sparkline, band tag) are small, carry no dependency, and are already
themed. Adopting a library now would trade them for a foreign visual language and
a version to track, on a deadline.


---

# Identity pass — name, mark, colour

## Name

**Digital Broodcock Farm Record Management System / DBFRMS**, held in
`config.gfms.system` and
read from there by every browser title, meta tag, OG tag, auth screen, report
heading and PDF header. Exactly one hardcoded occurrence existed — the PDF
running header. `tests/Unit/SystemNameTest.php` fails on any recurrence.

(The display name was briefly *Gamefowl Breeding Management System*; it was moved
back to match the thesis document, which keeps the Broodcock title. Because the
internals were never renamed, that reversal touched five files.)

**Internals deliberately unchanged:** `config/gfms-brand.php`, the `GFMS_*` env
keys, route names, table names, CSS prefixes and test filenames. Renaming them is
invisible to a user and risks the suite. Future work if it ever grates.

## Mark

A rooster head built from geometry, not traced: three overlapping circles for the
comb, a circle for the head, a triangle beak, two wattle circles, a knocked-out
eye. The raster favicon is drawn with the **same** geometry through GD rather
than converted from the SVG, so the vector and the bitmap cannot drift.

It took three passes to read as a bird:

1. An off-centre neck cut a notch where it met the head.
2. Notch fixed, but the neck was still the largest element and the silhouette
   read as a chess piece.
3. Neck removed entirely — head-only, which is what the brief said. Comb radius
   raised to 2.9 because at 16px the geometric r=2 merged three points into one
   lump.

Deliverables: Blade component, `favicon.svg`, a real `favicon.ico` (32px PNG-in-
ICO, previously a 0-byte placeholder), `apple-touch-icon.png` at 180px,
`mark-mono.svg` for print, and a lockup component with the wordmark dropping
below rail width.

## Colour

Deep comb red on the sidebar, not a saturated top bar. The rail is on every
console screen at full height, so it carries far more colour presence, and a dark
navigation rail is a current pattern where a bright top bar is a 2014 one. Plus a
3px brand rail across the very top of the viewport, brand markers on page titles,
a two-panel auth screen, and brand rules in the PDF chrome.

**Brand red is chrome only, and there is a test for it.** This app already uses
red for mortality and overdue vaccinations. If brand red also appeared on a
button, a keeper could not tell branded from urgent — in a system whose job
includes flagging dead birds that is a usability defect, not an aesthetic one.
Peacock stays the interactive colour.

### Deviation, logged

`brand_muted_fg` was specified as `#C9A2A0`, which measures **5.16:1** on the
sidebar. It carries inactive nav labels — Console body text, under a 7:1 floor.
Lightened to `#DBC2C1` per the brief's own instruction to adjust the foreground
and never the surface. Verified in the browser afterwards: **all 17 sidebar text
elements clear 7:1, worst 7.04**.

## Measured, after

| | Result |
|---|---|
| Routes checked (status, stock palette, centred column, old name, brand-on-control, new name present) | **14 / 14 clean** |
| Contrast failures at 390px | **0** |
| Touch targets under 44px | **0** |
| Horizontal scroll at 390px | **none** |
| Body scrolls in console | **no** |
| Tests | **476 passing** |
