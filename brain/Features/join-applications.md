# Join applications
Status: in progress
Phase: 3
Goal: Visitors apply to join the Audio Ministry for a chosen branch. That branch's heads and admins, and the super-admin, review and approve or reject. Approval creates a member account and sends a set-password email.
Users: visitor (submits), branch-head and branch-admin (review own branch only), super-admin (all branches)
Screens / routes:
- Public `GET` join form (`join.create`) and `POST` submit (`join.store`), then a thank-you page (`join.thanks`).
- Filament `JoinApplication` resource: list with branch and status filters, view, approve, reject (pending only).
- Homepage "Join Us" button points to `join.create`.
Form fields:
- Full name, age (integer, info only), contact number, email
- Facebook profile link (optional, URL)
- Branch (dropdown from `branches`)
- Interest areas (checkboxes): FOH, Monitors, Livestream, Stage Tech, Other
- Experience, availability
- Consent checkbox (required)
- Honeypot field (hidden, must stay empty). Rate limit on submit.
Data changes (tables, columns):
- `join_applications` table: `id`, `branch_id` (FK), `full_name`, `age`, `contact_number`, `email`, `facebook_url` nullable, `interests` (json), `experience`, `availability`, `consented_at`, `status` (pending / approved / rejected, default pending), `reviewed_by` nullable FK users, `reviewed_at` nullable, `user_id` nullable FK users (set on approval), timestamps.
- `$fillable` excludes `status`, `reviewed_by`, `reviewed_at`, `user_id`. These are set by the approval action only.
- Approval creates a `users` row with `branch_id` from the application and the `member` role.
Permissions:
- Policy plus query scope: branch-head and branch-admin see and act only on their own branch's applications. Super-admin sees all.
- Visitors and members have no admin access to applications.
- Only `pending` applications can be approved or rejected.
- Duplicate rule (owner decision, see [[join-application-rules]]): submit is blocked if the email already belongs to a user account or has a pending application. The message is generic and friendly, and does not reveal which case applies.
Notifications:
- New application: queued email to that branch's branch-head(s) and branch-admin(s), plus super-admin(s).
- Approval: set-password email to the new member (via the standard password broker flow).
- Rejection: no email (owner decision, see [[join-application-rules]]).
Out of scope:
- Rejection email.
- Applicant status-tracking page.
- Editing an application by the applicant.
- File uploads.
- Age-based consent rules or an age minimum (age is info only).
- Duty roster (Phase 4).
Slice 1 notes (2026-11-25, commit 1d84d6a):
- Status is string constants (`STATUS_PENDING`, `STATUS_APPROVED`, `STATUS_REJECTED`), not a PHP enum.
- `branch_id` FK uses restrictOnDelete to keep application history.
- Policy `create`, `update`, `delete` return false. Applications come only from the public form. No super-admin delete for now; owner can ask later.
- Model overrides `BelongsToBranch` visibleTo so plain members see nothing.
- `Gate::before` gives super-admin every ability, so the pending-only rule must be enforced inside the approve and reject actions (Slices 4 and 5).

Slice 2 notes (2026-11-25, commit 9103609):
- Routes: `join.create` (GET /join), `join.store` (POST, throttle `join-form` 5/min per IP, 429 when exceeded), `join.thanks`.
- Honeypot field `website` is checked first in `prepareForValidation`: filled means redirect to thanks, no save, no validation.
- Email trimmed and lowercased.
- Duplicate block (existing user or pending application) uses one generic message, `StoreJoinApplicationRequest::DUPLICATE_MESSAGE`. Rejected and approved applicants may reapply.
- `Cache::lock` per email wraps the re-check and create to stop races.
- `consented_at` is set server-side.
- Validation limits (spec silent): age 1-120; contact 7-30 chars, digits, `+-()` and spaces; `facebook_url` https only; experience and availability required, max 2000; interests required, array, min 1, distinct.
- Not yet browser-checked at 360px or in dark mode.

Slice 3 notes (2026-11-25, commit 5156e62):
- `NewJoinApplication` notification: `ShouldQueue`, mail channel, `afterCommit`, `deleteWhenMissingModels`. Stores the branch name as a string.
- Recipients via `App\Actions\NotifyStaffOfJoinApplication::recipients()`: super-admins plus branch-head and branch-admin with matching `branch_id`, deduped. Staff with null branch get nothing.
- No applicant email. Mail has only name, branch, interests, submitted time (no contact, age or Facebook; data minimisation).
- Name and branch are Markdown-escaped to stop link injection.
- Dispatch failure is `report()`ed; applicant is still redirected to thanks.
- Local dev: `QUEUE_CONNECTION=database`, `MAIL_MAILER=log`. Run `php artisan queue:work`; mail appears in `storage/logs`.

Tasks: [[join-applications-tasks]]
Notes / decisions: [[join-application-rules]], [[join-form-link]], [[homepage-sections]], [[member-accounts-roles]], [[temp-password-and-demo-emails]]
