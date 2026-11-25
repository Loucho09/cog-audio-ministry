<?php

use App\Enums\DutyStatus;
use App\Models\DutyAssignment;

test('guest assignment saves and reads back', function () {
    $row = DutyAssignment::factory()->guest()->create(['guest_name' => 'Juan Dela Cruz']);

    $fresh = DutyAssignment::find($row->id);

    expect($fresh->user_id)->toBeNull()
        ->and($fresh->isGuest())->toBeTrue()
        ->and($fresh->displayName)->toBe('Juan Dela Cruz (Guest)')
        ->and($fresh->status)->toBe(DutyStatus::Accepted)
        ->and($fresh->responded_at)->not->toBeNull();
});

test('member assignment is not a guest', function () {
    $row = DutyAssignment::factory()->create();

    expect($row->guest_name)->toBeNull()
        ->and($row->isGuest())->toBeFalse()
        ->and($row->displayName)->toBe($row->user->name)
        ->and($row->status)->toBe(DutyStatus::Assigned);
});
