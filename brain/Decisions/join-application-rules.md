# Decision: join application rules
Date: 2026-11-25
Related: [[join-applications]], [[join-applications-tasks]], [[join-form-link]]

## Owner decisions (2026-11-25)
- (a) No rejection email. Rejection is silent.
- (b) Duplicates are blocked. Submit is refused if the email already belongs to a user account or has a pending application. The validation message is friendly and does not say which case applies, so it does not leak account existence more than needed.
- (c) No age minimum. Age is collected as info only, validated as a reasonable integer.

## Reasons
- Silent rejection avoids awkward emails from a volunteer ministry.
- Blocking duplicates keeps the branch review queue clean.
- No age rule: the owner did not ask for one, and age-based consent is out of scope.
