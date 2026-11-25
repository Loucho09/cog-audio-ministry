# Demo Accounts

Status: fixed (owner rule, 2026-11-26). **Do not change these emails or the password** without the owner's OK.
Source: `database/seeders/DemoUserSeeder.php`. Created by `php artisan migrate:fresh --seed`.
Local/testing only. Remove before production (see [[Roadmap]], Phase 7).

Password for every demo account: `password`

## Admins (updated 2026-11-26: branch-head role removed)
Owner approved: the `branch-head` role is gone ([[remove-branch-head-decision]]). Each branch has 1 branch-admin; COG Dasmariñas has 2. `head@cogdasma.audio` keeps its email and is now Dasma's 2nd branch-admin.

| Role | Branch | Email |
|---|---|---|
| super-admin | all branches | superadmin@cogdasma.audio |
| branch-admin | COG Dasmariñas | admin@cogdasma.audio |
| branch-admin | COG Dasmariñas | head@cogdasma.audio |
| branch-admin | COG Trece | admin@cogtrece.audio |
| branch-admin | COG Silang | admin@cogsilang.audio |
| branch-admin | COG Jabez | admin@cogjabez.audio |

Removed (no longer seeded): head@cogtrece.audio, head@cogsilang.audio, head@cogjabez.audio.

## Members
Pattern: `member{N}@cog{slug}.audio` (e.g. member1@cogdasma.audio). Slugs: dasma, trece, silang, jabez.

## Real super-admin
Not seeded. Create with `php artisan ministry:create-super-admin` (see [[super-admin-via-artisan-command]]).
