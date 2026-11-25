<?php

namespace App\Filament\Resources\DutyRoles\Pages;

use App\Filament\Concerns\ReturnsToResourceList;
use App\Filament\Resources\DutyRoles\DutyRoleResource;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditDutyRole extends EditRecord
{
    use ReturnsToResourceList;

    protected static string $resource = DutyRoleResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        return DutyRoleResource::forceBranch($data);
    }

    /** Non-blocking: tell the admin when a role they just turned off is still on upcoming events. */
    protected function afterSave(): void
    {
        $role = $this->getRecord();

        if (! $role->wasChanged('is_active') || $role->is_active) {
            return;
        }

        $upcoming = $role->eventSlots()->whereHas('event', fn ($q) => $q->where('starts_at', '>', now()))->count();

        if ($upcoming > 0) {
            Notification::make()->warning()
                ->title("This role is still on {$upcoming} upcoming ".($upcoming === 1 ? 'event' : 'events').'.')
                ->body('Existing slots stay, but it will not appear for new events.')
                ->send();
        }
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
