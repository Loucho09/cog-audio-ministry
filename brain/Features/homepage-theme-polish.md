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

- D. (superseded 2026-11-25: What We Do section removed, see [[what-we-do-removed]]) What We Do cards (branch `feature/what-we-do-cards`, commit 8d2e5e5), 2026-11-24:
  - ~~2x2 grid at md+ (`max-w-5xl`), icon beside text from sm+, stacked on mobile.~~ (superseded 2026-11-24 by slice E)
  - ~~Titles shortened: "FOH (Front of House)" to "Front of House".~~ (superseded by slice E: tab buttons use short names)
  - ~~Tag pills (FOH, On stage, Online, Setup) use primary tokens.~~ (superseded by slice E: tags now shown in the panel only)
  - ~~Top gradient bar, faint watermark numbers (aria-hidden), hover lift removed.~~ (superseded by slice E)
  - Test `tests/Feature/HomepageSectionsTest.php` expects "Front of House" (still valid; slice E adds a tabs ARIA test).
  - Checked (commit 8d2e5e5): 1280px light, 360px dark, no horizontal scroll. Tag contrast about 5.2:1 light, 8:1 dark.

- E. (superseded 2026-11-25: What We Do section removed, see [[what-we-do-removed]]) What We Do role picker (branch `feature/what-we-do-cards`, commits 81ab9a8 and 287a5c9), 2026-11-24:
  - Owner asked for "more interactive, less text". 2x2 cards replaced by a role picker in `resources/views/components/home/what-we-do.blade.php`.
  - 4 tab buttons (icon + short name: FOH, Monitors, Livestream, Stage Tech) and one panel with title, tag pill, bold one-line summary and one short detail line.
  - Alpine.js, WAI-ARIA tabs pattern: arrow keys wrap, Home/End, roving tabindex. Alt/Ctrl/Meta combos ignored (Alt+Left still goes Back).
  - Panels share one grid cell (`[grid-area:1/1]`), hidden via invisible/opacity, so height does not jump. `motion-reduce` on tab transitions. Dark active glow server-rendered.
  - Section lead: "Pick a role to see what it does."
  - Copy shortened from the slice D descriptions with no new facts. Owner to check wording.
  - Tests: 146 total (145 pass, 1 skipped). New Pest test covers tabs ARIA wiring (tabindex, hidden panels). Pint clean. Reviewer: no must-fix.
  - Known limitation (accepted): without JS only the first role is visible.
  - Checked with Playwright: 1280px light, 360px dark, keyboard behaviour.

- F. Desktop layout (branch `feature/desktop-layout`, commit 806ce7d), 2026-11-24:
  - One container `max-w-7xl` for navbar, all sections and footer (replaces `max-w-6xl` shrinking to `max-w-2xl`-`max-w-4xl`). See [[desktop-container-and-splits]].
  - lg two-column splits for impact, What We Do, services, contact and stay updated.
  - Hero image width-fit on lg with bottom fade (fixes cropped wordmark behind text).
  - Checked with Playwright at 360, 1440, 1920 and 2560px: no horizontal scroll. Tests 145 pass, 1 skipped.

- G. Wide-screen balance (branch `fix/wide-screen-layout`, commit 0872038, branched from `feature/remove-what-we-do`, unmerged), 2026-11-25:
  - Problem (owner report, PC/laptop zoomed out or very wide window): content capped at `max-w-7xl` (1280px) while the hero photo stretched to the viewport. The photo's "COG Audio" wordmark drifted far right with empty space between, and everything looked small.
  - `resources/css/app.css`: root font-size 112.5% (18px) at min-width 1920px, and 131.25% (21px) at 2400px. The rem-based layout scales, so `max-w-7xl` becomes 1440px / 1680px. Percentages respect the user's browser font setting. Below 1920px nothing changes.
  - `resources/views/components/home/hero.blade.php`: photo wrapped in a centred frame capped at 110rem, with side fades from 2300px. The wordmark stays beside the text column.
  - Checked with Playwright at 360, 768, 1024, 1440, 1920 and 2760px, light and dark. Tests: 434 (433 pass, 1 skipped).
  - Owner browser check on a wide screen still pending.

Skipped: audit item to re-add FAQ. Owner removed FAQ (see [[homepage-hooks]]). (obsolete: FAQ removed fully 2026-11-25, see [[faq-removed]])

Data changes: none.
Permissions: public, read-only.

Remaining TODOs:
- Owner browser check at 360px, light and dark mode.

Out of scope:
- New sections or copy changes.

Tasks: [[homepage-hooks-tasks]]
Notes / decisions: [[card-style-and-section-rhythm]], [[section-backdrops]], [[stage-palette]]
