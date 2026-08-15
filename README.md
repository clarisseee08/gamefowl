# Digital Broodcock Farm Record Management System

A web-based record management system for gamefowl (broodcock) breeding
operations at **SSGuad Game Farm**.

Built with Laravel 12, Livewire 4, Tailwind CSS 4 and PostgreSQL (hosted on
Supabase). Submitted for a BS Information Systems degree and evaluated against
ISO/IEC 25010.

---

## ⚠️ Read this first if your defense is soon

**Free-tier Supabase projects are paused automatically after about 7 days of
low activity.** A paused project means the application cannot connect at all.

Three things protect you:

1. **Open the app at least once every few days** in the run-up to the defense.
   A handful of requests is enough to reset the inactivity timer.
2. **Keep a fresh backup.** See [Backups](#backups) below. Take one the day
   before the defense.
3. **Know the fallback.** See [Running against a local database](#running-against-a-local-database).
   With a recent dump this turns a paused project from a fatal problem into a
   five-minute one.

If the project *is* paused: open the [Supabase dashboard](https://supabase.com/dashboard),
select the project, click **Restore project**, and wait a few minutes. Data and
configuration are preserved.

---

## Requirements

| Software | Version | Notes |
|---|---|---|
| PHP | 8.2+ | XAMPP is fine |
| **`pdo_pgsql` extension** | — | **Ships with XAMPP but is DISABLED by default — see below** |
| Composer | 2.x | |
| Node.js | 20+ | For the asset build only |
| PostgreSQL client tools | 14+ | Optional, for backups |

> **Docker is not required and must not be used.** The Supabase CLI is not used
> either. Laravel talks to the hosted database directly over the Postgres wire
> protocol, and `php artisan migrate` is the only migration mechanism.

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
cd gfms-laravel

composer install
npm install

cp .env.example .env
php artisan key:generate

# Fill in the DB_* values in .env (see below), then:
php artisan migrate --seed
php artisan storage:link      # needed for locally-stored photos

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

---

## Photo storage

Photos work with **no cloud configuration at all** by default:

```env
GFMS_PHOTO_DISK=public
```

Files go to `storage/app/public` and are served through
`php artisan storage:link`.

To use Supabase Storage instead, set the disk and fill in the S3 credentials
from **Dashboard → Storage → S3 Connection**:

```env
GFMS_PHOTO_DISK=supabase
SUPABASE_S3_ENDPOINT=https://<project-ref>.storage.supabase.co/storage/v1/s3
SUPABASE_S3_REGION=<your-region>
SUPABASE_S3_BUCKET=gfms-laravel
SUPABASE_S3_ACCESS_KEY_ID=
SUPABASE_S3_SECRET_ACCESS_KEY=
```

The bucket is **private**. Photos are streamed through an authorized controller,
so access is checked by a Policy on every request rather than relying on an
unguessable URL. See `docs/storage-setup.md`.

---

## Demo accounts

Created by `php artisan db:seed`. **All three use the password `password`.**

| Role | Email | Can do |
|---|---|---|
| **Owner (Admin)** | `owner@ssguad.test` | Everything, including deleting records and managing users |
| **Record Keeper** | `staff@ssguad.test` | Add and edit records. **Cannot delete anything. Cannot manage users.** |
| **Customer** | `customer@ssguad.test` | View the catalogue, photos, health status and performance history only |

> Change these before any real deployment. They exist for the defense demo.

### Suggested demo path

1. Sign in as **owner** → the dashboard shows flock counts, overdue
   vaccinations and the breeding trend.
2. **Broodcocks → any bird → Family Tree** — the three-generation pedigree.
   This is the feature that distinguishes the system from the prior arts in
   Appendix B.
3. **Breeding → a record with unregistered chicks → Register chicks** — the
   offspring appear as bird records with their sire and dam already filled in,
   so the family tree grows as a by-product of normal data entry.
4. **Health → Vaccination Schedule** — overdue and upcoming follow-ups.
5. **Reports** → download any report as CSV and PDF, then scroll down: every
   generation is recorded with who ran it and exactly which filters were used.
6. Sign out, sign in as **staff** — note the Delete buttons are gone and the
   Users menu is absent.
7. Sign in as **customer** — you land on the catalogue. No dashboard, no
   breeding data, no internal notes.

---

## Commands

```bash
php artisan test                 # full suite
php artisan test --filter=Health # one module
vendor/bin/pint                  # format (run before committing)
vendor/bin/pint --test           # check formatting without writing

php artisan migrate              # apply new migrations
php artisan migrate:fresh --seed # rebuild the schema and reseed
php artisan db:show              # connection check
```

### Is `migrate:fresh` safe against Supabase?

**Yes.** Laravel's `search_path` is pinned to `public`, and `dropAllTables()`
only enumerates tables in the search path. Supabase's own `auth`, `storage`,
`realtime` and `vault` schemas are untouched — verified both by reading the
framework source and by checking the schemas afterwards.

**Do not add a Supabase schema to `search_path`.** That would put those tables
inside the command's blast radius.

---

## Backups

`pg_dump` must be **version 14 or newer** (it must be at least as new as the
server, which is PostgreSQL 17).

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

**Take a backup the day before the defense.**

---

## Running against a local database

The fallback if Supabase is paused, unreachable, or the venue has no internet.

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

With no backup at hand you can still demo from an empty local database:

```bash
php artisan migrate:fresh --seed
```

**Practise this once before the defense.** The first time you run it should not
be in front of the panel.

---

## Documentation

| File | Contents |
|---|---|
| `docs/architecture.md` | Why Supabase without Supabase Auth, the data model, connection topology, known limitations |
| `docs/conventions.md` | Coding conventions — where each kind of code lives, and why |
| `docs/deviations.md` | Every departure from the original specification, with a one-sentence defense answer for each |
| `docs/storage-setup.md` | Switching photo storage from local disk to Supabase |

---

## Troubleshooting

| Symptom | Cause |
|---|---|
| `could not find driver` | `pdo_pgsql` not enabled — see [Requirements](#requirements) |
| Connection times out, no error | Using the direct endpoint (IPv6-only). Use the pooler host. |
| `FATAL: Tenant or user not found` | Wrong pooler generation (`aws-0` vs `aws-1`) or wrong username. Copy both from Dashboard → Connect. |
| `prepared statement "…" already exists` | Connected to port 6543. Use 5432. |
| Authentication fails with the right password | The password was percent-encoded. Use the raw value. |
| Connection refused, project seems dead | The project is paused — restore it from the dashboard. |
| Photos show a placeholder | `php artisan storage:link` was not run |
| `Vite manifest not found` | Run `npm run build` |
