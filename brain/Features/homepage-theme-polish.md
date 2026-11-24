# Homepage theme polish
Status: done (pending owner browser check at 360px and in light and dark mode)
Phase: 1

Related: [[00-Index]], [[Roadmap]], [[homepage-hooks]], [[section-backdrops]], [[stage-palette]], [[dark-mode]], [[card-style-and-section-rhythm]]

Goal: better theme, lighting, consistency and light/dark balance on `/`. Owner request 2026-11-24.

Users: visitor

Method: read-only design audit gave 12 items. Built in 3 slices (branch `feature/homepage-hooks`).

Slices:
- A. Stage lighting (`resources/css/app.css`):
  - Backdrop alphas raised: light about 0.18-0.25, dark about 0.30-0.35.
  - Second accent cone beam.
  - Spotlight-left accent glow.
  - `backdrop-haze` layer.
  - `glow-primary` and `glow-accent` as radial gradients, replacing blur blobs.
  - `mix-blend` skipped: it darkened fade edges.
- B. Rhythm (Blade sections):
  - Strict surface/background alternation ([[card-style-and-section-rhythm]]).
  - Hero bottom fade.
  - Join fades and accent hairline.
  - "Stay updated" spacing and backdrop.
  - Impact strip uses the section header.
  - Eyebrows: "Our impact", "Who we are", "How we serve".
  - AA contrast fixes on numbers and body text.
- C. Cards:
  - `rounded-3xl`, `border-text/10`, `shadow-sm`.
  - Dark mode: `border-text/10` (token flips), dark hover glow `dark:hover:shadow-primary/20`. Review follow-up removed raw `dark:border-white/10`.
  - Gallery `rounded-3xl`.
  - Services list 360px overflow fix.

- D. What We Do cards (branch `feature/what-we-do-cards`, commit 8d2e5e5), 2026-11-24:
  - 2x2 grid at md+ (`max-w-5xl`), icon beside text from sm+, stacked on mobile.
  - Titles shortened: "FOH (Front of House)" to "Front of House".
  - Tag pills (FOH, On stage, Online, Setup) use primary tokens. Labels are UI wording chosen by Claude, not church facts; owner may reword.
  - Top gradient bar, faint watermark numbers (aria-hidden), hover lift removed (cards are not links).
  - Test `tests/Feature/HomepageSectionsTest.php` expects "Front of House".
  - Checked: 1280px light, 360px dark, no horizontal scroll. Tag contrast about 5.2:1 light, 8:1 dark.

Skipped: audit item to re-add FAQ. Owner removed FAQ (see [[homepage-hooks]]).

Data changes: none.
Permissions: public, read-only.

Remaining TODOs:
- Owner browser check at 360px, light and dark mode.

Out of scope:
- New sections or copy changes.

Tasks: [[homepage-hooks-tasks]]
Notes / decisions: [[card-style-and-section-rhythm]], [[section-backdrops]], [[stage-palette]]
