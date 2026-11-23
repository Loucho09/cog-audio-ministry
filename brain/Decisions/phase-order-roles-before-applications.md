# Phase Order: Roles Before Applications

Date: 2026-11-23

## Decision
Phase 2 (Member Accounts and Roles) now comes **before** Phase 3 (Join Applications). This reverses the original plan.

## Rationale

### Why applications need roles first
1. **Branch scoping:** join applications must belong to a branch and be viewable only by that branch's branch-head. Without the roles/branches system, there is no way to enforce this scoping or permission check.

2. **Filament access:** approving/rejecting applications is done in the Filament admin panel (Phase 1 install + Phase 2 setup). A branch-head cannot log in to Filament without authentication and role assignment.

3. **Notification scope:** when an application arrives, it must notify only that branch's branch-head. This requires branch-head accounts to exist and have role assignments.

4. **Data integrity:** the `join_applications` table must have a `branch_id` foreign key. If roles and branches don't yet exist, the schema design becomes confusing and tests fail.

### Cost of swapping
- **Negligible:** both phases are discrete, with minimal overlap. Swapping adds no extra work.
- **Benefit:** Phase 3 can assume all role and branch infrastructure is in place and simply build on top of it.

## See also
- [[Roadmap]] (Phase 2 and Phase 3 descriptions)
- [[new-project-brief-adopted]] (new phase structure)
