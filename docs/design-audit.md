# Design audit — EMS design system

## Stack
- Framework / router: Laravel 12.66 (PHP 8.2), server-rendered Livewire 4.4 page components via `Route::livewire()` in `routes/web.php`; Blade views; Alpine.js (bundled with Livewire); `wire:navigate` soft navigation. Not React — every EMS wrapper must be built as a Blade component (+ Alpine for behaviour).
- Tailwind version: v4 (CSS-first `@import "tailwindcss"` + `@theme{}` in `resources/css/app.css`, `@tailwindcss/vite`; no `tailwind.config.js`)
- daisyUI version: 5.7.50 (`@plugin 'daisyui' { themes: false; prefix: d-; exclude: rootcolor, scrollbar, rootscrollgutter }`; daisyUI colour names aliased to GFMS tokens)
- Current themes / dark mode: custom "forest" light + dark palettes (`config/gfms-brand.php` mirrored in `app.css`), `data-theme="light|dark"` on `<html>`, OS preference as fallback, pre-paint script, two-state toggle in the console top bar and sign-in pages (none on the public site)
- Icons: hand-written inline SVG (50 in views, 6 stroke widths, no library)
- Toasts: home-made daisyUI `d-toast` + `d-alert-soft` stack in `layouts/app.blade.php` (session flash + Livewire `toast` event), bottom-centre/bottom-right, 5 s, no close button
- Forms / validation: Livewire component state + Laravel server-side validation, inline `@error` messages; custom listbox selects (`x-form-select`, `x-filter-select`); Fortify for auth
- Tables: hand-built `<table class="d-table">` (9 screens), Livewire `WithPagination` + restyled vendor pagination views, `x-sort-control` on 2 screens
- Data fetching: Livewire server round trips (no client fetching); file-cache `RecordCache` on public pages
- Animation: CSS transitions/keyframes on a 100/150/200/300 ms token scale + Alpine `x-transition`; 320 ms page fade, 450 ms header stagger; reduced motion honoured
- Class merge helper: none (Blade `$attributes->merge()` / `@class`)

## Shared components
| Component | File | Used by | Action (keep / restyle / replace with wrapper / delete) |
|---|---|---|---|
| `x-anchor-block` (console page header: eyebrow, h1, hero figure, actions) | `resources/views/components/anchor-block.blade.php` | 17 console screens | replace with wrapper → PageHeader (title + one-line description + actions; drop eyebrow/hero figure/stagger) |
| `x-app-sidebar` | `resources/views/components/app-sidebar.blade.php` | `layouts/app` (desktop + mobile sheet) | restyle (EMS sidebar states; accessible name on rail profile link; collapse button must not act inside the sheet) |
| `x-app-topbar` | `resources/views/components/app-topbar.blade.php` | `layouts/app` | restyle (account menu, breadcrumb via `x-breadcrumb`, no empty landmark) |
| `x-command-palette` | `resources/views/components/command-palette.blade.php` | `layouts/app` | restyle (record search, combobox ARIA, focus ring) |
| `x-filter-bar` (filter row, active summary, Clear filters) | `resources/views/components/filter-bar.blade.php` | 5 list screens | replace with wrapper → Toolbar inside the table card; adopt on Gamefowl list + catalogue |
| `x-filter-select` (listbox filter, label inside border) | `resources/views/components/filter-select.blade.php` | 13 uses / 7 views | restyle → Select (share one Alpine `data` with form-select) |
| `x-form-select` (listbox field with label/help/error) | `resources/views/components/form-select.blade.php` | 16 uses / 7 views | restyle → Select + FormField |
| `x-empty-state` | `resources/views/components/empty-state.blade.php` | 18 uses / 12 views | keep → EmptyState (restyle sizes) |
| `x-table-skeleton` | `resources/views/components/table-skeleton.blade.php` | 7 list screens | keep → TableSkeleton; add a CardSkeleton for the 3 hand-written ones |
| `x-segmented` (`wire:click` filter tabs with counts) | `resources/views/components/segmented.blade.php` | 5 screens | replace with wrapper → Tabs (segmented) / toggle group with proper ARIA |
| `x-sort-control` (sortable `<th>` with `aria-sort`) | `resources/views/components/sort-control.blade.php` | 2 screens (4 uses) | keep → DataTable header cell; adopt on the 4 hand-rolled sorters |
| `x-alert` | `resources/views/components/alert.blade.php` | 1 screen (+ `/design`) | keep → Alert; adopt for the raw `d-alert` banners |
| `x-avatar` | `resources/views/components/avatar.blade.php` | Users list (3) | keep → Avatar; make it the only avatar (merge `x-icon.user`) |
| `x-icon.user` | `resources/views/components/icon/user.blade.php` | sidebar footer | delete (merge into Avatar) |
| `x-band-dot` (bloodline colour dot) | `resources/views/components/band-dot.blade.php` | 25 uses / 12 views | keep (domain) |
| `x-band-tag` (leg-band capsule) | `resources/views/components/band-tag.blade.php` | Performance list | keep (domain) |
| `x-bird-card` (public bird card) | `resources/views/components/bird-card.blade.php` | catalogue, landing | keep (fix reveal-after-search and image fallback) |
| `x-pedigree-tree`, `x-pedigree-bracket` | `resources/views/components/pedigree-tree.blade.php`, `pedigree-bracket.blade.php` | bird page, pedigree | keep (domain) |
| `x-sparkline` | `resources/views/components/sparkline.blade.php` | dashboard | keep |
| `x-field-row` (dt/dd row) | `resources/views/components/field-row.blade.php` | 3 views | keep |
| `x-brand-mark` | `resources/views/components/brand-mark.blade.php` | 5 views | keep |
| `x-theme-toggle` | `resources/views/components/theme-toggle.blade.php` | top bar, guest layout | keep (EMS offers Light/Dark/System in the account menu) |
| `x-error-page` (full-page HTTP error document) | `resources/views/components/error-page.blade.php` | 403/404/419/500/503 | keep; add a 429 page; not an in-page ErrorState |
| `x-page-header` (public page header) | `resources/views/components/page-header.blade.php` | about, visit, legal | keep (public only) |
| `x-long-form` (legal page layout) | `resources/views/components/long-form.blade.php` | privacy, terms | keep (public only) |
| `x-partials.head-meta` | `resources/views/components/partials/head-meta.blade.php` | all layouts | keep |
| `x-breadcrumb` | `resources/views/components/breadcrumb.blade.php` | `/design` only | keep → Breadcrumb and adopt (top bar + detail pages) |
| `x-dropdown` (+ `.item`, `.label`, `.separator`) | `resources/views/components/dropdown*.blade.php` | none | restyle → DropdownMenu (move ARIA to the button, arrow keys, focus return) and adopt for row actions |
| `x-tabs` (+ `x-tabs.tab`) | `resources/views/components/tabs.blade.php`, `tabs/tab.blade.php` | none | keep → Tabs (underline) and adopt on the bird page |
| `x-info-card` | `resources/views/components/info-card.blade.php` | `/design` only | replace with wrapper → Card (no `elev-1`) and adopt, or delete |
| `x-stat-card` | `resources/views/components/stat-card.blade.php` | none | replace with wrapper → KpiTile and adopt on dashboard/breeding, or delete |
| `x-progress` | `resources/views/components/progress.blade.php` | `/design` only | keep → Progress; adopt on pedigree completeness |
| `x-switch` | `resources/views/components/switch.blade.php` | `/design` only | keep → Switch, or delete |
| `x-separator` | `resources/views/components/separator.blade.php` | `/design` only | keep → Separator, or delete |
| `x-photo-thumb` | `resources/views/components/photo-thumb.blade.php` | none | delete (or adopt for photo grids) |
| `x-bloodline-chip` | `resources/views/components/bloodline-chip.blade.php` | none | delete |
| `x-brand-lockup` | `resources/views/components/brand-lockup.blade.php` | none | delete |
| (missing) Button, Input, Textarea, Checkbox, FormField, Card, StatusBadge, DataTable + Pagination, FormDialog, ConfirmDialog, Sheet, Toast, ErrorState, Tooltip, Popover | — | 142 `d-btn` lines, 79 `d-input` lines, 9 tables, 10 copy-pasted confirm modals, 19 inline badge class strings | replace with wrapper (build each as a Blade component) |

## Screens

### /dashboard — Dashboard
- File: `app/Livewire/Dashboard/Overview.php`, `resources/views/livewire/dashboard/overview.blade.php` (via `app/Http/Controllers/DashboardController.php`, `resources/views/dashboard.blade.php`)
- Purpose: The owner's and record keeper's landing page: on-farm count, headline KPIs, follow-ups due, recent deaths, breeding trend and flock breakdown. Customers are redirected to the catalogue.
- Layout: Console shell. Anchor-block header (eyebrow, greeting h1, "N birds on the farm" figure, intro sentence, "Add gamefowl" on the right) → 4 KPIs on hairlines → two columns "Vaccinations needing attention" / "Recent mortality" → "Breeding success trend" (period select, 3 figures, CSS bar chart) → "The flock" by status / class / bloodline → "Pedigree completeness" (only when there are gaps).
- Components present: `x-anchor-block`; `d-stat` ×4 assembled from class strings; `d-progress` ×3; `x-form-select` (Trend period); `x-sparkline`; soft `d-badge`; `x-band-dot`; a hand-built div bar chart; 0 raw buttons/inputs/selects/tables. `x-stat-card` exists but is unused.
- Gaps:
  - [ ] Header is a report hero (eyebrow, 30–37px greeting, 2rem figure, intro sentence) instead of a title plus one muted line
  - [ ] Two flock counts on one screen ("26 birds on the farm" vs "Total broodcocks 30"); "Total broodcocks" is the retired term and counts hens
  - [ ] No loading state when "Trend period" changes; no error state
  - [ ] Breeding figures under the period selector are all-time (75% chart month vs 81.3% "Overall fertility")
  - [ ] Soft red badges at 4.48:1 (7 elements below AA)
  - [ ] Phone chart clipped to 5 of 12 months with no scroll cue; chart values reachable only via `title`
  - [ ] Stray single sparkline dot; misaligned column headings; ~700px empty right column; double rules on phone
  - [ ] Class A/B badges reuse the status hues
  - [ ] Copy: eyebrow and intro, paragraph chart notes, "1 days overdue", "1 bird have", months without year, negative "8 of 14 … not overdue"
  - [ ] Logic, needs sign-off (not design): dead/sold birds in the attention list; compliance KPI from a list capped at 8; `?trendMonths=0` crash; UTC greeting
- Verdict: Restyle only
- EMS patterns to introduce:
  - [ ] PageHeader
  - [ ] KPI row (shared KPI tile; adopt or replace `x-stat-card`)
  - [ ] CardSkeleton (trend section while the period changes)
  - [ ] Badge/StatusBadge (AA-safe soft tints)
  - [ ] ErrorState with retry
