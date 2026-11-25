# Loading states
Status: planned
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

Tasks: [[loading-states-tasks]]
Notes / decisions: [[filament-widget-styling]], [[dark-mode]], [[Roadmap]]
