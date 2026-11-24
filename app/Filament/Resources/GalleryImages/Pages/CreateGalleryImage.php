<?php

namespace App\Filament\Resources\GalleryImages\Pages;

use App\Filament\Resources\GalleryImages\GalleryImageResource;
use Filament\Resources\Pages\CreateRecord;

class CreateGalleryImage extends CreateRecord
{
    protected static string $resource = GalleryImageResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return GalleryImageResource::forceBranch($data);
    }
}
