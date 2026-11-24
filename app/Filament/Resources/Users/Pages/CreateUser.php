<?php

namespace App\Filament\Resources\Users\Pages;

use App\Actions\AssignRole;
use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    /**
     * Branch and role never go through mass assignment: branch is set explicitly and
     * the role goes through AssignRole, all in one transaction.
     */
    protected function handleRecordCreation(array $data): Model
    {
        try {
            return DB::transaction(function () use ($data): User {
                $user = new User(Arr::only($data, ['name', 'email']));
                // Random and never shown; the user sets their own via the reset link.
                $user->password = Str::password(40);
                $user->branch_id = $data['branch_id'] ?? null;
                $user->save();

                app(AssignRole::class)->handle(auth()->user(), $user, $data['role'] ?? 'member');

                return $user;
            });
        } catch (ValidationException $e) {
            throw UserResource::formErrors($e);
        } catch (AuthorizationException $e) {
            Notification::make()->danger()->title('Not allowed')->body($e->getMessage())->send();
            $this->halt();
        }
    }

    protected function afterCreate(): void
    {
        $status = Password::broker()->sendResetLink(['email' => $this->record->email]);

        if ($status === Password::RESET_LINK_SENT) {
            Notification::make()->success()->title('Set-password email sent')->send();
        } else {
            Notification::make()->warning()->title('User created, but the set-password email could not be sent')->send();
        }
    }
}
