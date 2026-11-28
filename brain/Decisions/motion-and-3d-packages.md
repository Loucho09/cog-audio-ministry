# Decision: motion and 3D packages
Date: 2026-11-27
Approved by: owner (scroll and 3D hero requested)
Status: accepted
Part of [[homepage-redesign]]. See also [[homepage-console-brand]].

## Decision
Add these npm packages, used by the homepage only:
- `gsap` with ScrollTrigger, SplitText and DrawSVG. Free plugins only, no paid bonus plugins.
- `lenis` for smooth scroll on desktop.
- `three` for the procedural hero console. Loaded only on capable desktops (see [[homepage-redesign]]). (Superseded 2026-11-28: also tablets and capable phones, see [[3d-on-tablets-and-phones]].)

Icons: about 10 Tabler icons copied inline as SVG (MIT). No icon package added.

## Why
- The owner asked for a scroll-driven hero and a 3D console.
- Three effects from React Bits are ported to vanilla GSAP: SplitText heading, CountUp readouts, SpotlightCard. No React, no React Three Fiber.
- Lenis and GSAP run on desktop only. Phones use native scroll.

## Rules
- The three.js chunk is loaded only after first paint and only on capable desktops. Reduced motion never loads it. (Superseded 2026-11-28: also tablets and capable phones after the capability check, see [[3d-on-tablets-and-phones]].)
- A GLB model is optional. GLTFLoader is used with Meshopt. Draco and KTX2 are not wired (no decoders are shipped).
- No new Composer packages.

## Package approval
- Approved: the owner explicitly asked for `npm i gsap lenis three` in the redesign brief (2026-11-27), which satisfies the CLAUDE.md package rule.

## Known cost
- three.js chunk is about 753 kB. Trim later (see [[Backlog]]).
