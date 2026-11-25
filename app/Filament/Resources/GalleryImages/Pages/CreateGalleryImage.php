<?php

namespace App\Filament\Resources\GalleryImages\Pages;

use App\Filament\Resources\GalleryImages\GalleryImageResource;
use App\Filament\Resources\GalleryImages\Pages\Concerns\ReturnsToGalleryList;
use App\Filament\Support\ApprovalNotice;
use Filament\Resources\Pages\CreateRecord;

class CreateGalleryImage extends CreateRecord
{
    use ReturnsToGalleryList;

    protected static string $resource = GalleryImageResource::class;

    protected function getCreatedNotificationTitle(): ?string
    {
        return ApprovalNotice::savedTitle() ?? parent::getCreatedNotificationTitle();
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return GalleryImageResource::guardPublishing(GalleryImageResource::forceBranch($data));
    }
}
