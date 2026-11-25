<?php

namespace App\Filament\Resources\TeamMembers\Pages;

use App\Filament\Resources\TeamMembers\TeamMemberResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;

class ListTeamMembers extends ListRecords
{
    protected static string $resource = TeamMemberResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    /** Quick views. Counts start from the resource query, so they stay branch scoped. */
    public function getTabs(): array
    {
        $featured = fn (Builder $query) => $query->where('team_members.is_featured', true);
        $fullTime = fn (Builder $query) => $query->where('team_members.is_full_time', true);
        $pending = fn (Builder $query) => $query->where('team_members.is_published', false);

        $count = function (?callable $scope = null): int {
            $query = TeamMemberResource::getEloquentQuery();

            if ($scope) {
                $scope($query);
            }

            return $query->count();
        };

        return [
            'all' => Tab::make('All')
                ->icon(Heroicon::OutlinedUserGroup)
                ->badge($count()),
            'featured' => Tab::make('Featured')
                ->icon(Heroicon::OutlinedStar)
                ->badge($count($featured))
                ->modifyQueryUsing($featured),
            'full-time' => Tab::make('Full-time')
                ->icon(Heroicon::OutlinedClock)
                ->badge($count($fullTime))
                ->modifyQueryUsing($fullTime),
            'pending' => Tab::make('Pending approval')
                ->icon(Heroicon::OutlinedExclamationTriangle)
                ->badge($count($pending) ?: null)
                ->badgeColor('warning')
                ->modifyQueryUsing($pending),
        ];
    }
}