- Done: [ ]

### /broodcocks — Gamefowl list
- File: `app/Livewire/Broodcocks/Index.php`, `resources/views/livewire/broodcocks/index.blade.php`
- Purpose: Find, filter, sort and open birds; owners bulk-delete.
- Layout: Console shell. Anchor-block header ("Manage" / "Gamefowl" / "30 birds on the farm", "+ Add gamefowl" on the right) → toolbar loose on the page (search, Status, Class, Sex, Bloodline, Ownership, Columns popover) → status line ("Page 1 of 2." / "Searching…" / Clear filters) → `d-table` from `sm` (checkbox, photo, band, name, bloodline, sex, age, class, status, hover actions) or stacked link rows on phone → pager → floating selection bar → bulk-delete modal.
- Components present: `x-anchor-block`; `x-filter-select` ×5; Columns popover (`elev-2`); `x-empty-state` (filtered + first-run); `x-table-skeleton` + phone `d-skeleton` rows; `x-band-dot`; `d-table` with sticky header and `aria-sort`; `d-checkbox`; `d-badge`; Livewire pager view; selection bar (`elev-3`); bulk-delete `d-modal`; toast after bulk delete; 8 raw `<button>`, 4 raw `<input>` lines, 1 `<table>`.
- Gaps:
  - [ ] Toolbar and table are not one card
  - [ ] Row actions are a hover-revealed "View"/"Edit" cluster (opacity 0, invisible on touch) instead of a ⋯ menu
  - [ ] Loading skeleton renders above the table header and shifts the columns (`wire:loading.block` on the phone skeleton; inline-block `<tbody>`)
  - [ ] No error handling: raw Livewire 500 overlay, silent network failure, native 419 `confirm()`
  - [ ] No page-size control; position stated three times; ~6px chevrons; paging from the bottom keeps the scroll position
  - [ ] "30 birds on the farm" counts sold/dead birds (dashboard says 26)
  - [ ] Dark-mode selected row unreadable (primary-50 under foreground text)
  - [ ] Phone: search + 5 filters push the first record to the bottom of the first screen; no bulk selection on phone
  - [ ] Two "Clear filters" in the no-match state; `?page` past the end shows the first-run empty state
  - [ ] Selection checkboxes shown to staff who have no bulk action
  - [ ] Bulk-delete dialog opens after a ~0.9s server round trip
  - [ ] Copy: "Find a broodcock", "No broodcocks recorded yet", "Add your first broodcock"; "Broodcock (Male)" vs "Male (Broodcock)" vs "Male"
- Verdict: Needs new components
- EMS patterns to introduce:
  - [ ] PageHeader
  - [ ] Toolbar (search plus filters) inside the table card
  - [ ] DataTable (card, pagination footer with page size, sticky actions column)
  - [ ] row DropdownMenu (build on `x-dropdown`)
  - [ ] TableSkeleton (content-shaped; fix the display bug)
  - [ ] ErrorState with retry
  - [ ] ConfirmDialog (shared wrapper; opens without a server round trip)
  - [ ] Badge/StatusBadge
- Done: [ ]

### /broodcocks/create — Add a gamefowl
- File: `app/Livewire/Broodcocks/Form.php`, `resources/views/livewire/broodcocks/form.blade.php`
- Purpose: Register a bird: identity, parents (a typed-in outside parent becomes an `is_external` bird), particulars, notes and the for-sale flag, photos.
- Layout: Console shell with crumb "Gamefowl › New". One 40rem column: "← Back to gamefowl" → hand-built header (eyebrow, h1, required-field note) → Identity / Parents / Particulars / Notes / Photos sections → sticky Cancel + "Add Gamefowl" bar.
- Components present: `x-form-select` ×5 (Sex, Class, Status, Sire, Dam); raw `d-input` ×13 + checkbox + file input; `d-textarea` ×2; Alpine dropzone with progress bar and preview grid; `d-fieldset-legend` / `d-label`; inline `@error` with `role="alert"` and `aria-invalid`; "Saving…" spinner; flash toast on the bird page after the redirect.
- Gaps:
  - [ ] Header copies the anchor-block h1 classes; breadcrumb, back link, eyebrow, 40px title and intro all precede the first field
  - [ ] No error summary and no scroll/focus to the first invalid field on submit
  - [ ] Photo storage failure after save leaves the form filled; resubmitting can duplicate the bird (logic, needs sign-off)
  - [ ] Dark-mode dropzone text vanishes on hover/focus/drag
  - [ ] Sticky save bar is slightly see-through
  - [ ] Title Case "Add a Gamefowl" / "Add Gamefowl" and five names for one action (change with tests)
  - [ ] Bare `*` required marker (no `required` / `aria-required`); placeholders 3.15:1
  - [ ] "Tap to choose photos" on desktop; hard-coded "up to 4 MB"; `accept="image/*"` looser than the server; one aggregated photo error
  - [ ] For-sale checkbox under a "Notes" heading beside a field called "Notes"
  - [ ] Date inputs show mm/dd/yyyy while the app shows "18 March 2022"
  - [ ] Left-aligned 640px column leaves the right half of the desktop empty
- Verdict: Restyle only
- EMS patterns to introduce:
  - [ ] PageHeader
  - [ ] Breadcrumb (adopt `x-breadcrumb`; drop the duplicate back link)
  - [ ] inline field errors (present; add focus/scroll to the first one)
- Done: [ ]

### /broodcocks/{broodcock}/edit — Edit a gamefowl
- File: `app/Livewire/Broodcocks/Form.php`, `resources/views/livewire/broodcocks/form.blade.php`
- Purpose: Edit a bird's record, parents and particulars, and add photos.
- Layout: Same as create, prefilled: crumb "Gamefowl › Edit", "← Back to {band}", eyebrow "Edit bird", h1 "Edit {name}", sticky Cancel + "Save Changes".
- Components present: Same as create; saving redirects to the bird page with a toast.
- Gaps:
  - [ ] Crumb "Gamefowl › Edit" doesn't name the bird; back link uses the band, title the name
  - [ ] Create intro ("Everything else can be filled in later.") reused on edit
  - [ ] Photos help mentions "existing photos" but none are shown; adding photos here bypasses the 10-photo cap
  - [ ] "Save Changes" Title Case (change with tests)
  - [ ] Long class descriptions inside select values truncate on phone
  - [ ] As create: no focus-first-error, dark dropzone, see-through sticky bar, mm/dd/yyyy dates
- Verdict: Restyle only
- EMS patterns to introduce:
  - [ ] PageHeader
  - [ ] Breadcrumb (naming the bird)
  - [ ] inline field errors (present; add focus/scroll to the first one)
- Done: [ ]

### /broodcocks/{broodcock} — Bird page
- File: `app/Livewire/Broodcocks/Show.php`, `resources/views/livewire/broodcocks/show.blade.php` (+ embedded `photos.upload`, `photos.gallery`, `health.broodcock-health-history`, `performance.broodcock-timeline`)
- Purpose: One bird. Publicly it is the advertisement (photo, identity, pedigree, details, health, performance); for staff it is the working record (edit, delete, notes, offspring, photo management).
- Layout: Staff: console shell, back link (no crumb) → two columns (photo + thumbnail strip | sticky identity: bloodline, band h1, name, status badge, facts, notes, "Edit bird" / "Family Tree" / "Delete") → deceased alert → segmented tabs (Pedigree, Details, Photos, Health, Performance, Offspring) → tab panel → delete modal. Public/customer: same markup in the catalogue shell, "← Back to the catalogue", actions "Ask about this bird" / "Arrange a visit" / "Family Tree", no Offspring tab or photo management.
- Components present: `x-segmented` tabs with counts; `x-field-row`; `x-pedigree-tree`; `x-band-dot`; `d-badge`; `d-btn`; `d-alert`; `d-table` (offspring); three `d-modal` confirms (bird, photo, performance record); photo lightbox; dropzone with progress; `x-empty-state` in gallery/health/timeline; embedded paginators; toasts from save, delete, gallery and upload.
- Gaps:
  - [ ] Deleting a bird crashes /health, /health/schedule and /breeding/{id} (fix in those views) while the toast says "The history is kept"
  - [ ] Delete dialog promises an undo with no restore screen, omits the effect on offspring family trees, has no spinner, opens after a ~1.2s round trip
  - [ ] No breadcrumb (staff) while create, edit and pedigree have one; Delete sits ~4px from the primary
  - [ ] Tab switch has no loading state; phone tab strip clipped at "Pe" with no scroll cue; tabs lack `aria-controls` / `tabpanel` / arrow keys
  - [ ] Three row-action styles in the tabs (button stack per photo, ghost Edit, text links), no ⋯ menu
  - [ ] Timeline delete: inline alert, no toast; Health tab also embeds the timeline
  - [ ] Staff full names ("Added by", "Recorded by") shown to the public
  - [ ] Guest on a sold/dead bird gets an account-worded 403 with no way to the catalogue
  - [ ] Dark mode: dropzone text vanishes; lightbox becomes a near-white veil
  - [ ] Empty photo placeholder dominates the first screen with no action; tree connector detached and ~1.3:1
  - [ ] Typography: ≈60px h1, ≈36px "Parents"; section heads change size by tab; `<strong>` renders at 700
  - [ ] Copy: Title Case "Family Tree", "Add Health Record", "Add Performance Record", "Win Rate"; "Class: Class A"; "Broodcock" vs "Male"; "No photograph yet" vs "No photo yet"; public "No description"
  - [ ] Offspring empty state is a bare heading; unknown `?tab=` shows an empty panel
  - [ ] `pen` eager-loaded but never shown (extra query); dead test `assertDontSee('Edit Bird')`
- Verdict: Needs new components
- EMS patterns to introduce:
  - [ ] Breadcrumb (adopt `x-breadcrumb`)
  - [ ] PageHeader (action group; Delete in a ⋯ DropdownMenu)
  - [ ] Tabs (adopt `x-tabs`; panel loading state, phone scroll cue, full ARIA)
  - [ ] CardSkeleton (tab panel)
  - [ ] row DropdownMenu (photo tiles, health rows, timeline rows)
  - [ ] ConfirmDialog (shared; honest copy, spinner, opens without a round trip)
  - [ ] Toast on save/delete/submit (timeline delete)
  - [ ] EmptyState (Offspring)
  - [ ] ErrorState with retry
- Done: [ ]

