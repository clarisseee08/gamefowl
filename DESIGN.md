---
version: 1
name: GFMS-field-ledger
description: A registry interface built like a field ledger - dark ink on pale paper, hairline rules, no shadows and no gradients, with colour spent in exactly one place: the band tag. Modelled on the anodised aluminium leg band a gamefowl actually wears, it is the only capsule and the only chroma in the system. Console density is tuned for one-handed use outdoors in Philippine daylight, where 7:1 contrast is a legibility requirement rather than a style preference.

colors:
  action: "#16324f"
  action-hover: "#1e4468"
  action-wash: "#e9eef4"
  ink: "#1a1917"
  ink-80: "#55534d"
  ink-48: "#726f66"
  canvas: "#ffffff"
  parchment: "#faf9f7"
  pearl: "#f1efea"
  hairline: "#e3e0da"
  divider: "#e3e0da"
  ok: "#1b6b44"
  ok-wash: "#e6f1eb"
  warn: "#8a5a12"
  warn-wash: "#f8efdf"
  alert: "#a32219"
  alert-wash: "#f7e8e7"
  info: "#2a4e7a"
  info-wash: "#e9eef4"
  neutral: "#5e5b55"
  neutral-wash: "#efede9"
  band-crimson: "#b3202c"
  band-cobalt: "#1b4f9c"
  band-forest: "#1e6b45"
  band-amber: "#9a5b08"
  band-plum: "#6a3080"
  band-slate: "#41525e"

typography:
  display:
    fontFamily: "Fira Sans, ui-sans-serif, system-ui, sans-serif"
    fontSize: 32px
    fontWeight: 600
    lineHeight: 1.15
    letterSpacing: -0.02em
  title:
    fontFamily: "Fira Sans, ui-sans-serif, system-ui, sans-serif"
    fontSize: 22px
    fontWeight: 600
    lineHeight: 1.25
    letterSpacing: -0.01em
  subtitle:
    fontFamily: "Fira Sans, ui-sans-serif, system-ui, sans-serif"
    fontSize: 18px
    fontWeight: 500
    lineHeight: 1.35
    letterSpacing: 0
  body:
    fontFamily: "Fira Sans, ui-sans-serif, system-ui, sans-serif"
    fontSize: 17px
    fontWeight: 400
    lineHeight: 1.55
    letterSpacing: 0
  input:
    fontFamily: "Fira Sans, ui-sans-serif, system-ui, sans-serif"
    fontSize: 16px
    fontWeight: 400
    lineHeight: 1.4
    letterSpacing: 0
  body-console:
    fontFamily: "Fira Sans, ui-sans-serif, system-ui, sans-serif"
    fontSize: 15px
    fontWeight: 400
    lineHeight: 1.45
    letterSpacing: 0
  label:
    fontFamily: "Fira Sans, ui-sans-serif, system-ui, sans-serif"
    fontSize: 13px
    fontWeight: 500
    lineHeight: 1.3
    letterSpacing: 0.01em
  caption:
    fontFamily: "Fira Sans, ui-sans-serif, system-ui, sans-serif"
    fontSize: 12px
    fontWeight: 400
    lineHeight: 1.4
    letterSpacing: 0
  micro:
    fontFamily: "Fira Sans, ui-sans-serif, system-ui, sans-serif"
    fontSize: 11px
    fontWeight: 500
    lineHeight: 1.2
    letterSpacing: 0.06em
  datum:
    fontFamily: "Fira Code, ui-monospace, monospace"
    fontSize: 15px
    fontWeight: 400
    lineHeight: 1.45
    letterSpacing: -0.01em

rounded:
  none: 0px
  chip: 2px
  sm: 4px
  control: 6px
  full: 9999px

spacing:
  xxs: 4px
  xs: 8px
  sm: 12px
  md: 16px
  lg: 24px
  xl: 32px
  xxl: 48px
  section: 64px
---

# GFMS — Field Ledger

The full brief, with rationale and measured contrast ratios, lives in
**`docs/design-brief.md`**. Colour values are owned by
**`config/gfms-brand.php`** so the PDF reports (dompdf, CSS 2.1 only) can read
them without a stylesheet; `resources/css/app.css` mirrors that config and
`tests/Unit/BrandTokensAreMirroredTest.php` asserts the two agree.

> This file replaces a previous `DESIGN.md` that was a reverse-engineered
> analysis of apple.com's marketing pages. That world was built and then
> deliberately retired: a single-accent gallery aesthetic is the right answer
> for photographs of hardware and the wrong one for a livestock registry, where
> bloodline is the primary organising fact and deserves to be encoded.

## Overview

Two surfaces share one vocabulary and differ in density, not in kind.

- **Catalog** (public) — photo-led, spacious, 17px body, ≥4.5:1, fixed 4:5 images.
- **Console** (staff) — dense, scannable, 15px body, **≥7:1**, 16px input floor,
  44px rows. Used one-handed, outdoors, in glare.

## The signature

The **band tag**: a filled capsule in the bloodline's anodised colour carrying a
two-letter bloodline code and the band number in mono. It is the only capsule
and the only chroma in the system.

`bloodline` is free text, not an enum, so colour resolves through
`App\Support\BandTag` — a curated map for known stock, then a deterministic
`crc32` fallback so an unanticipated bloodline still renders. An unbanded bird
gets a dashed outline reading "Not yet banded", never a blank cell.

## Do

- Take every colour from the tokens above.
- Set every registry value — band numbers, dates, weights, egg counts, rates —
  in `datum` (Fira Code, `tabular-nums`) so columns align.
- Keep colour paired with a second channel: the code, the label, the dot.
- Separate surfaces with a hairline and a ground change.

## Don't

- Don't use a stock Tailwind palette class (`bg-gray-100`, `text-blue-600`).
- Don't introduce a second accent; interactive means `action`.
- Don't use a band colour for anything that is not a bloodline — it is an
  identity channel, and borrowing it for status destroys the encoding.
- Don't add a shadow. Depth is not how this system communicates hierarchy.
- Don't use a gradient anywhere.
- Don't round anything above 6px except the band tag. A second capsule kills
  the signature.
- Don't set a form control below 16px — iOS zooms the page on focus.
- Don't use `font-weight: 700`. The ladder is 400 / 500 / 600.
- Don't put a Tailwind class or custom property in `resources/views/reports/pdf/`.
- Don't ship dark mode. Light only, chosen from the use scene.
