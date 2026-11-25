<?php

use App\Models\Branch;
use App\Models\Service;
use App\Models\User;

beforeEach(function () {
    $this->dasma = Branch::factory()->create();
    $this->trece = Branch::factory()->create();
    $this->dasmaService = Service::factory()->create(['branch_id' => $this->dasma->id]);
    $this->treceService = Service::factory()->create(['branch_id' => $this->trece->id]);
});

it('stops a Trece branch-admin from viewing or changing Dasma services', function () {
    $head = User::factory()->branchAdmin($this->trece)->create();

    expect($head->can('view', $this->dasmaService))->toBeFalse()
        ->and($head->can('update', $this->dasmaService))->toBeFalse()
        ->and($head->can('delete', $this->dasmaService))->toBeFalse()
        ->and($head->can('update', $this->treceService))->toBeTrue();
});

it('lets a branch-admin manage own branch services but not another branch', function () {
    $admin = User::factory()->branchAdmin($this->trece)->create();

    expect($admin->can('view', $this->treceService))->toBeTrue()
        ->and($admin->can('update', $this->treceService))->toBeTrue()
        ->and($admin->can('view', $this->dasmaService))->toBeFalse();
});

it('lets a super-admin manage any branch', function () {
    $super = User::factory()->superAdmin()->create();

    expect($super->can('update', $this->dasmaService))->toBeTrue();
});

it('scopes visibleTo by branch', function () {
    $head = User::factory()->branchAdmin($this->trece)->create();
    $super = User::factory()->superAdmin()->create();
    $member = User::factory()->create();

    expect(Service::visibleTo($head)->pluck('id')->all())->toBe([$this->treceService->id])
        ->and(Service::visibleTo($super)->count())->toBe(2)
        ->and(Service::visibleTo($member)->count())->toBe(0)
        ->and(Service::visibleTo(null)->count())->toBe(0);
});

it('applies the visibleTo scope to a user without a branch', function () {
    $head = User::factory()->branchAdmin($this->trece)->create(['branch_id' => null]);

    expect(Service::visibleTo($head)->count())->toBe(0);
});

it('limits UserPolicy to plain members of the same branch', function () {
    $head = User::factory()->branchAdmin($this->trece)->create();
    $ownMember = User::factory()->member($this->trece)->create();
    $otherMember = User::factory()->member($this->dasma)->create();
    $ownAdmin = User::factory()->branchAdmin($this->trece)->create();
    $super = User::factory()->superAdmin()->create();

    expect($head->can('update', $ownMember))->toBeTrue()
        ->and($head->can('update', $otherMember))->toBeFalse()
        ->and($head->can('update', $ownAdmin))->toBeFalse()
        ->and($head->can('update', $super))->toBeFalse()
        ->and($head->can('update', $head))->toBeFalse()
        ->and($head->can('assignRole', $ownMember))->toBeFalse()
        ->and($super->can('assignRole', $ownMember))->toBeTrue();
});
