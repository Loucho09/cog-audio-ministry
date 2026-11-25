<?php

namespace App\Filament\Resources\Events\Pages;

use App\Filament\Resources\Events\EventResource;
use App\Models\Event;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class CreateEvent extends CreateRecord
{
    protected static string $resource = EventResource::class;

    /** Duties are assigned on the event page, so go there after creating. */
    protected function getRedirectUrl(): string
    {
        return EventResource::getUrl('view', ['record' => $this->record]);
    }

    protected function handleRecordCreation(array $data): Model
    {
        $data = EventResource::forceBranch($data);
        $quantities = EventResource::quantitiesFrom($data['dutySlots'] ?? []);

        return DB::transaction(function () use ($data, $quantities) {
            $event = Event::create(Arr::except($data, ['dutySlots']));

            foreach ($quantities as $roleId => $quantity) {
                $event->dutySlots()->create(['duty_role_id' => $roleId, 'quantity' => $quantity]);
            }

            return $event;
        });
    }
}