### /broodcocks/{broodcock}/pedigree — Family tree
- File: `app/Livewire/Broodcocks/Pedigree.php`, `resources/views/livewire/broodcocks/pedigree.blade.php`
- Purpose: The bird's ancestor chart (one generation, at the farm's request) and how complete it is.
- Layout: Console shell (crumb "Gamefowl › Family Tree") for staff, catalogue shell for guests and customers. "← Back to {band}" → header (bloodline, band h1, "{name} · Family tree", "Pedigree recorded n of m ancestors (x%)" with a progress bar) → two-column chart "This bird" | "Parents" → legend sentence.
- Components present: `x-band-dot`; `x-pedigree-bracket`; `d-progress`; `d-link`; its own chart markup (not `x-pedigree-tree`); "Not recorded" slots; staff "Edit this bird" link when nothing is recorded; no raw form controls.
- Gaps:
  - [ ] Page and bird-page tab draw the tree differently (two implementations; no connectors here; "Male" vs "Broodcock")
  - [ ] Linked as the "full family tree" but shows fewer generations than the tab when grandparents are recorded
  - [ ] Crumb "Gamefowl › Family Tree" skips the bird and is Title Case (subtitle says "Family tree"); back link repeats it
  - [ ] "This bird" label floats ~90px above its node; ~60% of the desktop canvas empty
  - [ ] Divider and connector lines ~1.3:1
  - [ ] A soft-deleted parent reads "Not recorded"
  - [ ] H1 size differs from the bird page (≈52 vs ≈60px) for the same band number
- Verdict: Restyle only
- EMS patterns to introduce:
  - [ ] PageHeader
  - [ ] Breadcrumb (naming the bird)
- Done: [ ]

### /health — Health records
- File: `app/Livewire/Health/Index.php`, `resources/views/livewire/health/index.blade.php`
- Purpose: Farm-wide ledger of vaccinations, medications, dewormings, treatments and check-ups that staff filter and sort; the owner can delete a record.
- Layout: Console shell (sidebar, topbar, `<main>` scroller) → x-anchor-block (eyebrow, H1, record-count figure, intro; "Add Health Record" and "Vaccination Schedule" on the right) → segmented "Record type" filter → filter bar (search, Bird listbox, From, To) → table that restacks to blocks below md, or an empty state → pager → owner-only delete dialog.
- Components present: x-anchor-block, x-segmented, x-filter-bar, x-filter-select, x-empty-state, x-band-dot, x-table-skeleton, Livewire pager (`resources/views/vendor/livewire/tailwind.blade.php`); hand-written `d-modal` confirm; toast on delete; raw 5 `<button>`, 3 `<input>`, 1 `<table>`.
- Gaps:
  - [ ] HTTP 500 for everyone once a bird with records is deleted — guard or cascade (logic fix with a test)
  - [ ] No table card; the toolbar is a separate ruled row above the table
  - [ ] Edit/Delete are a visible text cluster, not a ⋯ row menu (x-dropdown exists, unused)
  - [ ] No page-size control in the pager footer
  - [ ] No ErrorState or Retry; a failed request shows Livewire's raw overlay
  - [ ] No loading on sort or paging; the phone skeleton is a table row under block rows
  - [ ] Paging from the bottom keeps the scroll position (pager scrolls `body`, not `<main>`)
  - [ ] Delete dialog waits ~0.8s for the server, and promises a restore that has no UI
  - [ ] Deleting the last row on the last page shows "Nothing has been recorded yet" with no pager
  - [ ] Record-type badges reuse status colours (Treatment = Due soon, Deworming = Scheduled)
  - [ ] Header about twice EMS size (36px H1, 50px figure, intro); header buttons 4px apart (`.sb-line`)
  - [ ] Rows 100–140px tall (product, dosage, condition and remarks stacked in one cell)
  - [ ] Phone: segmented strip clipped at "Check-up" with no scroll cue; truncated placeholder; From/To misaligned
  - [ ] Light contrast: red Delete 4.53:1, badges 4.8–6.4:1 (floor 7:1), placeholder 3.15:1
  - [ ] Title Case H1 and buttons; one empty-state title for filtered and unfiltered; "Delete , …" aria label
- Verdict: Needs new components
- EMS patterns to introduce:
  - [ ] PageHeader
  - [ ] DataTable (card, page-size footer)
  - [ ] Toolbar (search plus filters) inside the table card
  - [ ] row DropdownMenu
  - [ ] ConfirmDialog (shared; opens without a server round trip)
  - [ ] ErrorState with retry
  - [ ] TableSkeleton on sort and paging
  - [ ] Sheet (side panel) for create/edit
  - [ ] Badge/StatusBadge (keep category and status colours apart)
- Done: [ ]

### /health/schedule — Vaccination schedule
- File: `app/Livewire/Health/Schedule.php`, `resources/views/livewire/health/schedule.blade.php`
- Purpose: Follow-up agenda: health records whose next due date has passed, and those due in the next 30 days.
- Layout: Console shell (crumb "Health › Schedule") → x-anchor-block (overdue count as the figure, "N follow-ups are due in the next 30 days.", "All Health Records" on the right) → "Overdue" section (count chip, rows grouped by due date, pager) → "Due in the next 30 days" section (same structure).
- Components present: x-anchor-block, x-empty-state ×2, x-band-dot; two Livewire pagers (`overduePage`, `upcomingPage`); headerless `<ol>`/`<ul>` rows, each with a "Record Follow-up" link; no raw form controls.
- Gaps:
  - [ ] HTTP 500 once a bird with a follow-up is deleted (logic fix with a test)
  - [ ] "Record Follow-up" adds a new record but never clears the overdue one (logic; verify)
  - [ ] No loading state at all on paging; no ErrorState
  - [ ] Rows have no column headers; the two sections are hand-duplicated blocks
  - [ ] Empty states have no action
  - [ ] Red "N days late" chips and the overdue count at 4.48:1 (soft error badge under AA)
  - [ ] Header about twice EMS size; crumb "Schedule" vs H1 "Vaccination Schedule"; eyebrow repeats the crumb
  - [ ] ~97px rows with a ~200px dead gap; mixed vertical alignment within a row
  - [ ] Identical "Vaccination" badge on every row
  - [ ] Saving a follow-up lands on /health, not back here; only the bird is prefilled
  - [ ] One due date can split across two pages (groups built per page); pagers scroll `body`, not `<main>`
  - [ ] Numbers in the h2 and empty title not `.datum`; Title Case "Record Follow-up"
- Verdict: Needs new components
- EMS patterns to introduce:
  - [ ] PageHeader
  - [ ] KPI row (overdue / due soon; x-stat-card exists)
  - [ ] DataTable (headed rows per section)
  - [ ] TableSkeleton on paging
  - [ ] EmptyState (with an action)
  - [ ] ErrorState with retry
  - [ ] Badge/StatusBadge (AA-safe error tint)
- Done: [ ]

### /health/create — Add a health record
- File: `app/Livewire/Health/Form.php`, `resources/views/livewire/health/form.blade.php`
- Purpose: Record one vaccination, medication, deworming, treatment or check-up for one bird.
- Layout: Console shell (crumb "Health › New") → back link → x-anchor-block (eyebrow, H1, one-line intro) → 640px form in four titled sections (The Bird; What Was Done; Dates; Notes) → full-width sticky Cancel/Save bar.
- Components present: x-anchor-block, x-form-select ×2 (Bird, Record Type), x-alert (follow-up nudge); raw 5 `<input>`, 1 `<textarea>`, 1 `<button>`; inline `@error` blocks with ARIA on the raw fields; session flash → toast after redirect.
- Gaps:
  - [ ] 8-field create is a full page; EMS would use a Sheet (or a two-column FormDialog)
  - [ ] 6 of 8 fields hand-written; no shared field component
  - [ ] Next Due Date help sits beside the input on desktop
  - [ ] Native validation bubbles pre-empt inline errors (no `novalidate`; min/max on dates)
  - [ ] x-form-select errors and help not tied to the control (no ids, `aria-describedby`, `aria-invalid`)
  - [ ] "A follow-up is usually needed" nudge stays after the date is filled
  - [ ] Save always returns to /health (loses the schedule or bird-page origin); message doesn't name the bird
  - [ ] A failed save shows Livewire's raw overlay, no error message
  - [ ] Translucent sticky bar (text ghosts through); Save far right of the fields
  - [ ] Phone crumb link "Health" 40×21px (under 44px)
  - [ ] Title Case heads, labels and buttons; asterisk gap on date labels; placeholder 3.15:1
  - [ ] Conventions differ from the Performance form (back link, widths, placeholders, intro)
- Verdict: Needs new components
- EMS patterns to introduce:
  - [ ] Sheet (side panel) for create/edit
  - [ ] inline field errors (shared FormField; ARIA on selects; `novalidate`)
  - [ ] Toast on save/delete/submit (error tone for a failed save; name the bird)
- Done: [ ]

### /health/{record}/edit — Edit a health record
- File: `app/Livewire/Health/Form.php`, `resources/views/livewire/health/form.blade.php`
- Purpose: Correct an existing health record.
- Layout: As /health/create, with crumb "Health › Edit", H1 "Edit Health Record" and primary "Save Changes"; every field prefilled.
- Components present: As /health/create (x-anchor-block, x-form-select ×2, x-alert; raw fields with inline `@error`; flash → toast).
- Gaps:
  - [ ] Full page; EMS would use a Sheet
  - [ ] Header doesn't name the record (bird, date)
  - [ ] Create's intro reused verbatim on edit
  - [ ] Create's gaps carry over (help beside Next Due Date, no `novalidate`, select ARIA, return to /health, translucent bar, Title Case)
- Verdict: Needs new components
- EMS patterns to introduce:
  - [ ] Sheet (side panel) for create/edit
  - [ ] inline field errors (shared FormField)
- Done: [ ]

### /performance — Performance records
- File: `app/Livewire/Performance/Index.php`, `resources/views/livewire/performance/index.blade.php`
- Purpose: Farm-wide ledger of sparring sessions, derbies, conditioning sessions and weigh-ins; the owner can delete a record.
- Layout: Console shell → x-anchor-block (count figure, intro, "+ Add Performance Record") → status banner after a delete → segmented "Type of event" → filter bar (search, Result, From Date, To Date) → phone `<ul>` plus desktop `<table>`, or two empty states → pager → owner-only delete dialog.
- Components present: x-anchor-block, x-segmented, x-filter-bar, x-filter-select, x-empty-state ×2, x-band-tag, x-table-skeleton, Livewire pager; hand-written `d-modal`; inline `d-alert` banner; raw 7 `<button>`, 3 `<input>`, 1 `<table>`.
- Gaps:
  - [ ] No table card; toolbar outside the table
  - [ ] Edit/Delete are visible text links, not a ⋯ row menu; sr-only labels don't name the bird or date
  - [ ] No page-size control; double rule above the pager
  - [ ] Delete feedback is a persistent banner, not a toast; delete jumps to page 1
  - [ ] No ErrorState or Retry
  - [ ] Delete dialog waits ~1s for the server; promises a restore with no UI; Escape tied to `.d-btn-outline`
  - [ ] Bird drawn as a two-tone tag here, dot + band + name on Health
  - [ ] Phone: unlabelled values ("2.12 kg · Not recorded · 5 of 5"); clipped segmented strip; truncated placeholder; unpadded Edit/Delete
  - [ ] Category badges reuse status colours (Derby = Due soon amber)
  - [ ] Two DOM copies of every row (phone list + table); skeleton 6 columns vs 8–9 shown
  - [ ] First press on a sort column sorts descending (siblings ascend)
  - [ ] Pager scrolls `body`, not `<main>`
  - [ ] Header about twice EMS size; "+" icon and "From Date/To Date" differ from Health
  - [ ] Dark mode: bloodline tags keep light fills
