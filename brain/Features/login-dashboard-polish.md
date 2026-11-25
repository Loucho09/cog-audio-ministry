# Login and dashboard polish
Status: in progress
Phase: 2 add-on (UI polish of existing Phase 2 screens; sits between Phase 2 and Phase 3). No new schema, no new packages.

Goal: Make the login and member dashboard interactive and welcoming, without changing auth or role logic.

Users: member, branch-head, branch-admin, super-admin (logged-in users). Visitors are redirected to login.

Branch: `feature/login-dashboard-polish`, cut from `fix/full-review`. `fix/full-review` is not yet merged to main, so this branch waits on that merge.

Screens / routes:
- `/login` (Breeze, themed). Slice 1.
- `/dashboard` (member home). Slice 2.

Slice 1, Login (`/login`):
- Split layout on desktop: cover photo left, form right. Form only on mobile.
- Show/hide password toggle (Alpine.js).
- Caps Lock warning while typing the password.
- Submit spinner and disabled button while submitting.
- Autofocus on email, correct `autocomplete` values.
- Throttle error shown as an alert box.
- Session status (e.g. password reset sent) styled as an alert box.
- Light and dark mode.
- Reduced motion respected (`prefers-reduced-motion`).

Slice 2, Dashboard (`/dashboard`):
- Time-of-day greeting (Asia/Manila time).
- Branch info card (branch name, address and contact if filled in; otherwise TODO-friendly empty state).
- Upcoming services from ALL branches (owner decision 2026-11-25). Grouped or labelled by branch, member's own branch first.
- Quick links: profile, change password, `/admin` only for admin roles.
- "Coming soon" teaser cards for Duties (Phase 4) and Announcements (Phase 5).
- 360px width check.

Data changes: none. Reads existing `users`, `branches`, `services`.

Permissions:
- `auth` and `verified` middleware on `/dashboard`.
- `/admin` link shown only when the server says the user has an admin role (same rule as `/admin` access). The check runs on the server, not only in the view.
- No change to roles, branch scoping, or login logic.

Tests:
- `DashboardTest`: guest is redirected to login; upcoming services from all branches are shown with the member's own branch first; admin link hidden for members and shown for admin roles.
- Login: existing auth tests must still pass.

Out of scope:
- Duties and announcements (Phases 4 and 5).
- Notifications (Phase 5).
- Any schema change.
- Any change to auth, roles, or throttle rules.

Tasks: [[login-dashboard-polish-tasks]]
Notes / decisions: [[login-and-dashboard-decisions]], [[member-accounts-roles]], [[temp-password]], [[Roadmap]]
