<?php

namespace App\Filament\Resources\Events\Pages;

use App\Exceptions\RosterRuleViolation;
use App\Filament\Concerns\ReturnsToResourceList;
use App\Filament\Resources\Events\EventResource;
use App\Models\Event;
use App\Services\DutyRosterService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Exceptions\Halt;
use Illuminate\Database\Eloquent\Model;

class EditEvent extends EditRecord
{
    use ReturnsToResourceList;

    protected static string $resource = EventResource::class;

    /** Slots are not a Filament relationship here; they save through the roster service. */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['dutySlots'] = $this->getRecord()->dutySlots()
            ->get(['duty_role_id', 'quantity'])
            ->map(fn ($slot) => ['duty_role_id' => $slot->duty_role_id, 'quantity' => $slot->quantity])
            ->all();

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var Event $record */
        if ($record->hasStarted()) {
            Notification::make()->danger()->title('This event has already started and can no longer be changed.')->send();

            throw new Halt;
        }

        try {
            return app(DutyRosterService::class)->updateEvent(
                $record,
                EventResource::attributesFrom($data),
                EventResource::quantitiesFrom($data['dutySlots'] ?? []),
            );
        } catch (RosterRuleViolation $e) {
            Notification::make()->danger()->title($e->getMessage())->send();

            throw new Halt;
        }
    }

    protected function getSaveFormAction(): Action
    {
        return parent::getSaveFormAction()->hidden(fn () => EventResource::isLocked($this->getRecord()));
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()->modalDescription('This also removes every duty assigned for this event.')->hidden(fn () => EventResource::isLocked($this->getRecord())),
        ];
    }
}
