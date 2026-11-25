<?php

namespace App\Filament\Resources\GalleryImages\Pages;

use App\Filament\Resources\GalleryImages\GalleryImageResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;

class ListGalleryImages extends ListRecords
{
    protected static string $resource = GalleryImageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    /** Quick views. Counts start from the resource query, so they stay branch scoped. */
    public function getTabs(): array
    {
        $published = fn (Builder $query) => $query->where('gallery_images.is_published', true);
        $pending = fn (Builder $query) => $query->where('gallery_images.is_published', false);

        $count = function (?callable $scope = null): int {
            $query = GalleryImageResource::getEloquentQuery();

            if ($scope) {
                $scope($query);
            }

            return $query->count();
        };

        return [
            'all' => Tab::make('All')
                ->icon(Heroicon::OutlinedPhoto)
                ->badge($count()),
            'published' => Tab::make('Published')
                ->icon(Heroicon::OutlinedCheckCircle)
                ->badge($count($published))
                ->modifyQueryUsing($published),
            'pending' => Tab::make('Pending approval')
                ->icon(Heroicon::OutlinedClock)
                ->badge($count($pending) ?: null)
                ->badgeColor('warning')
                ->modifyQueryUsing($pending),
        ];
    }
}
