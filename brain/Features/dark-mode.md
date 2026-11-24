# Dark mode
Status: done (pending owner check in browser)
Phase: 1

Goal: let visitors switch the public site between light and dark themes.

Users: visitor (public site)

Screens / routes:
- All public pages via [[public-layout]]
- Sun/moon toggle button in the navbar, at all screen sizes

Data changes (tables, columns): none. Choice stored in browser localStorage key `theme`.

Permissions: public, no login needed.

Behavior:
- Default: follows the device setting until the visitor picks a theme.
- Tokens and approach: [[dark-mode-css-variable-tokens]].

Out of scope:
- Breeze auth pages
- Member dashboard (Phase 2+)
- Filament `/admin` (has its own dark mode)

Tests: `tests/Feature/DarkModeTest.php`

Tasks: none (small slice, done in branch `feature/dark-mode`)
Notes / decisions: [[dark-mode-css-variable-tokens]], [[primary-dark-contrast-token]], [[Roadmap]], [[00-Index]]
