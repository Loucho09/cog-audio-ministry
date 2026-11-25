<?php

namespace App\Filament\Resources\Branches\Pages;

use App\Filament\Concerns\ReturnsToResourceList;
use App\Filament\Resources\Branches\BranchResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateBranch extends CreateRecord
{
    use ReturnsToResourceList;

    protected static string $resource = BranchResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        return BranchResource::persist(null, $data);
    }
}
