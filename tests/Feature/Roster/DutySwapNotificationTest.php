<?php

use App\Exceptions\RosterRuleViolation;
use App\Models\Branch;
use App\Models\User;
use App\Notifications\DutyAssigned;
use App\Notifications\DutySwapUpdated;
use Illuminate\Contracts\Notifications\Dispatcher;
use Illuminate\Support\Facades\Notification;

function sentType(User $user, string $type): bool
{
    return Notification::sent($user, DutySwapUpdated::class)->contains(fn ($n) => $n->type === $type);
}

test('open offer notifies nobody', function () {
    Notification::fake();
    $branch = Branch::factory()->create();
    User::factory()->branchAdmin($branch)->create();

    openSwap();

    Notification::assertNothingSent();
});

test('claim notifies branch admins, super-admin and offerer only', function () {
    [$swap, $duty] = openSwap();
    $branch = $duty->event->branch;
    $admin = User::factory()->branchAdmin($branch)->create();
    $super = User::factory()->superAdmin()->create();
    $otherAdmin = User::factory()->branchAdmin(Branch::factory()->create())->create();
    $bystander = rosterMember($duty->event);
    $claimer = rosterMember($duty->event);
    Notification::fake();

    swapSvc()->claim($swap, $claimer);

    expect(sentType($admin, 'swap_claimed'))->toBeTrue()
        ->and(sentType($super, 'swap_claimed'))->toBeTrue()
        ->and(sentType($duty->user, 'swap_claimed'))->toBeTrue();
    Notification::assertNotSentTo([$claimer, $otherAdmin, $bystander], DutySwapUpdated::class);
    Notification::assertSentTimes(DutySwapUpdated::class, 3);
});

test('claim message and link differ for admin and offerer', function () {
    [$swap, $duty] = openSwap();
    $admin = User::factory()->branchAdmin($duty->event->branch)->create();
    $claimer = rosterMember($duty->event);
    swapSvc()->claim($swap, $claimer);
    $note = new DutySwapUpdated($swap->fresh(), 'swap_claimed');

    $forAdmin = $note->toArray($admin);
    $forOfferer = $note->toArray($duty->user);

    expect($forAdmin['type'])->toBe('swap_claimed')
        ->and($forAdmin['message'])->toContain($claimer->name)->toContain($duty->user->name)
        ->and($forAdmin['url'])->toContain('/admin')
        ->and($forOfferer['url'])->toBe(route('dashboard'))
        ->and($forOfferer['message'])->toContain($claimer->name)
        ->and($forOfferer)->toHaveKeys(['swap_id', 'role', 'event_title', 'starts_at', 'status', 'message']);
});

test('approve notifies offerer and claimer only', function () {
    [$swap, $duty, $claimer] = claimedSwap();
    $admin = branchAdminFor($duty);
    $other = User::factory()->branchAdmin($duty->event->branch)->create();
    Notification::fake();

    swapSvc()->approve($swap, $admin);

    expect(sentType($duty->user, 'swap_approved'))->toBeTrue()
        ->and(sentType($claimer, 'swap_approved'))->toBeTrue();
    Notification::assertNotSentTo([$admin, $other], DutySwapUpdated::class);
    Notification::assertNotSentTo($claimer, DutyAssigned::class);
    Notification::assertSentTimes(DutySwapUpdated::class, 2);
});

test('reject notifies offerer and claimer only', function () {
    [$swap, $duty, $claimer] = claimedSwap();
    $admin = branchAdminFor($duty);
    Notification::fake();

    swapSvc()->reject($swap, $admin);

    expect(sentType($duty->user, 'swap_rejected'))->toBeTrue()
        ->and(sentType($claimer, 'swap_rejected'))->toBeTrue();
    Notification::assertNotSentTo($admin, DutySwapUpdated::class);
    Notification::assertSentTimes(DutySwapUpdated::class, 2);
});

test('rejecting an open swap notifies only the offerer', function () {
    [$swap, $duty] = openSwap();
    $admin = branchAdminFor($duty);
    Notification::fake();

    swapSvc()->reject($swap, $admin);

    Notification::assertSentTo($duty->user, DutySwapUpdated::class);
    Notification::assertSentTimes(DutySwapUpdated::class, 1);
});

test('a failed action sends nothing', function () {
    [$swap, $duty] = openSwap();
    Notification::fake();

    expect(fn () => swapSvc()->claim($swap, $duty->user))->toThrow(RosterRuleViolation::class);

    Notification::assertNothingSent();
});

test('email off means database only, default means mail too', function () {
    [$swap, $duty] = openSwap();
    $duty->user->forceFill(['email_notifications' => false])->save();
    $admin = User::factory()->branchAdmin($duty->event->branch)->create();
    $claimer = rosterMember($duty->event);
    Notification::fake();

    swapSvc()->claim($swap, $claimer);

    Notification::assertSentTo($duty->user, DutySwapUpdated::class, fn ($n, $ch) => $ch === ['database']);
    Notification::assertSentTo($admin, DutySwapUpdated::class, fn ($n, $ch) => $ch === ['database', 'mail']);
});

test('notification failure does not fail claim, approve or reject', function () {
    [$swap, $duty] = openSwap();
    $admin = branchAdminFor($duty);
    $claimer = rosterMember($duty->event);
    app()->instance(Dispatcher::class, new class
    {
        public function __call($name, $args)
        {
            throw new RuntimeException('boom');
        }
    });

    swapSvc()->claim($swap, $claimer);
    expect($swap->fresh()->status->value)->toBe('claimed');

    swapSvc()->approve($swap->fresh(), $admin);
    expect($swap->fresh()->status->value)->toBe('approved');

    [$swap2] = openSwap();
    swapSvc()->reject($swap2, branchAdminFor($swap2->assignment));
    expect($swap2->fresh()->status->value)->toBe('rejected');
});

test('mail escapes html in names and titles', function () {
    [$swap, $duty, $claimer] = claimedSwap();
    $duty->event->update(['name' => '<script>alert(1)</script>']);
    $claimer->update(['name' => '<b>Evil</b>']);
    $admin = branchAdminFor($duty);

    $html = (string) (new DutySwapUpdated($swap->fresh(), 'swap_claimed'))->toMail($admin)->render();

    expect($html)->not->toContain('<script>alert(1)</script>')
        ->and($html)->not->toContain('<b>Evil</b>')
        ->and($html)->toContain('&lt;script&gt;');
});
