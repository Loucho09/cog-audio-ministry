<?php

namespace App\Filament\Resources\DutyRoles\Pages;

use App\Filament\Concerns\ReturnsToResourceList;
use App\Filament\Resources\DutyRoles\DutyRoleResource;
use Filament\Resources\Pages\CreateRecord;

class CreateDutyRole extends CreateRecord
{
    use ReturnsToResourceList;

    protected static string $resource = DutyRoleResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return DutyRoleResource::forceBranch($data);
    }
}
