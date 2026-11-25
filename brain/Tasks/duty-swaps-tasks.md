# Duty swaps tasks
Spec: [[duty-swaps]]
Branch: `feature/duty-swaps` (start after [[duty-availability-tasks]] is merged)
Rule: rule tests first (Pest), then code. Each slice: `php artisan test` + `./vendor/bin/pint` green, then commit.

## Slice S1: data + statuses (backend-builder)
- [x] `DutyStatus`: add `swap_requested`, `swapped` (labels, badge colors)
- [x] Enum `app/Enums/SwapStatus.php` (`open`, `claimed`, `approved`, `rejected`, `cancelled`)
- [x] Migration `duty_swaps` (branch_id, duty_assignment_id cascade, offered_by, claimed_by nullable, status, claimed_at, decided_by nullOnDelete, decided_at)
- [x] Model `DutySwap` (`$fillable`, casts, relations, `scopeLive` = open/claimed on not-started events)
- [x] `DutyAssignment::scopeActive` excludes `swapped`; regression tests for capacity, overlap and slot-edit rules

## Slice S2: offer + cancel (backend-builder)
- [x] `app/Services/DutySwapService.php`: `offer(assignment, user)`, `cancel(swap, user)`
- [x] Rules: owner only, from `accepted` only, before start, one live swap per assignment, no guest rows; cancel returns assignment to `accepted`
- [x] Tests first

## Slice S3: claim (backend-builder)
- [x] `DutySwapService::claim(swap, user)` with row lock (first claim wins)
- [x] Rules: member/branch-admin role, same branch, not the offerer, not started, no overlapping active duty, not unavailable (reuse A3 helper), swap must be `open`
- [x] Tests first (incl. second claimer rejected, cross-branch rejected)

## Slice S4: approve / reject (backend-builder)
- [x] `DutySwapService::approve(swap, admin)`: re-check claim rules, original → `swapped`, new `accepted` assignment for claimer, one transaction
- [x] `reject(swap, admin)`: swap `rejected`, assignment back to `accepted`
- [x] Approver rule: admin of the branch or super-admin; an admin who offered/claimed can't self-approve unless they're the branch's only admin
- [x] `DutyRosterService::remove()` cancels a live swap
- [x] Tests first (incl. expired swap can't be approved, slot never empty)

## Slice S5: member dashboard (fullstack)
- [ ] `DutySwapPolicy` (offer/cancel/claim)
- [ ] Routes `swaps.store`, `swaps.claim`, `swaps.cancel` + thin `DutySwapController`
- [ ] `DashboardController` + `dashboard.blade.php`: Offer/Cancel buttons and badges on my duties; "Open swaps in your branch" with Claim (show AM/PM)
- [ ] Feature tests: happy path, another member can't cancel, other branch can't claim

## Slice S6: Filament Swaps (backend-builder)
- [ ] `app/Filament/Resources/DutySwaps/DutySwapResource.php` (+ List page): branch-scoped query, status filter, nav badge = claimed count
- [ ] Row actions Approve / Reject (only `claimed`), calling the service; errors shown as notifications
- [ ] Tests: other-branch admin can't see/approve, self-approval rule, member can't reach it

## Slice S7: demo + wrap-up
- [ ] `DutyRosterSeeder`: one open and one claimed swap per branch
- [ ] `reviewer` on the branch, fix issues
- [ ] `brain-keeper`: spec status, Roadmap (phase 4 done? fix "branch-head" rule text), Backlog (cross-branch swaps, swap history)
- [ ] Merge to main
