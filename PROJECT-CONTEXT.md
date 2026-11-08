# GFMS: project context and handover

Everything someone needs to pick this project up on a new machine: what it is,
where each piece runs, its current state, how committing and pushing work, and
how to push as **clarisseee08** with a token, without touching the laptop's own
Windows/Git credentials.

Written 8 November 2026, when the project moved off a borrowed laptop.
**Moving machines? Follow section 5 in order, then paste the starter prompt
in section 12 into Claude Code.**

**This file contains no passwords, tokens or keys, and never should.**
Secrets travel separately (section 9).

---

## 1. What this is

**GFMS (Gamefowl Farm Management System)** is the record system for
**SSGuad Game Farm**, Brgy. Igcadlum, San Joaquin, Iloilo. It is a BS Information
Systems capstone: evaluated against ISO/IEC 25010 and defended to an academic
panel. It is functionally complete; treat it as production code.

It has two sides:

- **Public site (no account needed):** home page, catalogue of the farm's birds
  (`/catalog`), each bird's page (`/broodcocks/{id}`) and family tree
  (`/broodcocks/{id}/pedigree`), About, Visit, Privacy, Terms. A guest is
  treated as an active customer. A guest only ever sees birds the catalogue
  would list (`Broodcock::isPubliclyVisible()`), so a guessed URL can't show a
  dead or outside bird.
- **Staff console (sign-in required):** dashboard, broodcocks, health and
  vaccination schedule, breeding and offspring, performance, mortality, reports
  (CSV/PDF), users (owner only), farm profile (owner only), diagnostics (owner
  only).

Roles are owner, staff and customer. Policies decide every permission on the
server; views never hide things with CSS alone.

## 2. Where everything lives

| Thing | Where | Notes |
|---|---|---|
| Main repo | `github.com/clarisseee08/gamefowl` (remote `origin`) | `main` is the integration branch. Work arrives as PRs. |
| Old mirror | `github.com/CernieCriz/gamefowlms` (remote `old-origin`) | Rewritten history. See 7.6 before pushing anything there. |
| Production | `https://gamefowl.onrender.com` | Render free plan, Singapore, service `gfms-laravel` (`render.yaml`). Auto-deploys every push to `main`. Health check `/up`. |
| Database | Supabase Postgres, region ap-northeast-1 (Tokyo) | Reached through the **session pooler, port 5432**. The direct `db.<ref>.supabase.co` host is IPv6-only and times out from most Philippine connections and from GitHub. |
| Photos | Supabase Storage via its S3 API (`supabase` disk, bucket `gfms-laravel`) | Thumbnails are written beside each original at upload. |
| CI | GitHub Actions, `.github/workflows/ci.yml` | Runs the tests, `pint --test` and `npm run build` on every push to `main` and every PR. The repo is private, so minutes are metered. |
| Keep-alive jobs | `keep-render-warm.yml`, `keep-supabase-awake.yml` | Supabase pauses a free project after 7 idle days. The daily job needs the `SUPABASE_DB_URL` repo secret. |

> **Local development and production share ONE Supabase database.** Anything
> you save, delete or upload while testing locally happens to the farm's real
> records. `migrate:fresh` is refused on every machine unless
> `GFMS_ALLOW_DESTRUCTIVE_DB=true` is set deliberately. The test suite uses
> SQLite in memory and is always safe.

## 3. Stack

