# Decision: duty guest assign
Date: 2026-11-26
Status: accepted

- Context: the owner asked to assign either an existing branch member (dropdown) or to type a guest name for someone without an account (visitor, volunteer from another church).
- Decision: guest assignments are auto-confirmed (status `accepted`, `responded_at` = now). No guest accept/decline step.
- Timing: built after the [[duty-roster]] v1 merge and before v2 swaps.
- Spec: [[duty-guest-assign]]
- Related: [[duty-roster]]
