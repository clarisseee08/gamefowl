# Tech Stack & Demo Accounts

Quick reference for the Digital Broodcock Farm Record Management System.
Written for defense preparation — every section says *what* it is and *why* it
was chosen, because the second question always follows the first.

---

## Demo accounts

All three use the password **`password`**.

| Role | Email | Name | What they can do |
|---|---|---|---|
| **Owner (Admin)** | `owner@ssguad.test` | Salvador S. Guadalupe | Everything — including deleting records and managing users |
| **Record Keeper** | `staff@ssguad.test` | Marilou D. Ocampo | Add and edit records. **Cannot delete anything. No Users menu.** |
| **Customer** | `customer@ssguad.test` | Ricardo B. Villanueva | Read-only catalogue, photos, health status, performance history |

If the accounts are missing, run `php artisan db:seed`.
Start the app with `php artisan serve` → <http://127.0.0.1:8000>.

> Change these before any real deployment. They exist for the defense demo.

---

## The shape of the system, in one sentence

A **server-rendered PHP application** — no JavaScript framework, no API layer,
no separate frontend app — talking to a **managed PostgreSQL database in Tokyo**.

That matters: the paper claims the system is built with "PHP, HTML and CSS",
and that stays literally true. The browser receives HTML, not JSON.

---

## 1. Runtime and tooling

| | Version | Role |
|---|---|---|
| **PHP** | 8.2.12 (XAMPP) | The language everything server-side runs on |
| **Composer** | 2.9.5 | PHP package manager |
| **Node.js** | 24.13.0 | **Build-time only** — compiles CSS. Not needed to *run* the system |
| **npm** | 11.10.0 | JavaScript package manager |

**PHP 8.2 is the binding constraint on the whole stack.** It caps the project at
Laravel 12 (Laravel 13 needs PHP 8.3), activitylog 4 (v5 needs 8.4) and
PHPUnit 11. Not a problem — but know it if asked why you are not on the newest
version of something.

> **Q: "You said no JavaScript, but you use Node."**
> **A:** "Node only compiles our stylesheet at build time. The deployed system
> is PHP and static CSS — the server never runs JavaScript."

---

## 2. Backend

**Laravel 12.66.0** — routing, ORM (Eloquent), validation, migrations,
sessions, authorization.

### Production packages

| Package | Version | Why it is there |
|---|---|---|
| `livewire/livewire` | 4.4.0 | Interactive UI without writing JavaScript |
| `laravel/fortify` | 1.38.0 | Authentication backend (login, logout, password reset) |
| `spatie/laravel-activitylog` | 4.12.3 | The audit trail the Significance chapter promises |
| `barryvdh/laravel-dompdf` | 3.1.2 (dompdf 3.1.6) | PDF report export — pure PHP, no Chromium needed |
| `league/flysystem-aws-s3-v3` | 3.35.2 | Talks to Supabase Storage (pulls `aws/aws-sdk-php` 3.392.3) |
| `laravel/tinker` | 2.11.1 | Interactive console |

### Architecture inside Laravel

```
Route → Livewire component → Action class → Eloquent model
           │                     │
           │                     └─ DB::transaction() for multi-table writes
           └─ Form Request rules + Policy check (every action, server-side)
```

- **Livewire components orchestrate** — they hold no business logic.
- **Action classes** (`app/Actions/`) hold anything writing more than one table.
  `RecordMortality` flips a bird's status in the *same transaction* as the
  mortality row.
- **Policies** (`app/Policies/`) are the authorization boundary — nine of them,
  one per model.
- **Form Requests** hold validation rules, shared with Livewire through static
  methods so the two cannot drift apart.

---

## 3. Frontend

### Livewire 4.4 — the key piece

Livewire lets you write **interactive UI in PHP**. When a user types in a search
box, the browser sends a small AJAX request, the PHP class re-runs, and Livewire
sends back HTML that it patches into the page.

