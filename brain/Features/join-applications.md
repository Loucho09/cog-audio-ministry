# Join applications
Status: planned
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
Tasks: [[join-applications-tasks]]
Notes / decisions: [[join-application-rules]], [[join-form-link]], [[homepage-sections]], [[member-accounts-roles]], [[temp-password-and-demo-emails]]
