<?php

namespace App\Filament\Support;

use Filament\Infolists\Components\TextEntry;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;

/** Shared "Published / Pending approval" badge for resources that use PublishGuard. */
class PublishStatus
{
    public static function label(bool $published): string
    {
        return $published ? 'Published' : 'Pending approval';
    }

    public static function color(string $state): string
    {
        return $state === 'Published' ? 'success' : 'warning';
    }

    public static function icon(string $state): Heroicon
    {
        return $state === 'Published' ? Heroicon::OutlinedCheckCircle : Heroicon::OutlinedClock;
    }

    public static function column(): TextColumn
    {
        return TextColumn::make('publish_status')
            ->label('Status')
            ->badge()
            ->state(fn ($record) => self::label((bool) $record->is_published))
            ->color(fn (string $state) => self::color($state))
            ->icon(fn (string $state) => self::icon($state));
    }

    public static function entry(): TextEntry
    {
        return TextEntry::make('summary_status')
            ->label('Status')
            ->badge()
            ->state(fn ($record) => self::label((bool) $record->is_published))
            ->color(fn (string $state) => self::color($state))
            ->icon(fn (string $state) => self::icon($state));
    }
}
