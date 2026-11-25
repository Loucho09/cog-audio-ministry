# Duty availability tasks
Spec: [[duty-availability]]
Branch: `feature/duty-availability`
Rule: rule tests first (Pest), then code. Each slice: `php artisan test` + `./vendor/bin/pint` green, then commit.

## Slice A1: data + AM/PM block (backend-builder)
- [x] `config/ministry.php`: `duty_pm_starts_at` => `'15:00'`
- [x] Enum `app/Enums/AvailabilityPart.php` (`whole_day`, `am`, `pm`, labels)
- [x] Migration `member_unavailabilities` (user_id cascade, branch_id, date, part, note 100 nullable, unique user_id+date)
- [x] Model `MemberUnavailability` (`$fillable`, casts, `user()`, `scopeUpcoming`); `User::unavailabilities()`
- [x] `Event::block()` returns `am`/`pm` from `starts_at` and the config cutoff
- [x] Tests: 13:30 = AM, 15:00 = PM, 15:30 = PM, cutoff read from config

## Slice A2: mark / remove rules (backend-builder)
- [x] `app/Services/AvailabilityService.php`: `mark(user, date, part, note)`, `remove(entry)`; violations throw `RosterRuleViolation`
- [x] Rules: today..+6 months, one entry per day, blocked if active duty in that block (whole day = both), past entries can't be removed
- [x] `app/Policies/MemberUnavailabilityPolicy.php`: owner only
- [x] Tests first for every rule + policy

## Slice A3: assign respects availability (backend-builder)
- [x] `DutyRosterService::assign()` rejects unavailable member (whole day or matching block); guests unaffected
- [x] Helper `MemberUnavailability::blocks(user, event)` (or service method) reused by Filament and swaps
- [x] Tests first: AM entry blocks 9:30, not 15:30; whole day blocks both; other date doesn't block

## Slice A4: member dashboard UI (fullstack)
- [x] Routes `availability.store`, `availability.destroy` (auth)
- [x] `AvailabilityController` (thin) + `StoreAvailabilityRequest`
- [x] `DashboardController`: pass upcoming entries
- [x] `dashboard.blade.php`: "My availability" section (form + list + Remove), light/dark, 360px
- [x] Feature tests: happy path, can't delete another member's entry, validation errors shown

## Slice A5: Filament + demo (backend-builder)
- [x] `AssignmentsRelationManager`: unavailable members labelled "(unavailable)" and disabled in the dropdown
- [x] `DutyRosterSeeder`: a few demo entries (AM, PM, whole day)
- [x] Test: Assign action refuses an unavailable member

## Wrap-up
- [x] `reviewer` on the branch, fix issues
- [ ] `brain-keeper`: spec status, Roadmap, Backlog (admin availability list)
- [ ] Merge to main
