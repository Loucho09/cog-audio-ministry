> OBSOLETE (2026-11-25, Phase 3 Slice 6, commit 7bc1fad): superseded by the Phase 3 join form (`join.create`). `ministry.join_form_url` and `MINISTRY_JOIN_FORM_URL` are retired. The Google Form is no longer linked. Kept for history.

# Join form link (interim)
Date: 2026-11-24
Status: obsolete (superseded by the Phase 3 join form)

Decision: the homepage "Join Us" button opens the ministry's existing Google Form in a new tab, as an interim join path until the Phase 3 application form replaces it (see [[Roadmap]] Phase 3).

Why:
- The Google Form is already used by the ministry (linked from the "Scan here to join" QR poster). Visitors can join today without waiting for Phase 3.

Config:
- `config/ministry.php` key `join_form_url`, env `MINISTRY_JOIN_FORM_URL`. Https only. Default is the form URL. Also in `.env.example`.
- The URL is public, not a secret, so it is a config default (same as the Facebook URL and email).
- Set the env var empty to turn the link off. The button then falls back to `#contact` with "Message us to join".

Consequences:
- Phase 3 must swap the button to the in-app application form and remove this config key or leave it unused. See [[homepage-sections]] and [[Roadmap]].
