# Public landing page and farm-visit appointments

**Date:** 2026-09-14
**Status:** approved, ready for an implementation plan

## What this is

The farm's public face is currently a redirect. `/` sends a guest to `/catalog`,
which renders a photo grid under a 56px-tall bar carrying a logo and a sign-in
link. There is no footer anywhere in the application, no description of the
farm, and no way for a customer to ask to visit.

This adds three things as one piece of work:

1. A real public shell — a full header, and a footer the application does not
   currently have at all.
2. A landing page at `/` carrying the farm's story **and the full, filterable
   catalogue inline**.
3. Farm-visit appointments: a public request form, and a console screen where
   the owner works through what comes in.

## Constraints that shaped this

**Mail does not work.** `render.yaml` leaves `MAIL_HOST`, `MAIL_USERNAME`,
`MAIL_PASSWORD` and `MAIL_FROM_ADDRESS` as `sync: false`, and they have never
been filled in. Laravel's default mailer is `log`, so a confirmation email is
accepted, reported as sent, and written to stderr. Nothing in this design may
depend on an email arriving. The owner rings the customer back on the number
they left; that is the whole notification mechanism.

**The application knows nothing about the farm.** `config/gfms.php` has
`farm.name` and a `farm.address` that defaults to an empty string. There is no
phone number, email address or set of visiting hours anywhere. A footer is
mostly contact information, so this adds config keys and the real values are
supplied through the environment rather than committed.

**This is the first anonymous write.** Every existing write sits behind `auth`
and a Policy. An appointment request is the first row a stranger can insert,
which brings rate limiting and spam handling into scope for the first time.

**A CAPTCHA is not available.** `docker/nginx.conf` documents a deliberately
partial CSP: `script-src` is unset because Livewire ships Alpine, which needs
`unsafe-eval`. Adding a third-party script widget to a page that has been kept
free of them is the wrong trade. A honeypot achieves most of the benefit with no
script at all.

**Schema changes are a staged act.** Development and production are the same
Supabase project, and `RUN_MIGRATIONS` is `false` on Render. The migration in
this work ships without running; it is run deliberately, once, by setting that
variable to `true`, deploying, confirming, and setting it back.

## Page architecture

```
/                  Landing\Index        public   hero -> about -> catalogue -> visit -> footer
/catalog           Catalog\Index        public   the catalogue on its own
/broodcocks/{id}   unchanged            public
/appointments      Appointments\Index   staff    the review queue
```

### `Catalog\Browse`, extracted

`Catalog\Index::render()` currently calls `->layout()` and `->title()`, which
makes it a full-page component. Nesting it inside the landing page would mean
relying on Livewire ignoring those calls for a child component.

So the browse UI — the query, the four filters, the pagination and the grid —
moves into a new nestable `App\Livewire\Catalog\Browse`. `Catalog\Index` becomes
a thin full-page wrapper that renders the shell and nests `Browse`.
`Landing\Index` nests the same component.

One implementation, two surfaces. The cost is real and larger than it first
looks: **22** call sites across `CatalogTest`, `CatalogCacheTest` and
`PublicCatalogueTest` drive `Catalog\Index` directly as a component
(`Livewire::test(Index::class)->set('bloodline', ...)`) and must move to
`Browse::class`. Assertions that go through the route
(`$this->get(route('catalog.index'))`) are unaffected and must keep passing
untouched — they are the proof the extraction changed no behaviour.

Because that is a mechanical change across three files that also contain the
catalogue's behavioural assertions, the extraction is its own step in the plan,
finished and green before any landing-page work begins.

`Browse` keeps the `RecordCache` wiring added on 2026-09-13. Both surfaces
therefore inherit it, which matters because `/` becomes the most-hit page in the
application and every uncached query against this database costs roughly 240ms.

### The public shell

`layouts::catalog` is upgraded in place rather than a second public layout being
introduced, so the landing, the catalogue and every bird page pick up the same
chrome.

**Header:** farm mark and name, navigation (Stock, Visit us, and Console for
internal users only), and the existing quiet sign-in control. Sticky, as now.

