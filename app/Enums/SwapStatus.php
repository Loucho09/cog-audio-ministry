<?php

namespace App\Enums;

enum SwapStatus: string
{
    case Open = 'open';
    case Claimed = 'claimed';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Open => 'Open',
            self::Claimed => 'Claimed',
            self::Approved => 'Approved',
            self::Rejected => 'Rejected',
            self::Cancelled => 'Cancelled',
        };
    }
}
