# Super-admin created by artisan command

Status: accepted
Date: 2026-11-24
Related: [[member-accounts-roles]], [[Roadmap]], [[00-Index]]

## Decision
- The real super-admin is created with `php artisan ministry:create-super-admin`. It prompts for name, email and password (hidden), validates them, creates a verified user, and assigns the `super-admin` role.
- Seeders create only fake demo admins (local use). No real super-admin is seeded.
- No passwords or emails for real accounts go in seeders, `.env`, or the repo.

## Why
- Works the same in production: run it once on the server after `migrate --force`.
- Keeps secrets out of the repo and `.env.example`.
- Matches production checklist item 7 (remove seeded fake data; keep only the super-admin, created manually).

## Consequences
- Deploy runbook must include this command as a first-login step.
- The command is tested (creates a user with the role).