- Verdict: Needs new components
- EMS patterns to introduce:
  - [ ] PageHeader
  - [ ] DataTable (card, page-size footer, one row markup for all widths)
  - [ ] Toolbar (search plus filters) inside the table card
  - [ ] row DropdownMenu
  - [ ] ConfirmDialog (shared)
  - [ ] Toast on save/delete/submit (replaces the banner)
  - [ ] ErrorState with retry
  - [ ] Sheet (side panel) for create/edit
  - [ ] Badge/StatusBadge
- Done: [ ]

### /performance/create — Add a performance record
- File: `app/Livewire/Performance/Form.php`, `resources/views/livewire/performance/form.blade.php`
- Purpose: Record one sparring session, derby, conditioning session or weigh-in for one bird.
- Layout: Console shell (crumb "Performance › New") → back link → x-anchor-block (eyebrow, H1, required-fields line) → 640px form: "The Event" (Bird, Date, Type), "How the Bird Did" (Result or a notice, Weight, Duration, star Rating), "Remarks" (internal only) → sticky Cancel/Save bar.
- Components present: x-anchor-block, x-form-select ×3 (Bird, Type of Event, Result); bespoke 5-star rating built from raw buttons; raw 3 `<input>`, 1 `<textarea>`; live inline validation with ARIA; flash → toast.
- Gaps:
  - [ ] 8-field create is a full page; EMS would use a Sheet (or FormDialog)
  - [ ] Hand-written fields; no shared field component
  - [ ] Conventions differ from the Health form (back-link casing, "Add a…", intro, subtitles, widths, placeholders)
  - [ ] Type of Event preselected as "Sparring"
  - [ ] Duration typed in seconds but listed as mm:ss
  - [ ] Weight, Duration and Date full width for short values
  - [ ] Tab title "Record an Event" vs the H1
  - [ ] Native validation bubbles (no `novalidate`; min/step); select and rating errors unlinked
  - [ ] Save always returns to /performance (loses the bird-timeline origin)
  - [ ] "Remarks" section holding one "Remarks" field; H1 wraps badly on phone; translucent bar
- Verdict: Needs new components
- EMS patterns to introduce:
  - [ ] Sheet (side panel) for create/edit
  - [ ] inline field errors (shared FormField; `novalidate`)
- Done: [ ]

### /performance/{record}/edit — Edit a performance record
- File: `app/Livewire/Performance/Form.php`, `resources/views/livewire/performance/form.blade.php`
- Purpose: Correct an existing performance record.
- Layout: As /performance/create, with crumb "Performance › Edit", H1 "Edit Performance Record" and primary "Save Changes"; values prefilled.
- Components present: As /performance/create (x-anchor-block, x-form-select ×3, star rating, raw fields with live inline errors; flash → toast).
- Gaps:
  - [ ] Full page; EMS would use a Sheet
  - [ ] Header doesn't name the record
  - [ ] Create's intro ("Everything else can be filled in later.") reused on edit
  - [ ] Title drops create's article; "Record" alone on a phone line
  - [ ] Create's gaps carry over (seconds input, full-width short fields, `novalidate`, unlinked errors, return to /performance)
- Verdict: Needs new components
- EMS patterns to introduce:
  - [ ] Sheet (side panel) for create/edit
  - [ ] inline field errors (shared FormField)
- Done: [ ]

### /breeding — Breeding records
- File: `app/Livewire/Breeding/Index.php`, `resources/views/livewire/breeding/index.blade.php`
- Purpose: Matings with their egg funnel, plus farm-wide eggs set, fertility rate and hatch rate.
- Layout: Console shell → x-anchor-block (matings figure, intro, "Record a Mating") → KPI `<dl>` (Eggs set, Fertility rate, Hatch rate) → filter bar (search, Mated from, Mated up to, Bloodline (sire)) → phone card list plus desktop table, or two empty states → pager.
- Components present: x-anchor-block, x-filter-bar, x-filter-select, x-empty-state ×2, x-band-dot, x-sort-control, x-table-skeleton (plus a phone block skeleton), Livewire pager; raw 1 `<button>`, 3 `<input>`, 1 `<table>`.
- Gaps:
  - [ ] Sire/dam dereferenced with no null guard — a page listing a mating with a deleted parent should 500 (page 1 survived; verify page 2)
  - [ ] KPI row renders stacked (`.sb-line` display:block beats `grid-cols-3`)
  - [ ] KPI row ignores the search and bloodline filters (logic)
  - [ ] Search ORs past the date and bloodline filters (logic, with a test)
  - [ ] No table card; toolbar loose above the table with three labelling styles
  - [ ] Row action is a bare "View"; phone cards give no cue that they open the record
  - [ ] No page-size control; double rules; unbalanced pager; one record alone on page 2
  - [ ] No loading on sort or paging; no ErrorState; skeleton 8 columns vs 7 shown below lg
  - [ ] Pager scrolls `body`, not `<main>`
  - [ ] Rates at 0–2 decimals; header case mix; phone cards drop the fertility rate
  - [ ] Search has no visible label or icon (sr-only only); placeholder 3.15:1
  - [ ] Header about twice EMS size; button aligned to the intro, not the title
- Verdict: Needs new components
- EMS patterns to introduce:
  - [ ] PageHeader
  - [ ] KPI row (x-stat-card)
  - [ ] DataTable (card, clickable rows, page-size footer)
  - [ ] Toolbar (search plus filters) inside the table card
  - [ ] TableSkeleton on sort and paging
  - [ ] ErrorState with retry
- Done: [ ]

### /breeding/create — Record a mating
- File: `app/Livewire/Breeding/Form.php`, `resources/views/livewire/breeding/form.blade.php`
- Purpose: Record a mating — sire and dam chosen from the farm or typed in as outside birds — with its date, egg counts and notes.
- Layout: Console shell (crumb "Breeding › New") → back link → x-anchor-block (eyebrow, H1, no description) → "The Pair" (explainer, Sire, Dam, each with optional outside-bird fields, Mating Date) → "Egg Results" (4 counts plus live Fertility/Hatch read-outs) → Notes (no heading) → sticky Cancel/Save bar.
- Components present: x-anchor-block, x-form-select ×2 (Sire, Dam) with a "Someone else's bird" disclosure; raw 5–9 `<input>`, 1 `<textarea>`, 1 `<button>`; live and cross-field validation; `aria-live` rate preview; flash → redirect to the record.
- Gaps:
  - [ ] Helpers beside three egg inputs on desktop, below the fourth
  - [ ] Hand-written fields; no shared field component
  - [ ] Required outside-parent names have no asterisk
  - [ ] Native validation bubbles (no `novalidate`); Sire/Dam errors unassociated
  - [ ] Title Case labels mixed with sentence case; "No data" at display size
  - [ ] Translucent sticky bar; Save far right of the fields; asterisk spacing drift
  - [ ] No anchor description; Notes has no heading
  - [ ] `save()` does not re-authorize (Health and Performance do)
- Verdict: Restyle only
- EMS patterns to introduce:
  - [ ] PageHeader
  - [ ] inline field errors (shared FormField; helpers below fields; `novalidate`)
- Done: [ ]

### /breeding/{record} — Breeding record (detail)
- File: `app/Livewire/Breeding/Show.php`, `resources/views/livewire/breeding/show.blade.php`
- Purpose: One mating, its hatch funnel, and registering the hatched chicks as bird records so the pedigree builds itself.
- Layout: Console shell (no topbar crumb) → back link → hand-written header (eyebrow, H1 "Sire × Dam", meta line, Edit on the right) → sire/dam cards → "The hatch" field rows → "Offspring" heading with "Register N chicks" → inline register panel → offspring table (phone list), or an empty state.
- Components present: x-band-dot, x-field-row ×5, x-empty-state, x-form-select ×2 (Sex, Pen, in the panel); `elev-1` link cards; raw 3 `<button>`, 2 `<input>`, 1 `<table>`; toast after registering.
- Gaps:
  - [ ] HTTP 500 once the sire or dam is deleted (logic fix with a test)
  - [ ] "Register N chicks" is an inline 4-field panel: no dialog, no focus move, no Escape; Cancel drops focus
  - [ ] Validation messages leak property names; the default hatch date can fail on its own
  - [ ] No breadcrumb; header hand-rolled instead of x-anchor-block
  - [ ] Section heads (~32px) nearly as large as the H1
  - [ ] Hatch facts stop at x≈976 while the cards and table run full width
  - [ ] Two date formats on one page; "×" dropped between the cards on phone
  - [ ] Link cards have no cue beyond the shadow
  - [ ] Detail actions differ from the bird page (outline Edit vs filled Edit plus Delete)
  - [ ] No breeding delete although the policy allows one (confirm deliberate)
- Verdict: Needs new components
- EMS patterns to introduce:
  - [ ] PageHeader
  - [ ] Breadcrumb
  - [ ] FormDialog (create/edit) for "Register chicks"
- Done: [ ]

### /breeding/{record}/edit — Edit a breeding record
- File: `app/Livewire/Breeding/Form.php`, `resources/views/livewire/breeding/form.blade.php`
- Purpose: Correct a mating's pair, date, egg counts or notes.
- Layout: As /breeding/create, with crumb "Breeding › Edit", H1 "Edit Breeding Record" and primary "Save Changes"; save returns to the record.
- Components present: As /breeding/create, with values prefilled and the read-outs computed live.
- Gaps:
  - [ ] Header and crumb don't name the pair; back link points at the list, not the record
  - [ ] Create and edit names not parallel ("Record a Mating" / "Edit Breeding Record"; "Save Breeding Record" / "Save Changes")
  - [ ] Option text "Tandang (KL-2001) - Kelso" (spaced hyphen beside a hyphenated band)
  - [ ] Read-out at 1 decimal place vs 2 on the list
  - [ ] Create's gaps carry over (helper placement, asterisks, `novalidate`, select ARIA, translucent bar)
- Verdict: Restyle only
- EMS patterns to introduce:
  - [ ] PageHeader (names the record)
  - [ ] inline field errors (shared FormField)
- Done: [ ]

