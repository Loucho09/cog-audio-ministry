# Loading states tasks
Spec: [[loading-states]]
Branch: `feature/loading-states`

- [ ] Slice 1: navigation progress bar + cross-document view transitions (layouts, app.css, app.js). Agent: frontend-builder.
- [ ] Slice 2: `<x-skeleton-image>` + apply to gallery, team, hero, churches. Agent: frontend-builder.
- [ ] Slice 3: submit-button loading state on all forms (component/Alpine directive, auth + profile views). Agent: frontend-builder.
- [ ] Slice 4: Filament lazy widget skeleton placeholders. Agent: backend-builder.
- [ ] Slice 5: shared skeleton utilities, scroll-in fades, aria-live polish. Agent: frontend-builder.
- [ ] Browser check (Playwright): 360px + desktop, light + dark, reduced motion.
- [ ] Reviewer, fixes, brain-keeper, merge.

After each slice: `php artisan test`, `./vendor/bin/pint`, `npm run build`, commit.
