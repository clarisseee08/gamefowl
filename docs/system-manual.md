# System Manual — Digital Broodcock Farm Record Management System

Complete technical reference for the SSGuad Game Farm team.

**Everyone works on the same codebase and the same database.** This is a 1:1
mirror: the schema, the data, the accounts and the code are identical on every
machine. Read §11 before you run anything destructive, because on a shared
database a mistake is not local.

Verified against the live system on 16 August 2026.

---

## 1. What the system is

A records system for a gamefowl breeding farm. It tracks individual birds
(*broodcocks*) and everything that happens to them: where they are housed, their
health and vaccinations, their breeding results, their performance, and their
death. It produces printable reports from those records.

It is **not** a marketplace, an accounting system, or a public website. There is
one farm, three roles, and no public sign-up.

### The six things it does

| Area | What it holds |
|---|---|
| **Broodcocks** | The bird registry — identity, physical traits, parentage, status |
| **Pens** | Housing, with capacity tracking |
| **Health** | Vaccinations, medication, check-ups, treatment, deworming, with follow-up dates |
| **Breeding** | Matings, egg counts, fertility and hatch rates |
| **Performance** | Sparring, conditioning, weigh-ins, derbies |
| **Mortality** | Deaths, cause, disposal |

Plus **Reports** (five, as CSV and PDF), a **public catalogue** for customers,
and **User management** for the owner.

---

## 2. Technology stack

| Layer | Technology | Version | Why |
|---|---|---|---|
| Language | PHP | **8.2** | Pinned — see note below |
| Framework | Laravel | 12.x | The thesis specifies a PHP/HTML/CSS system |
| Interactivity | Livewire | 4.4 | Server-rendered reactivity, no separate JS app |
| Light JS | Alpine.js | bundled with Livewire | Menus, sheets, the command palette |
| Styling | Tailwind CSS | v4 | CSS-first config, no `tailwind.config.js` |
| Build tool | Vite | 7 | Compiles CSS/JS |
| Database | PostgreSQL | 17.6 (Supabase) | Hosted, no local install needed |
| Auth | Laravel Fortify | 1.38 | Headless auth — we supply our own screens |
| PDF | barryvdh/laravel-dompdf | 3.1 | Server-side PDF generation |
| Audit trail | spatie/laravel-activitylog | 4.12 | Who changed what, when |
| File storage | Flysystem S3 | 3.x | Supabase Storage for photos |
| Tests | PHPUnit | 11.5 | **Not Pest** — Pest needs PHP 8.3+ |
| Fonts | Fira Sans / Fira Code | self-hosted | No CDN dependency at a demo |

> **Why PHP 8.2 exactly.** The test framework is PHPUnit rather than Pest
> because Pest requires PHP 8.3 or newer, and this project targets 8.2. Running
> on 8.1 fails outright.

> **There is no JavaScript framework, no API, and no build step on the server.**
> Every page is rendered by PHP. This is a deliberate scope decision consistent
> with the thesis methodology, not a limitation discovered late.

---

## 3. Database

### 3.1 The tables

Nine application tables plus the audit log. Live row counts as of writing.

| Table | Cols | Rows | Holds |
|---|---|---|---|
| `users` | 16 | 3 | Accounts, roles, profile photo |
| `broodcocks` | 22 | 30 | The bird registry |
| `broodcock_photos` | 9 | 0 | Photos, one row per file |
| `pens` | 9 | 4 | Housing |
| `health_records` | 13 | 60 | Vaccinations and check-ups |
| `breeding_records` | 13 | 16 | Matings and egg results |
| `performance_records` | 13 | 40 | Sparring, derbies, weigh-ins |
| `mortality_records` | 10 | 3 | Deaths |
| `activity_log` | 12 | — | Audit trail, written automatically |

### 3.2 `broodcocks` — the central table

```
id, band_number, name, breed, bloodline, class, sex,
date_hatched, date_acquired, weight, color, comb_type, leg_color,
distinguishing_marks, status, sire_id, dam_id, pen_id, notes,
created_at, updated_at, deleted_at
```

Points worth understanding:

- **`band_number` is nullable.** Birds are banded at an age, not at hatch, so
  "not yet banded" is a real state and the interface says so rather than showing
  a blank.
- **`sire_id` and `dam_id` point back at `broodcocks`.** This self-reference is
  what makes the pedigree possible. Both nullable — most birds have no recorded
  parents.
- **`bloodline` is free text (`varchar(120)`), not a fixed list.** See §7.
- **`deleted_at`** means deleting a bird hides it rather than destroying it.

### 3.3 Fixed value lists (enums)

Enforced in PHP **and** by `CHECK` constraints in the database, so bad data
cannot be inserted even by a direct SQL query.

| Enum | Allowed values |
|---|---|
| `UserRole` | owner, staff, customer |
| `BroodcockStatus` | active, breeding, resting, retired, sold, deceased |
| `BroodcockClass` | class_a, class_b, ordinary |
| `Sex` | male, female |
| `HealthRecordType` | vaccination, medication, checkup, treatment, deworming |
| `PerformanceEventType` | sparring, conditioning, weigh_in, derby |
| `PerformanceResult` | win, loss, draw, na |