### /mortality — Mortality records
- File: `app/Livewire/Mortality/Index.php`, `resources/views/livewire/mortality/index.blade.php`
- Purpose: Register of deaths with a cause breakdown; deleting a record (owner only) puts the bird back on the active list.
- Layout: Console shell → x-anchor-block (deaths this year as the figure, "N this month · N in total. For farm staff only.", "Record a Death") → banner after a delete → "Causes of death" ink bars → filter bar (Died on or after, Died on or before, Cause) → phone list plus desktop table, or two empty states → pager → owner-only delete dialog.
- Components present: x-anchor-block, x-filter-bar, x-filter-select, x-empty-state ×2, x-band-dot, x-sort-control ×2, x-table-skeleton (plus a phone skeleton), `d-progress` bars, Livewire pager; hand-written `d-modal`; inline banner; raw 5 `<button>`, 2 `<input>`, 1 `<table>`.
- Gaps:
  - [ ] The only row action is a red Delete ("Delete Record" on phone); no View or Edit; bird name not linked
  - [ ] No search; no filtered count; no "Showing x of y" footer
  - [ ] No table card; toolbar outside the table
  - [ ] Delete feedback is a non-dismissible banner, not a toast; delete resets to page 1
  - [ ] Delete dialog waits ~0.9s for the server; Title Case buttons ("No, Keep the Record")
  - [ ] No ErrorState or Retry
  - [ ] Phone Delete has no sr-only target
  - [ ] "1 mos"; zero-padded dates; header case mix
  - [ ] Hero is year-to-date while Breeding's is all-time; "For farm staff only." sits in the stat line
  - [ ] Skeleton 7/6 columns vs 5/4 shown below lg
  - [ ] Three names for one screen (tab, H1, command palette)
  - [ ] Phone: misaligned date inputs, double rule
  - [ ] Pager scrolls `body`, not `<main>` (once there are more than 15 records)
- Verdict: Needs new components
- EMS patterns to introduce:
  - [ ] PageHeader
  - [ ] KPI row (month / year / total; x-stat-card)
  - [ ] DataTable (card, count footer)
  - [ ] Toolbar (search plus filters) inside the table card
  - [ ] row DropdownMenu (View, then Delete last) or a row link
  - [ ] ConfirmDialog (shared)
  - [ ] Toast on save/delete/submit (replaces the banner)
  - [ ] ErrorState with retry
- Done: [ ]

### /mortality/create/{broodcock?} — Record a death
- File: `app/Livewire/Mortality/Form.php`, `resources/views/livewire/mortality/form.blade.php`
- Purpose: Record a bird's death in two steps — fill in and validate, then confirm by name; the bird is then marked deceased.
- Layout: Console shell (crumb "Mortality › New") → back link → x-anchor-block (eyebrow, H1, consequence intro) → "The bird" (find box plus "Which Bird Died?" select) → "When and why" (date, cause with a datalist) → "Afterwards" (disposal with a datalist, remarks) → sticky Cancel / "Record This Death" bar → confirm dialog.
- Components present: x-anchor-block, x-form-select ×1, native `<datalist>` ×2; hand-written `d-modal` confirm; raw 4 `<input>`, 1 `<textarea>`, 3 `<button>`; "Checking…"/"Saving…" labels; flash → toast on /mortality.
- Gaps:
  - [ ] 5-field create is a full page; EMS would use a FormDialog
  - [ ] Search box plus select instead of one combobox; dated "-- Choose a bird --"
  - [ ] Native datalists for cause and disposal (OS menus)
  - [ ] Empty submit shows browser bubbles (`required`, no `novalidate`)
  - [ ] Date helper beside the input on desktop
  - [ ] Date of death has no default (the other forms default to today)
  - [ ] Bird list silently capped at 200
  - [ ] No entry point from a bird page for /mortality/create/{broodcock}
  - [ ] Confirm dialog lacks `aria-describedby`; Title Case buttons
  - [ ] Title Case question labels under sentence-case sections; spaced hyphens as dashes; optional fields marked three ways
  - [ ] Translucent sticky bar; Save far right; truncated phone placeholder
- Verdict: Needs new components
- EMS patterns to introduce:
  - [ ] FormDialog (create/edit)
  - [ ] ConfirmDialog (destructive) as the shared second step
  - [ ] inline field errors (`novalidate`)
- Done: [ ]

### /reports — Reports
- File: `app/Livewire/Reports/Index.php`, `resources/views/livewire/reports/index.blade.php` (downloads: `app/Http/Controllers/ReportController.php`)
- Purpose: Owner and staff pick an optional date range, download any of five reports as CSV or PDF, and browse the audit trail of past generations.
- Layout: Console shell. Anchor block (eyebrow "Records", "Reports", hero count, intro) → "Date range" form in a 640px column (From and To stacked, "Last 12 months", "Clear") → "Download a report": five rows, each with outline "Download CSV" and "Download PDF" → "Report history" table with pager (stacks into blocks below `md`).
- Components present: `x-anchor-block`, `x-empty-state` (no action), `x-table-skeleton` (history paging only), vendor pager; raw `<table>`, two date `<input>`s, two `<button>`s, ten `d-btn d-btn-outline` download links; hand-rolled filter chips and per-cell table classes.
- Gaps:
  - [ ] No feedback on download (no loading or disabled state, no toast); the history never refreshes
  - [ ] Export links can carry the previous range while the date round trip is in flight
  - [ ] Reversed or malformed date range accepted with no inline error
  - [ ] Header has no action; hero "0" repeats the empty state; eyebrow "Records" vs nav group "Analyse"
  - [ ] Date range is a stacked standalone form, not a toolbar; two content widths on one page
  - [ ] Ten equal outline download buttons, no primary
  - [ ] History table hand-styled: no table card, no "Showing x to y of z" / page-size footer
  - [ ] Pager scrolls `body`, not `<main>` (user lands at the bottom of the next page)
  - [ ] Skeleton covers paging only and is not stacked on phone; stacked cells lose their headers
  - [ ] Copy: "Broodcock Inventory", "1 reports generated", raw ISO dates, history names ≠ report titles, Title Case report names, hyphen as dash
  - [ ] No ErrorState with retry
  - [ ] Public-site scroll-reveal and 450ms header stagger on a console screen
- Verdict: Needs new components
- EMS patterns to introduce:
  - [ ] PageHeader
  - [ ] Toolbar (date range + "Last 12 months")
  - [ ] DataTable (history, with pagination footer)
  - [ ] row DropdownMenu ("Download" → CSV / PDF)
  - [ ] Toast on download (or per-row progress)
  - [ ] inline field errors (date range)
  - [ ] TableSkeleton (stacked on phone)
  - [ ] EmptyState (keep; add an action)
  - [ ] ErrorState with retry
  - [ ] Badge/StatusBadge (format and filter chips)
- Done: [ ]

### /users — User accounts
- File: `app/Livewire/Users/Index.php`, `resources/views/livewire/users/index.blade.php`
- Purpose: The owner lists every account, filters by status, role and text, sorts, opens the edit page, and deactivates or reactivates accounts.
- Layout: Console shell. Anchor block (eyebrow "Admin", "User Accounts", count, intro, "Add user" on the right) → status segmented control on its own row → filter bar (search + Role listbox) → desktop table or phone stacked rows (or the empty state) → pager → deactivate ConfirmDialog.
- Components present: `x-anchor-block`, `x-segmented`, `x-filter-bar`, `x-filter-select`, `x-avatar`, `x-table-skeleton`, `x-empty-state`, vendor pager, `d-modal` ConfirmDialog; raw `<table>`, one search `<input>`, row buttons and links; `x-dropdown` exists but is unused.
- Gaps:
  - [ ] Row actions are a visible Edit / Deactivate pair on every row, not a ⋯ menu
  - [ ] Confirm dialog opens only after a server round trip (1.5 s locally) with no in-flight cue on the trigger; Escape also waits for the server
  - [ ] Toolbar split over two rows and not inside a table card; the empty state is carded while the table is not
  - [ ] No pagination footer (count, page size); doubled/stray rules around the pager; pager scrolls `body`, not `<main>`
  - [ ] Skeleton fixed at 4 columns (the table has 5–6) and taller than real rows
  - [ ] Two "Clear filters" controls on an empty result
  - [ ] Search has no visible label (placeholder 3.15:1); "Deactivate" 4.53:1 and "Active" ≈4.8:1, below the 7:1 console floor
  - [ ] Status control uses tab ARIA for a filter and shows no counts
  - [ ] Phone rows drop the contact number; "No position recorded" vs "Not recorded"
  - [ ] Copy: "User Accounts" vs "Users"; Title Case role labels; sr-only "Deactivate , name" stray space
  - [ ] Oversized header (eyebrow, ≈36px title, KPI numeral, intro); ~290px of chrome before the first row
  - [ ] No ErrorState with retry
- Verdict: Needs new components
- EMS patterns to introduce:
  - [ ] PageHeader
  - [ ] Toolbar (search + role + status, inside the table card)
  - [ ] DataTable (sticky actions column, pagination footer)
  - [ ] row DropdownMenu (Edit; Deactivate last)
  - [ ] ConfirmDialog (keep; open on the client)
  - [ ] Tabs (status, segmented with counts)
  - [ ] TableSkeleton (keep; match the columns)
  - [ ] EmptyState (keep)
  - [ ] Toast (keep)
  - [ ] ErrorState with retry
  - [ ] Badge/StatusBadge (keep)
- Done: [ ]

### /users/create — Add a user
- File: `app/Livewire/Users/Form.php`, `resources/views/livewire/users/form.blade.php` (rules: `app/Http/Requests/StoreUserRequest.php`)
- Purpose: The owner creates a sign-in account (there is no self-registration): details, role, active flag and password.
- Layout: Console shell. Top-bar breadcrumb "Users › New" → "← Back to user accounts" → anchor block (eyebrow "Admin", "Add a User", intro) → one 640px column: Person (5 fields), "What they are allowed to do" (3 role radio cards + "This person can sign in"), Password (2 fields) → full-width sticky bar (Cancel, Create Account).
- Components present: `x-anchor-block`; nine raw `<input>`s, each with a hand-written label / help / `@error` block; role radio cards; one submit `<button>` with spinner; no shared field component.
- Gaps:
  - [ ] Short admin form on a full page where EMS opens a dialog from the list
  - [ ] Dark mode: the preselected role card turns pale and its text unreadable (≈1.0:1)
  - [ ] Sticky bar 95% opaque (fields ghost through); its buttons sit 270–360px right of the form column
  - [ ] Three wayfinding devices (breadcrumb, back link, eyebrow) plus an intro
  - [ ] Required shown by a red `*` only (no `required`/`aria-required`); "Confirm Password" unmarked
  - [ ] One action named four ways ("Add user", "New", "Add a User", "Create Account"); Title Case labels, title, button and placeholder
  - [ ] Customer role copy says "broodcock profiles"
  - [ ] Field markup duplicated per field (no shared form field)
  - [ ] Password help wraps badly on phone ("— it is not emailed.")
  - [ ] No scroll-to-first-error on a long page with a sticky bar
- Verdict: Needs new components
- EMS patterns to introduce:
  - [ ] FormDialog (or Sheet) opened from the Users list
  - [ ] inline field errors (keep, via a shared form field)
  - [ ] Toast on create (keep)
- Done: [ ]