- Laravel 12, PHP **8.2** (not 8.3; Pest won't run, so tests are **PHPUnit 11**)
- **Livewire 4** (not 3): `Route::livewire()`, class-based components
- **Tailwind v4 CSS-first**: tokens in `@theme{}` in `resources/css/app.css`,
  mirrored from `config/gfms-brand.php`. There is no `tailwind.config.js`.
- **daisyUI 5 with a `d-` prefix** (`d-btn`, `d-input`, `d-modal`). Its own
  themes are off.
- UI follows the **EMS design system**: wrappers in
  `resources/views/components/ui/`, live reference at `/design`, decisions in
  `docs/design-audit.md`. Font: Inter. Palette: forest green. Colour means
  bloodline and nothing else.
- Fortify for auth (no self-registration; the owner creates accounts).
- dompdf for PDF reports. It is a CSS 2.1 engine: no custom properties, flex or
  grid in `resources/views/reports/pdf/`.
- Deployed as a Docker image on Render (`Dockerfile`, `docker/entrypoint.sh`).
  You never run Docker locally.

The project's own rules for AI assistants and contributors are in `CLAUDE.md`.
Code conventions are in `docs/conventions.md`. Read both before changing code.

## 4. Current state (8 November 2026)

- `main` is at `9a2240f`, after PR #9. Recent history, newest first:
  - **#9** `fix/cache-write-permissions`: dashboard 500 from root-owned cache
    folders; a cache failure no longer fails a page.
  - **#8** `perf/fast-pages`: cached console reads, local photo copies,
    month-long browser caching, prefetch on hover.
  - **#7** `redesign/ems`: the whole UI moved to the EMS design system
    (daisyUI wrappers, compact desktop density, slide-in forms, KPI tiles,
    Light/Dark/System theme).
  - Before that: `redesign/v2` (light "stud book" public site, console shell,
    sortable catalogue), cache and diagnostic fixes, and a startup-migration
    retry that survives Supabase's login circuit breaker (see 10.2).
- Test suite: **812 tests (2,484 assertions)**, all green on `main` at the time of writing.
  Re-check it rather than trusting this number.
- Other branches on `origin` (`redesign/daisyui`, `redesign/foundation`,
  `redesign/v2`, `fix/*`, `feat/public-catalogue`, `perf/fast-pages`, …) are
  already merged or superseded. `main` holds everything; branch new work from it.
- Production was healthy after the last deploy.

## 5. Moving to your own laptop, step by step

Do these in order. Each step says how to tell it worked.

### A. On the borrowed laptop, before you leave

1. **Make sure nothing is left unpushed:** `git status` and `git log origin/main..`
   for each branch you care about. Everything that matters is on `origin`.
2. **Copy the files git doesn't carry** (full list in section 11) onto a USB
   stick, or into a password manager for `.env`:
   - `gamefowlms\.env`
   - `gamefowlms\.claude\`
   - `gamefowlms\docs\frontend-analysis\` (only if you want it)
   - the thesis files in `Downloads`
   - `C:\Users\Westy\.claude\skills\`
3. **Leave the borrowed laptop clean.** It is not yours, and it still holds your
   access:
   - **Windows Credential Manager:** open *Credential Manager → Windows
     Credentials* and remove the `git:https://github.com` entry. It holds
     clarisseee08's GitHub login, and anyone using this laptop can push as you
     while it's there.
   - **Local `.env`:** after you have safely copied it, delete
     `gamefowlms\.env`. It holds the live database password.
   - **Claude Code:** sign out (`/logout`).
   - **Browsers:** sign out of GitHub, Render and Supabase.
   - **Optional:** delete the `gamefowlms` folder once everything is copied.

### B. On your laptop: tools

4. **Install the tools** by following `docs/handover-setup.md` section 1:
   - XAMPP for PHP **8.2**, then enable `pdo_pgsql`, `pgsql`, `zip`, `gd`,
     `intl` and `exif` in `php.ini` (handover-setup 1b explains this; do not
     skip it).
   - Composer 2.
   - Node 20 or newer.
   - Git for Windows.
   - Claude Code, if you want the assistant.

   Check with `php -v` (must say 8.2), `php -m` (must list `pdo_pgsql`),
   `composer -V`, `node -v` and `git --version`.

### C. On your laptop: the project

5. **Clone:**
   ```
   cd C:\Users\<you>
   git clone https://github.com/clarisseee08/gamefowl.git gamefowlms
   cd gamefowlms
   ```
   No login is needed to clone.
6. **Install the libraries:** `composer install`, then `npm ci`.
7. **Put the copied `.env` in `gamefowlms\`**, then run
   `php artisan config:clear`.
   **Check it worked:** `php artisan migrate:status` lists migrations instead
   of a connection error. It only reads; it changes nothing.
8. **Commit and push as clarisseee08 with a token** by following section 6 in
   full: make the token, run the four `git config --local` lines, create the
   credentials file.
   **Check it worked:** `git push --dry-run origin HEAD:refs/heads/auth-check`
   ends with `* [new branch]`.
9. **Build and test:**
   ```
   npm run build
   php artisan test --compact
   ```
   **Check it worked:** the build succeeds and every test passes.
10. **Run it:** `composer run dev`, or `php artisan serve`. Open the URL it
    prints, check the public pages, then sign in to the console with your
    existing account.

### D. On your laptop: the extras

11. **Put the copied local-only files back in place:**
    - `.claude\` into `gamefowlms\`
    - skills into `C:\Users\<you>\.claude\skills\`
    - thesis files wherever you keep them
12. **Set up Claude Code** (optional):
    - Install the plugins with `/plugin` (`superpowers`, `frontend-design`,
      `playwright`, `ux`).
    - Turn off commit and PR attribution in `/config` (section 7.2).
    - Paste the starter prompt from section 12 as the first message in this
      folder.
13. **First real change:** follow the flow in 7.3: a branch, small commits, the
    three local checks, push, PR, merge. Merging deploys to production.

## 6. Pushing as clarisseee08 with a token, without changing Windows credentials

Git for Windows normally hands every GitHub login to the **Windows Credential
Manager**, and it remembers one account for `github.com`. You don't want to
replace the account your laptop already uses, so the setup below applies to
**this repo only**. Everything goes into `gamefowlms/.git/config` and your
global Git and Windows credentials are never touched.

This was tested on the old laptop with Git 2.55:
- the per-repo setting fully bypasses the Credential Manager;
- the token file is what gets sent;
- a wrong token is rejected with `Invalid username or token`.

### 6.1 Make the token (signed in to GitHub as clarisseee08)

GitHub → avatar → **Settings → Developer settings → Personal access tokens →
Fine-grained tokens → Generate new token**:

- **Name:** e.g. `gfms-laptop`. **Expiration:** pick a date, and put a reminder
  in your calendar.
- **Resource owner:** clarisseee08.
- **Repository access:** *Only select repositories*, then `clarisseee08/gamefowl`.
- **Repository permissions:**
  - **Contents: Read and write.** Needed to push.
  - **Pull requests: Read and write.** Only needed to open PRs from the terminal.
  - **Workflows: Read and write.** Without it, GitHub rejects any push that
    changes a file in `.github/workflows/`.
  - Metadata: Read (added automatically).

Copy the token when GitHub shows it (it begins `github_pat_`). It is shown
**once**.

The old mirror (`CernieCriz/gamefowlms`) belongs to another account, and a
fine-grained token from clarisseee08 can't reach it. If you ever need to push
there, make a separate **classic** token with the `repo` scope (classic tokens
reach repos you are a collaborator on) and use it the same way.

### 6.2 Point this repo at the token (run inside `gamefowlms`)

```
git config --local user.name  "clarisseee08"
git config --local user.email "clarisseee08@gmail.com"

git config --local credential.helper ""
git config --local --add credential.helper "store --file=C:/Users/<you>/.gfms-git-credentials"
```

- The first two lines make every commit in this repo carry the clarisseee08
  name, whatever your global name is.
- `credential.helper ""` **empties** the helper list for this repo. That is the
  line that stops Git asking the Windows Credential Manager.
- The second helper line keeps the token in a plain-text file **outside the
  repo**. Pick any path in your user folder; use forward slashes.

Then put the token in that file, as **one line**:

```
https://clarisseee08:github_pat_XXXXXXXXXXXX@github.com
```

Use Notepad, and save it as plain text with no `.txt` added.

**Alternative:** skip the file and run a first push. Git asks for a username
(`clarisseee08`) and a password (**paste the token, not the GitHub password**)
and saves them to that file itself.

### 6.3 Check it, without pushing anything

```
git config --local --list | findstr /i "user credential"
git push --dry-run origin HEAD:refs/heads/auth-check
```

The dry run must end with `* [new branch]  HEAD -> auth-check`. It changes
nothing on GitHub. These mean something is wrong:
- `Invalid username or token`: the token is wrong or expired, or its line in
  the file is malformed.
- `Permission ... denied`: the token lacks Contents write on `gamefowl`.
- A browser login window: `credential.helper ""` is missing from this repo.

### 6.4 Opening PRs from the terminal (optional)

The `gh` CLI can use the same token for one terminal session, so you never
have to log `gh` in:

```
set GH_TOKEN=github_pat_XXXXXXXXXXXX
gh pr create --base main --head <branch> --title "..." --body "..."
```

Use `set` in cmd, or `$env:GH_TOKEN="..."` in PowerShell. Or open the PR on
github.com from the link Git prints after the push.

### 6.5 Keeping the token safe

- The credentials file is plain text. Keep it in your user folder, **never
  inside the repo**, and never paste the token into a chat, an issue or a commit.
- If it leaks, or the laptop is lost: GitHub → Settings → Developer settings →
  revoke the token, then make a new one and replace the line in the file.
- When it expires, pushes fail with `Invalid username or token`. Make a new one
  and replace the line.
- Avoid putting the token inside the remote URL
  (`https://clarisseee08:TOKEN@github.com/...`). It works, but
  `git remote -v` then prints it to anyone looking at the screen.

## 7. Committing and pushing guidelines

### 7.1 Identity

Every commit, on `origin` **and** on the old mirror, is authored and committed
as **`clarisseee08 <clarisseee08@gmail.com>`**. The `GFMS Build <claude@ai2aim.ai>`
identity in the mirror's older history is not to be used again.

### 7.2 No AI credit, anywhere

No `Co-Authored-By: Claude`, no "Generated with Claude Code", nothing like it.
That covers commit messages, PR titles, PR bodies and release notes.

- Claude Code adds attribution by default. On a new machine, turn it off in
  Claude Code's settings (`/config`). Also tell the assistant at the start of a
  session.
- Check before every push. This must print `0`:
  ```
  git log --format=%B origin/main..HEAD | grep -ci "co-authored\|generated with"
  ```
  In cmd use `findstr /i /c:"co-authored" /c:"generated with"` instead of grep.

### 7.3 The flow

1. **Start from an up-to-date `main`:**
   ```
   git switch main
   git pull --ff-only
   ```
2. **Branch for the work, never commit straight to `main`.** Names in use:
   `feat/…`, `fix/…`, `perf/…`, `design/…` or `redesign/…`, `docs/…`, `chore/…`.
   ```
   git switch -c fix/short-description
   ```
3. **Commit in small, meaningful steps** (7.4).
4. **Before pushing, make sure all three checks pass locally.** CI runs the
   same three, and CI minutes cost money:
   ```
   php artisan test --compact
   vendor/bin/pint --test
   npm run build
   ```
5. **Push the branch** (`-u` the first time):
   ```
   git push -u origin fix/short-description
   ```
6. **Open a PR into `main`** on github.com or with `gh` (6.4). CI runs on it.
7. **Merge on GitHub** when CI is green. That's how #7, #8 and #9 went in.
   **Merging deploys to production within minutes** (Render watches `main`),
   so only merge work you're ready to have live.
8. **Afterwards:** `git switch main`, then `git pull --ff-only`.

### 7.4 Commit messages

Follow the existing history:

```
type(scope): what changed, in plain words

A short body saying WHY: the problem, what it looked like to the user, and
why this fix rather than another. Wrap at about 72 characters.
```

- **Types:** `feat`, `fix`, `perf`, `design`, `docs`, `test`, `chore`, `ci`,
  `deploy`.
- **Scope:** the screen or area, e.g. `fix(dashboard): ...`,
  `design(ems): ...`, `perf(photos): ...`.
- Write the summary in the imperative or descriptive voice, lower-case after
  the colon, with no full stop.
- **Copy is the test API.** If you reword visible text, update the test that
  asserts it in the same commit.

### 7.5 Never

- **Never commit `.env`.** It is gitignored and holds the live database
  password. `.env.example` carries placeholders only.
- **Never force-push `main`** and never rewrite published history.
- **Never change the schema outside Laravel migrations.** Don't edit tables in
  Supabase Studio.
- **Never install the Supabase CLI**, and don't use Docker locally.
- **Don't commit local-only folders** (`.claude/`, `.taste-skill/`, scratch
  reports) unless that is deliberate.

