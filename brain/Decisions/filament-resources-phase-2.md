# Filament resources move to Phase 2

Date: 2026-11-24
Related: [[Roadmap]], [[homepage-sections]], [[package-filament]], [[phase-order-roles-before-applications]], [[Backlog]]

## Decision
Filament admin resources for Service, GalleryImage, Faq and TeamMember move from Phase 1 to Phase 2 (owner decision). Phase 1 closes without them.

## Reason
`/admin` is unusable until Phase 2 login and roles exist: `User::canAccessPanel` returns false. The resources cannot be tested before then.

## Consequence
- Until Phase 2, homepage content is edited via seeders only.
- Phase 2 adds the four resources, scoped by branch.
- The Service resource needs a friendly error for the unique branch/day/time slot (from [[Backlog]]).
