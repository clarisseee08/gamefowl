# Web redesign brief

You're redesigning this project's web app. Do every step below yourself: install, audit, design, build, check. Don't hand me commands to run. First, save this whole brief, word for word, as `.claude/web-redesign-brief.md` in the repo; don't commit it. Re-read it from there whenever the conversation is summarized. It comes from my redesigns of two other client projects; everything in quotes is what I said there, and the same bar applies here.

## Step 1: install the skills yourself, before anything else

Before you read the code or plan anything, install these skills with your own shell. Then list what got installed and what didn't.

**1. Plugins.** Use the `claude plugin` CLI from the terminal; slash commands are for me, the CLI is for you:

```
claude plugin marketplace add anthropics/claude-plugins-official
claude plugin install superpowers@claude-plugins-official
claude plugin install frontend-design@claude-plugins-official
claude plugin install playwright@claude-plugins-official
claude plugin marketplace add Laith0003/ux-skill
claude plugin install ux@ux-skill
```

**2. Design skills.** These are folders in `~/.claude/skills/`:
- Install `design-taste-frontend`, `redesign-existing-projects` and `high-end-visual-design` with `npx skills add Leonxlnx/taste-skill`, choosing Claude Code as the agent. Check `npx skills add --help` for the flags that make it run without prompts. In that repo they live under `skills/taste-skill`, `skills/redesign-skill` and `skills/soft-skill`.
- Install `impeccable`, `web-animation-design` and `better-ui` the same way. Find their repos with `npx skills find <name>` or a web search.
- If one can't be found, clone or download its repo and copy the folder that holds its `SKILL.md` into `~/.claude/skills/<name>/`.

**3. If a step fails or asks you to approve something,** tell me exactly which one. Skip only that skill and carry on with the rest. Newly installed skills may not show up until Claude Code restarts. If so, tell me in one line to restart, and when I'm back, continue from Step 2 without asking again.

**When to load each one** (with the Skill tool, right before its work starts):

| Skill | When |
|---|---|
| `redesign-existing-projects` | First: the audit-then-upgrade method, so nothing breaks |
| `ux:ux-audit` | Before any change: a look-only audit of the current pages |
| `design-taste-frontend` | Before designing any page. Output its one-line design read first, and set its dials from this brief. |
| `high-end-visual-design` | When setting the look: type, spacing, colour, depth |
| `superpowers:brainstorming` | Only to set the direction, with as few questions as possible |
| `impeccable` | For each page as you build it, then again to polish it |
| `web-animation-design` | Before writing any motion |
| `better-ui` | The final polish pass: radius, optical alignment, hit areas |
| `frontend-design:frontend-design` | Backup if `design-taste-frontend` didn't install |
| Playwright (MCP) | Screenshots at 390, 1440 and 1920 px to check your own work |

## Step 2: work out the project (tell me what you found)

Read the repo and tell me in a few lines:
- what the project is
- who uses the web app (public visitors, admins, or both)
- the stack
- where changes go: the branches, and whether there's a second repo to mirror

Ask me only about the last point if you can't tell, and ask once.

## 1. Ground rules

