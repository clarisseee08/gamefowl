# 99 — Redesign report

Branch `redesign/ui-system`, 32 commits. **695 tests pass**, `pint` clean,
`npm run build` clean.

This is the closing document for the redesign begun in `00-audit.md` against the
system frozen in `01-system.md`. It records what changed, what was found, and —
at the end, and in more detail than the rest — what is still not done.

---

## The finding that shaped everything else

The audit's first line was that the binding design document described a palette
the application did not have. That turned out to be true of **four** documents,
and the reason they all drifted the same way is a single property of the
toolchain:

> **Tailwind emits no CSS and no warning for a utility it does not recognise.**

A document naming a retired token has no feedback loop. Neither does a view
using one — the class simply resolves to nothing, the element renders unstyled,
and nothing anywhere reports it.

| Document | What it claimed | What the code had |
|---|---|---|
| `.claude/skills/…/SKILL.md` | 16 token names | none of them defined |
| `DESIGN.md` | primary = peacock `#0d6e75` | deep red `#8b2626` since the direction change |
| `DESIGN.md` | headings = Inter | Poppins since the type swap |
| `DESIGN.md` | no 17px step | the body size of the entire public surface, 18 views |
| `docs/design-brief.md` | "where it and the code disagree, the code is wrong" | the inverse |

`bg-pearl` and `text-ink-80` had already shipped dead into real views on the
strength of those documents.

**`DeadUtilityClassTest` closes the loop**: it fails the build when a colour
utility in a view resolves to no CSS. The documents were corrected against
`app.css` and `config/gfms-brand.php`, which `BrandTokensAreMirroredTest`
already keeps in agreement with each other.

---

## Bugs found

Not styling defects — things that were actually broken.

**CI had never passed in this repository's history.** Tests ran before
`npm run build`, so there was no Vite manifest and 61 tests died on
`Vite manifest not found`. Reordering two steps produced the first green run.

**A customer could not get back.** The bird page is public; the broodcock index
is not. Its back link pointed at the index for everyone, so a customer arriving
from the catalogue — the only way they can arrive — met a login form. Invisible
to anyone testing while signed in as staff. `NoDoorYouCannotOpenTest` now walks
the site as guest, customer, staff and owner and follows every link each role is
offered.

**"See all 26 birds" went nowhere.** `wire:navigate` on a same-page fragment, so
Livewire treated `/#stock` as a page visit and the scroll never happened.

**The theme reverted on every soft navigation.** `wire:navigate` copies the
fetched document's `<html>` attributes over the live ones, and the server cannot
know which theme the browser chose, so `data-theme` was wiped every time. The
head script only runs on a cold load. A `livewire:navigated` listener re-stamps
it.

**Dark mode was half a theme.** The semantic pairs were never inverted, so
`warning #755006` measured **2.57:1 on the dark card** — the colour the
dashboard drew its vaccination-compliance figure in, on the screen a keeper
opens every morning.

**Eighteen of twenty-two pages did not say what they were**, falling through to
the layout default and reporting themselves as "Dashboard" in the tab, the
history menu, and every screen-reader announcement on navigation.

**The public front page shipped two `<h1>`s** for the whole migration. It was
found during the migration, written into `gaps.md`, and left — which is the
lesson: written down is not fixed.

**`.table-hairline` was on a `<tbody>`.** The selector is
`.table-hairline tbody tr + tr`, so that screen had never had row separators.

**`.focus-ring` never emitted its anchor.** `@utility` is usage-gated while its
`::after` is plain CSS, so `position: relative` was dropped.

---

## Accessibility and contrast

Three of the four semantic pairs fail the Console 7:1 floor when the variant ink
is used as body text on its own tinted ground:

| Pair | Variant ink on its own tint | `foreground` on the same tint |
|---|---|---|
| success | 4.71:1 | 15.32:1 |
| warning | 5.66:1 | 13.56:1 |
| destructive | 4.64:1 | 16.13:1 |
| info | 7.15:1 | 14.95:1 |

`x-alert` had already solved this and says so in its own header. Twenty-six
hand-rolled panels re-introduced it — including **the session flash messages in
both layouts**, which is every success and failure notice on every page in the
application. `TintedPanelsKeepTheConsoleFloorTest` guards it now.

Badges are deliberately exempt and priced at the pairs' own 4.5:1 bar, which
`BrandTokensAreMirroredTest` asserts separately.

### Measured floors, all pages

| Check | Result |
|---|---|
| Horizontal overflow at 375px | 0 of 13 pages |
| Touch targets ≥ 44px | pass |
| Inputs ≥ 16px | pass |
| Images without `alt` | none |
| Interactive elements without an accessible name | none |
| Unlabelled form fields | none |
| Exactly one `h1` | all pages |

