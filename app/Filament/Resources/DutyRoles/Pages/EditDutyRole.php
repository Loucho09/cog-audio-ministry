<?php

namespace App\Filament\Resources\DutyRoles\Pages;

use App\Filament\Resources\DutyRoles\DutyRoleResource;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

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
            DeleteAction::make()->before(function (DeleteAction $action, Model $record) {
                if ($reason = DutyRoleResource::deleteBlocker($record)) {
                    Notification::make()->danger()->title('Cannot delete')->body($reason)->send();
                    $action->cancel();
                }
            }),
        ];
    }
}
