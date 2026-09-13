# Gaps found during migration

One line per gap: route, what was needed, what was used instead.

- `/` — the page needs a second `<h1>`-free catalogue: `catalog.browse` renders its own
  `<h1>` ("Our Gamefowl"), so `/` ships two `<h1>`s. Left alone; the heading belongs to
  the `/catalog` migration, not the landing view.
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