### 7.6 The old mirror (`old-origin`, CernieCriz/gamefowlms)

- Its history was rewritten, so its commits have different hashes from
  `origin`'s even where the files match. **Never push `main` there, and never
  force-push.**
- Its integration branch is `gfms-laravel`. Work is mirrored onto it as a
  **new branch named `<branch>-gfms`**. Each commit is rebuilt on top of
  `old-origin/gfms-laravel` with the same file tree, using `git commit-tree`,
  keeping each commit's message and author date, with clarisseee08 as author
  and committer.
- This is optional. `origin` is the repo that deploys and the one the panel sees.
- Pushing there needs a classic token (6.1), because a fine-grained token
  from clarisseee08 can't reach it.

## 8. Everyday commands

```
composer run dev                       # app + Vite + queue, for local work
php artisan serve                      # app only (run npm run build first)
php artisan test --compact             # whole suite
php artisan test --compact --filter=Catalog
vendor/bin/pint --dirty --format agent # format changed PHP
npm run build                          # production assets: always check this, not just dev
php artisan photos:backfill-thumbnails # writes to the LIVE photo bucket
```

## 9. Secrets: what exists and how to move it

| Secret | Where it lives | Move it by |
|---|---|---|
| `.env` (DB password, Supabase S3 keys, mail password, `APP_KEY`) | `gamefowlms/.env` on the old laptop | Copy the file itself: USB stick, password manager or encrypted archive. Not chat, email or git. |
| GitHub token (clarisseee08) | your credentials file (section 6) | Don't move it. Make a new one on your laptop and revoke tokens you no longer use. |
| Render environment | Render dashboard → service → Environment | Lives on Render; nothing to move. |
| GitHub Actions secret `SUPABASE_DB_URL` | repo → Settings → Secrets and variables → Actions | Lives on GitHub. The Supabase session-pooler URI with the current DB password in it. |

