# Visual audit — the field-ledger redesign

Every finding here was **screenshotted or measured in a browser**, not inferred
from source. Where a number appears it was computed at render time.

Rollback point for the previous design: `git tag design/apple-v1` (`3938e56`).

---

## Method

Per screen: refactor → screenshot at 1440px and 390px → **write down three
specific things still wrong** → fix → screenshot again → commit.

Step three is the whole method. Plain design is what you get when nobody looks,
and "it looks fine" is what you write when you have not looked. Every entry
below records what the screenshot actually showed.

Debugbar was disabled first. An earlier accessibility pass reported 22 contrast
failures and 13 undersized targets that were **all Debugbar's own toolbar** — a
measurement contaminated by the instrument.

---

## Screen by screen

### Catalog index — the public shopfront

| What the screenshot showed | Fix |
|---|---|
| The band tag — the signature of the whole system — was absent. Band numbers rendered as grey subtext under the name. | Band tag leads the card body. |
| The 4:5 photo well was the largest element on every card and every card said "No photo yet". A working farm photographs very few birds, so the biggest element on the page communicated nothing. | The empty well carries the bloodline's band colour and two-letter code. You can now read the bloodline mix of a page at arm's length. |
| Ages were proportional figures (`2 yrs 10 mos`, `4 yrs 1 mos`). | `.datum`. |
| **Second pass:** the placeholder at 52px was louder than the band tag, inverting the system's own "spend boldness in one place" rule. | Held to 38px at `0f` alpha. |
| **Mobile:** filters filled the entire first screen; a customer scrolled past four controls before seeing a bird. | Two-up grid, tighter padding. |

### Bird detail

| What the screenshot showed | Fix |
|---|---|
| The bird's own identity page did not show its identity — band as grey subtext. | Band tag leads. |
| Details was 13 equal-weight pairs in a two-column grid, so a comb type had the same visual authority as a band number, and the eye zig-zagged. | A ruled ledger: label left, value right, hairline between. Registry values in mono. |
| Delete was a filled crimson button and the loudest element on the page. Crimson is within a shade of the crimson **band** colour, so a filled crimson control on a bird's page reads as identity. | Outlined with alert text. Filled `btn-danger` kept for the confirmation dialog, where destroying the record genuinely is the primary action. |
| The 80px photo tile was an empty bordered box. | Carries the bloodline, same as the catalogue. |

### Pedigree — the most serious violation found

| What the screenshot showed | Fix |
|---|---|
| **The tree encoded sex as blue and pink card fills.** Colour meaning something other than bloodline is the one thing this system does not allow; it is also a lazy convention and it was *redundant*, since every card already stated Sire or Dam in words. | Removed. Structure carries it: sire above dam, labelled. Card colour is the bloodline band. |
| **No connecting lines.** You could not tell which grandparent belonged to which parent — the single question a pedigree chart exists to answer. | A real bracket, drawn in CSS. Alignment required `flex: 1` on both pair and node rather than `justify-content`: each generation doubles in count, and only proportional distribution puts a pair's midpoint exactly on its descendant's at every depth. |
| Twelve "Not recorded" boxes carried the same weight as real ancestors, so a young farm's tree read as broken rather than young. | They recede. |
| The root bird was labelled "Sire" because it ran through `parentTerm()` like every other node. | It is the subject of the tree, not somebody's parent. |
| **Measured:** `text-ink-48` on the pearl root card = **4.37:1**, a real failure. It clears 4.5:1 on parchment but not on the darker ground. | `text-ink-80`. Pinned by a test so it cannot recur elsewhere. |

### Console dashboard

| What the screenshot showed | Fix |
|---|---|
| The one card whose subject **is** bloodline was the only card not using the band colours. | Colour swatch per bloodline. |
| Every headline figure was proportional — `30`, `26`, `81.3%`, `77.8%`. | `.datum` throughout. |
| "141 days overdue" and "due in 29 days" differed only by the wash on a pill, and that difference disappears on a phone in daylight — which is exactly when this list is read. | Severity stripe on overdue rows. No copy changed. |

### Broodcock table

| What the screenshot showed | Fix |
|---|---|
| The leftmost and most valuable column spent itself on the word "None". | Band tag takes that position. |
| Rows were ~73px; five birds visible on a screen built for dense scanning. | Tightened to `py-2.5`. |
| **Sex was the only header rendering in caps.** Tailwind's preflight sets `button { text-transform: none }`, so every *sortable* header silently dropped the uppercase from its own `<th>` — and Sex is the one column that is not sortable. | `uppercase` repeated on the button. |

