# Homepage redesign
Status: done (pending owner browser check on a real GPU desktop and on a phone)
Phase: 1 (homepage rework, done during the Phase 5 timeline, 2026-11-27)

Related: [[00-Index]], [[Roadmap]], [[homepage-console-brand]], [[motion-and-3d-packages]], [[what-we-do-removed]], [[homepage-sections]], [[homepage-theme-polish]], [[typography-matches-logo]], [[stage-palette]]

Goal: "console at night, but clean". The homepage uses the language of a live-sound console (dark hero, faders, meters, LEDs, mono readouts) and stays calm and church-friendly. Concept from live sound work. Owner decision 2026-11-27.

Users: visitor (`/`). Members and admins see the same page when logged in.

Screens / routes:
- `/` named `home`, served by `HomeController`, rendered by `welcome.blade.php`.
- Section order:
  1. Hero (dark, pinned scroll story on desktop). 3D dLive S5000-style console on the right. Copy panels: "We serve behind the sound.", "Every service, every voice — clearly heard.", then CTA. Keeps `#hero-title`.
  2. CH 01 About. Statement, Vision and Mission (TODO placeholders, same ids), three CountUp mono readouts from real counts (churches, services per week, years serving only when configured).
  3. CH 02 What we do. Asymmetric list: FOH as the lead item, then Monitors, Broadcast/Livestream, Recording, Stage and Patching. Tabler icon and spotlight hover per item. Revives `components/home/what-we-do`.
  4. CH 03 Services. Mono timetable from the `services` table via `ServiceSchedule::build` (weekday rows, Sunday as a 7-slot strip). `resources/js/home/next-service.js` lights a blue NEXT or LIVE LED in `Asia/Manila` from `data-day` and `data-time`. Without JS, the server "Today/Tomorrow" text shows. LIVE window `data-live-minutes` defaults to 90 (TODO: confirm).
  5. CH 04 Satellites. Channel-strip row from the `branches` table. COG Dasmariñas is the wide master strip with "Marilag Subdivision" as its address. Trece, Silang and Jabez are narrower strips. No Marilag row.
  6. CH 05 Gallery. Desktop: horizontal pinned scroll, only with real photos. Mobile: 2-column grid. Lightbox and its Alpine strings kept. Photos get `saturate(.8)` and a cool overlay. Missing photos show "Photo pending — crew at the board."
  7. CH 06 Team. Behind `show_team` (still OFF by default). Full-time first. Flag on with no data shows labelled TODO cards.
  8. Join band (dark console band). Button to `route('join.create')`. Cursor-aware LED glow.
  9. CH 07 Contact, then footer. Facebook, email, Messenger and address TODO. Map only when an address is set. Stay-updated merged here.
- Removed from `/`: marquee band, impact strip (folded into About), stay-updated as its own section, magnet buttons.
- Shared Blade components (`resources/views/components/`): `section`, `section-heading`, `card`, `button`, `tabler-icon`. (`x-icon` is taken by blade-icons.)
- Old `site/section-header`, `site/button`, `glow`, `section-backdrop`, `wave-divider` are no longer used on the homepage. Files kept, not deleted.

Data changes: none. No migrations.

Permissions: public page. Team and gallery follow existing rules. No new auth.

Design system:
- Tokens in `tailwind.config.js` and `resources/css/app.css` (see [[homepage-console-brand]]).
- Fluid type classes `.t-display`, `.t-h2`, `.t-lead`.
- Radius `rounded-console` (6px). 1px `border-text/10` cards, no shadow. Grid gaps `gap-6 lg:gap-8`.
- Section wrapper: `py-24 lg:py-32`, `max-w-7xl mx-auto px-6 lg:px-8`.

Motion and 3D: see [[motion-and-3d-packages]].
- Lenis and GSAP desktop only. Phones use native scroll.
- Effects set up lazily per section.
- One entrance per section: fader-heading (About), patch-rows (What we do), meters (Services), strips (Satellites), horizontal scrub (Gallery), cascade (Team), LED power-on (Join), signal line (Contact).
- Reduced motion: the motion chunk is not loaded, a static poster shows, no pinning or scrub.
- Hero 3D: procedural console in `resources/js/home/console-scene.js` and `dlive-surface.js`, started by `hero.js`. No logos.
  - Live 3D only on desktop ≥1024px, fine pointer, hardware WebGL2, more than 4 cores, and not `saveData` or reduced motion. Otherwise the poster is shown and three.js is never downloaded.
  - Poster images: `public/images/hero-console-1600.webp`, `hero-console-800.webp`, and `hero-console-1600.jpg` / `hero-console-800.jpg` (captured from the scene).
  - Real model: put a Meshopt-compressed `public/models/dlive.glb` in place. Blade sets `data-glb` when the file exists. Draco and KTX2 are not wired (would need decoders in `public/vendor/three/`).