**If the database password ever changes**, update all three places the same
day, or production goes down:
- `DB_PASSWORD` on Render (then **Save, rebuild and deploy**)
- `DB_PASSWORD` in your local `.env`
- the password inside the `SUPABASE_DB_URL` GitHub secret

Use letters and digits only, so it never needs URL-encoding. The password was
pasted into an AI chat on 28 October 2026; consider resetting it once more
and updating those three.

## 10. Operations notes and lessons already learned

### 10.1 Deploys
Render builds the `Dockerfile` on every push to `main`. At start-up
`docker/entrypoint.sh` caches config, routes and views, and runs
`php artisan migrate --force` when `RUN_MIGRATIONS=true`. If a deploy fails,
Render keeps the previous instance running, so a failed deploy looks like
"nothing changed".

### 10.2 `ECIRCUITBREAKER: too many authentication failures`
Supabase's pooler blocks new connections for a while after a burst of failed
logins. This happened when the DB password changed: the old instance kept
failing to log in, so the new one couldn't connect while it started. The
entrypoint now retries migrations six times, 30 seconds apart. If it still
fails, wait a few minutes and use **Manual Deploy → Deploy latest commit** in
Render.

### 10.3 "Images not loading" on production
Usually it isn't the images. The home page is served from cache, so it can
look fine while every database-backed route (photos included) returns 500. Probe
`/catalog` and `/photos/<id>` before blaming storage.

