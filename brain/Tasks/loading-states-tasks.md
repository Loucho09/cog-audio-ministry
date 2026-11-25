# Loading states tasks
Spec: [[loading-states]]
Branch: `feature/loading-states` (merged to main 2026-11-25)

- [x] Slice 1: navigation progress bar + cross-document view transitions (layouts, app.css, app.js). Agent: frontend-builder. Commit 35bfd3f.
- [x] Slice 2: `<x-skeleton-image>` + apply to gallery, team, churches. Hero deliberately not wrapped (LCP image). Agent: frontend-builder. Commit 5bd104d.
- [x] Slice 3: submit-button loading state on all forms (component/Alpine directive, auth + profile views). Agent: frontend-builder. Commit c0b6c1d.
- [x] Slice 4: Filament lazy widget skeleton placeholders. Agent: backend-builder. Commit 113b026.
- [x] Slice 5: shared skeleton utilities, scroll-in fades, aria-live polish. Agent: frontend-builder. Commit 93fee43.
- [x] Browser check (Playwright): 360px + desktop, light + dark, reduced motion. Done 2026-11-25 (360px light, dark-mode admin, profile form lock, console clean).
- [x] Reviewer, fixes, brain-keeper, merge. Review fixes commit 2af8ee2; merged 2026-11-25.

After each slice: `php artisan test`, `./vendor/bin/pint`, `npm run build`, commit.

Result at merge: 403 tests, 402 pass, 1 pre-existing skip.
