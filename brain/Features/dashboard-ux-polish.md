# Dashboard UX polish
Status: done
Phase: 5
Goal: make the member and admin dashboards clearer: what needs action first, less clutter, accessible colours.
Users: member, branch-admin, super-admin
Screens / routes: `/dashboard` (member view and admin view). No new routes.
Data changes (tables, columns): none.
Permissions: unchanged.

## Built (commits 52a1d61, d2546bf; 2026-11-26, from a UI audit)
- Member: greeting is the single h1; "Needs your reply" first; duties sorted by action needed, 5 shown + "Show all"; declined under "Declined (N)"; status pills (`x-duty-status-pill`, theme tokens only); announcements placeholder removed; quick links as pills; services capped at 3 with disclosure; copy says "branch admin"; availability inputs aligned.
- Admin: "Needs attention" first as whole-card links; open-slot chips; quick actions as pills last.
- Reviewer: no must-fix; fixes applied.

Out of scope: new data, announcements (Phase 5 v2).
Tasks: [[duty-notifications-tasks]]
Notes / decisions: [[duty-notifications]], [[admin-dashboard]], [[dashboards]], [[Backlog]]
