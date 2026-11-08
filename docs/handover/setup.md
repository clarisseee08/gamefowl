# Running this system on your own machine

A complete setup guide for the SSGuad Game Farm team. Follow it top to bottom and
the application will run locally exactly as it does on the developer's machine.

**Estimated time:** 30–45 minutes on a machine that has none of the tools yet.

---

## 0. What you are installing

| Part | What it is | Version this project needs |
|---|---|---|
| PHP | The language the system is written in | **8.2** (not 8.1, not 8.3+) |
| Composer | Installs the PHP libraries | 2.x |
| Node.js + npm | Builds the CSS and JavaScript | Node 20 or newer |
| PostgreSQL | The database you develop against, on your own machine | **17** |
| Git | Fetches the code | any recent version |

**PHP 8.2 specifically.** The test framework in this project (PHPUnit 11) is
pinned because the alternative, Pest, requires PHP 8.3+. Running on 8.1 will fail
outright; 8.3 or 8.4 may work but is untested here. XAMPP 8.2.x is the
known-good bundle.

---

## 1. Install the tools

### 1a. PHP (via XAMPP, Windows)

1. Download **XAMPP for Windows with PHP 8.2.x** from
   <https://www.apachefriends.org/download.html>.
2. Install to the default `C:\xampp`.
3. Add `C:\xampp\php` to your **Path** environment variable:
   Start → "Edit the system environment variables" → Environment Variables →
   select `Path` under *System variables* → Edit → New → `C:\xampp\php` → OK.
4. Close and reopen your terminal, then check:

   ```
   php -v
   ```

   It must print **8.2.x**. If it prints "not recognized", the Path entry did not
   take — reopen the terminal, or reboot.

> You do **not** need to start Apache or MySQL from the XAMPP control panel. This
> project uses PHP's own built-in server and PostgreSQL 17 (§1f). Leave XAMPP's
> panel closed.

### 1b. Enable the PostgreSQL extensions — do not skip this

This is the single most common setup failure, and the error it produces does not
mention the real cause.

1. Open `C:\xampp\php\php.ini` in Notepad.
2. Find these four lines. Each will have a semicolon `;` at the start, which
   means "disabled".

   ```
   ;extension=pdo_pgsql
   ;extension=pgsql
   ;extension=fileinfo
   ;extension=gd
   ```

3. **Delete the leading semicolon** from all four so they read:

   ```
   extension=pdo_pgsql
   extension=pgsql
   extension=fileinfo
   extension=gd
   ```

4. Save the file and reopen your terminal.
5. Verify:

   ```
   php -m
   ```

   The list must contain `pdo_pgsql`, `pgsql`, `fileinfo` and `gd`.

6. Check the list also contains **`intl`**, **`zip`** and **`exif`**. If one is
   missing, remove the `;` from its line too (`;extension=intl`,
   `;extension=zip`, `;extension=exif`). In XAMPP 8.2.12, `intl` is the one
   that ships disabled.

**What each is for:** `pdo_pgsql`/`pgsql` connect to the database — without them
the app cannot start. `fileinfo` validates uploaded photos. `gd` generates image
sizes. Missing `gd` produces a confusing "class not found" much later, during a
photo upload, rather than at startup. `exif` turns phone photos the right way
up. `intl` formats numbers and percentages, `zip` is used by Composer.

### 1c. Composer

Download and run the Windows installer from <https://getcomposer.org/download/>.
When it asks for the PHP executable, point it at `C:\xampp\php\php.exe`.

```
composer -V
```

### 1d. Node.js

Install the **LTS** build from <https://nodejs.org>. Then:

```
node -v
npm -v
```

### 1e. Git

Install from <https://git-scm.com/download/win>, defaults are fine.

### 1f. PostgreSQL 17

Development runs against a PostgreSQL 17 database on your own machine. The
farm's real records live in Supabase, and only the deployed site uses them.

```
winget install --id PostgreSQL.PostgreSQL.17 --exact --interactive
```

or download the Windows installer from
<https://www.postgresql.org/download/windows/>. In the setup wizard:

- **Password:** choose the password for the `postgres` superuser. Keep it; you
  need it once, in §5.
- **Port:** keep **5432**.
- **Components:** Stack Builder is not needed. pgAdmin is optional.

