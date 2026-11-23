# Package: Filament

Date: 2026-11-23
Approved by: owner

## Decision
Add **Filament** (Laravel admin panel framework) to the project.

## What
Filament is a modern, headless admin panel built on Laravel and Livewire. It provides:
- Automatic CRUD scaffolding for models (branches, members, events, duties, join applications).
- Role-based access control integration with Spatie Permission.
- Beautiful, mobile-responsive UI built with Tailwind CSS.
- Forms, tables, notifications, and policies out of the box.

## Why
- **Replaces custom admin dashboard:** instead of building a custom "Admin Dashboard" phase, Filament lets us generate most admin features with minimal code.
- **Branch scoping:** Filament's policies work seamlessly with Spatie roles to enforce that branch-heads see only their own branch's data.
- **Faster iteration:** changes to admin workflows don't require custom controller/view development.
- **Maintenance:** Filament is actively maintained and battle-tested in production apps.

## Usage in this project
- Phase 2: set up Filament dashboard, role management, branch management.
- Phase 3: manage join applications in Filament (branch-head approval/rejection).
- Phase 4: roster builder and event management in Filament.
- Phase 7+: super-admin can manage all branches and configuration flags in Filament.

## Drawback and mitigation
- **Package dependency:** adds a third-party dependency (but it's a stable, widely-used package).
- **Learning curve for new devs:** brief runbook added in Phase 7 handover docs.

## See also
- [[new-project-brief-adopted]] (why Filament instead of a custom dashboard phase)
- [[Roadmap]] (Filament integrated into Phases 2, 3, 4, 7)
