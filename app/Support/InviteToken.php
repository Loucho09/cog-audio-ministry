<?php

namespace App\Support;

/**
 * Marks a password token as a 3-day welcome invite.
 *
 * Invite and normal reset tokens share one table, and each broker checks expiry
 * on its own. The link carries a signed marker (HMAC with the app key) so the
 * reset form knows which broker to ask. It cannot be forged, so a normal 60-minute
 * token can never be stretched to 3 days by editing the URL.
 */
class InviteToken
{
    public static function wrap(string $token): string
    {
        return $token.'.'.self::signature($token);
    }

    /** Returns the raw token when the marker is valid, otherwise null. */
    public static function unwrap(string $value): ?string
    {
        $parts = explode('.', $value);

        if (count($parts) !== 2 || $parts[0] === '') {
            return null;
        }

        return hash_equals(self::signature($parts[0]), $parts[1]) ? $parts[0] : null;
    }

    /** Derived key (domain separation): the raw app key is never used directly for invite links. */
    private static function key(): string
    {
        $appKey = (string) config('app.key');
        $bytes = str_starts_with($appKey, 'base64:') ? (base64_decode(substr($appKey, 7), true) ?: $appKey) : $appKey;

        return hash_hmac('sha256', 'invite-v1', $bytes);
    }

    private static function signature(string $token): string
    {
        return substr(hash_hmac('sha256', 'invite|'.$token, self::key()), 0, 32);
    }
}
