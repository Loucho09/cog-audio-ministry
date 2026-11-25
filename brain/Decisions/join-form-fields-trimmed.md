# Join form fields trimmed
Date: 2026-11-25
Status: decided (owner)

Decision: the owner decided to keep the join form short. Removed:
- "Where would you like to serve?" (`interests`)
- "Your experience" (`experience`)
- "Your availability" (`availability`)

The form now asks for: full name, age, contact number, email, Facebook link (optional), branch, consent.

Consequences:
- Migration `2026_11_25_140000_drop_interests_experience_availability_from_join_applications` drops the three columns. Existing values in them are lost on migrate. `down()` re-adds the columns as nullable, but not the values.
- The staff email no longer lists interests.

Related: [[join-applications]], [[join-application-rules]]
