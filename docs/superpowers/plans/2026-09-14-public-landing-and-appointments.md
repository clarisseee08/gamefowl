# Public Landing Page and Farm-Visit Appointments — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Give the farm a real public face — a shell with a footer, a landing page at `/` carrying the full filterable catalogue, and farm-visit appointments the owner works through in the console.

**Architecture:** The catalogue's browse UI is extracted into a nestable `Catalog\Browse` so `/` and `/catalog` share one implementation. `layouts::catalog` is upgraded in place into the real public shell, so every public page inherits the header and footer. Appointments are a new table with a public Livewire form and a staff-only review queue; nothing depends on email, because email does not work.

**Tech Stack:** Laravel 12, Livewire 4 (class components in `config/livewire.php`, `Route::livewire()`), Tailwind v4 CSS-first (`@theme{}` in `resources/css/app.css`, no config file), PHPUnit 11, SQLite in tests / Postgres in production.

**Spec:** `docs/superpowers/specs/2026-09-14-public-landing-and-appointments-design.md`

## Global Constraints

- **Nothing may depend on email.** `MAIL_*` are `sync: false` in `render.yaml` and unset; Laravel falls back to the `log` mailer and silently writes to stderr.
- **Colour means bloodline and nothing else.** No stock Tailwind palette classes (`blue-500`, `gray-600`, `slate-*`).
- **Verify every utility class against the built stylesheet.** Tailwind emits nothing and warns about nothing for an undefined class. The border token is `border-border`, not `border-hairline`.
- **Registry data is monospaced** — every date, count, band number gets `.datum`.
- **Catalog surface** (landing, catalogue, bird pages): 4.5:1 contrast, 17px body. **Console surface** (review queue): 7:1, 15px body, 44px touch targets, 16px inputs.
- No gradients, no `backdrop-blur`, no `font-bold`, nothing under 11px. Elevation only via `--shadow-e1/e2/e3`.
- **Visible copy is the test API.** Change copy and its assertion in the same commit.
- The CSS class vocabulary (`.badge-ok`, `.btn-primary`, `.input`) is a contract with five PHP enums. Restyle what a class resolves to; never rename it.
- `php artisan test` stays green (629 at the start of this work). `vendor/bin/pint --dirty`. `npm run build`, not `dev`.
- Migrations own the schema. Never alter a table in Supabase Studio.

---

### Task 1: Extract `Catalog\Browse`

The riskiest step, done first and alone. No new behaviour.

**Files:**
- Create: `app/Livewire/Catalog/Browse.php`
- Create: `resources/views/livewire/catalog/browse.blade.php`
- Modify: `app/Livewire/Catalog/Index.php` (becomes a thin wrapper)
- Modify: `resources/views/livewire/catalog/index.blade.php`
- Modify: `tests/Feature/CatalogTest.php`, `tests/Feature/CatalogCacheTest.php`, `tests/Feature/PublicCatalogueTest.php` — 22 `test(Index::class)` call sites move to `Browse::class`

**Interfaces:**
- Produces: `App\Livewire\Catalog\Browse` — public `$search`, `$bloodline`, `$sex`, `$class`, `$forSaleOnly`; computed `birds()`, `bloodlineOptions()`; nestable, sets no layout.
- `Catalog\Index` keeps route name `catalog.index` and its `ChoosesShellByViewer` layout choice.

- [ ] **Step 1:** Move the `#[Url]` properties, `mount()` authorize, `updating()`, `clearFilters()`, `hasActiveFilters()`, `birds()`, `bloodlineOptions()`, `sexOptions()`, `classOptions()` and `parentOptions` equivalents from `Index` into `Browse`. `Browse::render()` returns `view('livewire.catalog.browse')` with **no** `->layout()` and no `->title()`.
- [ ] **Step 2:** Move the entire body of `index.blade.php` into `browse.blade.php`. `index.blade.php` becomes `<livewire:catalog.browse />`.
- [ ] **Step 3:** `Index` keeps only `mount()` (authorize) and `render()` with `->layout($this->viewerShell())->title('Catalogue')`.
- [ ] **Step 4:** Update the 22 component test references from `Index::class` to `Browse::class`. Route-level assertions (`$this->get(route('catalog.index'))`) must stay untouched — they are the proof behaviour did not change.
- [ ] **Step 5:** `php artisan test --compact` — all green, same count.
- [ ] **Step 6:** Commit `refactor: extract the catalogue browse UI so two surfaces can share it`.