### 3.4 How the tables relate

```
users ──< health_records        (recorded_by)
      ──< breeding_records
      ──< performance_records
      ──< mortality_records
      ──< broodcock_photos      (uploaded_by)

pens  ──< broodcocks            (pen_id)

broodcocks ──< health_records
           ──< performance_records
           ──< broodcock_photos
           ──1 mortality_records  (one death per bird)
           ──< breeding_records   (as sire_id AND as dam_id)
           ──< broodcocks         (as sire_id / dam_id — the pedigree)
```

### 3.5 Connecting

PostgreSQL on Supabase, reached through the **session pooler**
(`...pooler.supabase.com`), not the direct host. The direct host is IPv6-only
and most Philippine connections are IPv4, where it fails with a timeout that
looks like the database being down.

Credentials live in `.env`, which is **never committed**. See
`docs/handover-setup.md`.

---

## 4. Who can do what

Three roles. Access is decided in **Policy classes** (`app/Policies/`), one per
record type — nine in total. Middleware only decides whether a request reaches a
screen; the real permission check runs per action.

| Action | Owner | Record Keeper | Customer |
|---|---|---|---|
| View catalogue | ✅ | ✅ | ✅ |
| View bird details, health, performance | ✅ | ✅ | ✅ |
| View internal notes | ✅ | ✅ | ❌ |
| Add / edit birds, health, breeding, performance | ✅ | ✅ | ❌ |
| Delete any record | ✅ | ❌ | ❌ |
| Record mortality | ✅ | ✅ | ❌ |
| Manage pens | ✅ | ✅ | ❌ |
| Run reports | ✅ | ✅ | ❌ |
| Manage users | ✅ | ❌ | ❌ |
| Edit own profile | ✅ | ✅ | ✅ |

**There is no public registration.** Every account is created by the owner under
**Admin → Users**. This is deliberate: it is a single farm's internal system.

---

## 5. How the code is organised

```
app/
  Enums/        7 fixed value lists
  Models/       9 Eloquent models — one per table
  Policies/     9 permission classes
  Livewire/     27 page components (the screens)
  Actions/      18 single-purpose operations (create, update, delete, store photo)
  Reports/      5 reports + a registry
  Http/         Controllers for dashboard, photos, report downloads
  Support/      BandTag — bloodline colour resolution

resources/
  views/
    livewire/   One Blade file per Livewire component
    layouts/    app (console), catalog (public), guest (auth)
    components/ Reusable pieces — sidebar, top bar, band tag, sparkline
    reports/pdf/ Six PDF templates
  css/app.css   The whole design system
  brand/        The logo source artwork

database/
  migrations/   15 files — these DEFINE the schema
  factories/    Test data generators
  seeders/      Sample data

tests/          37 test files, 494 tests
docs/           This document and the others
```

### The pattern to understand

A screen is a **Livewire component**: a PHP class in `app/Livewire/` plus a Blade
file in `resources/views/livewire/`. The class holds the state and the methods;
the Blade file renders it. When the user types or clicks, Livewire sends the
change to the server, re-runs the class, and swaps in the new HTML. There is no
separate front-end application.

Business operations live in **Actions** rather than in the components, so the
same operation can be called from a screen, a test, or a command without being
duplicated.

---

## 6. What each screen does

| Route | Screen | Notes |
|---|---|---|
| `/dashboard` | Overview | Flock counts, fertility trend, vaccination compliance, overdue list |
| `/broodcocks` | Bird registry | Search, 7 filters, sort, bulk select, column visibility |
| `/broodcocks/create` | Add a bird | Includes photo upload |
| `/broodcocks/{id}` | Bird detail | Tabs: overview, photos, health, performance, offspring |
| `/broodcocks/{id}/pedigree` | Family tree | Three generations, with completeness meter |
| `/catalog` | Public catalogue | Customer-facing browse, own layout, no sidebar |
| `/health` | Health records | All records, filterable |
| `/health/schedule` | Vaccination schedule | Overdue / due soon / scheduled |
| `/breeding` | Breeding records | Matings, egg counts, computed rates |
| `/performance` | Performance records | Sparring, derbies, ratings |
| `/mortality` | Death register | With cause breakdown |
| `/pens` | Pens | Capacity and occupancy |
| `/reports` | Reports | Five reports, CSV and PDF |
| `/users` | User management | Owner only |
| `/profile` | Own profile | Details, photo, password |
| `/design` | Design system | Internal reference for the interface |

---

## 7. Decisions worth being able to explain

These are the ones a panel is most likely to ask about.

### 7.1 Bloodline is free text, not a fixed list

`bloodline` is a `varchar(120)`. A keeper can type any bloodline, including one
nobody anticipated.

**The trade-off:** a fixed list would guarantee referential integrity but would
require a developer every time the farm acquires new stock. Free text keeps the
farm self-sufficient at the cost of losing a database-level guarantee.

