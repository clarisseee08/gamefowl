# GFMS — how this repo works, and where the EMS redesign stands

_Last updated 2026-11-06 (evening)._

## 1. The system in one paragraph

GFMS is a gamefowl breeding-farm record system (BSIS capstone). The stack is
Laravel 12 on PHP 8.2, Livewire 4 with its bundled Alpine, and Tailwind v4,
which is configured in CSS (`resources/css/app.css`, no
`tailwind.config.js`). On top of that sits daisyUI 5 with the `d-` prefix,
tests run on PHPUnit 11 and PDFs come from dompdf.
- **Production** runs on Render with Supabase.
- **Local development** runs against a local PostgreSQL 17 database named
  `gfms` on 127.0.0.1, filled with demo seed data. `.env` points at it.
- `CLAUDE.md` holds the project rules. Read it first.

## 2. Running it locally

```bash
php artisan serve --port=8000     # the app, http://127.0.0.1:8000
npm run dev                       # Vite: CSS/JS/Blade changes appear live
```
- Demo logins come from `database/seeders/UserSeeder.php`.
- Before trusting a test that reads the compiled CSS, run `npm run build`. The
  design-guard tests read `public/build`, not the dev server.

## 3. How committing works here

- **Never commit `.env`.** It holds a live Supabase password.
- **Branch first; never commit straight to `main`.** A push to `main` can
  deploy.
- **Push to `origin` only:** `github.com/clarisseee08/gamefowl`. `old-origin`
  is an old mirror. Never force-push.
- **Commit as `clarisseee08 <clarisseee08@gmail.com>`**, with no AI credit
  lines (no "Co-Authored-By", no "Generated with").
- **Message style:** `type(scope): what changed`, then plain prose on why.
  Types in use are `fix`, `design`, `design(ems)`, `deploy` and `docs`.
- **One fix per commit, together with its test.** Copy is pinned by tests, so
  a wording change and its test go in the same commit.
- **Before every commit**, run these three; CI runs the same three on PRs and
  on `main`:
  ```bash
  vendor/bin/pint --dirty --format agent
  php artisan test --compact
  npm run build
  ```

### Branches right now

| Branch | What | State |
|---|---|---|
| `main` | Production. Already contains the daisyUI redesign (PR #6). | pushed |
| `fix/blockers-and-logic` | The 2 blockers and 4 logic fixes, 6 commits | pushed; PR to `main` not opened yet |
| `redesign/ems` | Part B, the EMS design system, built on top of the fix branch | **local only, not pushed** |

## 4. The redesign (Part B, EMS design system)

The owner's decisions and every departure from the EMS spec are recorded in
`docs/design/design-audit.md`. Where they differ from the EMS spec, the decisions
win. In short:
- The forest-green palette stays.
- Inter is the one typeface.
- Controls are EMS-compact (28px, 12px text) from the `lg` breakpoint up;
  phones keep 44px targets and 16px inputs.
- EMS effects are allowed.
- Badges keep their current colours.
- The theme menu offers Light, Dark and System.
- EMS copy rules apply.
- Every create and edit opens as a modal.

### Done (on `redesign/ems`)

- **Foundation:** Inter, the EMS radius scale, compact desktop sizes, the
  daisyUI corrections, the `dark:` variant, scrollbars and EMS motion.
- **Wrappers** in `resources/views/components/ui/`, all shown in every state
  on `/design`:
  - Actions: button, row action.
  - Fields: field, input, textarea, select, checkbox, radio, switch,
    slider.
  - Surfaces: card, KPI tile, summary card, KPI row, status badge, count pill,
    avatar.
  - Navigation and disclosure: tabs (underline, segmented, boxed), tab panel,
    accordion.
  - Overlays: dropdown menu, popover, tooltip, form dialog, sheet, info dialog,
    confirm dialog, toaster.
  - Page and feedback: page header, skeleton, progress, separator.
- **Page headers:** every console page opens on the compact EMS page header.
  The catalogue gets a compact console view too.
- **KPI tiles:** on the dashboard, breeding and mortality. The mortality
  cause-of-death bars were removed.
- **Desktop density:** applied to every console screen, with compact filter
  pills and EMS-shaped badges.
- **Lists:** a visible Actions column with icon buttons. Gamefowl rows gain a
  per-row delete, which reuses the bulk-delete confirmation.
- **Tabs:** switch instantly on click.
- **Toasts:** top right, with a close button and a timer.
- **Forms as modals** (in progress, see the list below):
  - Centred dialog: health, performance, users, record a death.
  - Side sheet: breeding, gamefowl.
  - The old `/create` and `/{id}/edit` URLs open the list with the form
    already open.
  - Each form component itself is unchanged (an `inDialog` flag hides its
    page chrome).

### Not finished: start here

Done since the first version of this list:
- The modal reopen bug was a test-timing issue. Reopening works.
- Edit on a bird's page and on a mating's page opens a side sheet right there.
- Forms open instantly with a skeleton.
- The responsive sweep is clean at 375, 768, 1024 and 1366px.
- The sidebar has the EMS look.
- The top bar has the Light / Dark / System menu.

Still to do:
1. **Remaining EMS pieces:**
   - Empty and error states with Retry.
   - Breadcrumbs on detail pages.
   - Combobox for searchable selects.
   - Command palette restyle.
   - Account menu in the top bar (avatar, name, sign out).
   - The DataTable card with toolbar and page size.
2. **EMS copy pass:** sentence-case titles ("Health records") and buttons, and
   removing intro sentences. Change each test in the same commit as its
   wording.
3. **Ship it:** push `redesign/ems`, open PRs (fixes first, then the
   redesign), and merge to `main` only when the owner says so.

### Known decisions against the EMS spec (keep them)

- **No colour invented beyond the palette;** status colours come from the
  existing token pairs.
- **Nothing under 11px, and console text at 7:1.** This is why the toast
  gradients are darker than EMS's.
- **Missing values say "No data" in words, never a dash**
  (`AbsenceIsAWordTest`).
- **No new dependencies.** There is no `tailwind-merge`. Lucide icons are
  inlined in `App\Support\Icon`, and `IconNamesTest` checks every name.

## 5. Tests that guard the design

| Test | What it guards |
|---|---|
| `FontPayloadTest` | Inter only, 400–700 |
| `BrandTokensAreMirroredTest` | Palette and contrast floors |
| `DesignSystemGuardTest` | No stock Tailwind palette classes or raw hex |
| `DeadUtilityClassTest` | Every colour utility resolves (needs `npm run build`) |
| `BadgeVocabularyTest` | Enum badge classes exist |
| `IconNamesTest` | Every icon name exists |
| `tests/Feature/Ui/EmsComponentsTest.php` | The wrappers |
