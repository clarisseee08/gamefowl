# Changes affecting the written thesis

Every place the implemented system differs from what Chapter 3 (and its
appendices) currently describe. Work through this before submission.

Each item says **what the paper probably says now**, **what to change it to**,
and **why** — so the revision can be defended, not just made.

---

## 1. Database: MySQL → PostgreSQL  *(Chapter 3, System Architecture)*

**Now:** MySQL, most likely described as part of a XAMPP stack.

**Change to:** PostgreSQL 17.6, hosted on Supabase, reached over the Postgres
wire protocol using PHP's `pdo_pgsql` driver.

**Why:** Supabase provides managed PostgreSQL. Nothing in the system uses MySQL.

**Also fix wherever these appear:**
- Any ER diagram or data dictionary listing MySQL types (`INT(11)`, `TINYINT(1)`,
  `VARCHAR(255)` defaults, `AUTO_INCREMENT`). PostgreSQL equivalents:
  `bigserial`, `boolean`, `varchar(n)`, `numeric(5,2)` for weights.
- Any statement that the database runs locally. It is remote, in
  **ap-northeast-1 (Tokyo)**.
- The `reports.parameters` column is **`jsonb`**, which has no MySQL equivalent.

---

## 2. Hardware and software requirements table  *(Chapter 3)*

**Now:** likely lists XAMPP (Apache + MySQL + PHP), a browser, and 4 GB RAM.

**Change to:**

| Item | Requirement |
|---|---|
| PHP | 8.2 or higher, **with the `pdo_pgsql` extension enabled** |
| Web server | Apache (XAMPP) or `php artisan serve` |
| Database | PostgreSQL 17 (hosted on Supabase — no local database server needed) |
| Node.js | 20+ (build-time only; not needed to run the system) |
| Composer | 2.x |
| Browser | Any current browser |
| Internet | **Required at all times** — the database is remote |
| RAM | 4 GB is sufficient |

**Two things to state explicitly:**

- **`pdo_pgsql` ships with XAMPP but is disabled by default.** Enabling it is a
  documented setup step. This is the single most common installation failure.
- **MySQL is not used**, even though XAMPP includes it. If the paper lists XAMPP,
  clarify that only Apache and PHP are used from it.

---

## 3. System architecture figure  *(Chapter 3)*

**Now:** likely a two- or three-tier diagram with a local MySQL database.

**Change to:** three tiers, with the data tier **off-site**:

```
   Client            Application (local)              Data (remote, Tokyo)
  ┌────────┐      ┌─────────────────────┐         ┌──────────────────────┐
  │Browser │─────▶│ Apache + PHP 8.2    │────────▶│ Supabase PostgreSQL  │
  │        │ HTTP │ Laravel 12          │ Postgres│ 17.6 (session pooler)│
  │        │◀─────│ Livewire 4 / Blade  │◀────────│                      │
  └────────┘ HTML │ Tailwind CSS 4      │  TLS    ├──────────────────────┤
                  └─────────────────────┘────────▶│ Supabase Storage     │
                                          HTTPS/S3│ (broodcock photos)   │
                                                  └──────────────────────┘
```

Points the figure should make:
- The application connects through Supabase's **session pooler** on port 5432,
  not to the database directly.
- The link is **TLS-encrypted** (`sslmode=require`).
- Photos go to object storage, not the database.
- **All authentication and authorization happen in the application tier.**

---

## 4. Class diagram additions  *(Chapter 3)*

The current diagram cannot support objectives (c) and (d). Four additions:

### 4.1 `sire_id` and `dam_id` on Broodcock — self-referencing

The diagram stores `bloodline` as a **string** and models no link between a bird
and its parents. A string is a *label*; you cannot answer "show me this bird's
grandsire" with it. Two nullable self-referencing associations are required, and
they are what the pedigree feature is built on.

> **Likely panel question:** *"How do you trace a bloodline?"*
> **Answer:** "Each bird records its sire and dam as links to other birds, so
> the system can walk the family tree three generations back. The bloodline text
> field is only a label."

### 4.2 New class: `PerformanceRecord`

The diagram declares the methods `managePerformanceRecords()` and
`viewPerformanceHistory()` but contains **no such class**. Objective (c) cannot
be met without it. Attributes: `event_date`, `event_type`, `weight`, `result`,
`duration_seconds`, `rating`, `remarks`.

### 4.3 New class: `MortalityRecord`

Objective (c) names mortality explicitly; the diagram omits it. One-to-one with
Broodcock. Attributes: `date_of_death`, `cause_of_death`, `disposal_method`,
`remarks`.

### 4.4 New class: `ActivityLog`

