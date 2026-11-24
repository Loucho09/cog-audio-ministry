# Tasks: temp password and demo emails
Feature: [[temp-password]]
Branch: feature/temp-password (built, reviewed, fixed; merge pending owner OK)

- [x] Slice 1: forced password change (migration, middleware, change page, tests). Commit 3cccde2
- [x] Slice 2: Filament create choice and the "Set temporary password" action plus policy, with tests. Commit 6417829
- [x] Slice 3: demo seed emails. Commit 7d0ac73
- [x] Review hardening. Commit 6cc7b94
- [x] Positive-control test for direct temp-password calls. Commit 45b245e
- [x] After slices: reviewer, fixes, brain-keeper. Merge pending owner OK

Commits:
- 3cccde2: forced change
- 6417829: admin temporary password
- 7d0ac73: demo emails
- 6cc7b94: review hardening
- 45b245e: positive-control test

Tests: 301 total, 300 passed, 1 skipped.
