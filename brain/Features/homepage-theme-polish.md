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
  - ~~Eyebrows: "Our impact", "Who we are", "How we serve".~~ Partly superseded 2026-11-25 by slice K: only "Our impact" and "Our churches" keep an eyebrow.
  - AA contrast fixes on numbers and body text.
- C. Cards:
  - `rounded-3xl`, `border-text/10`, `shadow-sm`.
  - Dark mode: `border-text/10` (token flips), dark hover glow `dark:hover:shadow-primary/20`. Review follow-up removed raw `dark:border-white/10`. (Hover glow superseded 2026-11-25 by slice K: `dark:hover:shadow-black/60`.)
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
  - `resources/views/components/home/hero.blade.php`: photo wrapped in a centred frame capped at 110rem, with side fades from 2300px. The wordmark stays beside the text column. (Cap and fade superseded 2026-11-25 by slice K.)
  - Checked with Playwright at 360, 768, 1024, 1440, 1920 and 2760px, light and dark. Tests: 434 (433 pass, 1 skipped).
  - Owner browser check on a wide screen still pending.

- H. Homepage audit fixes (2026-11-25): impeccable audit at 360-2760px, light and dark, score 14/20. Items 1-6 fixed. Items 7-9 were parked in [[Backlog]] and are now done (see slice K). Report in `.impeccable/audit/` (not committed; owner to decide commit vs `.gitignore`).
- I. Gallery thumbnails (branch `fix/gallery-image-performance`, commits eaa4fdf, fdb12f1, b0881a1), 2026-11-25: WebP thumbnails at 800 and 1600px (capped at photo width) via PHP GD. Grid uses srcset and lazy loading; lightbox image renders only while open. Backfill once on production with `php artisan gallery:thumbnails`. See [[gallery-thumbnails-gd]].
- J. Mobile polish (branch `fix/mobile-polish`, commit 07d7f29), 2026-11-25: 44px tap targets on church Facebook links, Add to calendar, Get directions and the Join-the-team link; `text-wrap: pretty` on p, blockquote, figcaption and li; light-mode church-logo watermark removed from Our Churches (dark mode keeps the photo band); hero pill on one line at 320-360px; Sunday time chips in an equal-width grid.
- Merged to main (merge 1bc4ff0), 2026-11-25. Tests: 447 total (446 pass, 1 skipped). Reviewer run; must-fix items addressed.
- K. Homepage audit backlog items 7-9 (branch `fix/audit-backlog`, commits 4354f9f and f307974), 2026-11-25; merging to main:
  - Item 7 (owner chose "Balanced"): removed section eyebrows that only repeated the heading (Who we are, Photos, Services, Join the team, Stay Updated, Contact, Our team). Kept "Our impact" and "Our churches". Impact numbers are solid `text-primary-dark` instead of gradient text (AA in both themes). Dark-mode coloured hover glows (`shadow-primary/20`) replaced by `dark:hover:shadow-black/60`. Hero "Audio" gradient kept (echoes the logo). Section backdrops and blobs kept.
  - Item 8: hero photo frame `max-w-[min(110rem,2000px)]` (1760px at 16px root, 1980px from 1920px, 2000px from 2400px). Side fade from 1981px. The largest cover source is 1600px, so a wider cap needs a larger original (owner content TODO, see [[Backlog]]).
  - Item 9: `prefers-reduced-motion` no longer sets all durations to 0.01ms. `transition-property` is limited to colour, opacity and shadow, so transforms snap but state fades remain. Animations still effectively off.
  - Reviewer: no remaining issues. Nice-to-have: dark-mode hover feedback is now subtle (border and shadow); check on a device.
  - Tests: 447 total (446 pass, 1 skipped).

Skipped: audit item to re-add FAQ. Owner removed FAQ (see [[homepage-hooks]]). (obsolete: FAQ removed fully 2026-11-25, see [[faq-removed]])

Data changes: none.
Permissions: public, read-only.

Remaining TODOs:
- Owner browser check at 360px, light and dark mode.
- Owner check of dark-mode hover feedback on a device (border and shadow only).
- Larger cover photo (2560px+) so the hero frame cap can widen (see [[Backlog]]).

Out of scope:
- New sections or copy changes.

Tasks: [[homepage-hooks-tasks]]
Notes / decisions: [[card-style-and-section-rhythm]], [[section-backdrops]], [[stage-palette]]
