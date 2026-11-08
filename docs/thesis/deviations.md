# Deviations from the Build Specification

Every departure from the original spec, why it happened, and a one-sentence answer the
researchers can give a panel. Per §9 of the build brief.

Logged in the order they were discovered.

---

### Project was already scaffolded, not an empty folder

- **Spec said:** "You are building a capstone thesis system from scratch in this empty
  directory" and instructed running `composer create-project laravel/laravel .`
- **What I did instead:** Found `D:\gfms\gfms-laravel` already contained a complete,
  untouched `laravel/laravel` skeleton (Laravel **12.66.0**, default SQLite config, no
  Livewire, no auth scaffolding, no git repository). Skipped `composer create-project`,
  verified the skeleton was pristine, initialised git, and built forward from it.
- **Why:** Re-running `composer create-project` into a non-empty directory fails, and
  deleting an existing scaffold would have been destructive with no benefit — the
  skeleton was exactly what the command would have produced.
- **Defense answer:** "The Laravel skeleton was already in place and unmodified, so we
  version-controlled it as our baseline commit instead of regenerating it."

---

### PHP was missing the PostgreSQL driver entirely

- **Spec said:** "Laravel talks to the remote database directly over the Postgres wire
  protocol using the `pgsql` PDO driver, **which is already in PHP**."
