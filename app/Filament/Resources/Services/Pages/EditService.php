<?php

namespace App\Filament\Resources\Services\Pages;

use App\Filament\Concerns\ReturnsToResourceList;
use App\Filament\Resources\Services\ServiceResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\UniqueConstraintViolationException;

class EditService extends EditRecord
{
    use ReturnsToResourceList;

    protected static string $resource = ServiceResource::class;

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        try {
            return parent::handleRecordUpdate($record, $data);
        } catch (UniqueConstraintViolationException) {
            ServiceResource::duplicateSlotNotification()->send();

            $this->halt();
        }
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        return ServiceResource::forceBranch($data);
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()->modalDescription(ServiceResource::DELETE_NOTE),
        ];
    }
}
