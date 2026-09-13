---
version: 2
name: GFMS-registry-software
description: A gamefowl breeding registry that presents as a live product rather than a printed document. Tinted-neutral surfaces with a three-step elevation system, a peacock brand scale, and full semantic colour pairs for status. The console is a full-bleed application shell whose main region is the only scroll container. Colour still means bloodline and nothing else - the band tag, modelled on the anodised leg band a gamefowl actually wears, is the one identity channel and resolves its own foreground so every band stays legible. Console density is tuned for one-handed use outdoors in Philippine daylight, where 7:1 contrast is a legibility requirement rather than a style preference.

colors:
  background: "#fbfbfa"
  card: "#ffffff"
  muted: "#f4f5f3"
  popover: "#ffffff"
  border: "#e4e6e2"
  input: "#dfe2dd"
  foreground: "#161c19"
  muted-foreground: "#4e5550"
  card-foreground: "#161c19"
  popover-foreground: "#161c19"
  primary-50: "#edf7f7"
  primary-100: "#d2ecec"
  primary-200: "#a6d8d9"
  primary-400: "#35a0a6"
  primary: "#0d6e75"
  primary-600: "#0a5c62"
  primary-700: "#08494e"
  primary-900: "#052b2e"
  primary-foreground: "#ffffff"
  success: "#1f7a4d"
  success-bg: "#e8f4ee"
  warning: "#906308"
  warning-bg: "#fbf3e2"
  destructive: "#b3261e"
  destructive-bg: "#fbeae9"
  destructive-foreground: "#ffffff"
  info: "#3c4a8a"
  info-bg: "#eceef8"
  band-ember: "#e8552e"
  band-amber: "#f2a413"
  band-jade: "#1f9e6b"
  band-cobalt: "#1d5fd0"
  band-plum: "#8e44ad"
  band-rose: "#d6336c"
  band-fg-light: "#ffffff"
  band-fg-dark: "#10201b"
  print-rule: "#e4e6e2"
  print-rule-strong: "#c7cbc5"
  print-zebra: "#f7f8f6"

typography:
  display:
    fontFamily: "Inter, ui-sans-serif, system-ui, sans-serif"
    fontSize: 30px
    fontWeight: 600
    lineHeight: 1.15
    letterSpacing: -0.015em
  page-title:
    fontFamily: "Inter, ui-sans-serif, system-ui, sans-serif"
    fontSize: 26px
    fontWeight: 600
    lineHeight: 1.2
    letterSpacing: -0.015em
  figure:
    fontFamily: "JetBrains Mono, ui-monospace, monospace"
    fontSize: 34px
    fontWeight: 600
    lineHeight: 1.05
    letterSpacing: -0.01em
  figure-sm:
    fontFamily: "JetBrains Mono, ui-monospace, monospace"
    fontSize: 28px
    fontWeight: 600
    lineHeight: 1
    letterSpacing: -0.01em
  title:
    fontFamily: "Inter, ui-sans-serif, system-ui, sans-serif"
    fontSize: 19px
    fontWeight: 600
    lineHeight: 1.3
    letterSpacing: -0.015em
  subtitle:
    fontFamily: "Inter, ui-sans-serif, system-ui, sans-serif"
    fontSize: 18px
    fontWeight: 500
    lineHeight: 1.35
    letterSpacing: 0
  # The Catalog surface's body size. The two-surface split gives the public
  # pages 17px and the console 15px, so this step is real and in active use
  # across 18 views - it was simply missing from this ramp, which is why a
  # design hook kept flagging a documented size as off-scale.
  bodyCatalog:
    fontFamily: "Inter, ui-sans-serif, system-ui, sans-serif"
    fontSize: 17px
    fontWeight: 400
    lineHeight: 1.6
    letterSpacing: 0
  input:
    fontFamily: "Inter, ui-sans-serif, system-ui, sans-serif"
    fontSize: 16px
    fontWeight: 400
    lineHeight: 1.4
    letterSpacing: 0
  body:
    fontFamily: "Inter, ui-sans-serif, system-ui, sans-serif"
    fontSize: 15px
    fontWeight: 400
    lineHeight: 1.5
    letterSpacing: 0
  body-dense:
    fontFamily: "Inter, ui-sans-serif, system-ui, sans-serif"
    fontSize: 14px
    fontWeight: 400
    lineHeight: 1.45
    letterSpacing: 0
  label:
    fontFamily: "Inter, ui-sans-serif, system-ui, sans-serif"
    fontSize: 13px
    fontWeight: 500
    lineHeight: 1.3
    letterSpacing: 0.01em
  caption:
    fontFamily: "Inter, ui-sans-serif, system-ui, sans-serif"
    fontSize: 12px
    fontWeight: 400
    lineHeight: 1.4
    letterSpacing: 0
  micro:
    fontFamily: "Inter, ui-sans-serif, system-ui, sans-serif"
    fontSize: 11px
    fontWeight: 600
    lineHeight: 1.2
    letterSpacing: 0.07em
  datum:
    fontFamily: "JetBrains Mono, ui-monospace, monospace"
    fontSize: 15px
    fontWeight: 400
    lineHeight: 1.45
    letterSpacing: -0.01em

