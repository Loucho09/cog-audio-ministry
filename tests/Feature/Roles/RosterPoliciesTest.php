<?php

use App\Models\Branch;
use App\Models\DutyAssignment;
use App\Models\DutyRole;
use App\Models\Event;
use App\Models\User;

beforeEach(function () {
    $this->dasma = Branch::factory()->create();
    $this->trece = Branch::factory()->create();
    $this->role = DutyRole::factory()->make(['branch_id' => $this->dasma->id]);
    $this->event = Event::factory()->make(['branch_id' => $this->dasma->id, 'starts_at' => now()->addDay()]);
    $this->assignment = DutyAssignment::factory()->make(['branch_id' => $this->dasma->id, 'user_id' => 999999]);
});

it('allows own-branch head and admin on all roster records', function (string $state) {
    $user = User::factory()->{$state}($this->dasma)->create();

    foreach ([$this->role, $this->event, $this->assignment] as $record) {
        expect($user->can('viewAny', $record::class))->toBeTrue()
            ->and($user->can('view', $record))->toBeTrue()
            ->and($user->can('create', $record::class))->toBeTrue()
            ->and($user->can('update', $record))->toBeTrue()
            ->and($user->can('delete', $record))->toBeTrue();
    }
})->with(['branchAdmin']);

it('denies other-branch admins on all roster records', function (string $state) {
    $user = User::factory()->{$state}($this->trece)->create();

    foreach ([$this->role, $this->event, $this->assignment] as $record) {
        expect($user->can('view', $record))->toBeFalse()
            ->and($user->can('update', $record))->toBeFalse()
            ->and($user->can('delete', $record))->toBeFalse();
    }
})->with(['branchAdmin']);

it('denies members viewAny and create', function () {
    $member = User::factory()->member($this->dasma)->create();

    foreach ([DutyRole::class, Event::class, DutyAssignment::class] as $class) {
        expect($member->can('viewAny', $class))->toBeFalse()
            ->and($member->can('create', $class))->toBeFalse();
    }
});

it('lets only the owner respond to an assignment', function () {
    $owner = User::factory()->member($this->dasma)->create();
    $other = User::factory()->member($this->dasma)->create();
    $head = User::factory()->branchAdmin($this->dasma)->create();
    $this->assignment->user_id = $owner->id;

    expect($owner->can('respond', $this->assignment))->toBeTrue()
        ->and($other->can('respond', $this->assignment))->toBeFalse()
        ->and($head->can('respond', $this->assignment))->toBeFalse();
});

it('update is false for a started event', function () {
    $head = User::factory()->branchAdmin($this->dasma)->create();
    $this->event->starts_at = now()->subHour();

    expect($head->can('update', $this->event))->toBeFalse();
});

it('delete is false for a started event', function () {
    $head = User::factory()->branchAdmin($this->dasma)->create();
    $this->event->starts_at = now()->subHour();

    expect($head->can('delete', $this->event))->toBeFalse();
});
