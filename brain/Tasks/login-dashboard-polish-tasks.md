# Login and dashboard polish: tasks
Feature: [[login-dashboard-polish]]
Branch: `feature/login-dashboard-polish` (cut from `fix/full-review`)

## Slice 1: Login (`/login`)
- [ ] Split layout: cover photo left on desktop, form only on mobile (Blade, Tailwind)
- [ ] Show/hide password toggle (Alpine.js)
- [ ] Caps Lock warning on the password field
- [ ] Submit spinner and disabled button while submitting
- [ ] Autofocus and `autocomplete` values
- [ ] Throttle error as an alert box; session status as a styled alert box
- [ ] Light and dark mode check; reduced motion check
- [ ] Run existing auth tests; no auth logic changes
- [ ] Review, fix, commit

## Slice 2: Dashboard (`/dashboard`)
- [ ] Time-of-day greeting (Asia/Manila)
- [ ] Branch info card with empty state for unfilled fields
- [ ] Upcoming services from all branches, member's own branch first, labelled by branch
- [ ] Quick links: profile, change password; `/admin` only for admin roles (server-side check)
- [ ] "Coming soon" teaser cards for Duties and Announcements
- [ ] 360px check; light and dark mode check
- [ ] `DashboardTest`: guest redirect, services shown, own branch first, admin link rules
- [ ] Review, fix, commit

## Wrap-up
- [ ] `php artisan test` and `./vendor/bin/pint` pass
- [ ] Update [[login-dashboard-polish]] status and [[Roadmap]]
- [ ] Update [[00-Index]]
