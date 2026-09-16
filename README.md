# Digital Broodcock Farm Record Management System

A web-based record management system for gamefowl breeding, built for
**SSGuad Game Farm**.

The farm keeps working birds for breeding rather than for sale in volume, and
its records had lived in notebooks and spreadsheets: a bird's parentage, its
vaccination history, what it weighed at its last conditioning, and what happened
to it in the end. Those facts are only worth keeping if they can be put back
together — which is what this system is for.

Built with Laravel 12, Livewire 4, Tailwind CSS 4 and PostgreSQL on Supabase.

---

## What it does

### Pedigree is the centre of it

Every bird is a row in one `broodcocks` table, male or female, with `sire_id`
and `dam_id` pointing at other rows in the same table. That one decision is what
separates this from a spreadsheet with a "bloodline" column: bloodline as text
is a **label**, whereas two foreign keys are **traceability**. The family tree
screen says "Not recorded" where the farm genuinely does not know rather than
guessing, and its depth is one setting (`gfms.pedigree_generations`) —
currently the sire and dam, because the farm does not record beyond that and a
chart two thirds full of "Not recorded" reads as a screen that has failed
rather than one telling the truth. It loads breadth-first, one query per
generation, so raising the depth costs one more query per level rather than
doubling the page.

Two things follow from it that are easy to miss:

- **Registering a hatch grows the tree as a by-product.** Record a mating and
  how many chicks hatched, then register them, and each chick is created with
  its sire and dam already filled in. Nobody has to type parentage twice, which
  is the only reason it gets recorded at all.
- **A parent the farm does not own can still be recorded.** Farms mate their
  cocks to borrowed and visiting hens. Typing that hen's name creates a real
  bird row flagged `is_external`, so the branch above her survives — but she is
  excluded from stock counts, the catalogue and the public site, because she is
  a node in a family tree, not livestock in the farm's care.

### The records a working farm actually keeps

| | |
|---|---|
| **Broodcocks** | Identification, band number, appearance, class, status, photographs, and the parentage above. |
| **Health** | Vaccinations, dewormings and check-ups, each with an optional follow-up date. A schedule screen shows what is overdue and what is due soon. |
| **Performance** | Conditioning, sparring and contest events, with weight, result, duration and a rating. Win rate is computed over contests only — counting weigh-ins would dilute the one figure a buyer reads. |
| **Breeding** | Matings with eggs set, fertile and hatched. Fertility and hatch rates are always derived, never stored, so they cannot contradict the counts they come from. |
| **Mortality** | One record per bird, ever. Writing it flips the bird to deceased in the same transaction, so a dead bird can never stay in the live inventory. |

### Reports

Five reports — inventory, health compliance, breeding performance, mortality and
performance history — each exportable as **CSV and PDF**. Every generation
writes an audit row recording who ran it, when, and exactly which filters were
used, so a report can be explained and reproduced after the fact.

### A public shop window

The farm advertises itself. The landing page, the catalogue and each bird's page
— including its family tree — are readable **without an account**. Internal
fields are not: notes, mortality, pens and internal remarks stay behind the
login, and a visitor can only reach birds the catalogue itself would list. Photo
and bird ids are sequential, so without that last rule the public could simply
count upwards through every record the farm has ever kept.

### Roles

Three, enforced by Laravel Policies on every action rather than by hiding
buttons:

- **Owner** — everything, including deleting records and managing accounts.
- **Record Keeper** — add and edit; cannot delete, cannot manage users.
- **Customer** — the read-only catalogue.

Nothing is ever hard-deleted. Records soft-delete and the activity log keeps the
history, because an audit trail that can be erased is not an audit trail.

---

## How it is built

| | |
|---|---|
| **Laravel 12 + Livewire 4** | Server-rendered, reactive without a JavaScript front end. Alpine ships inside Livewire's bundle. |
| **PostgreSQL on Supabase** | Reached directly over the Postgres wire protocol. Supabase Auth is not used — Laravel owns authentication (Fortify), so there is one identity system rather than two. |
| **Tailwind CSS 4, CSS-first** | `@theme{}` in `resources/css/app.css`. No `tailwind.config.js`, no PostCSS. |
| **dompdf** | PDF reports. A CSS 2.1 engine, so the report templates read plain hex from `config/gfms-brand.php` rather than custom properties. |
| **PHPUnit 11** | 684 tests. Visible copy is the test API — the assertions target what a user reads, not CSS class names, so restyling is safe and rewording is not. |

