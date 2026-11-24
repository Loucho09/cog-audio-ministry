<?php

namespace App\Filament\Resources\GalleryImages\Pages;

use App\Filament\Resources\GalleryImages\GalleryImageResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditGalleryImage extends EditRecord
{
    protected static string $resource = GalleryImageResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Bundled "images/..." paths are not on the public disk, so the upload field
        // starts empty. Keep the old path unless a new file was uploaded.
        if (blank($data['path'] ?? null)) {
            unset($data['path']);
        }

        return GalleryImageResource::forceBranch($data);
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
