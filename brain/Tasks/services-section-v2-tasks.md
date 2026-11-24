# Services section v2 tasks

Related: [[services-section-v2]], [[00-Index]]

- [x] A. Service date window: nullable `starts_on`/`ends_on` on services; homepage hides dates outside window; weekly list shows "Resumes <date>" / "Last service <date>"; seeder sets Tuesday Revival Night and Wednesday Salvation Night `ends_on` 2026-11-24 (exact dates TODO; Wednesday name TODO confirm)
- [x] B. Next-service spotlight card; group upcoming services by day (Sunday's 7 times as chips in one card); remove "Show more dates"
- [x] C. Weekly schedule: compact 7-day list, today highlighted, folded behind a toggle; check 360px and dark mode
- [x] D. Add-to-calendar: hand-built `.ics`, weekly RRULE, named route `services.calendar`; tests (happy path, inactive and unknown service returns 404) (done 2026-11-24)
- [x] E. Get directions link from `config/ministry.php` address; hidden while address is empty (done 2026-11-24)
- [ ] Roadmap: add "include starts_on/ends_on fields, branch-scoped for branch-head" to the Phase 2 Filament Service resource line
- [ ] Backlog: single cancelled dates parked