The Significance chapter promises a "digital audit trail" and nothing in the
diagram provides one. Every model change is recorded automatically.

### 4.5 A `sex` attribute on Broodcock

The diagram models only males, but **breeding requires a hen**. Rather than add
a separate Hen class, Broodcock carries a `sex` attribute.

> **Likely panel question:** *"Why is a hen also a 'broodcock'?"*
> **Answer:** "Hens and cocks share every attribute we record and both appear in
> pedigrees, so they are one entity with a sex attribute — that keeps the family
> tree in a single table instead of splitting it across two."

---

## 5. Age is not stored  *(data dictionary)*

If the data dictionary lists an `age` column, **remove it**. Age is calculated
from `date_hatched` whenever it is displayed.

> **Answer:** "A stored age would be wrong the next day, so we store the hatch
> date and calculate age on the fly."

---

## 6. Fertility and hatch rates are not stored  *(data dictionary)*

If `fertility_rate` or `hatch_rate` appear as columns, **remove them**. Both are
calculated from the egg counts.

State the formulas, because they will be asked:

- **Fertility rate** = fertile eggs ÷ eggs set
- **Hatch rate** = eggs hatched ÷ **fertile** eggs — *not* eggs set. Hatchability
  is a property of incubation; dividing by eggs set would double-count the
  infertility the fertility rate already reports.
- Farm-wide and per-bloodline rates are calculated from **summed totals**, never
  by averaging each mating's percentage — averaging would weight a 2-egg mating
  the same as a 200-egg one.

---

## 7. Authentication: not Supabase Auth  *(Chapter 3 + any security section)*

If the paper mentions Supabase, state plainly which parts are used:

- **Used:** PostgreSQL as the database; Supabase Storage for photos.
- **Not used:** Supabase Auth, Row Level Security, PostgREST, the JavaScript
  client, Realtime, Edge Functions.

**Why**, in one paragraph the paper can reuse: the application connects as a
database superuser, which bypasses Row Level Security entirely, so RLS policies
would be dead code that create a false impression of where security is enforced.
Authentication and authorization are therefore implemented in the application
layer — session-based login plus a permission check on every action — and are
verified by automated tests.

---

## 8. Scope & Limitations — additions  *(Chapter 1)*

Two limitations should be stated because a panel will find them:

1. **The system requires an internet connection at all times.** The database is
   remote; there is no offline mode. (This is probably already stated — make
   sure it says *at all times*, not just "for some features".)
2. **The hosting is a free tier that pauses after about a week of inactivity**
   and must be manually restored. Documented operational procedure, with a
   backup and a local-database fallback, mitigates it.

---

## 9. Testing / ISO 25010 chapter — evidence available

The system carries **447 automated tests**. Where the paper describes evaluation,
these can be cited as objective evidence alongside the user-survey results:

| ISO 25010 characteristic | Evidence in the codebase |
|---|---|
| **Functional suitability** | Feature tests covering each objective against seeded data |
| **Performance efficiency** | Tests asserting **query counts do not grow with data volume** on the broodcock list, pedigree, dashboard, pen list, catalogue and every report. The pedigree loads a full 3-generation tree in 4 queries. |
| **Security** | One test per role per protected action, asserting both the HTTP status *and* that the data did not change — including that a Record Keeper cannot delete by calling the action directly rather than merely having the button hidden |
| **Reliability** | Tests proving multi-table writes are atomic — recording a death sets the bird's status in the same transaction, and a forced failure leaves neither behind |
| **Maintainability** | Enforced conventions (`docs/conventions.md`), automatic formatting, thin components with business logic in Action classes |
| **Usability** | Empty states, plain-language errors, confirmation dialogs naming the record — best evidenced by the user survey rather than by tests |

Run `php artisan test` to reproduce the count.

---

## 10. Smaller wording checks

- **"Broodcock"** is used throughout for any bird, male or female. If the paper
  uses it to mean only males, add a sentence explaining the widened usage.
- **Band numbers are optional.** Birds are banded at a certain age, not at hatch,
  so a bird can exist without one. If the paper implies the band number is the
  primary identifier, soften that.
- **Deleting is restricted to the Owner**, and even then it is a *soft* delete —
  the record is hidden, never removed. If the paper says records can be deleted,
  clarify that nothing is ever erased.
- **There is no public registration.** All accounts are created by the Owner. If
  a use-case diagram shows a "Register" case for customers, remove it.
- **Mortality rate is reported as a clearly-labelled proxy.** The system cannot
  compute a true rate because it records no event when a bird is *sold or
  transferred out*, so there is no way to reconstruct a past flock size. If the
  paper promises a mortality rate, describe the denominator honestly — or note
  recording outward transfers as future work.
