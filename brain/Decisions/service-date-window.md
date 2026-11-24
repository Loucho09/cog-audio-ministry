# Service date window

Status: accepted 2026-11-24 (owner)
Related: [[services-section-v2]], [[homepage-sections]], [[00-Index]]

Decision: each service has two nullable dates, `starts_on` and `ends_on`. Null means no limit.

Why:
- Simplest thing that covers season pauses (for example, Revival and Salvation Nights ending for the year).
- No exceptions table. A table of one-off dates is more work and not needed yet.

Trade-off: single cancelled dates are not covered. Parked in [[Backlog]].
