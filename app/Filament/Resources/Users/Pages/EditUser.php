<?php

namespace App\Filament\Resources\Users\Pages;

use App\Actions\AssignRole;
use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    /**
     * Only a super-admin may change branch and role (role via AssignRole only).
     * Anyone else is limited to name and email, whatever the request contains.
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var User $record */
        $actor = auth()->user();

        if (! $actor->hasRole('super-admin')) {
            $record->update(Arr::only($data, ['name']));

            return $record;
        }

        try {
            DB::transaction(function () use ($record, $data, $actor): void {
                $record->fill(Arr::only($data, ['name', 'email']));

                if (array_key_exists('branch_id', $data)) {
                    $record->branch_id = $data['branch_id'] ?: null;
                }

                $record->save();

                $role = $data['role'] ?? null;

                if ($role !== null && $role !== $record->getRoleNames()->first()) {
                    app(AssignRole::class)->handle($actor, $record, $role);
                }

                // Branch roles need a branch (AssignRole only checks when the role changes).
                if ($record->branch_id === null && $record->hasAnyRole(['branch-head', 'branch-admin'])) {
                    throw ValidationException::withMessages([
                        'branch_id' => 'A branch-head or branch-admin needs a branch.',
                    ]);
                }
            });
        } catch (ValidationException $e) {
            $record->refresh();

            throw UserResource::formErrors($e);
        } catch (AuthorizationException $e) {
            $record->refresh();
            Notification::make()->danger()->title('Not allowed')->body($e->getMessage())->send();
            $this->halt();
        }

        return $record;
    }

    protected function getHeaderActions(): array
    {
        return [
            UserResource::temporaryPasswordAction(),
            UserResource::resendAction(),
            DeleteAction::make()
                ->visible(fn () => UserResource::isSuperAdmin())
                ->before(function (DeleteAction $action, User $record) {
                    if ($record->dutyAssignments()->exists()) {
                        Notification::make()->danger()->title('Cannot delete')
                            ->body("{$record->name} has duty history, so the account can't be deleted.")->send();
                        $action->cancel();
                    }
                })
                ->using(fn (User $record) => UserResource::deleteUser($record))
                ->failureNotificationTitle('Cannot delete yourself or the last super-admin'),
        ];
    }
}