**You never write the JavaScript that does this.** That is the whole point, and
it is why the "PHP, HTML, CSS" claim holds.

```php
// app/Livewire/Broodcocks/Index.php  — this IS the frontend logic
#[Url] public string $search = '';

#[Computed]
public function broodcocks() {
    return Broodcock::query()->with(['pen','primaryPhoto'])
        ->search($this->search)->paginate(15);
}
```

```blade
<input wire:model.live.debounce.300ms="search">
```

Live search, with no JavaScript written by hand.

> **Livewire 4 differs from Livewire 3**, which most online tutorials still
> cover. In v4, routes use `Route::livewire()`, component tags must self-close
> (`<livewire:foo />`), and v3's `wire:model.blur` is now `wire:model.live.blur`.
> This project also configures **class-based components** rather than v4's
> default single-file format, which puts a literal ⚡ emoji in filenames and
> which Laravel Pint cannot format.

### Blade

Laravel's template engine — the `.blade.php` files. Plain HTML with `{{ }}` for
values and `@if` / `@foreach` for logic. Layouts live in
`resources/views/layouts/`.

### Tailwind CSS 4.3.3

Utility-first CSS. **Version 4 is "CSS-first"** — there is **no
`tailwind.config.js`**, no PostCSS, no autoprefixer. Everything is configured in
`resources/css/app.css`:

```css
@import 'tailwindcss';
@source '../**/*.blade.php';
@theme { --color-brand-600: oklch(0.53 0.13 155); }
```

> **Q: "Where is your Tailwind config file?"**
> **A:** "Tailwind 4 is configured through CSS directives instead of a
> JavaScript config file, so our theme lives in `app.css`."

### Alpine.js

A tiny (~15 KB) library for small interactions such as the mobile menu toggle.
**It ships bundled inside Livewire** — it was never installed separately.

### Vite 7.3.6

The build tool. Compiles `app.css` into a hashed production file.
`npm run build` for production, `npm run dev` for hot reload while developing.

---

## 4. Database

**PostgreSQL 17.6**, hosted on **Supabase**, region **ap-northeast-1 (Tokyo)**.

**Not MySQL** — this is the largest change needed in the paper.

### How the connection works

```
Laravel (pdo_pgsql)
   │  Postgres wire protocol, TLS (sslmode=require)
   ▼
aws-0-ap-northeast-1.pooler.supabase.com:5432   ← Supavisor, SESSION mode
   ▼
PostgreSQL 17.6
```

Three decisions carry weight, and each has a wrong option that fails
confusingly:

1. **Session pooler, not the direct endpoint.** `db.<ref>.supabase.co`
   publishes only an IPv6 address. Most Philippine ISPs are IPv4-only, so it
   simply times out with no useful error.
2. **Port 5432, not 6543.** Port 6543 is the *transaction* pooler and does not
   support prepared statements; Eloquent fails with
   `prepared statement already exists`.
3. **`pdo_pgsql` must be enabled in `php.ini`.** It ships with XAMPP but is
   commented out. Without it: `could not find driver`.

### Schema points worth defending

- Nine tables, `bigIncrements` IDs, snake_case, soft deletes everywhere
- **20 database-level CHECK constraints** — not just form validation
- **`sire_id` / `dam_id`** are self-referencing foreign keys on `broodcocks` —
  this is what makes the pedigree real rather than a text label
- **`reports.parameters` is `jsonb`** — no MySQL equivalent
- **Nothing derived is stored**: age comes from `date_hatched`, fertility and
  hatch rates from the egg counts

---

## 5. Storage

**Supabase Storage** through its S3-compatible gateway (bucket `gfms-laravel`,
**private**).

Because the bucket is private, photos are **streamed through an authorized
Laravel controller** — access is checked by a Policy on every request rather
than relying on an unguessable URL.

Configurable with one environment variable:

```env
GFMS_PHOTO_DISK=public     # local storage/app/public — works with zero cloud config
GFMS_PHOTO_DISK=supabase   # the private remote bucket
```