rounded:
  none: 0px
  chip: 2px
  band: 3px
  sm: 6px
  md: 10px
  lg: 14px
  full: 9999px

spacing:
  xxs: 4px
  xs: 8px
  sm: 12px
  md: 16px
  lg: 24px
  xl: 32px
  xxl: 48px
  section: 56px
---

# GFMS — Registry, running as software

Replaces the "field ledger" direction. That direction was executed correctly —
no elevation, one accent colour, print-derived austerity — and those are exactly
the properties that made it read as a printed document rather than a product. It
is preserved at the tag `design/field-ledger-v1`.

The full record of what changed, why, and what was measured lives in
**`docs/redesign-progress.md`**. Colour values are owned by
**`config/gfms-brand.php`** so the PDF reports (dompdf, CSS 2.1 only) can read
them without a stylesheet; `resources/css/app.css` mirrors that config and
`tests/Unit/BrandTokensAreMirroredTest.php` fails if the two drift.

## What carried over unchanged

- **The band tag.** Subject-derived, functional, memorable — the best thing in
  the app. Restyled, not redesigned.
- **Bloodline resolution over free text.** `bloodline` is a nullable
  `varchar(120)`, not an enum. Colour resolves through a curated map, then a
  deterministic `crc32` fallback, so a bloodline nobody anticipated still
  renders. "Not yet banded" is an honest state, never a blank cell.
- **Monospace for registry data** with `tabular-nums`, so digits align down a
  column.
- **The pedigree connectors** and the removal of sex-as-colour.
- **Colour means bloodline and nothing else.** Status uses the semantic pairs;
  navigation uses the brand scale; the six band colours mean one thing.

## What is new

- **Elevation**, three steps. It indicates interactivity or layering, never
  decoration. Nothing lifts on its own.
- **A full semantic palette.** Tinted status grounds are the point — this is
  where "one accent only" is deliberately abandoned.
- **Motion**: 120ms on colour and focus, 200ms on elevation, 260ms on entry.
  `prefers-reduced-motion` strips travel and transform while preserving the
  state change.
- **A full-bleed application shell.** The main region is the only scroll
  container in the console; the body never scrolls.

## Rules that are enforced, not just written down

`tests/Unit/DesignSystemGuardTest.php`:

1. No stock Tailwind palette class in any Blade view.
2. None in the compiled bundle either — prose in a scanned file compiles just
   like markup, and a design document's own anti-pattern list was generating the
   utilities it forbade.
3. No hardcoded hex outside `config/gfms-brand.php` and this theme layer.
4. No colour of its own in any PDF template.
5. No console view centres itself in a page-scale column.

`tests/Unit/BrandTokensAreMirroredTest.php` additionally asserts every band is
legible with its resolved foreground, that the code chip's tint moves the ground
away from the text rather than toward it, that console text clears 7:1 on all
three surfaces, and that every semantic pair clears 4.5:1.

## Band foregrounds are resolved, not fixed

Three of the six bands cannot carry white text — amber measures **2.08:1**.
Darkening them until white worked would have returned amber to the muted gold
this direction replaced. So each tag picks the foreground that passes, which also
covers whatever colour the hash hands an unanticipated bloodline.