The installer registers a Windows service, `postgresql-x64-17`, that starts
with Windows. Check it:

```
Get-Service postgresql-x64-17
```

It must say `Running`. The tools (`psql`, `pg_dump`, `pg_restore`) are in
`C:\Program Files\PostgreSQL\17\bin`, which is **not** added to `PATH`. Add it,
or type the full path in front of each command in §5.

---

## 2. Get the code

```
git clone https://github.com/clarisseee08/gamefowl.git gamefowlms
cd gamefowlms
```

`main` is the working branch, and the clone starts on it.

---

## 3. Install the project's libraries

From inside the project folder:

```
composer install
npm install
```

`composer install` takes a few minutes the first time. Both commands read
lockfiles (`composer.lock`, `package-lock.json`), so **everyone who runs them
gets byte-identical versions** — that is what makes "same everything" true rather
than approximately true.

---

## 4. Configure the environment

### 4a. Create your `.env`

```
copy .env.example .env
php artisan key:generate
```

`key:generate` writes a unique `APP_KEY`. It encrypts sessions and cookies. It is
**not** shared between machines and must never be committed.

### 4b. Point it at your local database

`.env.example` still shows the Supabase settings the deployed site uses. For
development, replace them with your local PostgreSQL 17 database. Open `.env` in
Notepad and set:

```
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=gfms
DB_USERNAME=gfms
DB_PASSWORD=<choose one>
```

You choose `DB_PASSWORD` yourself. In §5 you give the `gfms` database role the
same password. It only protects the database on your own machine, but still
never commit it: `.env` is gitignored, keep it that way.

### 4c. Photos: decide where test uploads go

Where photos are stored is set by `GFMS_PHOTO_DISK`, separately from the
database:

```
GFMS_PHOTO_DISK=public     # storage/app/public on your machine
GFMS_PHOTO_DISK=supabase   # the LIVE bucket the deployed site uses
```

`.env.example` ships `supabase`. **With `supabase`, a test upload goes to the
farm's real photo bucket**, even though the database is local: the file lands
in the live `broodcocks/<id>/` folder of whichever real bird has that id, and
nothing ever references it. Use `public` unless you have a reason to test
against the bucket. `php artisan config:show gfms.photo_disk` shows what your
machine is using.

---

## 5. Set up the database

### 5a. Create the role and the database

Sign in to PostgreSQL as the `postgres` superuser. It asks for the password you
chose when installing (§1f):

```
psql -h 127.0.0.1 -U postgres -d postgres
```

Then, inside `psql`:

```
CREATE ROLE gfms LOGIN;
\password gfms
CREATE DATABASE gfms OWNER gfms ENCODING 'UTF8' TEMPLATE template0;
\q
```

`\password` asks for the new password twice: type the `DB_PASSWORD` you put in
`.env`. It doesn't show what you type, and it keeps the password out of psql's
history file (`CREATE ROLE ... PASSWORD '...'` would not).
`TEMPLATE template0` makes the database UTF-8 whatever Windows' own language
setting is.

### 5b. Fill it

**If you were given a copy of the development database** (a `.dump` file):

```
pg_restore -h 127.0.0.1 -U gfms -d gfms --no-owner --no-privileges --exit-on-error gfms-local.dump
```

It asks for the `gfms` password. This gives you the same test birds and test
accounts as the developer's machine.

**If you are starting from nothing:**

```
php artisan migrate
php artisan db:seed
```

`migrate` creates the tables. `db:seed` loads sample birds, pens, health and
breeding records so the system has something to show.

### 5c. Check the connection

```
php artisan config:clear
php artisan migrate:status
```

Every migration must say `Ran`. An error means stop and fix it here,
because everything after this depends on it. See §8.

### 5d. Making a copy for someone else

On the machine that has the data (it asks for the `gfms` password):

```
pg_dump -h 127.0.0.1 -U gfms -d gfms -Fc --no-owner --no-privileges -f gfms-local.dump
```

`-Fc` is the compressed format `pg_restore` reads. `--no-owner
--no-privileges` leave out the old machine's role names, so the copy restores
anywhere. Keep the file **out of the project folder**. The repository is public,
and the dump contains the test accounts' password hashes.

