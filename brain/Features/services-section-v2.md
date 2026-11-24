# Services section v2
Status: in progress
Phase: 1 polish
Branch: feature/services-improve
Goal: shorter, less cluttered homepage Services section. Show the next service as a spotlight, group upcoming services by day, fold the full weekly schedule behind a toggle, add calendar and directions links. Services can have a date window (season pauses).
Users: visitor
Screens / routes:
- `/` homepage Services section (spotlight card, grouped by day, weekly list toggle)
- Add-to-calendar route (named route, serves a `.ics` file for an active service; 404 if inactive or unknown)
Data changes (tables, columns):
- `services`: nullable `starts_on` (date), `ends_on` (date). Null = no limit.
- Seeder: Tuesday Revival Night and Wednesday Salvation Night get `ends_on` = 2026-11-24 (last service tonight; resume next year, exact dates TODO). Wednesday may be renamed "fellowship" (TODO confirm with owner).
- `config/ministry.php`: address read for the directions link (already present; empty = link hidden).
Permissions: public read. Editing dates by branch-head comes with the Filament Service resource in Phase 2 (see [[Roadmap]]).
Behaviour:
- Homepage hides services outside their date window.
- Weekly list shows "Resumes <date>" when a window starts in the future and "Last service <date>" when it has ended.
- Add-to-calendar: hand-built `.ics` (no package), weekly RRULE, Asia/Manila time.
- Get directions link: built from the config address; hidden while address is empty.
Out of scope:
- Single cancelled dates (one-off exceptions). Parked in [[Backlog]].
- Filament editing of dates (Phase 2).
- New packages (no calendar library).
Tasks: [[services-section-v2-tasks]]
Notes / decisions: [[service-date-window]], [[homepage-sections]]
