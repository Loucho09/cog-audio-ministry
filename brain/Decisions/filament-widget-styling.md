# Filament widget styling
Date: 2026-11-25
Status: accepted
Related: [[dashboards]], [[Roadmap]]

Decision:
- Custom Filament Blade views (widgets, site-setup view) use Filament components only.
- No custom Filament theme yet, and no new packages.
- Arbitrary Tailwind classes do not render in `/admin` because the panel uses the default Filament CSS.

Why:
- The admin panel has no custom theme, so app Tailwind classes are missing there.
- Adding a theme or package needs owner approval (see [[Backlog]] and CLAUDE.md rules).

If richer styling is needed later:
- Create a Filament theme with `php artisan make:filament-theme`.
- Ask the owner first.
