<?php

use App\Models\MemberUnavailability;
use Database\Seeders\DatabaseSeeder;

it('seeds demo availability entries', function () {
    $this->seed(DatabaseSeeder::class);

    expect(MemberUnavailability::count())->toBeGreaterThan(0);
});