**Footer** (new): a hairline top rule, then the farm's contact block — address,
phone, email, visiting hours — alongside navigation and a copyright line. All
contact values come from `config('gfms.farm.*')`.

### Landing page order, and the trade it carries

```
hero            name, one sentence on what the farm does, two anchor CTAs
about           a short section on the farm and its bloodlines
catalogue       the FULL filterable catalogue, nested Browse component
visit           the appointment request form
footer          from the shell
```

The catalogue sits above the form, which means filtering resizes an element
above it. This was raised during design and the ordering was chosen anyway; it
is mitigated rather than ignored:

- `paginate(12)` bounds the grid to twelve cards, so the height varies by at
  most one row, and only on a final page.
- The hero carries a *Book a visit* anchor, so reaching the form never requires
  scrolling past the grid.

## Appointments

### Schema

Migration `create_appointments_table`:

| Column | Type | Notes |
|---|---|---|
| `id` | bigIncrements | |
| `name` | string | required |
| `contact_number` | string(40) | required — the reply channel |
| `email` | string, nullable | recorded, never depended on |
| `preferred_date` | date | required, today or later |
| `preferred_time` | enum | `morning` / `afternoon` |
| `party_size` | unsignedSmallInteger | default 1 |
| `message` | text, nullable | |
| `broodcock_id` | foreignId, nullable | nullOnDelete |
| `status` | enum | `AppointmentStatus::values()`, default `pending` |
| `handled_by` | foreignId users, nullable | nullOnDelete |
| `handled_at` | timestamp, nullable | |
| timestamps | | |

Indexes on `status` and on `preferred_date`.

`email` is nullable on purpose. Requiring an address the system cannot write to
would collect data under a false implication that it will be used.

`broodcock_id` is what makes the catalogue-on-the-landing pay off: a visitor
scrolling the stock can ask about a specific bird and the request arrives
attached to it. `nullOnDelete` because a removed bird must not take the visit
request with it.

### `AppointmentStatus` enum

`Pending`, `Confirmed`, `Declined`, `Completed`. TitleCase keys per the PHP
conventions, with `label()` and `badgeClasses()` returning the existing badge
vocabulary, matching the five enums already doing this. `badge-info` for
pending, `badge-ok` for confirmed, `badge-alert` for declined, `badge-neutral`
for completed.

### The public form

`Appointments\RequestForm`, a Livewire component nested in the landing page.
There is no separate submit route: Livewire posts to its own endpoint, so the
form is a component action rather than a controller.

**Protections, all of which are new to this application:**

- **Rate limit.** Laravel's `RateLimiter` facade **inside the submit action**,
  keyed on the request IP, five per hour. `throttle` middleware is the wrong
  tool here — it would have to sit on Livewire's shared update endpoint, where
  it would also throttle every filter keystroke on the catalogue that sits on
  the same page. When the limit is hit the component adds a validation error
  naming the wait in minutes; no exception, no 429 page.
- **Honeypot.** A field present in the markup, hidden from people, that must
  arrive empty. A submission carrying a value is accepted with the same success
  message a real one gets and silently discarded — telling a bot it failed
  teaches it to try again.
- **Validation.** `name` required, max 120. `contact_number` required, max 40.
  `email` nullable, valid, max 190. `preferred_date` required, a date, today or
  later. `preferred_time` in the enum. `party_size` integer 1-50. `message`
  nullable, max 1000. `broodcock_id` nullable and must exist.

A successful request flashes confirmation on the page and says plainly that the
farm will ring back on the number given — never that an email is on its way.

### The review screen

`Appointments\Index` at `/appointments`, behind `auth` and `active`, listed in
the console sidebar.

A table of requests: who, when they want to come, how many, which bird if any,
and a status badge. Filterable by status, defaulting to Pending so the screen
opens on what needs action. Confirm and Decline set the status and stamp
`handled_by` and `handled_at`.

`AppointmentPolicy`: owner and staff may view and act; customers are denied;
guests are denied. This is a console screen and takes the console's 7:1
contrast, 44px targets and 16px inputs.

## Config

`config/gfms.php`, under `farm`:

