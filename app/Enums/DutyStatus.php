<?php

namespace App\Enums;

enum DutyStatus: string
{
    case Assigned = 'assigned';
    case Accepted = 'accepted';
    case Declined = 'declined';
    case SwapRequested = 'swap_requested';
    case Swapped = 'swapped';

    public function label(): string
    {
        return match ($this) {
            self::Assigned => 'Assigned',
            self::Accepted => 'Accepted',
            self::Declined => 'Declined',
            self::SwapRequested => 'Swap offered',
            self::Swapped => 'Swapped',
        };
    }
}
