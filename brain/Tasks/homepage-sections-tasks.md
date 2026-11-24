# Tasks: homepage sections

Related: [[homepage-sections]] (spec), [[Roadmap]], [[00-Index]]

| Slice | Scope | Status | Commits |
|-------|-------|--------|---------|
| 1 | Data: `branches`, `services`, `gallery_images`, `faqs`, `team_members` tables, models, seeders, `App\Actions\UpcomingServices` | done | 1837909, d417b83, 6b93b94; fixes f13339c |
| 2 | Hero, about us, what we do | done | 43f4b05, c113b54; fixes 377c0f1 |
| 3 | Upcoming services, gallery with Alpine lightbox, `HomeController` | in progress | images: bd29ac3 (logo and cover), f726f09 (tighter logo crop) |
| 4 | Join us CTA, FAQ accordion, contact (map only when address is set), meet the team behind config flag | planned | - |

Notes:
- Slice 2 commit trailers (43f4b05, c113b54, 377c0f1) say Haiku instead of Opus. Fix before opening the PR (see [[Backlog]]).
- Decisions for this feature: [[branches-table-in-phase-1]], [[timezone-asia-manila]], [[image-path-convention]], [[logo-and-cover-assets]], [[seeders-first-or-create]].