Layout, SEO and assets:
- Bunny font link (Schibsted Grotesk, JetBrains Mono). App and guest layouts load the same fonts.
- Open Graph image (`images/cover-1200.webp` or a 1200×630 crop), `twitter:card`, canonical link, favicon from `logo.png` (`favicon-32.png`, `apple-touch-icon.png`, resize only).
- Navbar and footer restyled. Mono nav labels, fader-underline hover. Links include `#what-we-do`.

Tests (Pest): updated `TypographyTest`, `HomepageSectionsTest`, `HomepageImpactTest` (now About readouts), `HomepageChurchesTest`, `HomepageStayUpdatedTest` (now Contact), `ScrollRevealTest` (`data-reveal` on `#about` and `#join`). Kept invariants: one h1, skip link, `#main`, mobile-menu attributes, lightbox strings, service order and times, no `COG Marilag`, join href, map iframe rules. New test: section components (CH labels, shared section wrapper).

Results (2026-11-27, local `php artisan serve`):
- Lighthouse desktop: Performance 97, Accessibility 100, Best Practices 100, SEO 100.
- Lighthouse mobile: Performance 89, Accessibility 98, Best Practices 100, SEO 100.
- Tests: 1011 total (1010 pass, 1 skipped).
- Visual sweep 375 to 1920px, light and dark: no horizontal overflow, no console errors.
- 3D was verified only on SwiftShader with a spoofed GPU string. Owner must check on a real desktop GPU.

Design rules against AI-looking UI (checked, 10 items):
1. No purple or indigo gradients, gradient text, blurred blobs or aurora washes. The only light is the real glow from the 3D screens. The `site/glow` use on the homepage is removed.
2. No glassmorphism, neon outlines, sparkle icons or emoji. Flat surfaces, 1px hairlines, one Tabler icon set.
3. No Inter, Poppins, Space Grotesk or Montserrat on the homepage. Schibsted Grotesk and JetBrains Mono.
4. No everything-centred layout. Editorial 12-column grid, offset text, left-aligned headings. Centred text only in the CTA band.
5. No endless identical 3-card grids. Each section has its own inner layout.
6. No rounded-2xl with shadow-xl everywhere. One radius (`rounded-console`) and borders instead of shadows.
7. No generic copy ("Elevate", "Seamless"). Short church-voice copy. No invented stats or testimonials. Stats are live DB counts only.
8. No stock or AI people photos, no abstract 3D shapes. Real ministry photos (owner supplies) with one cool, desaturated treatment. Missing photos are labelled placeholders. The 3D model is the console the crew uses.
9. No fade-up-20px on every element. One entrance per section, one ease family.
10. No effects demo reel. Three effect ports only. Velocity marquee and magnet buttons removed.

Pending content (TODO placeholders):
- Vision and mission text.
- Real photos from the Facebook page (owner supplies; not scraped).
- Team data.
- Messenger link.
- Address.
- Years serving (readout hidden until set).
- Service length for the LIVE window (default 90 minutes).
- Optional real `public/models/dlive.glb`.

Out of scope:
- Restyling other pages beyond fonts and tokens (login and dashboard full restyle is in [[Backlog]]).
- Draco and KTX2 model support.
- New data or migrations.
- Deleting the unused components (`home/marquee`, `home/impact`, `home/stay-updated`) until owner OK (see [[Backlog]]).

Build slices (one commit each, with tests, pint and `npm run build`):
1. Tokens, fonts, favicon and OG; `accent` remap.
2. Components: section, section-heading, card, button, icon.
3. Navbar and footer.
4. About with readouts; What we do.
5. Services timetable and `next-service.js`.
6. Satellites and Team.
7. Gallery pin and Join band.
8. Contact (stay-updated merged), signal line; marquee and impact removed from `welcome`.
9. Motion layer cleanup, one entrance per section.
10. Hero 3D upgrade, GLB path, mobile and reduced-motion poster.
11. QA pass, self-review, reviewer, fixes, brain update.

Notes / decisions: [[homepage-console-brand]], [[motion-and-3d-packages]], [[what-we-do-removed]] (superseded), [[typography-matches-logo]] (obsolete), [[stage-palette]] (obsolete), [[homepage-sections]] and [[homepage-theme-polish]] (superseded by this spec).
