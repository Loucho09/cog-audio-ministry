<?php

namespace App\Filament\Resources\JoinApplications\Pages;

use App\Filament\Resources\JoinApplications\JoinApplicationResource;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Contracts\Support\Htmlable;

class ViewJoinApplication extends ViewRecord
{
    protected static string $resource = JoinApplicationResource::class;

    public function getTitle(): string|Htmlable
    {
        return $this->getRecord()->full_name;
    }

    protected function getHeaderActions(): array
    {
        return [
            JoinApplicationResource::approveAction(),
            JoinApplicationResource::rejectAction(),
        ];
    }
}