### /users/{user}/edit — Edit a user
- File: `app/Livewire/Users/Form.php`, `resources/views/livewire/users/form.blade.php` (shared with /users/create)
- Purpose: The owner edits an account's details, role, active flag and, optionally, password.
- Layout: As /users/create, prefilled: breadcrumb "Users › Edit", title "Edit {name}", optional password section, submit "Save Changes".
- Components present: as /users/create.
- Gaps:
  - [ ] Shared form gaps listed under /users/create (fix once in `resources/views/livewire/users/form.blade.php`)
  - [ ] Dark mode: the current role card renders blank
  - [ ] Unticking "This person can sign in" deactivates on Save with no confirmation (the list confirms the same action)
  - [ ] Own account: Role and "can sign in" look editable but are refused only after Save
  - [ ] h1 follows the unsaved name field ("Edit " when cleared)
  - [ ] Create page's intro reused on edit; breadcrumb ends "Edit", not the person's name
  - [ ] Address truncates in its single-line input on phone
  - [ ] "Save Changes" vs "Save changes" on Settings and Profile
- Verdict: Needs new components
- EMS patterns to introduce:
  - [ ] FormDialog (or Sheet) opened from the row DropdownMenu on /users
  - [ ] ConfirmDialog when "can sign in" is cleared
  - [ ] inline field errors (keep)
  - [ ] Toast on save (keep)
- Done: [ ]

### /settings — Farm profile
- File: `app/Livewire/Settings/Farm.php`, `resources/views/livewire/settings/farm.blade.php`
- Purpose: The owner edits the farm's public identity (name, phone, email, address, visiting hours, visitor note) shown on the public pages and in PDFs.
- Layout: Console shell. Anchor block (eyebrow "Settings", "Farm Profile", intro) → on `lg` two columns: a 640px form (Name; How the public reaches you; For visitors) and a sticky 18rem "On the public page" live preview → full-width sticky bar with "Save changes".
- Components present: `x-anchor-block`; `d-input` / `d-textarea` fields with help and inline errors (5 inputs, 1 textarea); hand-rolled preview `<dl>` in an `elev-1` card; one submit button with spinner.
- Gaps:
  - [ ] Static preview card lifted with `elev-1` and no visible border
  - [ ] Sticky bar 95% opaque (labels ghost through) and detached from the form; no Cancel / Discard
  - [ ] Required `*` and "(optional)" both used on one form; `*` is colour only
  - [ ] Eyebrow "Settings" vs nav group "Admin"; h1 "Farm Profile" vs tab title "Farm profile"
  - [ ] Sidebar probably keeps the old farm name until reload (verify in the browser)
  - [ ] Empty bordered `<dl>` when all four contact fields are blank
  - [ ] Oversized header and a long intro
- Verdict: Restyle only
- EMS patterns to introduce:
  - [ ] PageHeader
  - [ ] Toast on save (keep)
  - [ ] inline field errors (keep)
- Done: [ ]

### /profile — Your profile
- File: `app/Livewire/Profile/Edit.php`, `resources/views/livewire/profile/edit.blade.php`
- Purpose: Any signed-in user (owner, staff, customer) edits their own details and photo and changes their password; role and active flag are read-only.
- Layout: Console shell for every role (a customer's sidebar holds only "Catalogue"). Anchor block (eyebrow "Account", "Your Profile", intro) → 640px column: Photo (avatar, Choose / Change / Remove photo), Your details (5 fields), read-only Role badge, sticky "Save changes" bar → a separate "Change password" form (3 fields) with its own button; remove-photo ConfirmDialog.
- Components present: `x-anchor-block`; `d-btn` file label over an sr-only file input; `d-modal` ConfirmDialog (bound `d-modal-open`); `d-badge-info` role badge; nine raw inputs with inline errors; five buttons.
- Gaps:
  - [ ] Customers have no link to the page (the catalogue header offers only "Sign out")
  - [ ] Customers land in the staff console shell; on phone the top bar has no brand or title
  - [ ] Two save scopes on one page: sticky "Save changes" (details) vs inline "Change password"
  - [ ] ~150–190px dead gap above "Change password"
  - [ ] Role badge blue (info) here, grey on Users
  - [ ] Copy ignores the viewer ("set by the farm owner" to the owner; "What you do on the farm" to customers; "beside your name in the sidebar")
  - [ ] Upload help hard-coded and wrong ("JPG or PNG, up to 4 MB"); `accept="image/*"` wider than the server allows
  - [ ] Full Name and Email required but unmarked; Title Case labels and h1
  - [ ] Sticky bar 95% opaque and far right of the column
  - [ ] Remove-photo dialog closes on outside click and before the request finishes
  - [ ] Sidebar avatar and name probably stale after Save until reload (verify)
  - [ ] Password-mismatch message differs from the Users form
- Verdict: Rebuild layout
- EMS patterns to introduce:
  - [ ] PageHeader
  - [ ] Tabs ("Details" / "Password"), each with its own save
  - [ ] ConfirmDialog (keep; no outside-click close)
  - [ ] Toast (keep)
  - [ ] inline field errors (keep)
- Done: [ ]

### /design — Design system reference
- File: `app/Http/Controllers/DesignGalleryController.php`, `resources/views/design/gallery.blade.php`
- Purpose: Owner/staff-only live component reference with contrast ratios computed at render time; CLAUDE.md tells contributors to open it before touching UI.
- Layout: Console shell. Raw 30px header (not the anchor block) → ten stacked sections: Elevation, Colour (measured pairs, brand scale), Band tag, Controls, Status pills, Patterns (table, stat, empty, skeleton), daisyUI components, Primitives, Rationale; no in-page navigation.
- Components present: `x-band-tag`, `x-alert`, `x-info-card`, `x-avatar`, `x-progress`, `x-switch`, `x-breadcrumb`, `x-separator`, `x-sort-control`, `x-empty-state`; raw demo controls (29 `<button>`, 8 `<input>`, 2 `<select>`, 2 `<table>`, 1 `<textarea>`); a static modal; a tooltip demo.
- Gaps:
  - [ ] Omits the components the console is built from (anchor block, filter bar, filter/form select, segmented, table skeleton, band dot, toast, dropdown, tabs)
  - [ ] Exercises primitives no screen uses (info card, progress, switch, separator, breadcrumb)
  - [ ] Contradictory elevation guidance; the "static" card is drawn with `elev-1`
  - [ ] Swatches and ratios light-only; dark contrast undocumented
  - [ ] Dark: pagination current page invisible; modal scrim lighter than the page
  - [ ] Two table-header, two empty-state and two KPI-label styles; live screens diverge (Users table, segmented counts, menu active state)
  - [ ] Uppercase tracked micro-labels against the "never caps" rule; demo copy in Title Case and "click"
  - [ ] "Broodcock(s)" terminology
  - [ ] Phone: patterns table scrolls with no cue; pill rows and segmented control wrap
  - [ ] Polish: stretched Stats card, misaligned RADIO column, ~6px pagination chevrons, raw "d-badge-soft" label, invisible separator demo
  - [ ] Category tints reuse status colours
  - [ ] Header is not the shared page header; no in-page navigation for a 7,302px page
  - [ ] Access gate untested; page unlinked
- Verdict: Needs new components
- EMS patterns to introduce:
  - [ ] PageHeader
  - [ ] Toolbar (search plus filters)
  - [ ] DataTable (sticky actions column, pagination footer)
  - [ ] row DropdownMenu
  - [ ] FormDialog
  - [ ] Sheet
  - [ ] ConfirmDialog (live, not static)
  - [ ] Toast
  - [ ] TableSkeleton / CardSkeleton
  - [ ] EmptyState (one style)
  - [ ] ErrorState with retry
  - [ ] KPI row
  - [ ] Tabs
  - [ ] Badge/StatusBadge
  - [ ] Breadcrumb
  - [ ] inline field errors (keep)
- Done: [ ]

### /diagnostics — Diagnostics
- File: `app/Http/Controllers/DiagnosticsController.php`, `resources/views/diagnostics.blade.php`
- Purpose: Owner-only, read-only report of what the running server can do (PHP extensions, uploads, writable paths, storage disks, services, runtime), without printing secrets.
- Layout: Console shell. Anchor block (eyebrow "Owner only", "Diagnostics", failing-check count, 3-line intro) → `lg:grid-cols-2` grid of six rule-separated key/value sections.
- Components present: `x-anchor-block`; hand-rolled `<dl>` rows (`$rowClass`) with `d-badge-soft` success/error badges; plain red text for a failed disk; no forms, tables or buttons.
- Gaps:
  - [ ] Failing count ignores Uploads and Debug mode (view logic — a fix, not a restyle)
  - [ ] Count neither lists nor links the failing checks
  - [ ] Uploads values carry no badge; the failed disk is raw red text, not a badge
  - [ ] Soft error badge 4.48:1 in light (below AA); pass badges below the 7:1 console floor
  - [ ] No "Run checks again" control; no loading cue during synchronous disk probes
  - [ ] Paths break mid-word on phone
  - [ ] Mixed value casing ("loaded"/"MISSING", "ON"/"off", "yes"/"NO" — "MISSING" is test-pinned); developer-facing intro; eyebrow "Owner only"
  - [ ] Rows duplicate `x-field-row`
- Verdict: Restyle only
- EMS patterns to introduce:
  - [ ] PageHeader (with "Run checks again")
  - [ ] KPI row (failing per section)
  - [ ] Badge/StatusBadge (every check row)
  - [ ] ErrorState with retry (failed probe)
- Done: [ ]

### /user/confirm-password — Confirm password
- File: `resources/views/auth/confirm-password.blade.php` (Fortify view bound at `app/Providers/FortifyServiceProvider.php:38`; `resources/views/layouts/guest.blade.php`)
- Purpose: Re-check a signed-in user's password before a sensitive action; currently unused (no route uses `password.confirm`).
- Layout: Guest (sign-in) layout: farm photo on the left half (dropped on phone); right panel with logo, theme toggle, h1 "Confirm your password", a one-line lead, the Password field, a full-width "Confirm", and "← Back to the farm's website".
- Components present: one `<input>` (autofocus, inline `@error`), one `d-btn d-btn-primary`, theme toggle.
- Gaps:
  - [ ] Orphaned: reachable only by URL — wire it to a sensitive action or remove the binding
  - [ ] Boilerplate lead and a bare "Confirm"; never says what it protects
  - [ ] No loading state on submit; no show/hide toggle (the sign-in page has one)
  - [ ] Only exit is "Back to the farm's website" (staff bounce to the dashboard); no "back to where I was"
- Verdict: Restyle only
- EMS patterns to introduce:
  - [ ] inline field errors (keep)
- Done: [ ]

### / — Landing page
- File: `app/Livewire/Landing/Index.php`, `resources/views/livewire/landing/index.blade.php`
- Purpose: The farm's public front page: hero, an eight-bird preview, one real pedigree and the visiting details.
- Layout: Public shell (`layouts/catalog`: sticky header, footer). Hero (eyebrow, H1, lead, "See the birds" + "Arrange a visit", proof line; bird photo on the right when one exists) → "On the farm now" (bloodline pills, 2/3/4-column card grid, "See all N birds →") → dark "Why the pedigree matters" block (bird → sire/dam) → "Visit the farm" (photo, contact rows, "Plan a visit") → footer.
- Components present: `x-bird-card` (up to 8), `x-band-dot`, three `d-btn` anchors, `d-link` text links, pill links (`<a class="pill">`); inline copies of band-dot and field-row; no raw form controls.
- Gaps:
  - [ ] Phone tiles cut bird names to 1–3 letters
  - [ ] No fallback when no bird has a photograph (hero right half blank)
  - [ ] No empty state for a farm with no listed birds; the dark block's right column can render empty
  - [ ] Second primary button ("Plan a visit"); three labels for /visit; "Sign in" vs "Staff sign in"
  - [ ] Pill toggles are links with `aria-pressed`; no live count after filtering
  - [ ] Hero loads the original upload (up to 4 MB) as the LCP image
  - [ ] Inline duplicates of `x-band-dot` / `x-field-row` (140px vs 160px label column)
  - [ ] H1 still says "Broodcocks"; "parentage on record for 17" lacks a noun
  - [ ] Public shell: no skip link, brand name read twice, OS-driven dark mode without a toggle, stock-blue progress bar
- Verdict: Restyle only
- EMS patterns to introduce:
  - [ ] EmptyState (no birds listed / no photographs)
- Done: [ ]

### /catalog — Catalogue
- File: `app/Livewire/Catalog/Index.php`, `resources/views/livewire/catalog/index.blade.php`, `app/Livewire/Catalog/Browse.php`, `resources/views/livewire/catalog/browse.blade.php`
- Purpose: Browse and filter every on-farm bird; the customer's home after signing in (a customer's /dashboard redirects here).
- Layout: Public shell for guests and customers, console shell for staff. H1 + lead → hairline filter row (search; Bloodline / Type / Sort listboxes; "Available birds only"; count / "Searching…" / Clear filters; for-sale caption) → empty state or 2/3/4-column card grid → paginator.
- Components present: `x-filter-select` ×3 (custom ARIA combobox over a hidden native select), `x-bird-card` (up to 12), Livewire paginator override; 2 raw `<button>`, 2 raw `<input>`; three bespoke empty states.
- Gaps:
  - [ ] Cards go invisible after a second search or a sort (reveal vs Livewire morph; no `wire:key`)
  - [ ] Phone: names cut to 1–3 letters; filter values cut to "All …" / "Cocks …"
  - [ ] No profile/account link (or signed-in indicator) for customers in the public header
  - [ ] Combobox: `aria-activedescendant` on the listbox, unnamed listbox, Tab does not close it
  - [ ] No error state or retry (Livewire overlay on 500, native confirm on 419, silent when offline)
  - [ ] Text-only loading; stale cards stay up; Clear filters and paging silent
  - [ ] Filter row bespoke instead of `x-filter-bar`; empties bespoke instead of `x-empty-state`
  - [ ] Disabled "Available birds only" looks broken; its reason sits far away
  - [ ] Placeholder (3.15:1) is the only visible search label; field borders about 1.4:1
  - [ ] H1 about 32px (dead `lg:text-[2.4rem]`) vs 48–60px on sibling pages
  - [ ] Pager: tiny chevrons, half-styled, different phone pattern; count shown twice
  - [ ] Naming drift ("Our stock" / "Our gamefowl"; "Cocks and hens" vs Broodcock/Broodhen); hen alt text says "Broodcock"
  - [ ] Failed photo shows a broken-image icon (no fallback)
  - [ ] Staff shell: paginator scrolls `body` not `<main>`; no PageHeader
  - [ ] URL-only `class` filter with no control; bloodline case duplicates; unlocked `headingLevel`
