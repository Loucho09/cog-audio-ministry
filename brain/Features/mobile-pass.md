# Mobile pass (375–430px portrait, then 768px)
Status: planned (owner brief 2026-11-27, waiting for go-ahead on slices)
Phase: 1 (homepage rework)
Goal: the homepage feels built for phones first: stacked console hero with its own short chapter story, a full-screen menu, one even rhythm across sections, no horizontal scroll, smooth scrolling.
Users: visitor (phones and tablets)
Screens / routes: `/` (hero, all homepage sections), public navbar (all public pages).
Data changes: none.
Permissions: public.
Out of scope: desktop layout changes, admin/dashboard pages, new packages.
Tasks: [[mobile-pass-tasks]]
Notes / decisions: [[hero-console-story]], [[hero-join-brand-exception]], [[homepage-console-brand]]

Test widths: 375, 390, 414, 430 portrait, then 768.

## Hero (mobile)
- Stacked: text on top, 3D desk below (bottom ~45% of the screen), angled so the faders face the viewer. `100svh`.
- Wordmark: "COG" clamp(56px, 18vw, 84px), "Audio" script scaled with it and overlapping the G, "MINISTRY" letter-spacing 0.35em. Never wraps or overflows. (Open question: the script font was dropped on 2026-11-27, see [[homepage-console-brand]].)
- Hide the Psalm quote and the long paragraph on mobile; the quote moves to About (mobile). Keep: eyebrow label, wordmark, one-line tagline, buttons.
- Buttons full width, stacked, min-height 48px, Join us first. Tooltip above Join us on one line (shorter: "● Volunteers needed").
- No hover-only effects (magnet, hover scramble). On tap: press scale, sound-wave burst, scramble to "LET'S GO", then the form. Keep meter bars, border light, sonar (every 5s on mobile).

## 3D + chapters (mobile)
- Lighter scene: no DOF, no bloom, fewer instances, 1K textures, DPR cap 1.5, antialias off if FPS < 50. Target 50–60fps on a mid-range Android.
- Pin ~250vh. Portrait framing: fader close-up shows the fader vertically; the mix shot frames one bay.
- Chapter text in the top third, max 2 lines, 22–26px.
- No mouse tilt; gyroscope tilt only if no permission prompt is needed (otherwise skip).
- No jumps on iOS Safari: `ScrollTrigger.normalizeScroll(true)` if needed, `ignoreMobileResize: true`.
- Poster shows instantly; if WebGL fails or the phone is weak, the poster stays and the chapter text still animates over it.

## Navigation (mobile)
- Header: logo + "COG Audio Ministry / DASMARIÑAS" left, menu button right. Member login moves into the menu.
- Full-screen overlay (#0F172A / `console`), 28px links with "CH 0X" labels, staggered GSAP reveal, close button top-right, scroll locked.
- Header hides on scroll down, shows on scroll up.

## All sections (mobile)
- Container `px-5`, sections `py-16`, equal rhythm.
- Grids 1 column (2 only for small stat counters). Cards equal height, full width, 16px gap.
- Gallery: horizontal swipe with scroll-snap (no GSAP pin).
- Schedule: Sunday services as a compact time grid (2–3 per row), next service highlighted at the top.
- Body 16px min, line-height 1.6; headings clamp(). Tap targets ≥ 44×44. No horizontal scroll.
- Entrances: simple fade-up, 0.5s.

## Tablet portrait (768–1023px, `md` breakpoint)
Owner decision 2026-11-28.
- Below 1024px the hero uses the stacked mobile layout.
- At md and up, three tablet extras:
  - (a) The Psalm quote stays visible in the hero (it is hidden only below md).
  - (b) Hero buttons sit side by side (auto width, min-height 48px), not stacked full width.
  - (c) Member login shows in the header next to the menu button (part of slice 4, navigation; see [[mobile-pass-tasks]]).
- Landscape tablets (>=1024px) get the desktop layout unchanged.

## Checks
- Lighthouse mobile: Performance ≥ 75, Accessibility ≥ 95.
- Real devices (owner): one iPhone (Safari), one Android (Chrome).
- Screenshots at 390px: hero, each chapter, menu open, every section.
- Performance rule (owner, 2026-11-28): the site must never feel slow. Every slice checks:
  - No horizontal scroll.
  - No layout shift from the hero (CLS < 0.1).
  - Poster image loads first and is sized (width/height or aspect box).
  - ~~3D/three.js never downloads or runs below 1024px or on weak devices.~~ Superseded 2026-11-28: 3D only loads after the capability check passes (see [[3d-on-tablets-and-phones]]); the poster is always first and sized.
  - No new JS on the main thread for mobile-only layout (CSS first).
  - If a slice makes anything slower, fix it before commit.

## Notes
- Wordmark on mobile is the official logo image `public/images/logo-full.png` (it already contains the script "Audio"). No script web font. Decided 2026-11-27, restated 2026-11-28.
