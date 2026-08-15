# System Architecture

Digital Broodcock Farm Record Management System — SSGuad Game Farm.

---

## 1. Stack at a glance

| Layer | Choice | Version |
|---|---|---|
| Language | PHP | 8.2.12 |
| Framework | Laravel | 12.66.0 |
| Interactivity | Livewire (+ Alpine, bundled) | 4.4 |
| Styling | Tailwind CSS (CSS-first) | 4.x |
| Build | Vite | 7.x |
| Database | PostgreSQL, hosted on Supabase | 17.6 |
| Object storage | Supabase Storage (S3-compatible) | — |
| Audit log | `spatie/laravel-activitylog` | 4.12 |
| PDF export | `barryvdh/laravel-dompdf` | 3.1 |
| Tests | PHPUnit | 11.5 |

Everything is server-rendered PHP. There is no JavaScript framework and no
client-side application: Livewire sends HTML over the wire and Alpine handles
small interactions like the mobile menu. This matters because the thesis
describes the system as built with "PHP, HTML and CSS", and that description
stays literally true.

---

## 2. Why Supabase — but not Supabase Auth

A panel member familiar with Supabase will notice we use only part of it. This
is deliberate.

### What Supabase provides here

- **A managed PostgreSQL database.** That is the primary reason. The farm has
  no server administrator, and a managed database removes backup, patching and
  uptime from the researchers' responsibilities.
- **Object storage for broodcock photos**, reached through its S3-compatible
  gateway.

### What Supabase does NOT provide here

Supabase Auth, Row Level Security, PostgREST, the JavaScript client, Realtime
and Edge Functions are all unused.

### Why authentication lives in Laravel instead

**All authentication and authorization are enforced in the Laravel application
layer** — session authentication via Laravel Fortify, plus Laravel Policies for
every action.

The reasoning:

1. **Laravel connects to Postgres as the `postgres` superuser.** A superuser
   bypasses Row Level Security entirely. Writing RLS policies would therefore
   be *dead code* — it would look like a security layer while enforcing
   nothing, which is worse than having no layer at all because it creates a
   false impression of where security lives.
2. **Two authentication systems would mean two sources of truth.** Supabase
   Auth keeps its users in the `auth.users` table; our application needs
   `role`, `is_active`, `position` and a soft-delete history on the same
   record. Splitting a user across two tables in two systems invites them to
   disagree.
3. **Supabase Auth is designed to be called from a JavaScript client**, which
   this system does not have. Using it from a server-rendered PHP app means
   working against its grain for no benefit.

**Consequence to be aware of:** because Laravel connects as a superuser, the
database has no second line of defence. If application-layer authorization were
bypassed, the database would not stop it. That is why every Policy is tested —
there is one test per role per protected action, and the suite asserts both the
HTTP status *and* that the data did not change.

### A note on the two `users` tables

