# Services section v2
Status: in progress
Phase: 1 polish
Branch: feature/services-improve
Goal: shorter, less cluttered homepage Services section. Show the next service as a spotlight, group upcoming services by day, fold the full weekly schedule behind a toggle, add calendar and directions links. Services can have a date window (season pauses).
Users: visitor
Screens / routes:
- `/` homepage Services section (spotlight card, one compact card per day, weekly list toggle)
- Add-to-calendar route (named route, serves a `.ics` file for an active service; 404 if inactive or unknown)
Data changes (tables, columns):
- `services`: nullable `starts_on` (date), `ends_on` (date). Null = no limit.
- Seeder uses `firstOrCreate` (does not overwrite admin edits):
  - Revival Night, Tue 7 PM: `starts_on` 2027-01-05, `ends_on` null.
  - Salvation Night, Wed 7 PM (name kept per owner): `starts_on` 2027-01-06, `ends_on` null.
  - Audio Fellowship, Wed 6 PM (public, separate event per owner request): `starts_on` 2027-01-06, `ends_on` null.
- `config/ministry.php`: address read for the directions link (already present; empty = link hidden).
Permissions: public read. Editing dates by branch-head comes with the Filament Service resource in Phase 2 (see [[Roadmap]]).
Behaviour:
- Homepage hides services outside their date window.
- Weekly list reads all active services from the DB, so paused services stay visible with "Resumes <date>" when the window starts in the future.
- "Last service <date>" shows only when the window ended within the last 8 weeks.
- Homepage: spotlight card for the next service; one compact card per day for the next 7 days (multiple times as chips); no "later" list.
- Weekly schedule folded behind a toggle, today highlighted.
- Add-to-calendar: hand-built `.ics` (no package), weekly RRULE, Asia/Manila time.
- Get directions link: built from the config address; hidden while address is empty.
- Visual check at 360px, light and dark: done (slices A to C).
Out of scope:
- Single cancelled dates (one-off exceptions). Parked in [[Backlog]].
- Filament editing of dates (Phase 2).
- New packages (no calendar library).
Tasks: [[services-section-v2-tasks]]
Notes / decisions: [[service-date-window]], [[homepage-sections]]
