# Redesign progress — "Registry, running as software"

Resume file. If a session is interrupted, read this and continue from the first
unchecked step rather than restarting.

**Rollback:** `git tag design/field-ledger-v1` (`a6b640a`). Earlier world at
`design/apple-v1` (`3938e56`).

**Direction:** replacing the field-ledger's no-elevation / single-accent / paper
rules with a product UI that has depth, state and motion. Keeping the band tag,
the bloodline resolution logic, mono registry data, the pedigree connectors, and
the rule that colour means bloodline.

---

## Steps

- [x] 1. Tokens into `config/gfms-brand.php` + Tailwind theme (elevation, radius, motion, semantic pairs)
- [ ] 2. Rebuild `/design` against new tokens — screenshot + critique before proceeding
- [ ] 3. App shell — sidebar, top bar, breadcrumbs
- [ ] 4. Command palette (⌘K)
- [ ] 5. Card / table / form / badge components
- [ ] 6. Dashboard — stat tiles, sparklines, compliance bars
- [ ] 7. Catalogue index + bird detail
- [ ] 8. Pedigree — elevation, hover, focus affordance
- [ ] 9. PDF templates
- [ ] 10. Dark mode (only if 1–9 complete and green)

## Decisions taken

**1. Band foregrounds are resolved, not fixed.** §3.1's band hexes are brighter
than the previous anodised set, and three of the six cannot carry white text —
amber measures **2.08:1**, which is unreadable. Darkening them to fit white would
have walked amber straight back to the muted gold this direction replaced. So the
hexes stay exactly as specified and `BandTag::foreground()` picks white or deep
ink per band. This also covers the hash fallback, where the colour is not known
in advance.

**2. `muted-foreground` darkened from `#667069` to `#4E5550`.** The spec value
measures 4.96:1 on background and 4.70:1 on muted — both clear AA, but the same
document keeps the Console 7:1 floor. Same hue, lower lightness: 7.40 / 7.67 /
7.01 across the three grounds.

**3. `warning` darkened from `#A87409` to `#906308`.** At the specified value it
was 3.68:1 on its own tinted background — the one semantic pair that failed.

**4. The two-step ink scale collapsed to one.** `ink-80` and `ink-48` both map to
`muted-foreground`; the new value already clears 7:1, so a second, lighter step
would only have reintroduced the failure the old `ink-48` had on pearl.

## Open concerns

_(appended as they happen)_

## Could not do

_(appended as they happen)_
