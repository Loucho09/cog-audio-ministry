# Admin save returns to list
Date: 2026-11-26
Status: done (branch `fix/admin-return-to-list`)

Owner asked: after Save in `/admin`, go back to the list instead of staying on the form (alert alone was not enough).

- Create/Edit for Branches, Duty Roles, Services, Team Members, Users, and Edit Event → back to the list, keeping page, filters and search (shared trait `app/Filament/Concerns/ReturnsToResourceList.php`, same open-redirect checks as [[gallery-upload-fix]]).
- Create Event → that event's View page, because duties are assigned there ([[duty-guest-assign]]).
- Gallery keeps its own trait (no drive-by refactor).
