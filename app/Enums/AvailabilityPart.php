<?php

namespace App\Enums;

enum AvailabilityPart: string
{
    case WholeDay = 'whole_day';
    case Am = 'am';
    case Pm = 'pm';

    public function label(): string
    {
        return match ($this) {
            self::WholeDay => 'Whole day',
            self::Am => 'AM',
            self::Pm => 'PM',
        };
    }
}
