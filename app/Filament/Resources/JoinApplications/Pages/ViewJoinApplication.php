<?php

namespace App\Filament\Resources\JoinApplications\Pages;

use App\Filament\Resources\JoinApplications\JoinApplicationResource;
use Filament\Resources\Pages\ViewRecord;

class ViewJoinApplication extends ViewRecord
{
    protected static string $resource = JoinApplicationResource::class;

    // Approve and reject actions arrive in Slice 5.
}
