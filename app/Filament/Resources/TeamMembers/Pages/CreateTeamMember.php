<?php

namespace App\Filament\Resources\TeamMembers\Pages;

use App\Filament\Concerns\ReturnsToResourceList;
use App\Filament\Resources\TeamMembers\TeamMemberResource;
use App\Filament\Support\ApprovalNotice;
use Filament\Resources\Pages\CreateRecord;

class CreateTeamMember extends CreateRecord
{
    use ReturnsToResourceList;

    protected static string $resource = TeamMemberResource::class;

    protected function getCreatedNotificationTitle(): ?string
    {
        return ApprovalNotice::savedTitle() ?? parent::getCreatedNotificationTitle();
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return TeamMemberResource::guardPublishing(TeamMemberResource::forceBranch($data));
    }
}
