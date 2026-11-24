<?php

namespace App\Support;

class Directions
{
    /** Google Maps directions URL for the church address, or null when no address is set. */
    public static function url(): ?string
    {
        $address = trim((string) config('ministry.address'));

        if ($address === '') {
            return null;
        }

        return 'https://www.google.com/maps/dir/?api=1&destination='.rawurlencode($address);
    }
}
