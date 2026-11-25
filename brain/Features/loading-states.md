# Loading states
Status: done (merged 2026-11-25)
Phase: UI polish add-on (owner request 2026-11-25, done before Phase 3). No schema change, no new packages.

Goal: Every moment the user waits looks alive and polished: no blank gaps, no frozen screens, no double submits. Applies to the public site, member pages, and `/admin`.

Users: visitor, member, branch-admin, branch-head, super-admin.

Screens / routes: all public pages, `/login` and auth pages, `/dashboard`, profile, `/admin`.

Slice 1, navigation progress + page transitions:
- Thin stage-blue progress bar at the top of the page on internal link clicks and form submits (Alpine + CSS, starts on click, completes on `pageshow`). Ignores new-tab clicks, downloads, external links, and `#` anchors.
- Smooth fade between pages with the browser's cross-document View Transitions (`@view-transition { navigation: auto; }`). Browsers without support just navigate normally.
- Works in light and dark mode. Respects `prefers-reduced-motion` (no animation; the bar still shows progress instantly).

Slice 2, image skeletons:
- Reusable Blade component (e.g. `<x-skeleton-image>`) that shows a shimmer placeholder in the image's box until the image loads, then fades the image in. Keeps `loading="lazy"`, `alt`, width/height (no layout shift). Broken image falls back to a calm placeholder.
- Used in gallery, team, hero (if it is an `<img>`), churches, and anywhere else a photo is shown.

Slice 3, button and form loading states:
- Reusable behaviour for every form submit button: spinner, disabled, `aria-busy`, label like "Saving…". Prevents double submits. Login keeps its existing spinner (reuse, don't duplicate).
- Applied to profile, update password, forced password change, delete account, logout, forgot/reset password, and any future form (join form in Phase 3 uses it).

Slice 4, admin skeletons (`/admin`):
- Dashboard widgets lazy-load with skeleton placeholders matching each widget's shape (stat cards, tables, checklist) using Filament's lazy widget placeholder. Built with Filament components / inline styles only ([[filament-widget-styling]]).

Slice 5, extra polish ("anything else needed"):
- Shared skeleton primitives (`skeleton` utility classes in `resources/css/app.css` using theme tokens) so later features (duty roster, announcements) reuse them.
- Subtle entrance fade for dashboard cards and homepage sections as they scroll into view (only if not already present; reduced-motion safe).
- Visible focus and `aria-live` announcements where a loading state changes.

Data changes: none.

Permissions: none (presentation only).

Tests:
- Feature tests: pages still render (200) and include the progress bar root and skeleton markup; forms still submit (existing auth/profile tests pass).
- Browser check with Playwright at 360px and desktop, light and dark, with reduced motion on and off.

Out of scope:
- SPA navigation (Livewire navigate, Turbo, htmx): not in the stack.
- New packages (NProgress etc.): built by hand instead.
- Skeletons for data that is not loaded asynchronously (server-rendered pages arrive complete).

## Progress
Test run at merge: 403 total, 402 pass, 1 pre-existing skip.

- **Slice 1 (35bfd3f): navigation progress and page transitions.**
  - `components/site/page-progress` and `resources/js/page-progress.js`: same-origin link clicks and form submits, 150ms delay, trickle, `pageshow` and bfcache reset, 30s stuck timeout, SVG links ignored.
  - Cross-document view transitions: `@view-transition` in `app.css`, 200ms fade, off for reduced motion. Firefox just navigates.
- **Slice 2 (5bd104d): image skeletons.**
  - `.skeleton` shimmer CSS and `<x-skeleton-image>` (Alpine; cached-image and error handling; safe without JS).
  - Used in gallery, team, churches.
  - Hero and login cover photo deliberately not wrapped: the hero is the LCP image and uses `<picture>`, and the dark background already works as a fallback.
- **Slice 3 (c0b6c1d): submit guard.**
  - `resources/js/submit-guard.js`, `data-submit-guard` on 14 forms, `<x-spinner>`, `<x-loading-label>`, `loading-text` prop on primary, secondary and danger buttons.
  - Labels: "Signing in…" (was "Logging in…"), "Saving…", "Sending…", "Confirming…" (new, owner to review), "Logging out…", "Deleting…".
- **Slice 4 (113b026): admin widget skeletons.**
  - `App\Filament\Widgets\Concerns\HasSkeletonPlaceholder` overrides Livewire `placeholder()`.
  - View `filament/widgets/placeholders/skeleton.blade.php` with variants stats, table, checklist. Uses Filament CSS vars and the `cog-skel` keyframe.
- **Slice 5 (93fee43): primitives, scroll reveal, live region.**
  - `<x-skeleton>` primitives (line, block, circle, card) for future async UI.
  - Scroll reveal: `resources/js/reveal.js`, `data-reveal` on homepage sections (except hero) and dashboard cards.
  - One shared live region: `resources/js/live-region.js`.
- **Review and browser-check fixes (2af8ee2):**
  - Reveal could leave tall sections invisible. Fixed: threshold 0, scroll, hash and beforeprint safety net. Hiding only starts when `reveal.js` runs (`reveal-ready` class on `<html>`). Reveal transition removed after animating so card hover transitions still work.
  - App JS inits isolated with try/catch.
  - Submit guard disables buttons on the next tick so the clicked button's name and value are still sent. Skips forms that stay on the page (target `_blank`, dialog, `data-no-progress`). Unlocks after 30s.
  - Live region re-announces repeated text.
  - Admin skeleton had two `style` attributes (padding ignored); merged via `->style()`.
  - Hidden `autocomplete="username"` field on update-password, change-password and confirm-password forms.
- **Browser check (Playwright):** homepage at 360px light; reveal after fast scroll; profile form lock ("Saving…", live region, progress bar); `/admin` skeletons in dark mode; console clean.

Decision: [[loading-states-approach]]
Tasks: [[loading-states-tasks]]
Backlog: [[Backlog]]
