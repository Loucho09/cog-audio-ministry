<?php

namespace App\Filament\Resources\Services\Pages;

use App\Filament\Concerns\ReturnsToResourceList;
use App\Filament\Resources\Services\ServiceResource;
use Filament\Resources\Pages\CreateRecord;

class CreateService extends CreateRecord
{
    use ReturnsToResourceList;

    protected static string $resource = ServiceResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return ServiceResource::forceBranch($data);
    }
}