---

### Task 2: The public shell — header and footer

**Files:**
- Modify: `config/gfms.php` — add `farm.phone`, `farm.email`, `farm.hours`
- Modify: `.env.example`
- Modify: `resources/views/layouts/catalog.blade.php` — full header + new footer
- Create: `tests/Feature/PublicShellTest.php`

**Interfaces:**
- Produces: `config('gfms.farm.phone' | 'email' | 'hours' | 'address')`, each defaulting to `''`.
- Footer omits any value that is empty — never a blank row, never a dash.

- [ ] **Step 1:** Write `PublicShellTest`: the catalogue page shows the farm name; shows phone when configured; **omits the phone row entirely when the config value is empty**; shows a copyright line with the current year.
- [ ] **Step 2:** Run it — fails, no footer exists.
- [ ] **Step 3:** Add the three config keys and the `.env.example` block.
- [ ] **Step 4:** Header: farm mark + name, nav (Stock → `catalog.index`, Visit us → `/#visit`), Console for internal users, existing sign-in/sign-out. Footer: `border-t border-border`, contact block from config, nav, copyright.
- [ ] **Step 5:** Tests pass; full suite green.
- [ ] **Step 6:** Commit `feat: give the public pages a real header and the footer they never had`.

---

### Task 3: The landing page

**Files:**
- Create: `app/Livewire/Landing/Index.php`
- Create: `resources/views/livewire/landing/index.blade.php`
- Modify: `routes/web.php` — `/` becomes `Route::livewire('/', Landing\Index::class)->name('home')` for guests; internal users still redirect to `dashboard`
- Create: `tests/Feature/LandingTest.php`

**Interfaces:**
- Consumes: `Catalog\Browse` from Task 1.
- Produces: route name `home`.