Guard tests hold the rules that are cheap to state and expensive to notice
breaking: query counts on the pedigree and list screens, brand tokens mirrored
between the stylesheet and the PDF config, no stock Tailwind palette classes in
a view, and every referenced image actually committed.

> **There is no Docker in the development loop and no Supabase CLI.** Local work
> runs on a plain PHP install, and `php artisan migrate` is the only mechanism
> that changes the schema — never the Supabase table editor. The `Dockerfile`
> exists solely because the host builds from one.

---

## Requirements

| Software | Version | Notes |
|---|---|---|
| PHP | 8.2+ | XAMPP is fine |
| **`pdo_pgsql` extension** | — | **Ships with XAMPP but is DISABLED by default — see below** |
| Composer | 2.x | |
| Node.js | 20+ | For the asset build only |
| PostgreSQL client tools | 14+ | Optional, for backups |

### Enabling the PostgreSQL driver (the most common setup failure)

A fresh XAMPP has the PostgreSQL driver present but commented out. Without it
you will get `could not find driver`.

1. Find your `php.ini`:
   ```bash
   php --ini
   ```
2. Open it and **remove the leading `;`** from these lines:
   ```ini
   extension=pdo_pgsql
   extension=pgsql
   ```
   While you are there, also enable `extension=zip` and `extension=intl`.
3. Verify:
   ```bash
   php -r "echo implode(', ', PDO::getAvailableDrivers()), PHP_EOL;"
   ```
   `pgsql` must appear in the output.

---

## Setup

```bash
git clone <repository-url>
cd gamefowl

composer install
npm install

cp .env.example .env
php artisan key:generate

# Fill in the DB_* values in .env (see below), then:
php artisan migrate --seed
php artisan storage:link      # only needed when photos are stored locally

npm run build                 # or: npm run dev  (during development)
php artisan serve
```

Open <http://127.0.0.1:8000>.

---

## Database configuration

```env
DB_CONNECTION=pgsql
DB_HOST=aws-0-ap-northeast-1.pooler.supabase.com
DB_PORT=5432
DB_DATABASE=postgres
DB_USERNAME=postgres.<your-project-ref>
DB_PASSWORD=<your-database-password>
DB_SSLMODE=require
```

### Three things that will silently break this

**1. Use the SESSION pooler, not the direct connection.**
The direct endpoint `db.<ref>.supabase.co` publishes **only an IPv6 address**.
Most Philippine ISPs and shared hosts are IPv4-only, so it will simply time out
with no useful error. Always use the `...pooler.supabase.com` host.

**2. Use port 5432, not 6543.**
Port 6543 is the *transaction* pooler. It does not support prepared statements,
and Laravel's migrations and Eloquent will fail with
`prepared statement "pdo_stmt_00000001" already exists`. Port 5432 is session
mode, which is what this project needs.

**3. The pooler hostname is not always `aws-0`.**
Supabase runs more than one pooler generation per region. If you see
`FATAL: Tenant or user not found`, your project is on a different one — copy
the exact host from **Dashboard → Connect**. That error looks like a wrong
password but is not.

**Do not percent-encode the password.** Percent-encoding applies only to the
single-string `postgresql://user:pass@host/db` URL form. Laravel uses discrete
`DB_*` fields, so `DB_PASSWORD` takes the **raw** value. Encoding it produces an
authentication failure indistinguishable from a wrong password. If the password
contains `#`, a space or a quote, wrap the whole value in double quotes; if it
contains `$`, escape it or the value is read as a variable.

### Verifying the connection

```bash
php artisan db:show
```

This prints the server version and table list. If it works, migrations will.

### One database, every environment

There is no separate development copy: local work and the deployed site point at
the same Supabase project. Two consequences worth knowing before you touch
anything —

- `migrate:fresh`, `migrate:refresh` and `db:wipe` are **refused on every
  machine**, not just in production, because the machine most likely to run them
  is a developer's. `GFMS_ALLOW_DESTRUCTIVE_DB=true` lifts that deliberately.
- Photo storage should be `supabase` **everywhere, including locally** — see
  below.

---

## Photo storage

```env
GFMS_PHOTO_DISK=supabase
```

Set this to `supabase` in every environment. `public` writes files to the local
machine while recording, in the shared database, that the photo lives on
"public" — so the deployed site reads that row, looks in its own empty directory
and serves a 404. Nothing errors; the photograph simply does not appear.
`php artisan photos:migrate-disk --to=supabase` repairs rows already in that
state.