The database contains both `auth.users` (Supabase's own, unused) and
`public.users` (ours). They are in different schemas and never interact.
Laravel's `search_path` is `public`, so `User::query()` always resolves to
ours.

---

## 3. How `migrate:fresh` stays safe

This is worth documenting because it looks dangerous and is not.

Supabase keeps its own tables in the `auth`, `storage`, `realtime` and `vault`
schemas. Laravel's `config/database.php` pins `'search_path' => 'public'`, and
`PostgresBuilder::dropAllTables()` enumerates tables via
`getCurrentSchemaListing()`, which resolves to exactly that search path. The
generated SQL is therefore `drop table "public"."x", … cascade` and **cannot
reach another schema.**

This was verified two ways: by reading the framework source, and empirically —
after a `migrate:fresh`, the `auth` schema still had 23 tables, `storage` 8,
`realtime` 3 and `vault` 2, unchanged.

> **Do not add any Supabase schema to `search_path`.** Doing so would
> immediately put those tables inside `dropAllTables()`'s blast radius.

---

## 4. Connection topology

```
  Laravel (XAMPP, PHP 8.2, pdo_pgsql)
        │
        │  Postgres wire protocol, TLS (sslmode=require)
        ▼
  aws-0-ap-northeast-1.pooler.supabase.com : 5432      ← Supavisor, SESSION mode
        │
        ▼
  PostgreSQL 17.6  (project uieekjpnzxyrvwmfvlew, ap-northeast-1 / Tokyo)
```

Three decisions are load-bearing:

- **Session pooler, not the direct endpoint.** `db.<ref>.supabase.co` publishes
  only an AAAA record — it is IPv6-only without the paid IPv4 add-on, and most
  Philippine ISPs are IPv4-only. Verified by DNS lookup.
- **Port 5432 (session), not 6543 (transaction).** Transaction mode does not
  support prepared statements. PHP's `pdo_pgsql` names its statements from a
  per-connection counter that restarts at 1 on every request, so in transaction
  mode the names collide across pooled backends and Eloquent fails with
  `prepared statement "pdo_stmt_00000001" already exists`.
- **`aws-0`, verified rather than assumed.** Supabase runs more than one pooler
  generation per region. `aws-1-ap-northeast-1.pooler.supabase.com` also
  resolves, but rejects this project with `FATAL: Tenant or user not found`.
  The correct host was confirmed by test connection, not by copying docs.

**Every query is a network round trip to Tokyo.** That single fact drives the
performance rules in `docs/conventions.md`: every list is paginated, every
relation is eager-loaded, and `Model::shouldBeStrict()` is enabled outside
production so an accidental lazy load throws during development instead of
quietly costing a round trip per row.

---

## 5. Data model

```
                        ┌──────────┐
                        │  users   │──────┐ recorded_by / generated_by
                        └──────────┘      │ (nullOnDelete)
                                          ▼
   ┌────────┐      ┌──────────────┐   ┌──────────────────┐
   │  pens  │◀─────│  broodcocks  │──▶│ broodcock_photos │
   └────────┘ pen_id└──────────────┘   └──────────────────┘
                     │   ▲    ▲
       sire_id/dam_id└───┘    │ (self-referencing — the pedigree)
                              │
        ┌─────────────────────┼─────────────────────┬──────────────────┐
        ▼                     ▼                     ▼                  ▼
 ┌───────────────┐  ┌────────────────────┐  ┌──────────────────┐  ┌──────────┐
 │ health_records│  │performance_records │  │ mortality_records│  │ breeding │
 └───────────────┘  └────────────────────┘  │   (1:1, unique)  │  │ _records │
                                            └──────────────────┘  └──────────┘
                                                                   sire + dam
  ┌─────────┐        ┌──────────────┐
  │ reports │        │ activity_log │  (spatie — every model, every change)
  └─────────┘        └──────────────┘
```

### The four gaps this model closes

The thesis class diagram was incomplete. Each addition below exists because an
objective could not otherwise be met:

1. **`sire_id` / `dam_id` on `broodcocks`.** The diagram stored `bloodline` as a
   string and modelled no parent link. A string is a *label*, not traceability —
   you cannot answer "show me this bird's grandsire" with it. Objective (d)
   requires these two self-referencing keys.
2. **`performance_records`.** The diagram declared `managePerformanceRecords()`
   and `viewPerformanceHistory()` but contained no such class. Objective (c)
   cannot be met without the table.
3. **`mortality_records`.** Objective (c) names mortality explicitly; the
   diagram omitted it entirely.
4. **`activity_log`.** The Significance chapter promises a "digital audit
   trail"; nothing in the diagram provided one.

### Design rules enforced throughout

- **Nothing derived is stored.** Age comes from `date_hatched`; fertility and
  hatch rates come from the egg counts. A stored derived value is stale or
  self-contradictory the moment its inputs change. There is a test asserting no
  `fertility_rate` column exists, so this cannot be quietly reversed.
- **Every bird is a `Broodcock`, male or female.** Breeding needs a hen, and the
  thesis modelled only males. Adding a `sex` enum rather than a separate `hens`
  table keeps the pedigree on one table and lets `sire_id` and `dam_id` both
  resolve to the same model.
- **Soft deletes everywhere**, and `forceDelete()` returns `false` in every
  Policy. Nothing is ever removed permanently from a system whose selling point
  is an audit trail.
- **Constraints live in the database, not only in forms.** 20 CHECK constraints
  enforce the egg funnel (`hatched <= fertile <= set`), non-negative weights,
  the 1–5 rating range, that a bird is not its own parent, and that
  `next_due_date >= checkup_date`. Form Requests produce the readable message;
  the constraints are what guarantee the rule.

---

## 6. Application layering

```
  Route  →  Livewire component  →  Action  →  Model
              │                     │
              │                     └─ DB::transaction() for multi-table writes
              └─ Form Request rules (shared, so UI and API cannot drift)
                 Policy check (every action, server-side)
```

- **Livewire components orchestrate; they contain no business logic.**
- **Actions** (`app/Actions/`) hold anything that writes more than one table or
  encodes a domain rule — `RecordMortality` flips a bird's status in the same
  transaction as the mortality row; `GenerateOffspring` creates chicks and
  increments the parent record's counter under a row lock.
- **Policies** are the authorization boundary. Hiding a button in Blade is a
  courtesy, never the gate — the suite proves this by calling component methods
  directly, bypassing the UI entirely.
- **Reports** implement a `ReportDefinition` interface and describe a query
  only. CSV, PDF and the audit row are implemented once in `ReportController`,
  so no report can ship without them.

---

## 7. Photo storage

Photos are written to whichever disk `config('gfms.photo_disk')` names:

- `public` — local `storage/app/public`, needs `php artisan storage:link`.
  The default, so the system runs with no cloud credentials at all.
- `supabase` — the private `gfms-laravel` bucket over the S3-compatible
  gateway.

The bucket is **private**, so photos are never linked directly. They are
streamed through `BroodcockPhotoController`, which calls
`$this->authorize('view', $photo)` on every request — access is checked by a
Policy rather than by the URL being hard to guess.

Signed URLs also work (Supabase implements SigV4 query-string auth) and were
verified end to end against the real bucket: upload → signed URL → fetch →
delete. The streaming controller is preferred anyway because it is the only
option that runs the Policy.

---

## 8. Known limitations

Stated plainly, because a panel will find them:

- **The database superuser bypasses RLS**, so the application layer is the only
  enforcement boundary. Mitigated by exhaustive Policy tests, not by a second
  layer.
- **Free-tier Supabase projects pause after ~7 days of inactivity.** See the
  README — this is a live risk on a scheduled defense date.
- **Tests run against SQLite in memory**, not Postgres. This keeps the suite
  fast and offline, but the CHECK constraints are Postgres-only and are
  therefore not exercised by the suite; they are gated behind a driver check in
  the migrations. Application-layer validation covers the same rules in tests.
- **Photo upload has a soft per-bird cap** checked with a `SELECT`, so two
  simultaneous uploads could momentarily exceed it. It is farm policy, not a
  security invariant.
