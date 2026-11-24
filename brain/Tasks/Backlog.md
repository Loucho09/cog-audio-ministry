# Backlog

Related: [[00-Index]], [[Roadmap]], [[second-brief-merged]]

Parked ideas. Add them here instead of starting them mid-feature.

- Decide what to do with the stray nested `COG_AUDIO/` Obsidian vault folder in the project root (Welcome.md, create a link.md); owner to confirm
- Revisit Spatie teams feature before Phase 2 if owner wants it (current plan: `branch_id` + Policies + query scopes, no teams)
- Tests depend on nothing built now (`withoutVite`); consider a CI workflow later (added 2026-11-24)
- ~~Self-host the Inter font for the Phase 7 Lighthouse pass (added 2026-11-24; see [[Roadmap]])~~ superseded 2026-11-24
- Self-host Montserrat and Mrs Saint Delafield for the Phase 7 Lighthouse pass (added 2026-11-24; see [[typography-matches-logo]])
- The Filament Service resource must validate the unique (branch, day, time) slot with a friendly error (added 2026-11-24; see [[homepage-sections]])
- Branch-scoped visibility rules for nullable `branch_id` on `gallery_images` and `team_members` (global vs branch) to be defined in Phase 2 (added 2026-11-24; see [[homepage-sections]])
- Fix commit trailers on 43f4b05, c113b54 and 377c0f1 (they say Haiku instead of Opus) before opening the PR (added 2026-11-24; see [[homepage-sections]])
- Optional grace window so a service stays visible briefly after its start time (added 2026-11-24; see [[homepage-sections]])
- **NEXT (owner request 2026-11-24):** UI/UX polish pass on all homepage sections. First a read-only audit (360px and desktop, light and dark: spacing, type, contrast, touch targets, consistency, focus, motion). Owner picks fixes, then a "homepage polish" slice. Waiting for owner answers: what bothers them now, and whether to keep the current look or allow bolder changes. See [[homepage-sections]].
- Contact section shows only heading and intro if email, Facebook, Messenger and address are all empty; consider a "coming soon" fallback (added 2026-11-24; see [[homepage-sections]]) — **Done 2026-11-24:** contact section now shows "Contact details are coming soon."
- FAQ panels use `role="region"`; drop it if FAQs grow beyond about 6 (WAI-ARIA guidance) (added 2026-11-24; see [[homepage-sections]])
- Phase 7: consider a `sandbox` attribute on the map iframe as part of the CSP and privacy review (added 2026-11-24; see [[homepage-sections]])
- The Join Us button focus ring is overridden with `!ring-surface`; a cleaner fix is an "on-dark" variant in the button component (added 2026-11-24; see [[homepage-sections]])
- Owner browser check of homepage at 360px and desktop, light and dark, once MySQL runs: lightbox above navbar (open, close, Esc, arrows, Tab), backdrop strength, verse readability on mobile over the photo (added 2026-11-24; see [[homepage-sections]], [[hero-theme-verse]], [[section-backdrops]])
- Vignette ellipse stretches on tall mobile sections; consider `farthest-corner` if it looks odd (added 2026-11-24; see [[section-backdrops]])
- Backdrop fade seam colours assume default section order; check if team or FAQ is hidden (added 2026-11-24; see [[section-backdrops]])
- Public updates or news feed on the homepage (Phase 5; owner idea 2026-11-24; see [[homepage-hooks]], [[Roadmap]])
- Testimonies section (owner idea 2026-11-24; see [[homepage-hooks]])
- Sticky join CTA on mobile (owner idea 2026-11-24; see [[homepage-hooks]])
- "No experience needed" steps on the join section (owner declined for now, 2026-11-24; see [[homepage-hooks]])
