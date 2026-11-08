# Gaps found during migration

One line per gap: route, what was needed, what was used instead.

- ~~`/` — the page needs a second `<h1>`-free catalogue: `catalog.browse` renders its own
  `<h1>` ("Our Gamefowl"), so `/` ships two `<h1>`s. Left alone; the heading belongs to
  the `/catalog` migration, not the landing view.~~
  **CLOSED.** `Catalog\Browse` takes a `headingLevel` prop (allow-listed to `h1`/`h2`), so
  it keeps its `<h1>` when routed at `/catalog` and renders `<h2>` when embedded under the
  farm's nameplate on `/`. Only the tag changes; size, weight and the brand marker come
  from the classes, so both renderings look identical. `OneHeadingPerPageTest` now walks
  every public and console page and fails on any page with a count other than one.
  Deferring it was the mistake worth recording: written down is not fixed, and this one
  survived on the public front page for the whole migration because the note existed.
- `/` — the Catalog surface is specified photo-led, but `Landing\Index` exposes no data
  (no farm photograph, no stock figure), so the masthead is type-only. Adding either
  needs a component change, which is out of scope for a presentation migration.
- `/health`, `/performance`, `/breeding` — the system has no date primitive to sit beside
  `x-filter-select`, so a date range keeps a visible `.label` next to a plain `.input`
  inside the bar instead of the label-inside-the-border group the selects use.
- `/broodcocks`, `/health`, `/performance` — `x-filter-bar`'s `:summary` takes a plain
  string, so the count in the bar's footer loses `.datum` and its digits are proportional.
  The catalogue's migrated summary already reads this way; matching it beat diverging.
- `/health` — `x-filter-bar` has no slot for a loading state, so the existing
  "Searching&hellip;" indicator moved from the card's foot to the end of the control row,
  and the foot's "Clear Filters" is now the component's "Clear filters".
- `x-progress` — the Motion whitelist has no row for a progress bar, and `.meter` in
  app.css sets width once with no transition. The bar borrows the accordion/tab step
  (`transform` only, `--dur-base`, `--ease-in-out`) rather than inventing a duration;
  if a progress row is ever added to the whitelist this should be reconciled with it.
- `x-form-select` — the Motion whitelist names `--dur-base` / `--dur-fast` for a dropdown,
  but Tailwind exposes no `duration-*` utility that reads those tokens, so the enter/leave
  classes are `duration-200` / `duration-150` — the same literals `x-dropdown` and
  `x-filter-select` already ship. The numbers match the tokens exactly; a `duration-base`
  utility would let all three stop repeating them.
- `/mortality/create` — the bird picker's HTML `required` attribute is gone. On a select the
  browser can no longer show, constraint validation blocks the submit and anchors its bubble
  to a hidden element, so the form stops with nothing on screen to explain it. The server
  rule is untouched and its message renders under the control; no other converted select
  carried the attribute.
- `x-form-select` — the system has no way to name a control without printing the name, so
  the component takes a `label-hidden` flag for the dashboard's trend picker, whose heading
  already says what it does. It replaces an `aria-label` and changes no visible copy.