- Verdict: Needs new components
- EMS patterns to introduce:
  - [ ] Toolbar (search plus filters) via `x-filter-bar`
  - [ ] CardSkeleton
  - [ ] EmptyState
  - [ ] ErrorState with retry
  - [ ] PageHeader (staff shell)
- Done: [ ]

### /about — About
- File: `resources/views/public/about.blade.php`
- Purpose: The farm's story and the bloodlines it keeps, with live counts.
- Layout: Public shell. Page header (eyebrow, H1, lead) → spread one (text beside a 4:5 photo) → spread two (two offset photos beside "Pedigree kept" / "Seen in person") → "Bloodlines kept here" ruled list → "See the N birds" / "Arrange a visit" → footer.
- Components present: `x-page-header`, `x-band-dot`, two `d-btn` anchors; no raw controls.
- Gaps:
  - [ ] Serves full-size originals (up to 4 MB) instead of thumbnails, two of them in a grid
  - [ ] Spreads keep their two-column grid when photos are missing (large voids)
  - [ ] About 130px of dead space under "Arrange a visit →" on phone
  - [ ] Copy: third catalogue-CTA variant, "Arrange a visit" twice, a sentence repeated on Terms
  - [ ] Counting logic duplicates the landing's (drift risk)
- Verdict: Restyle only
- EMS patterns to introduce:
  - [ ] None — verify tokens only
- Done: [ ]

### /visit — Visit us
- File: `resources/views/public/visit.blade.php`
- Purpose: How to arrange a visit, with the farm's contact details.
- Layout: Public shell. Two columns: page header with "Ring the farm" / "Send an email", contact list and the owner's note | sticky photo; below, "How a visit works" in three steps.
- Components present: `x-page-header` (actions slot), up to four `x-field-row`, two `d-btn` anchors (`tel:`, `mailto:`); no raw controls.
- Gaps:
  - [ ] New-tab "Open in Google Maps ↗" has no screen-reader notice
  - [ ] Photo fades in from opacity 0 (delays the largest paint)
  - [ ] Step labels use the display face and primary colour on non-headings (partly deliberate)
  - [ ] Eyebrow repeats the nav label; lead repeats the landing's visit text
  - [ ] Seeded phone number shows ungrouped (seed data)
- Verdict: OK
- EMS patterns to introduce:
  - [ ] None — verify tokens only
- Done: [ ]

### /privacy — Privacy notice
- File: `resources/views/legal/privacy.blade.php`
- Purpose: What the site collects, why, and who can see it.
- Layout: Public shell. Page header (eyebrow, H1, lead) → sticky "On this page" contents beside a 65ch article of six H2 sections (contents stacked above on phone).
- Components present: `x-long-form`, `x-page-header`; no raw controls.
- Gaps:
  - [ ] False sentence "Accounts exist only for the farm's own staff" (Customer accounts exist) — keep the pinned "only the owner can"
  - [ ] No effective / last-updated date
  - [ ] Contents links 40px (site uses 44px); no current-section state
  - [ ] Anchor offset (96px) may be shorter than the phone header
  - [ ] Eyebrow "Privacy" repeats the H1
- Verdict: OK
- EMS patterns to introduce:
  - [ ] None — verify tokens only
- Done: [ ]

### /terms — Terms of use
- File: `resources/views/legal/terms.blade.php`
- Purpose: What the listings mean and the terms for using the site.
- Layout: Public shell; the same long-form template as /privacy, six sections with links to /visit and /privacy.
- Components present: `x-long-form`, `x-page-header`; no raw controls.
- Gaps:
  - [ ] False sentence "Accounts are for the farm's staff"
  - [ ] `<strong>For sale</strong>` renders at 700 (no-bold rule)
  - [ ] Straight quotes around "Not recorded"; a phone line starting with an em dash
  - [ ] Sentence repeated from About
  - [ ] No effective date; 40px contents links; short anchor offset (shared with /privacy)
- Verdict: OK
- EMS patterns to introduce:
  - [ ] None — verify tokens only
- Done: [ ]

### /login — Sign in
- File: `resources/views/auth/login.blade.php`, `resources/views/layouts/guest.blade.php`
- Purpose: Sign in for staff and customers (accounts are created by the owner; no sign-up).
- Layout: Guest shell. Photo panel (lg and up) | form column: brand link and theme toggle → `<main>` with H1, lead, email, password (reveal, Caps Lock), "Keep me signed in", "Forgot password?", full-width "Sign in" → "← Back to the farm's website".
- Components present: 3 raw `<input>` and 2 raw `<button>` classed `d-input` / `d-checkbox` / `d-btn`; `x-brand-mark`, `x-theme-toggle`; inline `@error` messages with `role="alert"`.
- Gaps:
  - [ ] Sixth failed attempt lands on the stock 429 page (no wait time, no way back)
  - [ ] No submit loading / disabled state
  - [ ] Heavy double focus ring on inputs
  - [ ] Back link floats far below the form; theme toggle has no visible label
  - [ ] Field error red about 4.5:1 (under the 7:1 console floor)
  - [ ] Entrance motion runs about 1.06s (over the 300ms ceiling)
  - [ ] Brand and back links outside landmarks; brand name read twice; stale comments
- Verdict: Restyle only
- EMS patterns to introduce:
  - [ ] ErrorState with retry (branded throttle message with the wait time)
- Done: [ ]

### /forgot-password — Forgot password
- File: `resources/views/auth/forgot-password.blade.php`
- Purpose: Request a password-reset link by email.
- Layout: Guest shell. H1 + lead → status alert → email field → "Email password reset link" → "← Back to sign in"; the layout's "Back to the farm's website" below.
- Components present: 1 raw `<input>` (`d-input`), 1 raw `<button>` (`d-btn`), inline `@error`, a `role="status"` alert.
- Gaps:
  - [ ] No submit loading state
  - [ ] Status alert styled differently from login's (shared `x-alert` unused); tab title differs from the H1
  - [ ] Two stacked back links in different styles; split underline
  - [ ] Form block sits at a different height on each auth screen
  - [ ] Button copy "Email password reset link"
  - [ ] Mailer defaults to `log` (success shown, nothing sent) — config risk
- Verdict: Restyle only
- EMS patterns to introduce:
  - [ ] None — verify tokens only
- Done: [ ]

### /reset-password/{token} — Choose a new password
- File: `resources/views/auth/reset-password.blade.php`
- Purpose: Set a new password from the emailed link.
- Layout: Guest shell. H1 + lead → hidden token, email, new password (help text), confirm → "Save new password"; only the layout's back link.
- Components present: 4 raw `<input>` (1 hidden), 1 raw `<button>`; inline `@error`.
- Gaps:
  - [ ] No reveal toggle or Caps Lock warning on the new-password fields (login has both)
  - [ ] Autofocus on the pre-filled email instead of "New password"
  - [ ] No "Back to sign in" link
  - [ ] Password requirement stated twice
  - [ ] No submit loading state
- Verdict: Restyle only
- EMS patterns to introduce:
  - [ ] None — verify tokens only
- Done: [ ]

