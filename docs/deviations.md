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
