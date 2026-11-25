# Decision: full review of Phases 1-2 (owner decisions)
Date: 2026-11-25
Related: [[Roadmap]], [[member-accounts-roles]], [[temp-password]], [[admin-user-management]], [[Backlog]]
Fix branch: `fix/full-review`

## Owner decisions
- **Welcome set-password link lasts 3 days.** Uses a separate `invites` password broker. Normal forgot-password links stay at 60 minutes. Password emails are queued.
- **Branch gallery photos and team members need super-admin approval.** New `is_published` flag. Existing rows are backfilled as published.
- **A super-admin MAY set a temporary password on another super-admin.** Kept on purpose as a rescue path.
  - Accepted risk: a rogue super-admin could take over another super-admin.
- **Only plain members may delete their own account.** Staff roles must ask the super-admin.

## Other approved fixes
- Login and reset throttles keyed by email + IP, because a church shares one IP.
- No account enumeration on forgot-password (same response for known and unknown emails).
- A password reset clears `must_change_password`.
- An email change requires the current password.
- Branch Facebook links must be https only.
- Uploaded files are deleted when the record is deleted or the file is replaced.
- Timezone comes from config (`APP_TIMEZONE`), not hardcoded.

## Follow-ups
- Phase 7 items: see [[Roadmap]] (Phase 7, review items).
- Parked items: see [[Backlog]] (review items).
