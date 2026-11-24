# Temporary password and forced change
Status: done (built and reviewed on `feature/temp-password`; merge pending owner OK, 2026-11-25)
Phase: 2 (add-on, after Phase 2 merged)
Goal: Let staff give a member a temporary password. The user must change it on next login. Also switch demo seed emails to branch domains.
Users: super-admin, branch-head, member
Screens / routes:
- Filament create user: choice "Set a temporary password" or "Send set-password email".
- Filament users table: "Set temporary password" action on existing users.
- "Change your password" page (`password.change`, POST `password.change.store`). Any authenticated route, including /admin, is blocked until changed. Logout stays allowed.
- An unflagged user who opens /change-password is redirected to the profile page.
Data changes (tables, columns):
- `users.must_change_password` boolean, default false. Set via `forceFill`, NOT in `$fillable`.
- Demo seed emails: superadmin@cogdasma.audio, head@cog{slug}.audio, admin@cog{slug}.audio, member1..3@cog{slug}.audio (replaces @example.test). Demo password: "password" (local and testing only).
Permissions:
- super-admin: can set a temporary password on any user.
- branch-head: can set one only on plain members (role `member`) of their own branch. Enforced in UserPolicy, not only UI.
- Setting a temporary password logs the user out of all sessions.
- Setting a temporary password marks the email as verified, because the admin vouches for it.
- Middleware blocks every authenticated route while `must_change_password` is true. Allow-list is exactly: `password.change`, `password.change.store`, `logout`, `filament.*.auth.logout`.
- `AuthenticateSession` is on the web group.
- New password must not equal the temporary one. Changing it clears the flag, cycles `remember_token`, and logs out other devices.
- The change POST is limited to 5 attempts per 10 minutes per user.

Owner decision (2026-11-25): password rules are now min 10 characters with letters and numbers. Set in `Password::defaults` (AppServiceProvider) and applied everywhere. `uncompromised()` is not used, because it calls an external service. See [[temp-password-and-demo-emails]].

Out of scope:
- Password expiry.
- Password history.
- Email delivery of temporary passwords (owner shares it out of band).
Tasks: [[temp-password-tasks]]
Notes / decisions: [[temp-password-and-demo-emails]]
