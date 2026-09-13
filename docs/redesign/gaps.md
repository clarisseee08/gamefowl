# Gaps found during migration

One line per gap: route, what was needed, what was used instead.

- `/` — the page needs a second `<h1>`-free catalogue: `catalog.browse` renders its own
  `<h1>` ("Our Gamefowl"), so `/` ships two `<h1>`s. Left alone; the heading belongs to
  the `/catalog` migration, not the landing view.
- `/` — the Catalog surface is specified photo-led, but `Landing\Index` exposes no data
  (no farm photograph, no stock figure), so the masthead is type-only. Adding either
  needs a component change, which is out of scope for a presentation migration.
