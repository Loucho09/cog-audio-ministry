# Join applications
Status: done (closed 2026-11-25; merged into main)
Phase: 3
Goal: Visitors apply to join the Audio Ministry for a chosen branch. That branch's heads and admins, and the super-admin, review and approve or reject. Approval creates a member account and sends a set-password email.
Users: visitor (submits), branch-head and branch-admin (review own branch only), super-admin (all branches)
Screens / routes:
- Public `GET` join form (`join.create`) and `POST` submit (`join.store`), then a thank-you page (`join.thanks`).
- Filament `JoinApplication` resource: list with branch and status filters, view, approve, reject (pending only).
- Homepage "Join Us" button points to `join.create`.
Form fields (current, 2026-11-25; see [[join-form-fields-trimmed]]):
- Full name, age (integer, info only), contact number, email
- Facebook profile link (optional, URL)
- Branch (dropdown from `branches`)
- Consent checkbox (required)
- ~~Interest areas (checkboxes): FOH, Monitors, Livestream, Stage Tech, Other~~ (obsolete: removed 2026-11-25)
- ~~Experience, availability~~ (obsolete: removed 2026-11-25)
- Honeypot field (hidden, must stay empty). Rate limit on submit.
Data changes (tables, columns):
- `join_applications` table: `id`, `branch_id` (FK), `full_name`, `age`, `contact_number`, `email`, `facebook_url` nullable, ~~`interests` (json), `experience`, `availability`~~ (dropped 2026-11-25 by migration `2026_11_25_140000_drop_interests_experience_availability_from_join_applications`; `down()` re-adds them as nullable), `consented_at`, `status` (pending / approved / rejected, default pending), `reviewed_by` nullable FK users, `reviewed_at` nullable, `user_id` nullable FK users (set on approval), timestamps.
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
- Validation limits (spec silent): age 1-120; contact 7-30 chars, digits, `+-()` and spaces; `facebook_url` https only; ~~experience and availability required, max 2000; interests required, array, min 1, distinct~~ (obsolete 2026-11-25, see [[join-form-fields-trimmed]]).
- Not yet browser-checked at 360px or in dark mode (checked 2026-11-25, see Browser check below).

Slice 3 notes (2026-11-25, commit 5156e62):
- `NewJoinApplication` notification: `ShouldQueue`, mail channel, `afterCommit`, `deleteWhenMissingModels`. Stores the branch name as a string.
- Recipients via `App\Actions\NotifyStaffOfJoinApplication::recipients()`: super-admins plus branch-head and branch-admin with matching `branch_id`, deduped. Staff with null branch get nothing.
- No applicant email. Mail has only name, branch, ~~interests~~ (removed 2026-11-25, see [[join-form-fields-trimmed]]), submitted time (no contact, age or Facebook; data minimisation).
- Name and branch are Markdown-escaped to stop link injection.
- Dispatch failure is `report()`ed; applicant is still redirected to thanks.
- Local dev: `QUEUE_CONNECTION=database`, `MAIL_MAILER=log`. Run `php artisan queue:work`; mail appears in `storage/logs`.

