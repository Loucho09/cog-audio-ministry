<?php

namespace App\Filament\Resources\DutyRoles\Pages;

use App\Filament\Resources\DutyRoles\DutyRoleResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditDutyRole extends EditRecord
{
    protected static string $resource = DutyRoleResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        return DutyRoleResource::forceBranch($data);
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
