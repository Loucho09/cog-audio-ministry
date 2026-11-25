<?php

namespace App\Filament\Support;

use Filament\Schemas\Components\Text;

/** Feedback for staff whose gallery/team changes wait for super-admin approval (see PublishGuard). */
class ApprovalNotice
{
    public const NOTE = 'New and changed items appear on the homepage after the super-admin approves them.';

    public const SAVED_TITLE = 'Saved. Waiting for approval.';

    public static function isSuperAdmin(): bool
    {
        return (bool) auth()->user()?->hasRole('super-admin');
    }

    /** Form note, shown to everyone except the super-admin. */
    public static function note(): Text
    {
        return Text::make(self::NOTE)
            ->columnSpanFull()
            ->color('warning')
            ->visible(fn () => ! self::isSuperAdmin());
    }

    /** Title override for the create/save notification; null keeps Filament's default. */
    public static function savedTitle(): ?string
    {
        return self::isSuperAdmin() ? null : self::SAVED_TITLE;
    }
}
