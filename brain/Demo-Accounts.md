# Demo Accounts

Status: fixed (owner rule, 2026-11-26). **Do not change these emails or the password.**
Source: `database/seeders/DemoUserSeeder.php`. Created by `php artisan migrate:fresh --seed`.
Local/testing only. Remove before production (see [[Roadmap]], Phase 7).

Password for every demo account: `password`

## Admins
| Role | Branch | Email |
|---|---|---|
| super-admin | all branches | superadmin@cogdasma.audio |
| branch-head | COG Dasmariñas | head@cogdasma.audio |
| branch-head | COG Trece | head@cogtrece.audio |
| branch-head | COG Silang | head@cogsilang.audio |
| branch-head | COG Jabez | head@cogjabez.audio |
| branch-admin | COG Dasmariñas | admin@cogdasma.audio |
| branch-admin | COG Trece | admin@cogtrece.audio |
| branch-admin | COG Silang | admin@cogsilang.audio |
| branch-admin | COG Jabez | admin@cogjabez.audio |

## Members
Pattern: `member{N}@cog{slug}.audio` (e.g. member1@cogdasma.audio). Slugs: dasma, trece, silang, jabez.

## Real super-admin
Not seeded. Create with `php artisan ministry:create-super-admin` (see [[super-admin-via-artisan-command]]).
