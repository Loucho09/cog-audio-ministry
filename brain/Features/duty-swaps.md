# Duty swaps
Status: done (merged 2026-11-26)
Phase: 4 (v2b of the duty roster; after [[duty-availability]])
Goal: a member who accepted a duty but can't make it offers it to their branch; another eligible member claims it; a branch-admin approves. Replaces "drop a message in Messenger and hope someone covers".
Users: member, branch-admin (own branch), super-admin (any branch)

## Approach (owner picked 2026-11-26)
**Open offer.** Offer to the whole branch → any eligible member claims → admin approves.
Rejected: "ask one person" (stalls when that person doesn't answer; no notifications until Phase 5), "admin-only reassign" (breaks the Roadmap rule that another member must accept).

## Flow
1. Member has an **accepted** duty, event not started → **Offer swap**. Assignment status → `swap_requested`. Swap status `open`.
2. Another eligible member sees it under **Open swaps** on their dashboard → **Claim**. Swap status → `claimed`. First claim wins.
3. Branch-admin opens Filament → **Swaps** → **Approve** or **Reject**.
   - Approve: original assignment → `swapped`; new assignment for the claimer, same event + duty role, status `accepted`, `assigned_by` = approver. One transaction.
   - Reject: swap `rejected`; original assignment back to `accepted` (original member still on duty; may offer again).
4. Offerer can **Cancel** while `open` or `claimed` → swap `cancelled`, assignment back to `accepted`.
5. Event starts with the swap still `open` or `claimed` → treated as expired (hidden from boards, can't be claimed or approved). Original member stays on duty. No scheduler needed. *(my call)*

## Screens / routes
- Member dashboard:
  - "My upcoming duties": **Offer swap** on accepted duties; badges "Swap offered" / "Waiting for admin"; **Cancel swap**.
  - New section **"Open swaps in your branch"**: event, time, AM/PM, duty role, offered by → **Claim**. Own offers and ineligible ones hidden.
- Routes (auth): `POST /duties/{assignment}/swap` (`swaps.store`), `POST /swaps/{swap}/claim` (`swaps.claim`), `POST /swaps/{swap}/cancel` (`swaps.cancel`).
- Filament, nav group Roster → **Swaps** (list): branch-scoped, filter by status, nav badge = claimed swaps waiting. Row actions Approve / Reject (only when `claimed`).

## Data changes
- New table `duty_swaps`: `id`, `branch_id`, `duty_assignment_id` (cascade), `offered_by` (user), `claimed_by` (user, nullable), `status` (`open` | `claimed` | `approved` | `rejected` | `cancelled`, enum `SwapStatus`), `claimed_at`, `decided_by` (nullable, nullOnDelete), `decided_at`, timestamps.
- `DutyStatus` enum gains `swap_requested` and `swapped` (column is already a string; no schema change).
- `DutyAssignment::scopeActive` excludes `swapped` (it still excludes `declined`). Check every v1 rule that uses it.

## Rules (Pest tests first, in a new `DutySwapService`)
- Offer: only the assigned member, only from `accepted`, only before start, one open/claimed swap per assignment. Guest rows can't be swapped.
- Claim: claimer has role member or branch-admin, same branch as the event, is not the offerer, event not started, no overlapping active duty, **not unavailable** for that block ([[duty-availability]]). Row lock on the swap so two members can't claim at once.
- Approve: re-checks all claim rules (things may have changed since the claim), then swaps atomically. The slot stays filled the whole time, so **a swap never leaves an event without its required duties** (Roadmap rule).
- Approver: branch-admin of the event's branch or super-admin. An admin who offered or claimed the swap can't approve it when the branch has another admin; the super-admin can always approve; if they are the branch's only admin they may approve it themselves. Owner decision 2026-11-26.
- Admin removing an assignment with an open/claimed swap → swap `cancelled`.
- Cross-branch claims are blocked.

## Permissions
- Policy `DutySwapPolicy`: offer/cancel = owner of the assignment; claim = eligible member of the same branch; approve/reject = admin of that branch or super-admin.
- Filament Swaps resource scoped by branch in the query, like Events.

## Out of scope
- Cross-branch swaps (Roadmap: "unless super-admin approves") → Backlog.
- Offering to one named person.
- Email / in-app notifications for offers, claims and approvals → Phase 5 ("swap approved" email trigger).
- Swapping guest duties.
- Swap history page → Backlog (duty history page already parked).

## Decisions
- Self-approval: the other admin approves; a branch with a single admin may self-approve (owner, 2026-11-26).

Tasks: [[duty-swaps-tasks]]
Notes / decisions: [[duty-roster]], [[duty-availability]]. Roadmap rule text still says "branch-head or branch-admin approves"; update to branch-admin only (branch-head removed 2026-11-26).

## Notes
- Open-offer flow: offer → claim → admin approve or reject. The offerer can cancel while the swap is `open` or `claimed`.
- Approve marks the old row `swapped` and creates a new `accepted` row for the claimer, with `assigned_by` = approver and `responded_at` = approval time. The slot is never empty.
- Expired swaps (event started) are not cleaned up. The assignment stays `swap_requested`, still counts as on duty, and the dashboard shows "Swap expired. You're still on this duty." No scheduler job yet (see [[Backlog]]).
- The claimer sees "Waiting for your admin" on their pending claims.
- Self-approval rule as decided (see Decisions above).
- Removing an assignment or deleting an event deletes its swap rows (FK cascade, no history kept). Admin removal cancels a live swap first.
- Lock order: approve = swap → event → user → assignment; claim = swap → user; cancel and reject = swap → assignment.
- Filament: Roster → Swaps, with a nav badge showing claimed swaps waiting for an admin.
- Demo: `DutyRosterSeeder` creates one open and one claimed swap where a branch has enough members.
