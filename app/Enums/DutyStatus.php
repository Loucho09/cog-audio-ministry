<?php

namespace App\Enums;

enum DutyStatus: string
{
    case Assigned = 'assigned';
    case Accepted = 'accepted';
    case Declined = 'declined';

    public function label(): string
    {
        return match ($this) {
            self::Assigned => 'Assigned',
            self::Accepted => 'Accepted',
            self::Declined => 'Declined',
        };
    }
}
