# New Project Brief Adopted

Date: 2026-11-23

## Decision
Adopted a new project brief that replaces the original 9-phase plan. The brief introduces updated terminology, organizational structure, and package choices.

## Main Changes

### Terminology
- **"Satellites" → "Branches"** throughout: each ministry location (Trece, Silang, Jabez) is now a branch of the main organization. (Update 2026-11-25: Marilag is not a branch; it is the subdivision where COG Dasmariñas is. See [[marilag-is-dasma-subdivision]].) The main ministry (COG Dasmariñas) is also a branch. All branch-scoped records have `branch_id`.

### Roles and Ownership
- **"super-admin" is the project owner** (one person, the Audio Head). This role creates branches, assigns branch-heads, and assigns all roles. No other super-admin can be created without deliberate manual action.
- **"branch-head"** (admin of one branch) manages members, events, duties, and receives notifications in their own branch only.
- **"member"** (contributor).
- **"visitor"** (unauthenticated).

### Notifications and Apply Flow
- Join applications are submitted by visitors picking a branch.
- Approval notification goes to that **branch's branch-head** (not a custom admin dashboard).

### Database and Configuration
- Color tokens stored as `background`, `surface`, `text`, `primary`, `accent` (Tailwind theme tokens, not hardcoded hex).
- Contacts and config flags (e.g., "Meet the Team" visibility) live in `config/ministry.php`.
- Schedule seeded into a `services` table, managed in Filament (admin panel).
- Logo stored in `public/images/logo` (do not redraw; use the official image).

### Phases and Admin Panel
- **New phase count:** 7 phases (down from 9) + production launch merged.
  - Phase 1: Setup and homepage
  - Phase 2: Member accounts and roles (swapped up; see [[phase-order-roles-before-applications]])
  - Phase 3: Join applications
  - Phase 4: Duty roster
  - Phase 5: Notifications and reminders
  - Phase 6: Equipment, training, reports (plan later)
  - Phase 7: Production launch
- **Admin interface:** Filament (Laravel admin panel package) instead of a custom-built "Admin Dashboard" phase. Filament is used for role management, branch management, event/duty management, and join application approval.

### Packages Approved
- **Filament:** modern Laravel admin panel (see [[package-filament]])
- **Spatie Permission:** roles and permissions (see [[package-spatie-permission]])
- **Laravel Breeze Blade:** authentication scaffolding (see [[package-breeze]])
- **Pest:** modern testing framework (see [[package-pest]])
- **Laravel Mail + Queue:** notifications (built into Laravel, no package needed)

## Impact
- **Phase reordering:** roles come before join applications (Phase 2 before Phase 3) so applications can be properly scoped and approved by branch-heads in Filament.
- **Homepage is cleaner:** one super-admin user, one branch-head per branch, member signup by application only.
- **More flexible:** branches, contacts, and flags can now be managed in Filament without code changes.

## See also
- [[Roadmap]] (new phases)
- [[Project-Brief]] (updated terminology and structure)
- [[phase-order-roles-before-applications]] (why roles moved before applications)
- [[package-filament]], [[package-spatie-permission]], [[package-breeze]], [[package-pest]]
