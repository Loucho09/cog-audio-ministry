<?php

use App\Enums\SwapStatus;
use App\Models\DutySwap;
use Database\Seeders\DatabaseSeeder;

it('seeds demo open and claimed swaps', function () {
    $this->seed(DatabaseSeeder::class);

    expect(DutySwap::where('status', SwapStatus::Open)->count())->toBeGreaterThan(0)
        ->and(DutySwap::where('status', SwapStatus::Claimed)->count())->toBeGreaterThan(0);
});
