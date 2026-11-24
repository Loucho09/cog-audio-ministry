# Tasks: homepage sections

Related: [[homepage-sections]] (spec), [[Roadmap]], [[00-Index]]

| Slice | Scope | Status | Commits |
|-------|-------|--------|---------|
| 1 | Data: `branches`, `services`, `gallery_images`, `faqs`, `team_members` tables, models, seeders, `App\Actions\UpcomingServices` | done | 1837909, d417b83, 6b93b94; fixes f13339c |
| 2 | Hero, about us, what we do | done | 43f4b05, c113b54; fixes 377c0f1 |
| 3 | Upcoming services, gallery with Alpine lightbox, `HomeController` | done | 6908e55, faaf084, 85dbf4e, 30a156b, 078d77e, b4f064b; images: bd29ac3 (logo and cover), f726f09 (tighter logo crop) |
| 4 | Join us CTA, FAQ accordion, contact (map only when address is set), meet the team behind config flag | done | 1c8d658 (review fixes included in the same commit) |
| 5 | Polish pass, stage backdrops, hero theme verse | done, browser check pending | 0e95da6, 6abf5bb (CLAUDE.md accent-text rule), 93afab1, ce75b8a, 7cd3941, 21e7723 (review follow-ups) |

Notes:
- Slice 4 is not yet checked in a browser at 360px or in dark mode because MySQL is not connected. Owner to check once the DB works.
- Slice 2 commit trailers (43f4b05, c113b54, 377c0f1) say Haiku instead of Opus. Fix before opening the PR (see [[Backlog]]).
- Decisions for this feature: [[branches-table-in-phase-1]], [[timezone-asia-manila]], [[image-path-convention]], [[logo-and-cover-assets]], [[seeders-first-or-create]].
