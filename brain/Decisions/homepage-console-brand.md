# Decision: homepage console brand
Date: 2026-11-27
Approved by: owner
Status: accepted
Supersedes: [[typography-matches-logo]], [[stage-palette]] (both obsolete). Part of [[homepage-redesign]].

## Decision
A new brief overrides the earlier brand lines for the public homepage. Concept: "console at night, but clean", based on a live-sound console.

Fonts (both from Bunny, loaded without blocking first paint):
- `sans`: Schibsted Grotesk (headings and body).
- `mono`: JetBrains Mono (labels, times, stats, readouts).
- Remove `font-script` (Mrs Saint Delafield) from the homepage. Montserrat is no longer used on the homepage.

Colours (Tailwind tokens, not hex in views):
- Light: `background` #F8FAFC, `surface` #FFFFFF, `text` #0F172A, `primary` #0284C7.
- `signal` #0EA5E9 (dark #38BDF8). Blue LED accent only, for active, live and next states.
- `console` #0F172A. Dark hero and dark CTA band.
- Dark theme: `background` #07080D, `primary` and `signal` #38BDF8.
- No red on the homepage. Red accent and the script font are dropped.

Shape:
- Radius `rounded-console` 6px, one token.
- Cards: 1px `border-text/10`, no shadow.

Dark mode and its navbar toggle stay. Default follows the device setting.

Scope:
- App and guest layouts (login, dashboard) load the new fonts too. Their full restyle is parked in [[Backlog]].

## `accent` tokens (resolved)
- `accent` and `accent-text` stay defined in `tailwind.config.js` and `app.css` for non-homepage pages (dashboard badges and similar). They are not used on the homepage. No app-wide remap was done (owner said not to touch pages outside the homepage). A full restyle of login and dashboard is parked in [[Backlog]].

## Replaces
- [[typography-matches-logo]]: Montserrat and script accent. Now obsolete.
- [[stage-palette]]: stage red and accent tokens on the homepage. Now obsolete. The `#F8FAFC` vs `#EEF2F7` drift in that note is resolved: light background is `#F8FAFC`.

## Related rules updated
- [[card-style-and-section-rhythm]]: update 2026-11-27. Section rhythm is now `py-24 lg:py-32` via the `x-section` component. Card rule still applies, shadow and radius replaced.
- [[section-backdrops]]: update 2026-11-27. Backdrops are not used on the homepage.
- [[hero-theme-verse]]: update 2026-11-27. The redesigned hero still shows the verse (first panel), hidden when empty.
