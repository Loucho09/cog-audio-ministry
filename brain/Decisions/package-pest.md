# Package: Pest

Date: 2026-11-23
Approved by: owner

## Decision
Add **Pest** (testing framework) instead of PHPUnit.

## What
Pest is a modern testing framework for PHP built on top of PHPUnit. It provides:
- Cleaner, more expressive test syntax (inspired by Jest and Cypress).
- Tests written as simple functions instead of classes.
- Familiar assertion helpers and hooks (beforeEach, afterEach).
- 100% compatible with PHPUnit under the hood.
- Plugins for Laravel (database, authentication, expectations, etc.).

## Why
- **Readable:** test code is more concise and easier for new developers to understand.
- **Laravel-first:** Pest has first-class Laravel support; works seamlessly with Factories, Seeds, and artisan test runner.
- **Lower ceremony:** no need for class boilerplate; just write a test function.
- **Active maintenance:** Pest is actively developed and widely adopted in the Laravel community.

## Usage in this project
- Phase 2+: write feature tests for roles, branch scoping, and member permissions.
- Phase 3: test join applications, email queuing, branch-head approval.
- Phase 4: test duty assignment logic, swaps, double-booking rules.
- Phase 5+: test notifications per trigger.
- Run tests with `php artisan test` (Pest integrates with artisan).

## Example
```php
test('branch-head cannot view another branch\'s applications', function () {
    $branch1 = Branch::factory()->create();
    $branch2 = Branch::factory()->create();
    
    $app1 = JoinApplication::factory()->create(['branch_id' => $branch1->id]);
    $head2 = User::factory()->branchHead($branch2)->create();
    
    actingAs($head2)
        ->get(route('applications.index'))
        ->assertDontSee($app1->name);
});
```

## See also
- [[Roadmap]] (tests per phase)
- [[CLAUDE.md]] (write at least one feature test per feature)
