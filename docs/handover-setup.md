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
| PostgreSQL | The database | Supplied by Supabase — nothing to install |
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
> project uses PHP's own built-in server and a hosted database. Leave XAMPP's
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

**What each is for:** `pdo_pgsql`/`pgsql` connect to the database — without them
the app cannot start. `fileinfo` validates uploaded photos. `gd` generates image
sizes. Missing `gd` produces a confusing "class not found" much later, during a
photo upload, rather than at startup.

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

---

## 2. Get the code

```
git clone https://github.com/CernieCriz/gamefowlms.git
cd gamefowlms
git checkout gfms-laravel
```

`gfms-laravel` is the working branch. Confirm you are on it:

```
git branch --show-current
```

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

### 4b. Fill in the database credentials

**The credentials are not in the repository, and they never will be.** A database
password committed to git is recoverable from the history forever, even after the
file is deleted — so they are handed over separately (see §7).

Open `.env` in Notepad and set:

```
DB_CONNECTION=pgsql
DB_HOST=aws-0-<region>.pooler.supabase.com
DB_PORT=5432
DB_DATABASE=postgres
DB_USERNAME=postgres.<project-ref>
DB_PASSWORD=<the password you were given>
```

> **Use the session pooler host**, the one containing `pooler.supabase.com`.
> Supabase also publishes a direct host (`db.<ref>.supabase.co`) which is
> **IPv6-only**. Most Philippine home and office connections are IPv4, where the
> direct host fails with a connection timeout that looks exactly like the
> database being down. Find it in Supabase under
> **Project Settings → Database → Connection string → Session pooler**.

### 4c. Check the connection before going further

```
php artisan migrate:status
```

A table of migrations means you are connected. An error means stop and fix it
here — everything after this depends on it. See §8.

---

## 5. Set up the database

### If you were given access to the existing shared database

The tables and data already exist. **Run nothing.** Skip to §6.

> ### ⚠ Never run `migrate:fresh` on a shared database
>
> `php artisan migrate:fresh` **drops every table and deletes all data**. On a
> shared database it wipes it for everyone, instantly, with no confirmation and
> no undo.
>
> This is not hypothetical — it happened during development, when two processes
> ran it against the same database at the same time and each destroyed the
> other's work. If you only ever need one command from this document, it is the
> one you must not run.

### If you are setting up your own fresh database

```
php artisan migrate
php artisan db:seed
```

`migrate` creates the tables. `db:seed` loads sample birds, pens, health and
breeding records so the system has something to show.

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

Accounts are listed in `docs/tech-stack.md`. There is **no public sign-up** — by
design. Every account is created by the farm owner from **Admin → Users**.

---

## 7. Getting the credentials safely

You need two secrets that are not in the repository:

1. The **database password**
2. The **storage keys** (`AWS_ACCESS_KEY_ID`, `AWS_SECRET_ACCESS_KEY`) if you are
   using the hosted photo storage

Ask the developer for these **through a private channel** — a direct message or
a password manager share, not a group chat, not email, and never by pasting them
into a document that gets shared around.

If you would rather not deal with hosted storage at first, set:

```
FILESYSTEM_DISK=local
```

Photos are then stored in the project's own `storage` folder on your machine, and
the AWS keys can be left blank. This is fine for local use and for a demo.

---

## 8. When something goes wrong

| What you see | What it actually means | Fix |
|---|---|---|
| `could not find driver` | The Postgres extensions are still disabled | Redo §1b, then **reopen the terminal** |
| Connection times out | You are using the IPv6-only direct host | Switch to the session pooler host (§4b) |
| `SQLSTATE… password authentication failed` | Wrong password, **or** wrong username — the username includes the project ref, e.g. `postgres.abcdefgh` | Recheck both against Supabase |
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

**492 tests should pass.** This is the real proof the setup is right: it exercises
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
- **`php artisan test` is the acceptance check.** If 492 tests pass on your
  machine, your environment matches.