The default is `public`, so the system runs with no cloud credentials at all.

---

## 6. Authentication and authorization

This is the part a Supabase-literate panel member will probe.

- **Used from Supabase:** PostgreSQL, and Storage.
- **Not used:** Supabase Auth, Row Level Security, PostgREST, the JavaScript
  client, Realtime, Edge Functions.

**All authentication and authorization live in Laravel.** The reason to
memorise:

> Laravel connects as a **database superuser, which bypasses Row Level Security
> entirely**. Writing RLS policies would be dead code — it would look like a
> security layer while enforcing nothing, which is worse than having no layer,
> because it creates a false impression of where security lives.

- **Fortify** provides the auth backend; the Blade views are hand-written.
- **Registration is disabled** — accounts are created by the Owner only.
- **2FA and passkeys were removed.** Fortify installs them by default; they are
  outside the thesis scope.
- **Login is rate-limited** to 5 attempts per minute per email and IP.
- **Policies enforce the role matrix server-side.** Hiding a button is a
  courtesy, never the gate — and tests call component methods directly,
  bypassing the interface, to prove it.

---

## 7. Testing and quality

| Tool | Version | Role |
|---|---|---|
| **PHPUnit** | 11.5.56 | **447 tests**, 1,351 assertions |
| **Laravel Pint** | 1.30.4 | Automatic code formatting |
| **Debugbar** | 4.4.0 | Query profiling during development |
| Faker / Mockery / Collision | — | Test data, mocks, readable error output |

**Not Pest** — Pest 4 and 5 both require PHP 8.3 or newer, so neither can
install here.

Tests run against **SQLite in memory** (fast, offline). That is why the
PostgreSQL-only CHECK constraints are gated behind a driver check inside the
migrations.

**The tests are the ISO 25010 evidence.** Query-count tests demonstrate
performance efficiency; one test per role per action demonstrates security;
transaction tests demonstrate reliability.

---

## 8. Infrastructure

| Layer | Where it runs |
|---|---|
| Web server | XAMPP Apache, or `php artisan serve` — **local** |
| Application | PHP 8.2 — **local** |
| Database | Supabase — **remote, Tokyo** |
| Object storage | Supabase Storage — **remote** |
| Version control | Git → GitHub (`CernieCriz/gamefowlms`, branch `gfms-laravel`) |

**No Docker anywhere.** No Supabase CLI. No CI/CD pipeline. Migrations are the
only schema mechanism.

**The system requires internet at all times** — the database is remote. This
belongs in Scope & Limitations.

---

## 9. What is deliberately *not* in the stack

Explaining an absence is often more convincing than listing what is present.

| Not used | Why |
|---|---|
| React / Vue / any JS framework | Would contradict the paper's "PHP, HTML, CSS" description and add a build layer it does not describe |
| REST or GraphQL API | Nothing consumes one — Livewire renders server-side |
| MySQL | Supabase provides PostgreSQL |
| Supabase Auth / RLS | The superuser connection bypasses RLS; auth lives in Laravel |
| Laravel Breeze | Its Livewire stack would have **downgraded** Tailwind 4 → 3 and pinned Livewire 3 |
| `spatie/laravel-permission` | Only three fixed roles — an enum plus Policies is enough |
| Chart.js or any charting library | The dashboard chart is drawn with CSS |
| Docker | Only needed to run Supabase locally, which this project does not do |
| Redis, queues, websockets | No background work; realtime is an explicit non-goal |

---

## The 30-second answer

> "It is a Laravel 12 application written entirely in PHP. Livewire lets us
> build the interactive parts — live search, filters, forms — without writing
> JavaScript, so the browser only ever receives HTML. Styling is Tailwind
> CSS 4. The database is PostgreSQL 17 hosted on Supabase, which we reach
> directly over the Postgres protocol. We use Supabase for the database and
> file storage only; all authentication and permissions are enforced in the
> Laravel application layer, verified by 447 automated tests."
