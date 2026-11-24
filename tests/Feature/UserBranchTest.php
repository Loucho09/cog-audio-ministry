<?php

use App\Models\Branch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('links users to a branch', function () {
    $branch = Branch::factory()->create();
    $user = User::factory()->member($branch)->create();

    expect($user->branch->is($branch))->toBeTrue()
        ->and($branch->users->contains($user))->toBeTrue()
        ->and($user->hasRole('member'))->toBeTrue();
});

it('ignores branch_id on mass assignment', function () {
    $branch = Branch::factory()->create();

    $user = User::create(['name' => 'X', 'email' => 'x@example.test', 'password' => 'secret-pass', 'branch_id' => $branch->id]);

    expect($user->fresh()->branch_id)->toBeNull();
});
