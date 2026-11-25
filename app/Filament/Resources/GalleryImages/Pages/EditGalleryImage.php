<?php

namespace App\Filament\Resources\GalleryImages\Pages;

use App\Filament\Resources\GalleryImages\GalleryImageResource;
use App\Filament\Resources\GalleryImages\Pages\Concerns\ReturnsToGalleryList;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditGalleryImage extends EditRecord
{
    use ReturnsToGalleryList;

    protected static string $resource = GalleryImageResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Bundled "images/..." paths are not on the public disk, so the upload field
        // starts empty. Keep the old path unless a new file was uploaded.
        if (blank($data['path'] ?? null)) {
            unset($data['path']);
        }

        return GalleryImageResource::guardPublishing(GalleryImageResource::forceBranch($data), $this->record);
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
