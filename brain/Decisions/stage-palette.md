# Decision: stage palette

Date: 2026-11-24
Approved by: owner
Status: obsolete (superseded 2026-11-27 by [[homepage-console-brand]]). The homepage no longer uses stage red or the script font. `accent` and `accent-text` tokens stay for non-homepage pages (final state to confirm, see [[homepage-console-brand]]).

## Decision
Theme colours follow the cover photo (stage blue and stage red). Tokens live in `resources/css/app.css` as CSS variables.

Light mode:
- `background` #F8FAFC, `surface` #FFFFFF, `text` #0F172A
- `primary` #0284C7 (stage blue), `primary-dark` #0369A1
- `accent` #E11D48 (stage red, for fills, bars, glows)
- `accent-text` #BE123C (small red text, AA contrast)

Dark mode (`.dark`):
- `background` #07080D (near-black), `surface` #11131B, `text` #F1F5F9
- `primary` #38BDF8, `primary-dark` #38BDF8
- `accent` #F43F5E
- `accent-text` #FB7185

## Replaces
- The old sky-blue accent #0EA5E9. It is no longer used. See [[primary-dark-contrast-token]].

## Rules
- Use `accent` for fills, bars and glows only.
- Use `accent-text` for small red text. Never red body text.
- Views use token classes, never hex values.
- Photos keep `white` and `black` for text and overlays (see [[logo-and-cover-assets]]).

## See also
- [[dark-mode-css-variable-tokens]]
- [[section-backdrops]]
- [[homepage-sections]]

## Update 2026-11-24 (branch feature/light-mode-polish)
Owner feedback: light mode was too bright and glary, "Our Churches" stayed dark in light mode, and "Join Us" looked blurry and hard on the eyes.
- Light `background` changed #F8FAFC -> #EEF2F7. White `surface` cards now stand out. Dark mode unchanged.
- Muted text one step stronger site-wide: `text/70` -> `/80`, `/75` -> `/80`, `/60` -> `/70`. Card borders `border-text/10` -> `/15`. Dark mode muted text is slightly brighter too.
- Our Churches: theme-aware. Light = light background, church logo as faint watermark under a background veil, white cards, dark text, `primary-dark` links. Dark = unchanged dark photo band.
- Join Us: light = clean white band with border, no glows or black-to-white fades. Dark = unchanged near-black stage band with glows.
- This supersedes the earlier rule that Churches and Join are dark bands in both themes. The hero stays dark in both themes.

## Update 2026-11-27
- Obsolete: superseded by [[homepage-console-brand]].
- The light background drift is resolved: `#F8FAFC` is the light background now (the 2026-11-24 #EEF2F7 value is no longer used).
