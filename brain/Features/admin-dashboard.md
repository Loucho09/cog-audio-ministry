# Admin dashboard
Status: done (2026-11-26, branch `feature/admin-dashboard`, merged to main; commits 8111511, a64ebef, e5ab030, 77aeb70 review fixes; 916 tests, 915 pass, 1 skipped)
Phase: 4 follow-up (done before Phase 5)
Related: [[Roadmap]], [[dashboards]], [[duty-roster]], [[duty-swaps]], [[admins-manage-not-serve]]

Goal: `/dashboard` shows a different view per role. Members keep the member dashboard. Branch-admins and the super-admin get an admin dashboard that helps them run the branch. Admins only manage; they do not serve on duty (see [[admins-manage-not-serve]]).

Users: member | branch-admin | super-admin

Screens / routes:
- `/dashboard`: picks the view by role.
  - Member view (current, unchanged): my duties, announcements (placeholder), open swaps, my availability.
  - Admin view (`dashboard-admin`): admin cards below.
- Admin links go to existing `/admin` pages (New event, applications, swaps, assign).

Admin cards (scoped to own branch; super-admin sees all branches, one row per branch):
- Needs attention: pending join applications, swaps awaiting approval, declined duties to refill.
- Unfilled slots: events in the next 14 days with gaps, e.g. "Sun 9 AM: Sound 1 of 2". Links to assign.
- Waiting for reply: assigned, not yet accepted or declined; nearest event first.
- Who's unavailable: members marked unavailable in the next 14 days.
- Branch snapshot: member count, accepted vs declined in the last 30 days, upcoming services.
- Quick actions: New event, Review applications, Approve swaps (links to `/admin`).

Data changes (tables, columns):
- None. Reads existing tables: users, branches, events, duty_assignments, duty_swaps, duty_availability, join_applications, services.

Permissions:
- Every query is branch-scoped in the query layer (scope or action), not only in the UI.
- branch-admin never sees counts or rows from another branch.
- Admins cannot be assigned duties, cannot claim swaps, and cannot mark availability (policies and server rule in `DutyRosterService`).
- Members never see the admin view.

Out of scope:
- Notifications (Phase 5).
- Charts or graphs.
- New packages.
- Changing the member dashboard content.

Tasks: [[admin-dashboard-tasks]]
Notes / decisions: [[admins-manage-not-serve]]

## Test plan
- Admin sees the admin view; member sees the member view.
- Admin cannot be assigned, cannot claim a swap, cannot mark availability.
- Branch-admin never sees another branch's counts.
- Super-admin sees one row per branch.