```php
'phone'   => env('GFMS_FARM_PHONE', ''),
'email'   => env('GFMS_FARM_EMAIL', ''),
'hours'   => env('GFMS_FARM_HOURS', ''),
'address' => env('GFMS_FARM_ADDRESS', ''),   // exists already
```

`.env.example` gains all four with placeholder values and a comment saying the
footer reads them. Any that is empty is omitted from the footer entirely rather
than rendering a blank row or a dash.

## Design direction

The landing and catalogue are the **catalog surface**: 4.5:1 contrast minimum,
17px body text, photo-led. The review screen is **console**: 7:1, 15px, 44px
targets, 16px inputs.

Colour means bloodline and nothing else. The hero is ink, paper and rule — not a
coloured banner. Band tags stay the only coloured thing on the page. No
gradients, no `backdrop-blur`, no `font-bold`, nothing under 11px. Elevation is
the three-step `--shadow-e1/e2/e3` scale where it is used at all.

Every date, count and band number in new markup takes `.datum`.

Utility class names are verified against the built stylesheet before they are
considered done. Tailwind emits nothing and warns about nothing for a class that
does not exist — `border-hairline` shipped on 2026-09-14 and rendered as no
border at all. The token is `border-border`.

## Testing

Written test-first, as the project requires.

**Landing** — renders for a guest; renders for staff; shows the farm name and
contact details; the catalogue is present and filterable from it; a query-count
guard, because `/` becomes the most-hit public page.

**Catalogue** — every existing assertion still passes, through both
`route('catalog.index')` and the landing. The eleven component-level tests move
from `Catalog\Index` to `Catalog\Browse`.

**Appointment submission** — a valid request is stored as pending; a past date
is rejected; a missing name and a missing contact number are each rejected; a
party size outside 1-50 is rejected; a filled honeypot is silently discarded
while still showing success; the rate limit blocks the sixth attempt in an hour;
a request from a bird's page arrives with `broodcock_id` set.

**Review screen** — the owner sees pending requests first; confirm and decline
each set status, `handled_by` and `handled_at`; a customer is denied; a guest is
redirected to login.

**Guards** — `BadgeVocabularyTest` and `DesignSystemGuardTest` already scan
every Blade view and cover the new ones automatically.

Full suite green, `vendor/bin/pint`, and `npm run build` — not `dev` — before
this is called done. Screens checked at 1440px and 390px.

## Build order

This is a large piece of work for one plan, and the order matters because two
steps are refactors of already-tested code. Each phase ends green.

1. **Extract `Catalog\Browse`.** No new behaviour. Move the 22 component test
   references; the route-level assertions must pass untouched throughout. This
   is the riskiest step and it is done first, alone, while nothing else is in
   flight.
2. **The public shell.** Header and footer on `layouts::catalog`, plus the four
   `farm.*` config keys and `.env.example`. Every existing public page picks
   these up, so the existing suite is the regression check.
3. **The landing page.** `Landing\Index` at `/`, nesting `Browse`. Hero, about,
   catalogue. The visit section renders the farm's contact details at this
   point; the form arrives in step 5.
4. **Appointments, the record.** Migration, model, factory, `AppointmentStatus`
   enum, `AppointmentPolicy`. No UI. Tested through the model and policy.
5. **The request form.** `Appointments\RequestForm` on the landing, with
   validation, honeypot and rate limit. This is where the visit section becomes
   interactive.
6. **The review screen.** `Appointments\Index`, sidebar entry, confirm and
   decline.

The migration from step 4 ships without running. It is applied deliberately by
setting `RUN_MIGRATIONS=true` on Render, deploying, confirming, and setting it
back to `false`.

## Explicitly out of scope

- Any email. No confirmation to the customer, no notification to the owner.
  Adding it is a separate piece of work that starts by configuring SMTP.
- A customer-facing view of their own request's status. Customers have no
  accounts on the public side, so there is nothing to log in to and check.
- Calendar integration, availability windows, or preventing double-booking. The
  farm confirms by phone; the form collects a preference, not a reservation.
- Rescheduling. A changed plan is a phone call and a new request.
