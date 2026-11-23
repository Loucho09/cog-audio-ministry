# Package: Laravel Breeze Blade

Date: 2026-11-23
Approved by: owner

## Decision
Add **Laravel Breeze** (Blade stack) for authentication scaffolding.

## What
Laravel Breeze is Laravel's official starter kit that includes:
- Blade templates for login, register, password reset, and email verification.
- Authentication controllers and middleware (Laravel `auth`).
- Form validation using Form Requests.
- Tailwind CSS pre-configured.
- Simple, readable code (no JavaScript framework overhead).

## Why
- **Official, lightweight:** Breeze is Laravel's recommended starting point; zero bloat.
- **Blade stack:** uses only Blade templates + Tailwind + Alpine, no Vue/React (matches our existing tech).
- **Maintainable:** starter kit code is easy to customize for our custom flow (e.g., members created by approval, not open signup).
- **Quick launch:** scaffolds auth in minutes instead of hours of custom dev.

## Usage in this project
- Phase 2: publish Breeze, customize the register route to disable open signup (members are added by approval only).
- Member login/password reset/email verification use Breeze as-is.
- Branch-head login also uses Breeze; roles and Filament access are added via Spatie + Filament.

## Customization
- **No open registration:** `register` and `password.request` routes remain, but new accounts are created only by admin approval (Phase 3: join applications).
- **Profile page:** extend Breeze's profile template to show member info (branch, duties, availability).

## See also
- [[Roadmap]] Phase 2 (member auth and profile page)
- [[package-spatie-permission]] (roles assigned after Breeze account creation)
