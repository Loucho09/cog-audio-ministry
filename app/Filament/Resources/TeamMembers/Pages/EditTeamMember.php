<?php

namespace App\Filament\Resources\TeamMembers\Pages;

use App\Filament\Resources\TeamMembers\TeamMemberResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditTeamMember extends EditRecord
{
    protected static string $resource = TeamMemberResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Bundled "images/..." paths are not on the public disk, so the upload field
        // starts empty. Keep the old path unless a new file was uploaded.
        if (blank($data['photo_path'] ?? null)) {
            unset($data['photo_path']);
        }

        return TeamMemberResource::forceBranch($data);
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