### 10.4 Supabase pausing
A free project pauses after 7 days with no activity, and only the dashboard can
un-pause it. The daily keep-awake workflow prevents this, but only while
`SUPABASE_DB_URL` is set and correct. Check Actions now and then.

### 10.5 Photos and thumbnails
A missing thumbnail is normal: the controller serves the original instead. A
thumbnail failure must never fail an upload. Thumbnails are never larger than
the original (up to 800px, sharpened), so a small original stays soft.

### 10.6 Data the farm should tidy (as of 28 October 2026)
- Birds 00995 and 1096 share the same photo.
- Bird 0113's photo is only 640px wide.
- No bird is marked for sale yet. To mark one: edit the bird and tick
  "This bird is for sale".

## 11. Files that were never in git, to copy from the old laptop

| What | Old laptop path | Why |
|---|---|---|
| `.env` | `C:\Users\Westy\gamefowlms\.env` | Section 9. |
| Local AI briefs and agents | `C:\Users\Westy\gamefowlms\.claude\` (`web-redesign-brief.md`, `agents\route-migrator.md`, `ui-auditor.md`, `ui-reviewer.md`, `skills\daisyui\`) | The redesign brief and the helper agents and skill used for UI work. Untracked; put them in the same place on the new laptop. |
| Frontend analysis | `C:\Users\Westy\gamefowlms\docs\frontend-analysis\` (`REPORT.md`, `screens\`) | An untracked analysis report. Copy it if you still want it. |
| Thesis documents | `C:\Users\Westy\Downloads\Design Specifications - GFMS.docx`, `System Architecture - GFMS.docx`, `GFMS Diagrams\`, `Thesis Diagram Kit\` | The design-specification chapter and diagrams (Figures 3–12, Tables IV–VII), numbered to match the reference paper below. |
| Reference paper | `C:\Users\Westy\Downloads\(Lague et al., 2025) Online Personal Grooming with a Subscription Based-Based Salon Services App.pdf` | The earlier approved paper the thesis chapters copy the structure of. |
| Claude Code skills | `C:\Users\Westy\.claude\skills\` (`better-ui`, `design-taste-frontend`, `high-end-visual-design`, `impeccable`, `redesign-existing-projects`, `web-animation-design`) | Personal skills; copy the folder to the same path. |
| Claude Code plugins | enabled: `superpowers`, `frontend-design`, `playwright` (claude-plugins-official), `ux` (ux-skill) | Reinstall them with `/plugin` on the new machine. |

### Working preferences the assistant had learned (from its memory notes)
- During UI redesign work, check changes in the browser, and write or run the
  tests in **one batch** when asked, not after every change. **Never commit
  without the suite green.**
- Thesis chapters are produced to match the earlier paper's order, numbering
  and style (Times 12, double-spaced). Every actor, use case, class and table
  comes from the real code (roles, policies, routes, migrations), never
  invented. Reusable prompts and drawing scripts are in the Thesis Diagram Kit.

## 12. Starter prompt for Claude Code on the new laptop

Open Claude Code inside the freshly cloned `gamefowlms` folder and paste this
as the first message. Replace the `<…>` parts first.

```text
I've just moved this project (GFMS, my capstone gamefowl farm record system)
from a borrowed laptop to my own. Before anything else, read these in full:
PROJECT-CONTEXT.md, CLAUDE.md, docs/conventions.md, and docs/handover-setup.md.
PROJECT-CONTEXT.md is the handover: what the system is, where it runs, current
state, the committing rules, and how this repo pushes as clarisseee08 with a
token.

