<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\GalleryImages\GalleryImageResource;
use App\Filament\Resources\Services\ServiceResource;
use App\Filament\Resources\TeamMembers\TeamMemberResource;
use App\Filament\Resources\Users\UserResource;
use App\Filament\Widgets\Concerns\HasSkeletonPlaceholder;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Counts come from each resource's own getEloquentQuery(), so the numbers
 * always match what the same user sees in the lists (branch scoped on the server).
 */
class BranchStatsWidget extends StatsOverviewWidget
{
    use HasSkeletonPlaceholder;

    protected string $skeletonVariant = 'stats';

    protected static ?int $sort = 1;

    public static function canView(): bool
    {
        return (bool) auth()->user()?->hasAdminAccess();
    }

    public function mount(): void
    {
        abort_unless(static::canView(), 403);
    }

    protected function getHeading(): ?string
    {
        return auth()->user()?->hasRole('super-admin') ? 'All branches' : 'Your branch';
    }

    protected function getStats(): array
    {
        $gallery = GalleryImageResource::getEloquentQuery();
        $team = TeamMemberResource::getEloquentQuery();

        $galleryPublished = (clone $gallery)->where('is_published', true)->count();
        $galleryTotal = (clone $gallery)->count();
        $teamPublished = (clone $team)->where('is_published', true)->count();
        $teamTotal = (clone $team)->count();

        return [
            Stat::make('Accounts', UserResource::getEloquentQuery()->count()),
            Stat::make('Services', ServiceResource::getEloquentQuery()->count()),
            Stat::make('Gallery photos', $galleryTotal)
                ->description($galleryPublished.' published, '.($galleryTotal - $galleryPublished).' draft (incl. all-churches photos)'),
            Stat::make('Team members', $teamTotal)
                ->description($teamPublished.' published, '.($teamTotal - $teamPublished).' draft (incl. all-churches members)'),
        ];
    }
}