Credentials come from **Dashboard → Storage → S3 Connection**:

```env
SUPABASE_S3_ENDPOINT=https://<project-ref>.storage.supabase.co/storage/v1/s3
SUPABASE_S3_REGION=<your-region>
SUPABASE_S3_BUCKET=gfms-laravel
SUPABASE_S3_ACCESS_KEY_ID=
SUPABASE_S3_SECRET_ACCESS_KEY=
```

The bucket is **private**. Photos are streamed through an authorized controller,
so a Policy is consulted on every request rather than relying on an unguessable
URL. Each upload is also resized once to a ~400px thumbnail, which is what the
grids request — a page of cards should not pull full-size phone photographs.
See `docs/storage-setup.md`.

---

## Accounts

`php artisan db:seed` creates three. **All use the password `password`.**

| Role | Email |
|---|---|
| Owner (Admin) | `owner@ssguad.test` |
| Record Keeper | `staff@ssguad.test` |
| Customer | `customer@ssguad.test` |

> Change these before any real deployment. There is no self-registration
> anywhere in the system; the owner creates every account.

---

## Commands

```bash
php artisan test                 # full suite
php artisan test --filter=Health # one module
vendor/bin/pint                  # format (run before committing)
vendor/bin/pint --test           # check formatting without writing

php artisan migrate              # apply new migrations
php artisan db:show              # connection check

php artisan photos:backfill-thumbnails   # thumbnails for older photos
php artisan photos:migrate-disk --to=supabase --dry-run
```

`/diagnostics` (owner only) reports what the running server can actually do —
loaded extensions, writable paths, storage reachability, mail transport. It
exists because the host has no shell, so there is otherwise no way to ask the
container anything.

### Is `migrate:fresh` safe against Supabase?

**Yes**, mechanically: Laravel's `search_path` is pinned to `public`, and
`dropAllTables()` only enumerates tables in the search path, so Supabase's own
`auth`, `storage`, `realtime` and `vault` schemas are untouched.

It is still refused by default, because the database it would empty is the real
one. **Do not add a Supabase schema to `search_path`** — that would put those
tables inside the command's blast radius.

---

## Backups

`pg_dump` must be **version 14 or newer** (at least as new as the server, which
is PostgreSQL 17).

```powershell
$env:PGPASSWORD = '<database password>'

pg_dump `
  -h aws-0-ap-northeast-1.pooler.supabase.com -p 5432 `
  -U postgres.<project-ref> -d postgres `
  --schema=public --no-owner --no-privileges `
  -Fc -f backups\gfms-$(Get-Date -Format yyyy-MM-dd).dump
```

Why those flags:

- `--schema=public` — dump only our tables. Without it you drag in Supabase's
  own schemas, which will not restore into a plain PostgreSQL install.
- `--no-owner --no-privileges` — Supabase-only roles (`anon`, `authenticated`,
  `service_role`) do not exist locally; without these the restore floods with
  `role does not exist` errors.

Since there is only one database, a dump is the only copy of the farm's records
that exists anywhere else. Take them regularly.

---

## Running against a local database

Useful when Supabase is unreachable, or for work that should not touch the real
records at all.

```powershell
# 1. Create the database and restore
createdb -h 127.0.0.1 -U postgres gfms
pg_restore -h 127.0.0.1 -U postgres -d gfms --no-owner --no-privileges backups\gfms-YYYY-MM-DD.dump

# 2. Point .env at it
#    DB_HOST=127.0.0.1
#    DB_DATABASE=gfms
#    DB_USERNAME=postgres
#    DB_PASSWORD=<local password>
#    DB_SSLMODE=disable        <-- a default local server has no TLS
#    GFMS_PHOTO_DISK=public    <-- Supabase Storage will also be unreachable

php artisan config:clear
php artisan db:show
```

With no dump at hand, an empty local database still runs — and this is the one
situation where the destructive-command guard is worth lifting, because the
database it is pointed at is now yours:

```bash
GFMS_ALLOW_DESTRUCTIVE_DB=true php artisan migrate:fresh --seed
```

Take it back out of `.env` afterwards, before the `DB_*` values go back to
Supabase.

---

## Hosting

Deployed on Render from the `Dockerfile` — nginx and php-fpm under supervisor,
PHP 8.2 to match the test environment.

Two free-tier behaviours shape the setup, and both are worth knowing:

- **The web service sleeps** after ~15 minutes of no traffic and takes the
  better part of a minute to wake. A scheduled workflow pings `/up` during the
  hours the farm uses the site. GitHub Actions bills every job as a full minute,
  so round-the-clock coverage is not affordable there; a free external pinger is
  the better tool if it is ever needed.
- **A free Supabase project is paused** after about 7 days of inactivity, and
  restoring it is a manual step in the dashboard. A second scheduled workflow
  runs one real query a day to prevent it. Pinging the web service is not
  enough — that was measured, and it registers no database activity.

The container filesystem is wiped on every restart, which is why photo storage
must be the bucket rather than local disk.

---

## Documentation

| File | Contents |
|---|---|
| `docs/architecture.md` | Why Supabase without Supabase Auth, the data model, connection topology, known limitations |
| `docs/conventions.md` | Coding conventions — where each kind of code lives, and why |
| `docs/deviations.md` | Every departure from the original specification, and the reasoning for each |
| `docs/storage-setup.md` | Switching photo storage between local disk and Supabase |

`CLAUDE.md` carries the standing rules for anyone — or anything — working in
this repository.

---

## Troubleshooting

### `no connection to the server` partway through a migration or seed

The connection to Supabase can drop during a long run — a full
`migrate:fresh --seed` is around a minute of continuous work against a database
in Tokyo, and the pooler will sometimes close a connection held that long. It is
not a data problem.

**Both commands are safe to simply run again.** Migrations skip what has already
been applied, and every seeder carries a guard that skips a table it has already
populated, so re-running resumes rather than duplicating:

```bash
php artisan migrate --force     # repeat until it reports nothing left to run
php artisan db:seed --force     # repeat until it completes
```

`DatabaseSeeder` already reconnects and retries each seeder up to three times on
a dropped connection. On a bad link you may still need a few passes.

**If a run was interrupted, it can leave a transaction open** holding locks that
block the next attempt — which looks like the seed hanging for ~40 seconds and
then dying. Clear it with:

```sql
select pg_terminate_backend(pid)
from pg_stat_activity
where datname = 'postgres'
  and pid <> pg_backend_pid()
  and state = 'idle in transaction';
```

Run that from the Supabase SQL editor. It only affects stuck connections.

### Other problems

| Symptom | Cause |
|---|---|
| `could not find driver` | `pdo_pgsql` not enabled — see [Requirements](#requirements) |
| Connection times out, no error | Using the direct endpoint (IPv6-only). Use the pooler host. |
| `FATAL: Tenant or user not found` | Wrong pooler generation (`aws-0` vs `aws-1`) or wrong username. Copy both from Dashboard → Connect. |
| `prepared statement "…" already exists` | Connected to port 6543. Use 5432. |
| Authentication fails with the right password | The password was percent-encoded. Use the raw value. |
| Connection refused, project seems dead | The project is paused — restore it from the Supabase dashboard. |
| Photos show a placeholder | The row's disk does not match where the file is — see [Photo storage](#photo-storage) |
| `Vite manifest not found` | Run `npm run build` |
| Page renders completely unstyled | A stale `public/hot` from a dead `npm run dev`. Delete it. |

---

## Interface: "Registry, running as software"

The console is a full-bleed application shell — sidebar floor-to-ceiling, top bar
inside the content column, and the main region as the **only** scroll container
in the document. Tokens follow shadcn-style semantic naming
(`background`/`foreground`, `card`/`card-foreground`, `muted`/`muted-foreground`),
which is why the dark theme is a second `:root` block rather than a second set
of components.

Two rules carry most of the character. **Colour means bloodline and nothing
else** — everything else is ink, rule and paper, so a page of birds can be read
by bloodline at a glance. And **registry data is monospaced**: every band
number, date, weight, count and percentage, so digits align down a column.

`config/gfms-brand.php` is the single source of colour, consumed by both the
Tailwind theme and the PDF templates, with guard tests holding the two in step.

### Dark mode

Shipped, and it costs no component any knowledge of it: `:root[data-theme="dark"]`
redefines the same token names, so a surface that declared its own foreground
already gets the right one. The toggle writes `gfms-theme` to `localStorage`;
with nothing stored, the operating system's preference decides.

Two details that are easy to get wrong and are already handled — the choice is
applied by an inline script in `<head>`, before first paint, so a dark-mode user
never gets a white flash; and it is reapplied after a soft navigation, which
otherwise wipes the attribute and snaps the page back to light.