### Performance table

The agent that restyled this verified against the CSS bundle and the test suite,
not a browser. Screenshotting it found three things it could not have seen:

- The table ran 68px past its card, and the column falling off the right edge was
  **Actions** — Edit and Delete unreachable without scrolling sideways.
- Pinning Actions fixed reachability but then truncated Recorded By to "Mari",
  which reads as a broken table rather than a shortened name.
- Final: Recorded By shows only at `2xl`. Table measures **1054px in a 1054px
  container** — fits exactly, no page-level scroll.

### Sign-in

Pure white, no identity, a generic centred form — and the first thing a panel
sees. Now on parchment, in a ledger panel, headed by the six band colours: the
same six a bird wears on its leg. Still no logo tile, because "GF" in a rounded
square was a placeholder for an identity the farm does not have. The band is the
identity it does have.

### PDF reports — the artefact a panel actually holds

**Every template still carried the retired palette.** `#6b7280`, `#d1d5db` and
`#111827` are Tailwind's gray-500/300/900, hand-typed into six files, and not one
of them read `config/gfms-brand.php` — which is the entire reason that config
exists. The printed reports looked nothing like the system on screen.

All six now read the config, putting them under the same guarantee as the app.
Also found: the mortality report drew its deaths-per-period bars in the **OK
green** — the success token measuring deaths.

---

## Cross-cutting defects

These were invisible from any single screen.

**The paginator was the last thing outside the system, twice.** Laravel's stock
Tailwind paginator was replaced first — then the rendered HTML of six routes
*still* carried 55–81 stock palette classes each, because **Livewire ships its
own separate paginator** and every Livewire table renders that one. Invisible to
a source-level grep of `resources/views`.

**The rule was generating the violation.** After every view was clean, the
production bundle still contained `bg-gray-100` and `border-slate-200`. The
source was `docs/design-brief.md` — the document whose anti-pattern list names
those exact classes as forbidden. Tailwind v4 auto-detects the whole project and
cannot tell prose from markup. Fixed with `source(none)` plus explicit sources;
bundle went 52.7KB → 45.9KB.

**`badge-quiet` never existed.** I invented the name in the brief, then used it
in two views, where it rendered as an unstyled pill. Tailwind emits no CSS and no
warning for an undefined utility. The real class is `badge-neutral`.

**Synthetic bold.** Only Fira Code 400 and 500 were loaded while headline figures
are `.datum` at 32–44px, so the browser was smearing the 500 into fake bold.

**A broken Escape handler.** `".querySelector(...)"` with a bare leading dot is
invalid JS, so Escape did nothing on two dialogs. When this was first flagged I
checked that each file contained both a handler and a `.btn-secondary` target,
found both, and reported the claim false — the wrong check, because file-level
presence cannot see a malformed expression.

---

## Measured results

Debugbar disabled. Measured in-page at 390px on the densest screens
(vaccination schedule, broodcock table, pedigree, catalogue):

| | Result |
|---|---|
| Contrast failures | **0** |
| Touch targets under 44px | **0** |
| Inputs under 16px | **0** |
| Horizontal page scroll at 390px | **none** |
| Stock palette classes in rendered HTML | **0** across all 13 routes |
| Shadows / gradients / blur / `font-bold` | **0** |
| Tests | **466 passing** |

Two of those were fixed at the token layer and therefore app-wide: selects and
text inputs were landing at **43px** — one pixel under, which a measured audit
fails exactly as hard as thirty — and the wordmark was a 26px text run rather
than a 44px home link.

---

## Guards added

Findings are worth little if they can silently return. Each of these converts one
into a red test:

| Test | Catches |
|---|---|
| `BrandTokensAreMirroredTest` | config/stylesheet drift; band colours illegible on white; Console ink below 7:1; `ink_faint` used on pearl |
| `BadgeVocabularyTest` | enum/CSS drift, **and** any component class typed into a Blade view that app.css does not define |
| `PaginationViewTest` | a re-style dropping `wire:click`, leaving a paginator that renders perfectly and does nothing |

The second one is the important one. Every pre-existing assertion in this suite
targets copy and data — **zero** target class names — which is what makes
restyling safe and rewording dangerous, but also means an unstyled component
produces a green suite.
