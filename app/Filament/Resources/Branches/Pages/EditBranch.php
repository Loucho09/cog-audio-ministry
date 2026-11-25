<?php

namespace App\Filament\Resources\Branches\Pages;

use App\Filament\Concerns\ReturnsToResourceList;
use App\Filament\Resources\Branches\BranchResource;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditBranch extends EditRecord
{
    use ReturnsToResourceList;

    protected static string $resource = BranchResource::class;

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return BranchResource::persist($record, $data);
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()->before(function (DeleteAction $action, Model $record) {
                if ($reason = BranchResource::deleteBlocker($record)) {
                    Notification::make()->danger()->title('Cannot delete')->body($reason)->send();
                    $action->cancel();
                }
            }),
        ];
    }
}