- **The features stay.** Restyle and restructure every page, but don't drop a page, field, or flow the app has today. If something must move, say where it went.
- **Audit first.** Before changing anything, run a look-only UX audit of the current pages (use `ux:ux-audit` if it's installed) and list the worst problems. The redesign must fix those, not just repaint.
- **Show the order first.** The work is large. Before building, show me: a one-line design read, the list of pages in the order you'll do them, and the "how this differs" list from section 4. Then go page by page, and show me each one when it's done.
- **Don't ask me about every choice.** Make the call, say it in one line, and keep going. Only ask when it changes what you build (for example, whether a page should exist at all).
- **How I react:** short messages, often in capitals, often a screenshot with "???". That means something looks wrong. Fix it straight away; don't re-ask what I meant unless you truly can't tell.
- **Before every commit:** typecheck, lint, and build all pass.
- **Safety:** never print or copy `.env` files or secrets. Against live databases or production data, look only; never submit, edit, or delete unless I say so.
- **No AI credit anywhere.** No "Generated with Claude Code", no `Co-Authored-By: Claude`, nothing like it, in commit messages, PR titles, PR bodies, or release notes. Before you finish, check them for `claude|generated with|co-authored`.

## 2. What I asked for last time (apply all of it here)

### Motion: "there's not even a single animation, a page hits hard"
- Add motion with `motion` / framer-motion (install it if missing).
- Scroll reveals; staggered lists; the hero headline rises in line by line.
- **Page transitions:** the header and nav stay put while the page content fades and rises. Scroll to the top only after the old page has exited. Preload page chunks (lazy routes) so a transition never shows a blank frame.
- Short and calm: about 200–450 ms, ease-out. Nothing bounces, nothing loops forever.
- **Respect reduced motion** (`prefers-reduced-motion` / `useReducedMotion`): content appears with no movement.
- Buttons and cards get a pressed state (a scale of about 0.97) and real hover and focus states.

### Looks: "it looks AI slop"
- **Real, licensed photos** (method in section 5) and **real screenshots** of the product where you're showing the product. No stock-illustration packs, no blob shapes, no purple gradients.
- **Asymmetric layouts.** No row of three equal feature cards. No icon-in-a-circle feature grids. Use numbered steps only where the order really matters.
- **Rows, not card soup.** Only one or two things per page are real cards; everything else is plain rows grouped with hairlines.
- **Use real content.** No lorem ipsum, no "Lorem Co.", no fake testimonials, no invented stats. If a number isn't real, leave it out.

### Space: "too many items on the nav, no area to breathe" / "why not full page?"
- Trim the top nav to about 4 links plus Sign in (or the main action). Everything else goes to the footer.
- Widen the container to about 1440 px, with responsive gutters (16 px on phones). Sections get generous vertical space.
- Check every page at **390, 1440 and 1920 px** wide, with no horizontal scroll at any of them.

### Long pages: "I don't want a long scroll"
- Split long pages with in-page tabs (a segmented control) or sections the user can jump between, so they rarely scroll far. This applies to the admin pages too.

### Login
- Redesign it: a photo or brand panel beside the form, an animated entrance, and errors that animate in under the field they belong to.
- Keep whatever sign-up behaviour the app has today, unless I say otherwise. (On one project I chose "no web sign-up, sign up in the phone app", so ask if it's unclear.)

### Small things I noticed
- **Hit targets:** every button and icon button is at least 44×44 and looks like a button. Small "Save" links in a corner were a complaint.
- **No native `alert()` / `confirm()`.** Destructive actions get a designed, in-app confirm dialog.
- **Privacy notice and Terms** are real pages inside the site, not external links or PDFs.
- **Live state stays live.** After every save, refresh what it affected (invalidate the queries). A status that says "Off" while something is on was a bug last time; look for that kind of bug across the whole app, not only where I point.
- **Loading:** skeletons shaped like the content, not spinners. Empty states get an icon, a one-line title, and a one-line next step.
- **Feedback:** use the app's existing toast library for success and error messages.

## 3. What made the last redesign look finished

Structure and density, not effects. No gradients, no glass blur, no decorative illustrations.

- **One dark anchor block per page (I liked this a lot).** Each main page or dashboard opens with one solid brand-colour block:
  - a small accent-colour eyebrow line (the date or the context)
  - a large display title
  - one oversized number (tabular figures), with its unit in a muted, smaller size beside it
  - optionally, a thin progress strip of segments
  - one primary action in the accent colour, full width or prominent, with a one-line helper under it saying why it matters
  - a secondary action as a translucent pill in the top-right corner

  It's the only dark block on a light page, so the eye always lands there first. It answers "where am I, how am I doing, what do I press" in one glance.
- **iOS-style segmented control (I liked this too).**
  - A full-width pill track on a muted grey surface, with 4 px of inner padding.
  - The selected segment is a white raised pill (shadow `0 1px 3px rgba(16,24,40,.12)`) with a dark semibold label and a dark count badge.
  - Unselected segments have no fill, a grey label, and a light-grey badge.
  - The selection slides between segments. Use it for in-page tabs instead of long scrolls.
- **A "next thing" card.** At most one card per page gets a brand-colour border and a soft tinted shadow, because it's the focus.
- **Type.**
  - A display font for titles and big numbers, and a separate text font for everything else.
  - Scale: 11 / 12 / 13 / 15 / 16 / 18 / 20 / 24 / 28, plus one big size for hero numbers. Use larger steps on the public site.
  - Sentence case everywhere, short labels, tabular numbers in tables and stats.
- **Colour.** Text passes WCAG AA, captions included. Every status colour has a darker "ink" shade for text.
- **Spacing.** A strict scale (4 / 8 / 12 / 16 / 20 / 24 / 32 / 40 / 64 / 96). Don't use one-off pixel values.
- **Icons.** One icon set, in one style, used everywhere.

## 4. It must not look like my other two client projects

My clients might compare apps. Nothing a client could spot side by side may match these two:

- **Pick Me Up:**
  - colours and type: navy + canopy green + leaf accent; fonts Lexend + Public Sans
  - icons: lucide on the web
  - layouts: large soft radii; a navy hero card with stat chips; a home with a headline at left and a map at right; two audience "door" cards; numbered steps on a drawn line; a navy proof band; a sliding nav pill in the header
  - motion: rise-and-fade on cubic-bezier(0.22, 1, 0.36, 1)
- **TriData:**
  - colours: deep teal + amber, with an M3-style tab pill
  - layout: map-first home

So this project gets its **own**:
- palette and accent role
- font pair: **Lexend (display) + Public Sans (text) is approved for this project** — amended, and it deliberately matches Pick Me Up so the two read as one house. Every other "must look different" rule below still binds: colours, shapes, icons, radii, motion and layouts stay this project's own. Not Inter, Roboto, Arial, or the system default.
- icon set or style
- corner-radius scale and card treatment
- motion signature: different easing and entrance style
- hero composition, footer layout, and nav style
- photo treatment: framing, crop, or duotone
- copy voice

Patterns like the anchor block and the segmented control are fine to use, because they're platform patterns. Build them in this project's colours, fonts, radii, and order. Before building, write a short "how this differs from Pick Me Up and TriData" list covering each item above, and show it to me with the design read.

## 5. Photos from Pexels (the method that worked)

1. **Find candidates.** Search the web for `pexels photo <subject> free stock photo`, with subjects that fit this project's place and people. Each result URL ends in the photo's numeric ID, e.g. `https://www.pexels.com/photo/11115604/`.
2. **Download by ID from the image CDN,** with a browser User-Agent; without one you get a 403:
   ```python
   import urllib.request
   url = f'https://images.pexels.com/photos/{i}/pexels-photo-{i}.jpeg?auto=compress&cs=tinysrgb&w=1600&h=1000&fit=crop'
   req = urllib.request.Request(url, headers={'User-Agent': 'Mozilla/5.0'})
   open(f'{name}.jpg', 'wb').write(urllib.request.urlopen(req, timeout=30).read())
   ```
   Set `w` and `h` to the frame: about 1000×640 for cards, larger for a hero.
3. **Look at every image before using it,** and throw out bad matches. Several first picks are usually wrong, so download a few candidates per slot and keep the best.
4. **Convert to WebP** with Pillow: `Image.open(src).convert('RGB').save(dst, 'WEBP', quality=82, method=6)`.
   - Aim for 30–150 KB each, in `public/photos/`.
   - Always set `width`, `height`, `alt`, and `loading="lazy"`; the hero image gets `fetchpriority="high"` instead.
5. **Credit them** in `public/photos/CREDITS.md`, one row per file with the photo title and its pexels.com URL. Pexels doesn't require it; I do.

## 6. Check your own work before you show me

- Run the app locally and screenshot every page you changed at 390, 1440 and 1920 px (use Playwright if it's available). Look at the screenshots yourself before telling me it's done.
- No console errors, no layout shift when photos load, no horizontal scroll, and keyboard focus is visible.
- Turn on reduced motion once and confirm every page still works and nothing moves.
- Tell me plainly what you changed, what you didn't get to, and anything that failed.
