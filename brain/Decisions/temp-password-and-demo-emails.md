# Decision: temp passwords and branch-style demo emails
Date: 2026-11-25
Related: [[temp-password]], [[temp-password-tasks]]

## Choices
- Super-admin picks at user creation: "set a temporary password" or "send set-password email".
- Temporary password can be set on any user by super-admin. Branch-head can set one only on plain members of their own branch (enforced in UserPolicy).
- Setting one logs the user out of all sessions and marks the email as verified (the admin vouches for it).
- `users.must_change_password` (default false) set via `forceFill`, not fillable. Middleware forces a "Change your password" page on all authenticated routes, including /admin. Allow-list: `password.change`, `password.change.store`, `logout`, `filament.*.auth.logout`. New password must pass Password::defaults and differ from the temporary one. Changing it clears the flag, cycles `remember_token`, and logs out other devices. The change POST is limited to 5 attempts per 10 minutes per user.
- Demo seed emails use branch domains: superadmin@cogdasma.audio, head@cog{slug}.audio, admin@cog{slug}.audio, member1..3@cog{slug}.audio. Demo password: "password".
- Out of scope: password expiry, password history.

## Password rules (owner decision, 2026-11-25)
- Min 10 characters with letters and numbers, set in `Password::defaults` (AppServiceProvider) and applied everywhere.
- `uncompromised()` is not used, because it calls an external service.
- Reason: stronger than 8 characters but still easy to type on a phone.

## Reasons
- Members may not have working email, so a temporary password is needed as a fallback to the set-password email.
- Forced change keeps the temporary password short-lived.
- Forcing it via `forceFill` keeps mass assignment safe.
- Branch-head limit to own-branch members keeps scoping rules consistent.
- Owner prefers branch-styled demo emails for realistic demos, despite the risk.

## Risk
- `.audio` domains are real. Keep `MAIL_MAILER=log` locally so demo mail never reaches real inboxes.
