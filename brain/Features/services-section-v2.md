# Services section v2
Status: done (2026-11-24, merged to main)
Phase: 1 polish
Branch: feature/services-improve
Goal: shorter, less cluttered homepage Services section. Show the next service as a spotlight, group upcoming services by day, fold the full weekly schedule behind a toggle, add calendar and directions links. Services can have a date window (season pauses).
Users: visitor
Screens / routes:
- `/` homepage Services section (spotlight card, one compact card per day, weekly list toggle)
- `services.calendar`: GET `/services/{service}/calendar.ics` (numeric id, throttle 60/min). Serves a `.ics` file for an active service; 404 if inactive, ended, or unknown.
Data changes (tables, columns):
- `services`: nullable `starts_on` (date), `ends_on` (date). Null = no limit.
- Seeder uses `firstOrCreate` (does not overwrite admin edits):
  - Revival Night, Tue 7 PM: `starts_on` 2027-01-05, `ends_on` null.
  - Salvation Night, Wed 7 PM (name kept per owner): `starts_on` 2027-01-06, `ends_on` null.
  - Audio Fellowship, Wed 6 PM (public, separate event per owner request): `starts_on` 2027-01-06, `ends_on` null.
- `config/ministry.php`: address read for the directions link and the calendar LOCATION (already present; empty = link hidden).
Permissions: public read. Editing dates by branch-head comes with the Filament Service resource in Phase 2 (see [[Roadmap]]).
Behaviour:
- Homepage hides services outside their date window.
- Weekly list reads all active services from the DB, so paused services stay visible with "Resumes <date>" when the window starts in the future.
- "Last service <date>" shows only when the window ended within the last 8 weeks.
- Homepage: spotlight card for the next service with "Add to calendar" and "Get directions"; one compact card per day for the next 7 days (multiple times as chips); no "later" list.
- Weekly schedule folded behind a toggle, today highlighted. Each time chip is the calendar link (calendar icon inside the chip). Separate icon buttons were too cluttered on Sunday (7 times).
- Add-to-calendar: hand-built `.ics` in `app/Support/ServiceIcs.php` (no package). Weekly RRULE, TZID Asia/Manila (TZNAME PHT). DTSTART is the first occurrence on or after max(today, `starts_on`). UNTIL comes from `ends_on`. Fixed 90-minute duration (TODO owner to confirm real length). LOCATION = branch address, else branch name.
- Get directions link: `app/Support/Directions.php` builds the link from `config('ministry.address')`, the same source as the Contact map. Hidden while the address is empty (TODO address).
- Visual check at 360px, light and dark: done (slices A to E).
Out of scope:
- Single cancelled dates (one-off exceptions). Parked in [[Backlog]].
- Filament editing of dates (Phase 2).
- New packages (no calendar library).
Tasks: [[services-section-v2-tasks]]
Notes / decisions: [[service-date-window]], [[homepage-sections]]

## Bug fixes (branch fix/services-section, 2026-11-24)
- Weekly schedule grid is `7rem / minmax(9rem,1fr) / minmax(0,auto)` on desktop; time chips wrap, so a long day (Sunday, 7 times) no longer hides the service name.
- Weekly list filters with `ServiceIcs::available` (same rule as the .ics link). A service whose last weekday before `ends_on` has passed is dropped, so no calendar link 404s.
- "Last service <date>" shows the final matching weekday on or before `ends_on`, not `ends_on` itself.
- Covered by 3 tests in `tests/Feature/ServiceScheduleTest.php`. Reviewer: no must-fix issues.