One caution about that table. The first accessibility pass reported **16
unlabelled selects** and was wrong: `x-filter-select` keeps a native
`<select aria-hidden="true" tabindex="-1">` purely as a value holder for
`wire:model`, and the visible control is a `role="combobox"` named by
`aria-labelledby`. A tool that does not model `aria-hidden` reports all 16, and
"fixing" them would put every filter name into the screen-reader output twice.

---

## Motion

Every duration in the production bundle is now on the token scale — 100, 150,
200, 300. Two were not:

- **260ms** in the command palette and the mobile drawer, replaced by the steps
  whose own comments name them: `--dur-base` reads "dropdowns, popovers, tabs,
  accordions" and `--dur-slow` reads "modals, drawers".
- **500ms**, which was never used by anything. It appeared in a *comment*
  explaining why a half-second entrance is wrong here, and Tailwind scans the
  file as text with no notion of what a comment is. The rule against it was
  generating it — the same trap `app.css`'s own header records one layer up.

Nine places used the bare `transition` shorthand, which animates eight
properties including `box-shadow` on panels that set an elevation shadow. Each
now names the two properties it actually changes.

The `prefers-reduced-motion` block killed animation **duration** but not
**delay**, so a staggered entrance left a delayed control invisible and then
popped it in — strictly worse than the animation it replaced, and only for the
people who asked for less motion.

---

## What was built

- Three type roles, self-hosted: Poppins headings, Inter body, JetBrains Mono
  for every number via `.datum`.
- The motion token set, with the legacy names aliased rather than orphaned.
- Ink and primary ramps completed 50–950, derived from existing hues. No new
  brand hue was introduced.
- A primitives layer, and a light and dark theme.
- `x-filter-bar`, `x-filter-select` and `x-form-select`: every `<select>` in the
  application is a real listbox rather than an OS-drawn menu.
- The sign-in screen carries the leg band at full height, a password reveal, a
  Caps Lock warning, and the theme toggle it never had.

### Guard tests added

| Test | What it catches |
|---|---|
| `DeadUtilityClassTest` | a colour utility in a view that resolves to no CSS |
| `NoDoorYouCannotOpenTest` | a role offered a link it is then refused at |
| `EveryPageNamesItselfTest` | a page falling through to the layout's default title |
| `AbsenceIsAWordTest` | a bare dash standing in for a missing value |
| `TintedPanelsKeepTheConsoleFloorTest` | variant ink on its own tinted ground |
| `OneHeadingPerPageTest` | a page with more or fewer than one `<h1>` |

Each asserts it actually inspected something — a guard that quietly stops
finding files reports success forever, which is worse than no guard at all.

---

## What is NOT done

Stated plainly, because a report that only lists wins is not a report.

**Most routes have had a consistency pass, not a redesign.** Four surfaces were
genuinely re-composed: the landing masthead, the bird page, the breeding record,
and the sign-in screen. The rest were corrected against the system — figures
into `.datum`, dashes into words, band tags where band numbers were bare text,
contrast, motion, headings, titles — but their layouts are substantially as they
were. Several deserve the treatment the breeding record got, where five
identical metric cards turned out to be one funnel and two derived ratios.

**The index pages still open with a row of metric cards.** That is the
hero-metric template the craft floor names as a default to refuse. It was left
because the dashboard establishes it as this system's own device, and changing
one without the other would be worse than either — but it is a default, not a
decision.

**The landing page still uses `01 / 02 / 03` section numbers.** Section numbers
are on the same list, and this sequence carries no information the reader needs.

**Focus and error look the same.** The focus ring is primary at 35%; the error
ring is destructive at 25%. Both are red, both 3px. On the sign-in screen the
error was given its own channel — the label turns and the ring does not — but
the underlying collision is unfixed everywhere else. Fixing it properly means
deciding what colour focus should be across the whole application, which is a
token decision and was not one to take in passing.

**Two contrast checks are hand-verified, not guarded.**
`TintedPanelsKeepTheConsoleFloorTest` reads one class attribute at a time, so it
catches a tinted ground and variant ink only on the *same* element. Seven real
failures were split across a parent and its child and were found by hand.
Catching those automatically means rendering each page and walking computed
styles, which is a browser's job rather than a unit test's.

**`.claude/skills/` is gitignored.** The corrections made to the design skill's
16 dead token names exist only on one machine and are not in this repository.
`CLAUDE.md` was updated to say so and to point at `/design` and
`docs/design-brief.md` as the real references.

**The remaining entries in `gaps.md` are still open** — the missing date
primitive, `x-filter-bar`'s plain-string summary losing `.datum`, and the
`duration-*` utilities that repeat the token values because Tailwind exposes no
utility that reads them.