- **What I did instead:** It was not. `php -m` showed only `pdo_mysql` and `pdo_sqlite`;
  `PDO::getAvailableDrivers()` returned `mysql, sqlite`. The XAMPP install ships
  `php_pdo_pgsql.dll`, `php_pgsql.dll` and `libpq.dll` but leaves them commented out.
  Backed up `C:\xampp\php\php.ini` to `php.ini.bak-gfms`, then uncommented
  `extension=pdo_pgsql`, `extension=pgsql`, plus `extension=zip` (Composer archive
  handling) and `extension=intl` (Laravel's `Number` helper and locale formatting).
- **Why:** Without `pdo_pgsql` there is no possible connection to Supabase — this was a
  hard blocker, not a preference.
- **Defense answer:** "PostgreSQL support ships with XAMPP but is disabled by default, so
  part of our documented setup procedure is enabling the `pdo_pgsql` extension in
  `php.ini`."

---

### Confirmed the Supabase pooler hostname empirically rather than trusting the spec

- **Spec said:** Use `aws-0-ap-northeast-1.pooler.supabase.com`, and separately warned
  "Verify hostnames, ports, and endpoints in the dashboard rather than assuming them."
- **What I did instead:** Both `aws-0-` and `aws-1-ap-northeast-1.pooler.supabase.com`
  resolve in DNS (they are generic regional load balancers), so DNS alone cannot identify
  the right one. Wrote a throwaway PDO probe that attempted authentication against all
  four combinations of `{aws-0, aws-1} x {5432, 6543}`. Result: `aws-0` on both ports
  authenticates; `aws-1` returns `FATAL: (ENOTFOUND) tenant/user
  postgres.uieekjpnzxyrvwmfvlew not found`. Kept `aws-0` port **5432** (session pooler).
- **Why:** Newer Supabase projects are provisioned on the `aws-1` pooler generation. Had
  this project been one of them, the spec's hostname would have failed with an error that
  looks like a wrong password. Verified instead of assumed.
- **Defense answer:** "We confirmed the exact pooler endpoint by test connection rather
  than copying it from documentation, because Supabase runs more than one pooler
  generation per region."

---

### Direct connection endpoint confirmed IPv6-only

- **Spec said:** The direct `db.<ref>.supabase.co` endpoint is IPv6-only without the paid
  IPv4 add-on and will time out on IPv4-only ISPs.
- **What I did instead:** Nothing — but verified the claim.
  `Resolve-DnsName db.uieekjpnzxyrvwmfvlew.supabase.co` returns **only an AAAA record**
  (`2406:da14:25a:5800:...`) and no A record. The spec was correct; recording the evidence
  because it justifies the pooler choice in the written thesis.
- **Why:** Documented so the researchers can defend the architecture choice with evidence.
- **Defense answer:** "The direct database endpoint publishes only an IPv6 address, so we
  connect through Supabase's IPv4 session pooler."

---

### `DB_SSLMODE` is env-driven in Laravel 12.66 (spec was right, verified)

- **Spec said:** Put `DB_SSLMODE=require` in `.env`.
- **What I did instead:** Confirmed before relying on it. `config/database.php:99` reads
  `'sslmode' => env('DB_SSLMODE', 'prefer')`, so the `.env` key is honoured. (In some
  Laravel releases this value is hard-coded, which would have made the `.env` line dead
  config.) No change needed.
- **Why:** A silently-ignored TLS setting is a security finding, not a cosmetic one.
- **Defense answer:** "We verified that our `DB_SSLMODE=require` setting actually reaches
  the PDO connection rather than assuming the framework reads it."

---

### Livewire is at version 4, not 3 — and the spec's install command does not exist

- **Spec said:** Install the official Livewire starter kit via
  `php artisan install:livewire`.
- **What I did instead:** That command does not exist in Laravel 12.66 — the only
  `install:` commands shipped are `install:api` and `install:broadcasting` (verified in
  `vendor/laravel/framework/src/Illuminate/Foundation/Console/`). Independently confirmed
  via `composer show --available` that **Livewire's current stable is v4.4.0**, not v3.
- **Why:** The spec was written against older Laravel/Livewire knowledge.
- **Defense answer:** "We used the installation path current for Laravel 12 and Livewire 4
  rather than a command from an earlier release."

---

### Rejected Laravel Breeze — it would have downgraded the project

- **Spec said:** Use the starter kit's Tailwind and auth scaffolding.
- **What I did instead:** Did **not** use `laravel/breeze`. Breeze's Livewire stack pins
  `livewire/livewire:^3.6.4` and `livewire/volt:^1.7.0`, and installs **Tailwind v3**
  (`tailwindcss ^3.1.0` + `autoprefixer` + `postcss`) along with stub `tailwind.config.js`
  and `postcss.config.js` files. This project ships **Tailwind v4** via the
  `@tailwindcss/vite` plugin. Running Breeze would have actively downgraded both Tailwind
  and Livewire. The official `laravel/livewire-starter-kit` repo was also unusable: its
  `main` branch requires PHP `^8.3` and Laravel `^13.17`, and it has no 12.x branch — this
  machine runs PHP 8.2.12.
- **Why:** Correctness. Following the spec literally would have downgraded two major
  dependencies and introduced a build layer the thesis does not describe.
- **Defense answer:** "The pre-built starter kits target a newer PHP and an older Tailwind
  than our environment, so we wired the same authentication features up directly."

---

### Tailwind v4 (CSS-first), so there is no `tailwind.config.js`

- **Spec said:** Listed `tailwind.config` among the shared single-writer files to protect
  from concurrent edits.
- **What I did instead:** No such file exists and none will be created. Tailwind v4
  configures itself from CSS: `resources/css/app.css` uses `@import 'tailwindcss'` with
  `@source`, `@theme` and `@utility` directives instead of a JavaScript config object and
  a `content: []` array. There is no `postcss.config.js` and no `autoprefixer` dependency.
- **Why:** Following the installed toolchain, not the document.
- **Defense answer:** "Tailwind version 4 is configured through CSS directives rather than
  a JavaScript config file, so our theme customisation lives in `resources/css/app.css`."

---

### Tests are PHPUnit, not Pest

- **Spec said:** "Write Pest/PHPUnit feature tests."
- **What I did instead:** Used **PHPUnit 11.5** throughout, which is what the skeleton
  ships (`phpunit/phpunit: ^11.5.50` in `require-dev`; no `vendor/pestphp`, no
  `tests/Pest.php`). Tests extend `Tests\TestCase`. Note that Livewire 4's
  `make:livewire --test` flag generates a *Pest* test file that will not run here, so
  Livewire component tests are written by hand in PHPUnit style.
- **Why:** Adding Pest would mean a second test runner for no benefit; the spec allowed
  either.
- **Defense answer:** "We used PHPUnit, the test framework Laravel ships with, so there is
  a single test command for the whole project."

---

## Deviations found during implementation

Everything above was found during setup. What follows was found while building —
including several defects the test suite caught.

---

### Pedigree eager-loading made the page slower, not faster

- **Spec said:** "Eager-load the three levels; do not N+1 it."
- **What I did instead:** Followed that literally at first —
  `with(['sire.sire.sire', 'sire.sire.dam', 'dam.dam.dam', ...])`, 14 relation
  paths. A query-count test measured **45 queries** for one pedigree page.
  Laravel issues **one query per relation path**, so nested eager loading cannot
  help a binary ancestor tree — it is 2 + 4 + 8 = 14 queries by construction, no
  better than walking it lazily. Rewrote it to load the tree **breadth-first**:
  collect the parent ids of the current level, fetch that whole level in one
  `whereIn`, repeat. Now **4 queries**, regardless of how complete the pedigree is.
- **Why:** Performance efficiency is an ISO 25010 characteristic, and every query
  is a round trip to Tokyo. `PedigreePerformanceTest` asserts the query count, so
  the regression cannot return silently.
- **Defense answer:** "Nested eager loading issues one query per relation path, so
  we load the family tree one generation at a time instead — four queries instead
  of forty-five, and a test fails if anyone changes it back."

---

### Livewire computed properties only cache on property access

- **Spec said:** Nothing — this is a framework subtlety.
- **What I did instead:** Blade templates initially called `$this->generations()`
  and `$this->bird()` as methods. A `#[Computed]` property is cached only when
  read as a **property**; calling it as a method re-runs the query every time.
  Fixed across the pedigree and detail views.
- **Why:** Found by the same query-count test. It was multiplying every page's
  query count by the number of times the template referenced the value.
- **Defense answer:** "Livewire caches a computed property only when you read it
  as a property, so our templates read them that way."

---

### PostgreSQL-only SQL had to be made portable for the test suite

- **Spec said:** "Database-level check constraints, not just form validation."
- **What I did instead:** Kept all 20 CHECK constraints, but wrapped the raw
  `ALTER TABLE … ADD CONSTRAINT` statements in
  `if (DB::getDriverName() === 'pgsql')`. SQLite — which `phpunit.xml` uses for
  the in-memory test database — cannot add a constraint through `ALTER TABLE`, so
  the schema could not be built for tests at all. Separately, search scopes moved
  from `ilike` (PostgreSQL-only) to
  `whereLike($col, $term, caseSensitive: false)`, which Laravel 12 compiles to
  `ILIKE` on PostgreSQL and `LIKE` on SQLite.
- **Why:** Without this the entire suite could not run. The constraints are still
  real in production; Form Request validation enforces the same rules in tests.
- **Defense answer:** "The database constraints are PostgreSQL-specific, so they
  are applied only on PostgreSQL — our tests run on SQLite for speed, where the
  same rules are enforced by the application's validation."

---

### Soft-deleted mortality rows still occupied the unique index

- **Spec said:** `mortality_records.broodcock_id` is unique, and soft deletes are
  used everywhere.
- **What I did instead:** Those two rules conflict. The unique index is a plain
  index, so a soft-deleted row **still occupies the slot** — once a mortality
  record was deleted, that bird's death could never be recorded again: validation
  would pass and the INSERT would then hit the constraint. `RecordMortality` now
  revives the trashed row instead of inserting, and the uniqueness rule is scoped
  with `->whereNull('deleted_at')`.
- **Why:** Correctness — without it, "delete" was not actually reversible.
- **Defense answer:** "A soft-deleted row still occupies a unique index, so
  re-recording a death restores the previous record rather than inserting a
  duplicate the database would reject."

---

### `nullOnDelete()` does not fire on a soft delete

- **Spec said:** "Every FK gets … the right `onDelete` behavior."
- **What I did instead:** A soft delete never issues a `DELETE`, so the foreign
  key action never runs and `pen_id` would keep pointing at a hidden row. Deleting
  a pen now explicitly nulls its birds' `pen_id` and soft-deletes the pen inside
  one transaction.
- **Why:** Otherwise the confirmation dialog's promise — "the birds in this pen
  will become unassigned" — was simply false.
- **Defense answer:** "Foreign key actions only run on a real delete, so our soft
  delete unassigns the birds explicitly rather than relying on the database."

---

### Livewire type coercion turned "no value" into a meaningful zero

- **Spec said:** Pen capacity is an integer.
- **What I did instead:** Held it as a string on the form component. Livewire
  coerces `null` back to `0` when rehydrating a typed `int` property, so clearing
  the capacity box silently became "capacity 0" — which in this domain means *no
  limit*. The `required` rule never fired and the pen quietly lost its limit.
- **Why:** An empty box must stay distinguishable from a deliberate zero.
- **Defense answer:** "A typed integer property let the framework turn 'the user
  erased the limit' into 'this pen has no limit' without an error, so the field is
  validated as text and converted once on save."

---

### A stale model instance could leave a bird with no primary photo

- **Spec said:** Exactly one photo per bird is primary.
- **What I did instead:** Found by a test that promotes photos in rotation and
  asserts after *every* promotion that exactly one row carries the flag. With a
  stale instance `is_primary` was already `true` in memory, so Eloquent's dirty
  check wrote nothing while the previous primary was still demoted — leaving the
  bird with **zero** primary photos. Fixed with a `refresh()` before the swap.
- **Why:** A real bug, caught only because the test checked the invariant after
  each step rather than once at the end.
- **Defense answer:** "Eloquent skips a write when the model already holds the new
  value, so the record is refreshed before the swap to guarantee exactly one
  primary photo."

---

### Flash messages are invisible to a Livewire update

- **Spec said:** Confirmation and feedback on destructive actions.
- **What I did instead:** Several modules hold their status message on the
  component instead of `session()->flash()`. A Livewire update re-renders only
  that component, not the layout that prints flash messages, so a flashed
  confirmation would not appear until the next full page load. Forms that redirect
  still use flash, because a redirect *is* a full page load.
- **Why:** Feedback the user never sees at the moment they act is not feedback.
- **Defense answer:** "A Livewire update re-renders only the component, so in-place
  confirmations are rendered by the component itself; flash messages are used only
  where the action ends in a redirect."

---

### Report filters were implemented, tested, and unreachable

- **Spec said:** Each report is parameterised by date range and filters.
- **What I did instead:** `ReportController` narrowed the request with an explicit
  `$request->only([...])` allow-list, and four keys were missing from it
  (`compliance`, `broodcock_id`, `sire_id`, `dam_id`). Those filters worked and had
  passing tests but could never be supplied over HTTP. Two independent report
  authors reported it; the allow-list was widened and now carries a comment saying
  to keep it in sync.
- **Why:** A tested feature no user can reach is not a feature. The allow-list
  itself stays — a report must never receive an arbitrary request key.
- **Defense answer:** "Report filters are restricted to an explicit allow-list so
  no unexpected input reaches a query; the list had gaps, which our integration
  tests now cover."

---

### Aggregate rates are computed from totals, never averaged

- **Spec said:** "`fertility_rate` and `hatch_rate` are computed accessors."
- **What I did instead:** Extended that rule to every aggregate. A farm-wide or
  per-bloodline rate is `SUM(fertile) / SUM(set)`, never the mean of each mating's
  percentage — averaging percentages weights a 2-egg mating the same as a 200-egg
  one. Asserted by test in three separate places: 2/2 fertile plus 50/100 fertile
  is **51.0%**, not the naive **75%**.
- **Why:** The naive figure is not a rounding difference; it is a different and
  wrong number, and exactly what a panel statistician would probe.
- **Defense answer:** "Farm-wide rates are calculated from the total eggs, not by
  averaging each mating's percentage, because that would let a two-egg mating count
  as much as a two-hundred-egg one."

---

### "No data" is reported as null, never as zero

- **Spec said:** Nothing explicit.
- **What I did instead:** Every rate returns `null` rather than `0` when its
  denominator is zero, and the UI renders that as "No data" or a dash. A bird with
  no contests has a win rate of *null*, not 0%; a month with no matings shows a
  dash, not a 0% bar.
- **Why:** "Never competed" and "lost every fight" are completely different claims
  about a bird, and a customer buying breeding stock cares about the difference.
- **Defense answer:** "A missing figure is displayed as 'no data' rather than zero,
  because a bird that never competed is not a bird that lost every fight."

---

### The mortality rate uses a stated proxy denominator, not an invented one

- **Spec said:** "Mortality (rate by period, cause breakdown)."
- **What I did instead:** A true mortality rate needs an average flock size over
  the period, and the schema **cannot** reconstruct one: it records a dated event
  when a bird hatches and when it dies, but **none when a bird is sold or
  transferred out**, so no past-day headcount is recoverable. Rather than invent a
  denominator or silently present a count as a rate, the report uses *deaths in
  range ÷ (birds on the farm today + birds that died within the range)*, labels it
  "Mortality Rate (proxy)", and prints the denominator in words on the PDF itself.
- **Why:** Presenting a count as a rate, or dividing by a made-up number, unravels
  under a single panel question.
- **Defense answer:** "We report a clearly-labelled proxy rate and state its
  denominator on the report itself, because the system does not record when a bird
  leaves the farm, so a true average flock size would be a guess."

---

### Report templates cannot use Tailwind at all

- **Spec said:** PDF export via `barryvdh/laravel-dompdf`.
- **What I did instead:** Wrote the five report templates in hand-written CSS 2.1
  with table-based layout, sharing one `_layout.blade.php`. dompdf supports neither
  flexbox nor CSS grid and cannot parse Tailwind v4's output at all — v4 is built
  on CSS custom properties and `oklch()` colours. The application stylesheet is
  deliberately not linked from any report template.
- **Why:** A template referencing an unsupported feature throws at render time
  rather than degrading. Every report has a test asserting the output really begins
  with `%PDF`.
- **Defense answer:** "The PDF engine only understands CSS 2.1, so the report
  templates use table-based layout and their own stylesheet rather than the
  application's Tailwind styles."

---

### Photo deletion commits the database row before removing the file

- **Spec said:** Nothing explicit; the ordering was left open.
- **What I did instead:** The database row is committed first, then the file is
  removed. Object-storage deletes are not transactional and cannot be undone, so
  "delete the file, roll back the row on failure" can leave a row whose file is
  already gone — a permanently broken image and an audit trail that lies.
  Committing first can only leave an unreferenced file, which no query finds and no
  screen reaches.
- **Why:** Given two imperfect failure modes, the one that costs storage beats the
  one that costs data integrity.
- **Defense answer:** "Deleting a file cannot be rolled back, so we commit the
  record first — the worst case is an orphaned file nobody can reach, rather than a
  record pointing at a file that no longer exists."

---

### Registration is disabled, and 2FA / passkeys were removed

- **Spec said:** Three fixed roles, with accounts seeded for the demo.
- **What I did instead:** Disabled `Features::registration()` in Fortify entirely —
  a private farm system must not let anyone create their own account — and removed
  the two-factor-authentication and passkey features Fortify installs by default,
  along with their migrations. `CreateNewUser` is retained for Fortify's contract
  and hard-codes the `customer` role, so even if registration were re-enabled
  nobody could grant themselves privilege.
- **Why:** 2FA and WebAuthn appear nowhere in the thesis scope and would add
  roughly fifteen dependencies and two tables the researchers would have to defend.
- **Defense answer:** "Accounts are created by the owner, so self-registration is
  switched off; and we removed the two-factor and passkey features because they are
  outside the scope our paper describes."
