# Making this a defensible thesis

Written after auditing what the system actually contains, not from a generic
checklist. Split into what already exists but is **invisible**, and what is
genuinely **missing**.

**The core risk is not the code.** The build is in good shape. The risk is that
an Information Systems capstone is graded on its *evaluation*, and the strongest
engineering in this project cannot be seen in a demo.

---

## P0 — will be asked, and currently has no answer

### 1. There is no ISO/IEC 25010 evaluation instrument

The thesis states the system is evaluated against ISO/IEC 25010. Nothing in the
repository is an instrument: no questionnaire, no respondent set, no collected
data, no computed scores. A panel will open here, because this *is* the thesis —
the software is the artefact, the evaluation is the contribution.

What is needed, in order:

- A questionnaire mapped to the 25010 characteristics being claimed. Do not
  claim all eight. Pick the ones the system genuinely addresses — **Functional
  Suitability, Usability, Reliability, Security, Maintainability** — and say
  explicitly in Chapter 3 why Portability and Compatibility are out of scope.
  A narrowed, justified scope defends better than a wide, thin one.
- Respondents in two groups, because they answer different questions:
  the **farm's own staff** (3 roles exist: owner, record keeper, customer) for
  Usability and Functional Suitability, and **IT/domain evaluators** for
  Security and Maintainability.
- A 4- or 5-point Likert instrument with the mean and standard deviation per
  characteristic, and the interpretation scale stated *before* the results.
- The raw responses in an appendix. A panel that cannot see the raw data will
  assume it does not exist.

**This is the single highest-value thing left to do, and no amount of further
code improves it.**

### 2. The thesis title no longer matches the running system

Flagged previously and still outstanding: the paper says *Digital Broodcock Farm
Record Management System*; the application says *Gamefowl Breeding Management
System*. Title page, abstract, every chapter reference, figure and table
captions, the class diagram, and the appendices. A mismatch here is the first
thing a panel notices and the easiest to avoid.

### 3. No documented backup and recovery procedure

The database is Supabase Postgres in Tokyo. There is no written answer to "what
happens when this fails, and how does the farm get its data back?" — which is a
standard question for any records system, and a fair one for a farm that would
lose its breeding history.

Minimum viable answer: Supabase's own PITR/backup tier documented with retention
period, plus a manual `pg_dump` export procedure the farm can run, plus a stated
RPO/RTO even if it is modest. **Say it in one page rather than improvising it at
the defense.**

---

## P1 — strong work that is currently invisible

Each of these already exists. None of it can be seen in a demo, so it has to be
put in the paper deliberately.

### 4. An authorization matrix

Nine Policy classes enforce per-action permissions, and middleware only decides
whether a request reaches a screen at all. That distinction is a genuinely good
design decision and there is no single artefact showing it.

Produce one table: **rows = actions** (view bird, edit bird, delete bird, record
mortality, manage users, run reports…), **columns = the three roles**, cells =
allowed/denied. It is a half-page table that answers an entire category of panel
questions, and it is derivable from the existing policies.

### 5. The test suite as evidence

**488 automated tests** is well beyond what a capstone normally shows, and it is
worth a section rather than a footnote — but only if you explain what *kinds* of
tests they are, because "488 tests" alone reads as a number:

- **Behavioural tests** — the boundary between "due today" and "overdue" in the
  vaccination schedule, mortality rate denominators, pedigree completeness.
- **Query-count tests** — assert no N+1 on the bird list, pedigree, dashboard and
  catalogue. This is a *performance guarantee enforced by the build*, which is a
  much stronger claim than a stopwatch reading.
- **Guard tests** — design-system and naming rules that fail the build on drift.
- **Authorization tests** — every policy path.

Include one concrete example of a test catching a real defect. There are several
in the git history; they are more persuasive than the count.

### 6. Data integrity at the database level

There are `CHECK` constraints in the migrations, soft deletes on 7 models, and
an activity log on 9. Together those are a real answer to "how do you know the
data is correct and how do you recover from a mistake?" — currently unstated.

### 7. Security measures actually implemented

All present, none written down: login throttled to **5 attempts per minute per
email+IP**, hashed passwords, CSRF on every form, no self-registration (accounts
are created by the owner only), policy checks on every action, private photo
storage streamed through an authorizing controller rather than public URLs, and
`.env` excluded from version control.

Write these as a short table with the threat each addresses. It is the
Security characteristic of 25010, evidenced.

### 8. Deployment and environment

Currently XAMPP locally against Supabase. State the intended production
environment, the PHP/Postgres versions, and the deployment steps. If it will run
on the farm's own machine rather than hosted, say so and justify it — a panel is
fine with a modest answer, and hostile to a missing one.

---

## P2 — would strengthen it further

### Front end

- **Usability testing with the actual farm staff.** Even 5 participants and a
  System Usability Scale score is real primary data, and it is the difference
  between "we believe it is usable" and "it scored 82".
- **An accessibility conformance statement.** The measured data already exists:
  0 contrast failures, 0 touch targets under 44px, no horizontal scroll at
  390px, WCAG-referenced ratios. That is a defensible WCAG 2.1 AA claim for the
  screens tested — state the scope honestly rather than claiming the whole app.
- **Poor-connection behaviour.** The farm is in Nueva Ecija and the database is
  in Tokyo. A slow or dropped connection is a realistic condition, and what the
  interface does about it is a fair question. Loading states exist; a documented
  answer does not.

### Back end

- **A short data dictionary** — table, column, type, constraint, meaning.
  Tedious, mechanical, and panels ask for it.
- **Explain the free-text `bloodline` decision.** It is a genuinely interesting
  design trade-off (extensibility vs referential integrity) with a real solution
  in the code. It is the kind of specific decision that demonstrates judgement.
- **Say deliberately that there is no public API.** Not an omission — a scope
  decision, consistent with a single-farm internal system.

---

## What NOT to do before the defense

- **Do not add features.** The system already does more than the objectives
  require. Every new feature is new surface to be questioned and new code to
  break.
- **Do not refactor internals for tidiness.** The `gfms` prefix, the route
  names, the table names — all invisible to a panel and all risk.
- **Do not chase dark mode or further visual polish.** The interface is past the
  point where more of it changes a grade.

Spend the remaining time on the **evaluation instrument and the paper**. That is
where the marks are.
