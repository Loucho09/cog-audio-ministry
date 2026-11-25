<?php

namespace App\Filament\Resources\Events\Pages;

use App\Filament\Resources\Events\EventResource;
use Filament\Resources\Pages\ViewRecord;

/** Read-only page so leaders can see who is (or was) on duty, even after the event started. */
class ViewEvent extends ViewRecord
{
    protected static string $resource = EventResource::class;

    /** Slots are not a Filament relationship here, so fill them by hand for display. */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['dutySlots'] = $this->getRecord()->dutySlots()
            ->get(['duty_role_id', 'quantity'])
            ->map(fn ($slot) => ['duty_role_id' => $slot->duty_role_id, 'quantity' => $slot->quantity])
            ->all();

        return $data;
    }
}