### Error 403 — Forbidden
- File: `resources/views/errors/403.blade.php`, `resources/views/components/error-page.blade.php`
- Purpose: Tell a viewer they may not open this page, and offer a way out.
- Layout: Standalone page, no shell: brand link → "Error 403" → H1 → two paragraphs → one action ("Back to the farm" / "Back to the dashboard").
- Components present: `x-error-page` (with `x-partials.head-meta`, `x-brand-mark`).
- Gaps:
  - [ ] Account wording shown to guests on stale sold/dead bird links — branch the copy
  - [ ] Add "Browse the catalogue" for guests and customers
  - [ ] Tone of "That one is not yours to open"
  - [ ] Dark theme not captured — verify
- Verdict: Restyle only
- EMS patterns to introduce:
  - [ ] None — copy branch and one extra action
- Done: [ ]

### Error 404 — Not found
- File: `resources/views/errors/404.blade.php`, `resources/views/components/error-page.blade.php`
- Purpose: Explain a missing page (including sold or removed birds) and point back.
- Layout: Standalone page, no shell: brand link → "Error 404" → H1 → two paragraphs → "Back to the farm" + "Browse the catalogue".
- Components present: `x-error-page` (with `x-partials.head-meta`, `x-brand-mark`).
- Gaps:
  - [ ] Signed-in 404 shows no signed-in context (identical to the guest page)
  - [ ] "Back to the farm" does not say where it goes
  - [ ] "Error NNN" eyebrow; block height shifts between error pages
  - [ ] Brand name announced twice
- Verdict: OK
- EMS patterns to introduce:
  - [ ] None — verify tokens only
- Done: [ ]

### Error 419 — Page expired
- File: `resources/views/errors/419.blade.php`, `resources/views/components/error-page.blade.php`
- Purpose: Explain an expired form and how to resubmit it.
- Layout: Standalone page, no shell: brand link → "Error 419" → H1 → two paragraphs → "Back to the farm" (filled) + "Go back" (outline).
- Components present: `x-error-page`, 1 raw `<button>` ("Go back").
- Gaps:
  - [ ] Make "Go back" the primary; "Back to the farm" secondary
  - [ ] Copy contradicts itself (reload vs typed input kept)
  - [ ] `history.back()` does nothing on a direct arrival
  - [ ] Livewire screens show a native `confirm()` instead of this page
- Verdict: Restyle only
- EMS patterns to introduce:
  - [ ] ErrorState with retry (Livewire 419 handler instead of the native confirm)
- Done: [ ]

### Error 429 — Too many requests
- File: none — Laravel's stock view (`vendor/laravel/framework/src/Illuminate/Foundation/Exceptions/Handler.php:956-971`); triggered via `config/fortify.php` and `app/Providers/FortifyServiceProvider.php`
- Purpose: Tell someone they hit the sign-in limit, how long to wait, and how to get back.
- Layout: Laravel's stock centred "429 | TOO MANY REQUESTS" line; no brand, no action.
- Components present: none of the app's.
- Gaps:
  - [ ] Add `errors/429` on `x-error-page` with the wait time and "Back to sign in"
  - [ ] Add `errors/4xx` and `errors/5xx` fallbacks
  - [ ] Fix the stale login comment about where the limit shows
  - [ ] Update `tests/Feature/Auth/AuthenticationTest.php` if the 429 behaviour changes
- Verdict: Needs new components
- EMS patterns to introduce:
  - [ ] ErrorState with retry (wait time + back to sign in)
- Done: [ ]

### Error 500 — Server error
- File: `resources/views/errors/500.blade.php`, `resources/views/components/error-page.blade.php`
- Purpose: Explain a server fault and what to do next.
- Layout: Standalone page, no shell: brand link → "Error 500" → H1 → two paragraphs → "Back to the farm".
- Components present: `x-error-page` (with `x-partials.head-meta`, `x-brand-mark`).
- Gaps:
  - [ ] Copy promises a reference number that does not exist
  - [ ] No "Try again" action
  - [ ] "whoever maintains this" names nobody
  - [ ] Livewire requests show Livewire's overlay instead, with no retry
  - [ ] `auth()->check()` can throw when the database is down
- Verdict: Restyle only
- EMS patterns to introduce:
  - [ ] ErrorState with retry
- Done: [ ]

### Error 503 — Down for maintenance
- File: `resources/views/errors/503.blade.php`, `resources/views/components/error-page.blade.php`
- Purpose: Tell visitors the records are briefly closed for an update.
- Layout: Standalone page, no shell: brand link → "Error 503" → H1 → two paragraphs → "Back to the farm".
- Components present: `x-error-page` (with `x-partials.head-meta`, `x-brand-mark`).
- Gaps:
  - [ ] No "Try again" (reload) action; "Back to the farm" leads to the same closed site
- Verdict: Restyle only
- EMS patterns to introduce:
  - [ ] ErrorState with retry
- Done: [ ]

## Order of work
1. Foundation (theme, tokens, base CSS, motion) — first decide the EMS-vs-GFMS conflicts listed in docs/frontend-analysis/REPORT.md ("Where EMS conflicts with this project's own rules"), and fix the two Blockers (bird delete crashes /health, /health/schedule, /breeding, /breeding/{record}; /catalog goes blank after a re-search) before any restyle
2. Shared wrappers (section 5)
3. App shell (section 6.1)
4. Screens marked Rebuild layout: /profile
5. Screens marked Needs new components: /broodcocks, /broodcocks/{broodcock}, /health, /health/schedule, /health/create, /health/{record}/edit, /performance, /performance/create, /performance/{record}/edit, /breeding, /breeding/{record}, /mortality, /mortality/create/{broodcock?}, /users, /users/create, /users/{user}/edit, /reports, /catalog, /design, Error 429
6. Screens marked Restyle only: /dashboard, /broodcocks/create, /broodcocks/{broodcock}/edit, /broodcocks/{broodcock}/pedigree, /breeding/create, /breeding/{record}/edit, /settings, /diagnostics, /user/confirm-password, /, /about, /login, /forgot-password, /reset-password/{token}, Error 403, Error 419, Error 500, Error 503
7. Screens marked OK: verify only — /visit, /privacy, /terms, Error 404

## Decisions (settled with the owner, 2026-11-05 — Part B follows these where they differ from the EMS spec)
- Components: build **every** EMS component in Part B section 5 as a Blade component (+ Alpine where it needs behaviour), not only the missing patterns.
- Colour: **keep the forest-green palette** (`config/gfms-brand.php` → `app.css`); EMS's blue primary is not used. EMS gradients/accents are drawn in the green.
- Size: **compact EMS sizes on desktop only** (28px controls, 12px text from the `lg` breakpoint up); **phones keep 44px targets and 16px inputs**.
- Effects: **allow EMS's bold titles, gradients and blurred dialog backdrops**. This reverses the "no font-bold / no gradients / no backdrop-blur" rule in `CLAUDE.md`, which must be updated in Part B's foundation step.
- Fonts: **switch to Inter** (replaces Lexend + Public Sans; registry figures keep tabular numbers). Update `CLAUDE.md` and the font-payload test with it.
- Status badges: **keep the current badge colours** (the five enums' `badgeClasses()`); use the EMS StatusBadge component shape, not its colour palette. Fix the red soft badges that measure 4.48:1.
- Theme: **Light / Dark / System menu** (EMS-style, in an account menu), still using `data-theme="light|dark"` underneath — never a daisyUI or EMS theme name.
- Copy: **adopt EMS copy rules** — sentence case everywhere, no eyebrow labels, no intro sentences, compact page titles. Visible copy is pinned by tests; change each test in the same commit as its wording.
- Bugs: the two Blockers, the vaccination due lists (on-farm birds only) and the logic bugs (26 vs 30 bird count, 75% vs 81.3% fertility, Breeding search ignoring filters, Diagnostics headline count) are fixed separately from the redesign, each with a test.
- Forms as modals: create/edit no longer navigates to a separate page. **Short forms open as a centred FormDialog** over their list (add/edit health record, add/edit performance record, record a death, add/edit user); **long forms open as a side-panel Sheet** (add/edit gamefowl — five sections plus photos — and add/edit breeding record with hatch registration). The existing `/…/create` and `/…/{id}/edit` URLs keep working: they open the list with the form already open, so links, bookmarks and tests do not break. Confirm dialogs stay modal and should open without waiting for a server round trip.
- Everything else from EMS is in scope: ⋯ row menus, DataTable cards with page size, top-right toasts with close, ErrorState with Retry, skeletons, compact PageHeader, breadcrumbs, KPI tiles, record search in the command palette, account menu with Light / Dark / System.

## Implementation notes — where GFMS departs from the EMS spec, and why
Kept current as Part B proceeds. Each line is a deliberate departure, not an omission.
- **Theme names and storage.** No `ems` / `ems-dark` daisyUI themes: daisyUI themes stay off and its colour names stay aliased to GFMS tokens (owner's decision; CLAUDE.md reserves `data-theme` for the toggle). `data-theme` is `light` or `dark`, and absent for System, so the CSS media query follows the OS live with no listener. The choice is stored as `gfms-theme`, not `theme`, so every existing visitor keeps the choice they already made.
- **Primary is the forest green**, not EMS blue (owner's decision). The EMS secondary pink and the `color-3/4/5` accents are not used: colour means bloodline.
- **Inter loads 400–700**, not 300–900: the UI uses 400, 500, 600 and 700 only (Part B §3), and FontPayloadTest forbids requesting a weight nothing is set in.
- **Compact sizes from `lg` up only** (owner's decision): `--size-field` is 0.28125rem (45px) below 1024px and EMS's 0.175rem (28px) above; the 44px base-layer floor and the 16px field font apply below `lg` only.
- **Nothing under 11px.** EMS's 9.5px, 10px and 10.5px text (KPI labels, field errors, sub-lines) is set at 11px — CLAUDE.md's floor, which the owner did not lift.
- **Muted text keeps the 7:1 console floor.** EMS's `text-gray-500` / `text-gray-700` pairs map to `text-muted-foreground` / `text-foreground`; stock palette classes stay banned (DesignSystemGuardTest).
- **Scrollbars** use the green ink ramp (`ink-200` light, `ink-500` dark) in place of EMS's `#d1d5db` / `#737373` — the same lightness, inside the palette.
- **No `clsx` / `tailwind-merge`** (a dependency change needs approval): wrappers merge with Blade's `$attributes->class()` and expose props (`variant`, `size`) for anything a caller would otherwise override with a class.
- **Icons are Lucide's own paths in Blade components** (ISC licence), not `lucide-react` — no new dependency, same glyphs.
- **daisyUI corrections live in `@layer utilities` under `:where()`**: daisyUI is nested inside `utilities` in this build, so they beat daisyUI while any wrapper utility still beats them.
- **The pressed-button reset is `translate: none` alone**: beside `transform: none`, Lightning CSS drops it from the production build.