- [ ] **Step 1:** Write `LandingTest`: a guest gets 200 at `/`; the farm name renders; the catalogue renders on it (a seeded bird's name is visible); filtering works from the landing; an internal user hitting `/` is redirected to `dashboard`; a query-count guard — rendering with 3 birds and with 11 birds issues the same number of queries.
- [ ] **Step 2:** Run — fails, no component.
- [ ] **Step 3:** Build `Landing\Index` (layout `layouts::catalog`, title the farm name) and the view: hero (farm name, one sentence, two anchor CTAs `#stock` and `#visit`), about section, `<livewire:catalog.browse />` under `id="stock"`, and a `#visit` section that at this point renders the farm's contact details from config.
- [ ] **Step 4:** Route `/` to it, keeping the internal-user redirect.
- [ ] **Step 5:** Tests pass; full suite green.
- [ ] **Step 6:** Commit `feat: a landing page that is the farm's front door, not a redirect`.

---

### Task 4: Appointments — the record

No UI. Model, enum, policy, factory, migration.

**Files:**
- Create: `database/migrations/<ts>_create_appointments_table.php`
- Create: `app/Models/Appointment.php`, `app/Enums/AppointmentStatus.php`, `app/Policies/AppointmentPolicy.php`, `database/factories/AppointmentFactory.php`
- Create: `tests/Feature/Appointments/AppointmentRecordTest.php`

**Interfaces:**
- Produces: `AppointmentStatus` — `Pending`, `Confirmed`, `Declined`, `Completed`; `label()`, `badgeClasses()` returning `badge-info` / `badge-ok` / `badge-alert` / `badge-neutral`.
- `Appointment` — `$fillable` name, contact_number, email, preferred_date, preferred_time, party_size, message, broodcock_id; casts `preferred_date` date, `status` enum, `handled_at` datetime; `broodcock()` belongsTo nullable, `handledBy()` belongsTo User; scope `pending()`.
- `AppointmentPolicy` — `viewAny`/`view`/`update` true for owner and staff, false for customer; guests denied by `auth` middleware.

- [ ] **Step 1:** Write `AppointmentRecordTest`: a created appointment defaults to `Pending`; `preferred_date` casts to a date; `broodcock()` resolves; deleting the bird nulls `broodcock_id` rather than deleting the appointment; the policy admits owner and staff and denies a customer; every status has a badge class defined in `app.css`.
- [ ] **Step 2:** Run — fails, no table.
- [ ] **Step 3:** `php artisan make:migration create_appointments_table`; columns exactly as the spec table; `foreignId('broodcock_id')->nullable()->constrained()->nullOnDelete()`; `foreignId('handled_by')->nullable()->constrained('users')->nullOnDelete()`; indexes on `status` and `preferred_date`.
- [ ] **Step 4:** Enum, model, policy, factory.
- [ ] **Step 5:** Tests pass; full suite green.
- [ ] **Step 6:** Commit `feat: record a request to visit the farm`.

---

### Task 5: The public request form

**Files:**
- Create: `app/Livewire/Appointments/RequestForm.php`
- Create: `resources/views/livewire/appointments/request-form.blade.php`
- Modify: `resources/views/livewire/landing/index.blade.php` — nest the form in `#visit`
- Create: `tests/Feature/Appointments/RequestVisitTest.php`

**Interfaces:**
- Consumes: `Appointment`, `AppointmentStatus` from Task 4.
- Produces: `Appointments\RequestForm` — public `$name`, `$contact_number`, `$email`, `$preferred_date`, `$preferred_time`, `$party_size`, `$message`, `$broodcock_id`, `$website` (honeypot); action `submit()`.

- [ ] **Step 1:** Write `RequestVisitTest`: a valid request is stored `Pending`; a past `preferred_date` is rejected; missing name rejected; missing contact number rejected; `party_size` of 0 and of 51 rejected; **a filled honeypot stores nothing yet shows the same success message**; the sixth submission within an hour is refused with a message naming the wait; a request carrying `broodcock_id` stores it.
- [ ] **Step 2:** Run — fails, no component.
- [ ] **Step 3:** Build the component. Rate limit with the `RateLimiter` facade **inside `submit()`**, keyed `appointment:{ip}`, 5 per hour — never `throttle` middleware, which would sit on Livewire's shared endpoint and throttle catalogue filtering on the same page. Honeypot field `$website`: non-empty means return the success message without writing.
- [ ] **Step 4:** Build the view; nest it in the landing's `#visit` section. Success copy says the farm will ring the number given — never that an email is coming.
- [ ] **Step 5:** Tests pass; full suite green.
- [ ] **Step 6:** Commit `feat: let a customer ask to visit the farm`.

---

### Task 6: The review queue

**Files:**
- Create: `app/Livewire/Appointments/Index.php`
- Create: `resources/views/livewire/appointments/index.blade.php`
- Modify: `routes/web.php` — `Route::livewire('/appointments', ...)->name('appointments.index')` inside the `auth`+`active` group
- Modify: `resources/views/components/app-sidebar.blade.php` — nav entry under Manage, `'internal' => true`
- Create: `tests/Feature/Appointments/ReviewQueueTest.php`

**Interfaces:**
- Consumes: everything from Tasks 4 and 5.
- Produces: route `appointments.index`; actions `confirm(int $id)`, `decline(int $id)`.

- [ ] **Step 1:** Write `ReviewQueueTest`: owner sees a pending request; the list defaults to Pending; `confirm()` sets status Confirmed and stamps `handled_by` and `handled_at`; `decline()` sets Declined; a customer gets 403; a guest is redirected to login; the bird's name shows when the request names one.
- [ ] **Step 2:** Run — fails, no route.
- [ ] **Step 3:** Build the component and console-surface view: table with status badges, filter by status defaulting to Pending, Confirm/Decline buttons. Dates and counts get `.datum`.
- [ ] **Step 4:** Route + sidebar entry.
- [ ] **Step 5:** Tests pass; full suite green.
- [ ] **Step 6:** Commit `feat: work through visit requests in the console`.

---

### Task 7: Verify and ship

- [ ] `php artisan test --compact` — full suite green.
- [ ] `vendor/bin/pint --dirty --format agent`.
- [ ] `npm run build` — confirm every new utility class resolves in the built CSS.
- [ ] Drive it in a browser: landing at 1440 and 390, submit a request, confirm it in the console. No console errors.
- [ ] Push, open the PR, merge when checks pass.
- [ ] **Do not run the migration on production.** It ships dormant; `RUN_MIGRATIONS=true` is a separate deliberate act.