> ### ⚠ Never run `migrate:fresh` against Supabase
>
> `php artisan migrate:fresh` **drops every table and deletes all data**, with
> no confirmation and no undo. Against your local database that only costs you
> your test data (restore the dump again). Against Supabase it wipes the farm's
> real records.
>
> It is refused on every machine unless `GFMS_ALLOW_DESTRUCTIVE_DB=true` is
> set. To start over locally, drop and recreate the `gfms` database (5a) and
> restore again instead. Before any destructive command, check that `DB_HOST`
> is `127.0.0.1`.

---

## 6. Build and run

```
npm run build
php artisan serve
```

Open <http://127.0.0.1:8000>.

**`npm run build`, not `npm run dev`.** `dev` starts a live-reload server for
editing code. `build` compiles the real stylesheet once. If you skip it you get
an unstyled page, or a "Unable to locate file in Vite manifest" error.

Re-run `npm run build` only after someone changes the CSS or JavaScript.

### Sign in

Accounts are listed in `docs/reference/tech-stack.md`. There is **no public sign-up** — by
design. Every account is created by the farm owner from **Admin → Users**.

---

## 7. Getting the credentials safely

For local development you need **no shared secrets**: you choose the local
database password yourself (§4b).

The one secret you might be given is the **Supabase Storage keys**
(`SUPABASE_S3_ACCESS_KEY_ID`, `SUPABASE_S3_SECRET_ACCESS_KEY`), and only if
you set `GFMS_PHOTO_DISK=supabase` (§4c). Those keys reach the farm's live photo
bucket. Ask the developer for them **through a private channel**: a direct
message or a password manager share, not a group chat, not email, and never by
pasting them into a document that gets shared around.

With `GFMS_PHOTO_DISK=public`, photos are stored in the project's own `storage`
folder on your machine and the keys can be left blank. This is fine for local
use and for a demo.

---

## 8. When something goes wrong

| What you see | What it actually means | Fix |
|---|---|---|
| `could not find driver` | The Postgres extensions are still disabled | Redo §1b, then **reopen the terminal** |
| `Connection refused` on `127.0.0.1:5432` | PostgreSQL isn't installed or its service isn't running | §1f; `Start-Service postgresql-x64-17` |
| `SQLSTATE… password authentication failed for user "gfms"` | `DB_PASSWORD` in `.env` doesn't match the `gfms` role's password | Make them match: `\password gfms` as `postgres` (§5a) |
| `database "gfms" does not exist` | §5a was skipped | Run §5a |
| Page loads with no styling | `npm run build` has not been run | Run it |
| `Unable to locate file in Vite manifest` | Same cause | Run `npm run build` |
| `No application encryption key` | `.env` has no `APP_KEY` | `php artisan key:generate` |
| Photo upload fails | `gd` or `fileinfo` extension disabled | Redo §1b |
| Changes to a page do nothing | Cached compiled views | `php artisan view:clear` |

A general reset that is always safe:

```
php artisan config:clear
php artisan view:clear
php artisan cache:clear
```

None of these touch your data.

---

## 9. Confirming the install is correct

```
php artisan test
```

**Every test should pass** (812 on 8 November 2026). This is the real proof the setup is right: it exercises
the database, the models, the permission rules and the reports. If they pass,
the system is working — regardless of how the screens look on your machine.

The tests use their own temporary in-memory database and **never touch your real
data**, so this is safe to run at any time.

---

## 10. Everyday commands

| Task | Command |
|---|---|
| Start the system | `php artisan serve` |
| Stop it | `Ctrl + C` in that terminal |
| Get the latest code | `git pull` |
| After pulling changes | `composer install && npm install && npm run build && php artisan migrate` |
| Check everything still works | `php artisan test` |

---

## 11. Why "same everything" actually holds

Worth knowing, because it is what makes this reproducible rather than hopeful:

- **`composer.lock` and `package-lock.json` are committed.** Everyone gets the
  same library versions down to the patch number, not "whatever was newest that
  day".
- **The database schema is defined by migrations in the repository**, not created
  by hand. The structure is code, and it is identical everywhere.
- **The `.env` file is deliberately excluded from git.** That is why credentials
  are a separate step — it is the one thing that legitimately differs per
  machine, and the one thing that must never be shared through the repository.
- **`php artisan test` is the acceptance check.** If every test passes on your
  machine, your environment matches.
