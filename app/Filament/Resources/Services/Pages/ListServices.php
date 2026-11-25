<?php

namespace App\Filament\Resources\Services\Pages;

use App\Filament\Resources\Services\ServiceResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;

class ListServices extends ListRecords
{
    protected static string $resource = ServiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    /** Quick views. Counts start from the resource query, so they stay branch scoped. */
    public function getTabs(): array
    {
        $active = fn (Builder $query) => $query->where('services.is_active', true);
        $inactive = fn (Builder $query) => $query->where('services.is_active', false);

        $count = function (?callable $scope = null): int {
            $query = ServiceResource::getEloquentQuery();

            if ($scope) {
                $scope($query);
            }

            return $query->count();
        };

        return [
            'all' => Tab::make('All')
                ->icon(Heroicon::OutlinedRectangleStack)
                ->badge($count()),
            'active' => Tab::make('Active')
                ->icon(Heroicon::OutlinedCheckCircle)
                ->badge($count($active))
                ->modifyQueryUsing($active),
            'inactive' => Tab::make('Inactive')
                ->icon(Heroicon::OutlinedPauseCircle)
                ->badge($count($inactive))
                ->badgeColor('gray')
                ->modifyQueryUsing($inactive),
        ];
    }
}