Slice 4 notes (2026-11-25, commit 3b52520, not browser-checked yet):
- `App\Filament\Resources\JoinApplications\JoinApplicationResource` has List and View pages only. Approve and reject come in Slice 5.
- `getEloquentQuery()` uses `visibleTo`. Another branch's view page returns 404. Members get 403. Staff with a null `branch_id` get 403 and see 0 rows.
- Status filter defaults to pending (builder's call: the list acts as a review queue).
- Branch filter is shown to super-admin only.
- Name and email are searchable. Newest first.
- Infolist has Applicant, Application and Review sections. The Facebook link shows only when it starts with https, and opens in a new tab with `rel="noopener"`.
- Nav badge shows the live, scoped pending count.
- `canCreate`, `canEdit`, `canDelete` and `canDeleteAny` are explicitly false, because super-admin bypasses policies through `Gate::before`.
- Global search is disabled.
- Staff email links to `JoinApplicationResource::getUrl('view', ..., panel: 'admin')`. The queue worker relies on APP_URL being set, which matters in production.

Slice 5 notes (2026-11-25, commit 40e4815):
- Services: `App\Actions\ApproveJoinApplication` and `RejectJoinApplication`. Each runs in `DB::transaction` with `lockForUpdate`.
- Check order: authorize `view` (branch), then explicit `isPending()` (applies to super-admin too, despite `Gate::before`), then authorize `approve` or `reject`. A cross-branch user gets "not allowed" whatever the status, so status does not leak.
- Approve:
  - Creates User: trimmed name, trimmed and lowercased email, `branch_id` from the application, random 40-char password.
  - Assigns only `member` via `assignRole` directly (not the AssignRole action, which needs a super-admin actor).
  - Sets status, `reviewed_by`, `reviewed_at`, `user_id` with `forceFill`.
  - Queues Phase 2 `WelcomeSetPassword` (`invites` broker, 3-day link, afterCommit).
  - `email_verified_at` stays null, matching the Phase 2 set-password path.
  - Email already has an account: refused, application stays pending. A unique-index race returns the same friendly message.
- Reject: sets status, reviewer, time. No email (owner decision).
- Errors: `JoinApplicationReviewException` messages are safe to show staff.
- Filament: Approve and Reject as view-page header actions and list row actions, with confirmation. `visible()` is UI only; services re-authorize.
- Tests: 20 in `tests/Feature/Admin/JoinApplicationReviewTest.php`.
  - The race test runs on one DB connection, so it is not a true concurrent race.
  - SQLite is case-sensitive, MySQL is not. A mixed-case duplicate is caught by MySQL collation, or by the unique-violation fallback.

Slice 6 notes (2026-11-25, commit 7bc1fad):
- Links to `route('join.create')`, same tab: hero "Join Us", navbar "Join Us", footer "Join Us", churches section "Want to serve with us? Join the team", and the Join Us section button (label now "Apply to join").
- Before, navbar, footer, hero and churches links pointed to `#join`.
- `ministry.join_form_url` and `MINISTRY_JOIN_FORM_URL` retired. The Google Form is no longer linked anywhere on the site. See [[join-form-link]] (obsolete).

Slice 7 notes (2026-11-25, commit 364f476):
- `App\Filament\Widgets\PendingApplicationsWidget` is a TableWidget on the `/admin` dashboard, `$sort = 0`, full width.
- Shows the 5 newest pending applications, built from `JoinApplicationResource::getEloquentQuery()->pending()`, so scoping is shared with the resource.
- Heading count is scoped, e.g. "Pending applications (3)".
- Each row links to the view page. A "View all" header action links to the list. All URLs use `panel: 'admin'`.
- `canView` uses the policy's `viewAny`. Members and branchless staff are refused.
- Empty state: "No pending applications".

Final review notes (2026-11-25, commit cc05d36):
- `JoinApplicationSeeder` runs only in local and testing.
- Reviewed demo rows use the branch's branch-head, or the super-admin, as reviewer.
- Approved demo rows get a real member account linked through `user_id`. Demo accounts have a verified email; the real approval flow leaves it unverified.
- Factory `approved()` and `rejected()` accept an optional reviewer.
- Tests: 552 total, 551 passed, 1 skipped (pre-existing).

Browser check (2026-11-25): passed. Covered:
- `/join` at 360px, light and dark, no horizontal overflow
- Submit, then the thanks page
- Duplicate email gets the generic message
- Staff email went to super-admin, Dasma head and Dasma admin only
- Dashboard widget and list are scoped
- Approve
- Trece head opening a Dasma application gets 404
- Set-password link, then member login, then `/admin` returns 403
- Admin list at 360px in dark mode

Admin UX rework (2026-11-25, commit 1ba1f6d, branch `feature/join-admin-ux`, merged into main). Matches the Users screens:
- List: tabs Pending (default), Approved, Rejected, All. Each tab shows a branch-scoped count. Pending count uses the warning colour and is hidden at 0. Tabs replace the status filter. Branch filter still shown to super-admin only.
- Rows: Split/Stack reflow, no sideways scroll at 360px. Name with email under it, branch, status badge with icon, "Submitted … ago". Approve and Reject sit in an action menu.
- View page: title is the applicant's name. Sections: Contact details, Application (submitted, consent), Review. Applicant summary card. Review links to the member account once approved.
- Approve modal names applicant and branch, and says the set-password link works for 3 days. Reject modal names applicant and branch. Success messages name the applicant.
- Dashboard widget renamed "Waiting for review (N)". Rows use the list style.
- Browser check 2026-11-25: passed. Desktop in light mode, and 360px in dark mode.

Set-password page (Phase 2 reset page, updated 2026-11-25, commit f623b4f):
- Two modes. Invite links (detected with `InviteToken::unwrap`) show "Set your password", a "Hi {first name}, your application was approved…" greeting and a "Save password and continue" button. Normal resets show "Choose a new password".
- First name shown only when `Password::broker('invites')->tokenExists($user, $raw)` is true. The signed marker covers the token, not the email, so without this check a swapped email would reveal another person's name.
- Email field is read-only.
- Shows the real password rules: at least 10 characters, with letters and numbers.
- Show/hide toggle and a live "passwords match" hint.
- Expired link shows a friendly message after submit.
- Login status after success: "Your password is set…" for invites, "Your password has been reset…" for normal resets.

Definition of done:
- [x] Matches its spec (routes, fields, duplicate rule, notifications, approval flow)
- [x] Works on mobile and desktop: browser-checked 2026-11-25, passed (see Browser check above).
- [x] Validation and authorization in place (Form Requests, policies, branch scope, pending-only review)
- [x] Tests pass (`php artisan test`: 552 total, 551 passed, 1 skipped pre-existing)
- [x] `reviewer` run and issues addressed (final review, cc05d36)
- [x] Brain updated (spec status, Roadmap, tasks, production notes)

Production notes (for Phase 7, see [[Roadmap]]):
- Set trusted proxies so the join-form throttle (5/min per IP) and the login throttle see the real client IP behind the host's proxy.
- Production `CACHE_STORE` must support atomic locks (database, redis or file). The join form uses `Cache::lock`.
- `APP_URL` must be set correctly. Queued staff emails build admin links from it.
- A queue worker must run for the staff email and the set-password email.
- Replace the old Google Form QR if it is still shared offline or on Facebook, with a link or QR to `/join`.

Tasks: [[join-applications-tasks]]
Notes / decisions: [[join-application-rules]], [[join-form-fields-trimmed]], [[join-form-link]], [[homepage-sections]], [[member-accounts-roles]], [[temp-password-and-demo-emails]]
