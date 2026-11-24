# Decision: stage palette

Date: 2026-11-24
Approved by: owner
Status: accepted

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
