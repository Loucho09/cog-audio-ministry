<?php

namespace App\Filament\Resources\DutyRoles\Pages;

use App\Filament\Resources\DutyRoles\DutyRoleResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListDutyRoles extends ListRecords
{
    protected static string $resource = DutyRoleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
