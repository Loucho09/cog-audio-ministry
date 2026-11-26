# Reminder delivery is at-most-once
Date: 2026-11-26. Spec: [[duty-notifications]]. Tasks: [[duty-notifications-tasks]]

## Decision
`DutyReminderService` claims a row first (atomic conditional UPDATE sets `reminded_*_at`, re-checking status), then queues the notification.

## Why
- Claim-then-send means two scheduler runs can never remind twice.
- If queuing (enqueue) throws, the claim is released so the next run retries.
- `DutyReminder::shouldSend` re-checks Accepted, same user and same future `starts_at`, so a job that runs late is dropped if the duty changed.

## Trade-off
- Once the job is queued, a failed mail job is not retried by re-reminding. Delivery is at-most-once.
- Chosen over duplicate reminders. Revisit if mail failures show up in production.