Then help me finish setting up this laptop by working through section 5 of
PROJECT-CONTEXT.md, from part B onwards, one step at a time:
- Check what's already installed (php -v, php -m, composer -V, node -v,
  git --version) and tell me what's missing. Check that PHP is 8.2 and that
  pdo_pgsql is enabled.
- Run composer install and npm ci.
- I'll put the .env file in place myself. Don't create it, open it, print it
  or copy it, and never commit it.
- Walk me through section 6: the fine-grained token and the four
  `git config --local` lines. I'll create the token and the credentials file
  myself; never ask me to paste the token into this chat. Verify with
  `git push --dry-run origin HEAD:refs/heads/auth-check`.
- Run npm run build and php artisan test --compact. Report the exact results.

Rules for this whole project, always:
- Commit as clarisseee08 <clarisseee08@gmail.com>. Put no AI credit anywhere:
  no Co-Authored-By, no "Generated with Claude Code", in commits, PR titles or
  PR bodies. Check with the grep in PROJECT-CONTEXT.md 7.2 before every push.
- Never commit straight to main. Branch, push, open a PR, and merge only when I
  say so. Merging deploys to production on Render.
- The local .env points at the LIVE database the farm uses. Read-only queries
  only. Never save, delete or upload real records, never run migrate:fresh or
  seeders, and never run photos:backfill-thumbnails unless I say so.
- Follow CLAUDE.md for all code and UI work (EMS design system, daisyUI with
  the d- prefix, copy is the test API, PHPUnit not Pest).

When setup is done, give me a short status: what passed, what I still need to
do by hand, and anything in PROJECT-CONTEXT.md that no longer matches the code.
Then wait. My next task is: <what you want to work on>.
```
