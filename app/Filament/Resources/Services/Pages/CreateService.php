<?php

namespace App\Filament\Resources\Services\Pages;

use App\Filament\Concerns\ReturnsToResourceList;
use App\Filament\Resources\Services\ServiceResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\UniqueConstraintViolationException;

class CreateService extends CreateRecord
{
    use ReturnsToResourceList;

    protected static string $resource = ServiceResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        try {
            return parent::handleRecordCreation($data);
        } catch (UniqueConstraintViolationException) {
            ServiceResource::duplicateSlotNotification()->send();

            $this->halt();
        }
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return ServiceResource::forceBranch($data);
    }
}