**How the cost is managed:** `App\Support\BandTag` resolves a bloodline to a
colour in three tiers — a curated map for known stock, then a deterministic
`crc32` hash for anything else, then a neutral tone when nothing is recorded.
The same bloodline always produces the same colour, on every screen and every
machine, so a newly typed bloodline still renders correctly instead of appearing
unstyled.

### 7.2 Colour means bloodline

Each bird's band tag is coloured by bloodline, mirroring the physical anodised
leg band a gamefowl actually wears. Colour is never used decoratively.

Because three of the six band colours cannot carry white text (amber measures
2.08:1 against white — unreadable), each tag **computes** its own text colour
rather than assuming one. This also covers colours produced by the hash, which
are not known in advance.

### 7.3 Deleting hides, it does not destroy

Seven tables use soft deletes. A deleted bird keeps its health, breeding and
performance history and can be restored. Combined with the activity log, the
system can answer "who changed this, when, and what did it say before?"

### 7.4 The mortality rate states its own denominator

The report says in words what was divided by what, and states plainly that the
figure is a proxy rather than a true average-flock-size rate. A rate whose
denominator is not visible cannot be checked.

### 7.5 Fertility is computed from egg totals

Not by averaging each mating's percentage — that would let a 2-egg mating count
as much as a 200-egg one.

### 7.6 Photos are private

The storage bucket is not public. Photos are streamed through a controller that
authorises every request, so a photo URL cannot be shared to bypass permissions.

### 7.7 Performance is enforced by tests

Several tests assert the **number of database queries** a screen runs. If a
change introduces an N+1 query on the bird list, pedigree, dashboard or
catalogue, the test fails. Performance is a build guarantee, not a stopwatch
reading — which matters because the database is in Tokyo.

---

## 8. Reports

Five reports, each available as **CSV** (for Excel) and **PDF** (for printing):

1. **Broodcock Inventory** — the flock, filterable
2. **Health & Vaccination Compliance** — overdue, due soon, compliance rate
3. **Breeding Performance** — fertility and hatch rates, by bloodline
4. **Performance History** — events and results per bird
5. **Mortality** — deaths, causes, rate with its denominator stated

Every report prints the **exact filters it was generated with**. A report that
cannot state its own parameters cannot be defended or reproduced.

PDFs are rendered by dompdf, which is a **CSS 2.1 engine** — it cannot read
modern CSS. The PDF templates therefore read their colours from
`config/gfms-brand.php`, the same file the screen styling mirrors, so print and
screen cannot drift apart. A test asserts the two agree.

---

## 9. The interface

- **Console** (staff): full-height application shell, sidebar on the left, the
  content region as the only scrolling area. Built for one-handed use outdoors —
  **7:1 contrast**, 44px touch targets, 16px input text.
- **Catalogue** (customers): lighter, photo-led, no sidebar.
- **Registry data is monospaced** — band numbers, dates, weights, rates — so
  digits line up in a column.
- Brand green is **chrome only** (sidebar, logo, rails). It never appears on a
  button, because the system already uses red for mortality and overdue, and
  branded must never be confusable with urgent.

The `/design` route shows every component in every state, with contrast ratios
computed live.

---

## 10. Testing

```
php artisan test
```

**494 tests must pass.** They use a temporary in-memory database and never touch
real data, so this is safe to run at any time.

| Kind | What it protects |
|---|---|
| Behavioural | The "due today vs overdue" boundary, mortality denominators, pedigree counts |
| Authorization | Every role against every action |
| Query count | No N+1 on the heavy screens |
| Design guards | No stray colours, no centred layouts, no undefined CSS classes |
| Asset guards | Every image the views reference is actually in the repository |

If all 494 pass on your machine, your environment matches everyone else's. This
is the acceptance check.

---

## 11. Shared database — read this before running commands

Everyone points at the **same** Supabase database. There is no separation
between machines: what you change, everyone sees immediately.

### Commands that are safe

```
php artisan serve          Run the system
php artisan test           Safe — uses its own temporary database
php artisan migrate        Adds new tables/columns only
php artisan view:clear     Clears caches, touches no data
```

### The one command never to run

```
php artisan migrate:fresh      ❌ DROPS EVERY TABLE. ALL DATA. NO UNDO.
```

On a shared database this wipes the farm's records for everyone, instantly, with
no confirmation. **This already happened once during development**, when two
processes ran it against the same database at the same time and each destroyed
the other's work.

Also avoid `db:seed` on the shared database — it adds duplicate sample data on
top of the real records.

### Practical rules

1. Tell the group before running any `migrate` command.
2. Never run `migrate:fresh` or `db:seed` against the shared database.
3. If you want to experiment freely, ask for your own Supabase project. It is
   free and it removes the risk entirely.

---

## 12. Other documents

| File | What it covers |
|---|---|
| `docs/handover-setup.md` | Installing and running the system on a new machine |
| `docs/tech-stack.md` | Login accounts and the stack summary |
| `docs/architecture.md` | Deeper structural notes |
| `docs/conventions.md` | Coding conventions used throughout |
| `docs/thesis-defensibility.md` | What the paper still needs |
| `docs/deviations.md` | Where the build departed from the original spec, and why |
| `/design` (in the app) | The live interface reference |
