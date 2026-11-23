# Package: Spatie Permission

Date: 2026-11-23
Approved by: owner

## Decision
Add **Spatie Permission** (Laravel roles and permissions package) to the project.

## What
Spatie Permission is a mature, widely-used package that provides:
- Role and permission models with pivot tables (many-to-many).
- Authorization gate/policy integration.
- Seeders for creating roles and assigning permissions.
- Middleware for route protection.
- Branch-scoped roles (via custom gates/policies).

## Why
- **Avoids hand-rolled auth logic:** implementing roles, permissions, and scoping from scratch is error-prone.
- **Multi-tenancy support:** Spatie Permission integrates easily with branch scoping (each member belongs to a branch; a branch-head has permissions scoped to their branch).
- **Testable:** policies and gates can be tested in feature tests without mocking.
- **Battle-tested:** thousands of Laravel apps use Spatie Permission in production.

## Usage in this project
- Phase 2: define roles (super-admin, branch-head, member) and permissions; use policies to enforce branch-scoped access.
- Phases 3+: branch-head can manage only their branch's applications, events, and duties.
- Phase 7: migrations lock down permissions to prevent privilege escalation.

## Roles: super-admin, branch-head, member
- **super-admin:** the project owner (Audio Head). Creates branches, assigns all roles, sees all data.
- **branch-head:** admin of one branch. Manages members, events, duties, applications in their branch only.
- **member:** contributor. Accepts/declines duties, views schedules, updates own profile.

## Implementation detail
- Branch scoping is enforced in model Policies and query scopes, never only in the UI.
- Tests verify that a branch-head cannot view another branch's data.

## See also
- [[new-project-brief-adopted]] (roles and branch-scoping design)
- [[Roadmap]] Phase 2 (roles implemented here)
- [[package-filament]] (integrates with Filament for admin UI)
