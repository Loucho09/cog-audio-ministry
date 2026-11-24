<?php

namespace App\Filament\Resources\Users;

use App\Actions\AssignRole;
use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Models\Branch;
use App\Models\User;
use App\Notifications\WelcomeSetPassword;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\Validation\ValidationException;
use Throwable;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static ?string $navigationLabel = 'Users';

    protected static ?string $modelLabel = 'user';

    /** Rows are limited in the query so list, edit and URL access are all branch scoped. */
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->with(['branch', 'roles']);
        $user = auth()->user();

        if ($user?->hasRole('super-admin')) {
            return $query;
        }

        if ($user?->branch_id === null) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where('branch_id', $user->branch_id);
    }

    public static function isSuperAdmin(): bool
    {
        return (bool) auth()->user()?->hasRole('super-admin');
    }

    /**
     * Super-admin only (the policy would also let a branch-head delete members; the
     * approved rule is super-admin only). Refuses self and the last super-admin.
     */
    public static function deleteUser(User $record): bool
    {
        $actor = auth()->user();

        if (! $actor?->hasRole('super-admin') || $actor->is($record)) {
            return false;
        }

        return DB::transaction(function () use ($record): bool {
            $superAdmins = User::role('super-admin')->lockForUpdate()->get();

            if ($superAdmins->contains('id', $record->id) && $superAdmins->count() <= 1) {
                return false;
            }

            return (bool) $record->delete();
        });
    }

    /** Sends the set-password link and notifies the result. Never throws. */
    public static function sendSetPasswordLink(User $record): bool
    {
        try {
            // Welcome link: 3-day 'invites' broker, sent as our own queued notification
            // (the broker would send the normal reset email). Throttled like sendResetLink.
            $broker = Password::broker('invites');
            $user = $broker->getUser(['email' => $record->email]);

            if ($user === null) {
                $status = Password::INVALID_USER;
            } elseif ($broker->getRepository()->recentlyCreatedToken($user)) {
                $status = Password::RESET_THROTTLED;
            } else {
                $user->notify(new WelcomeSetPassword($broker->createToken($user)));
                $status = Password::RESET_LINK_SENT;
            }
        } catch (Throwable $e) {
            report($e);
            $status = null;
        }

        if ($status === Password::RESET_LINK_SENT) {
            Notification::make()->success()->title('Set-password email sent')->send();

            return true;
        }

        Notification::make()->warning()
            ->title($status === Password::RESET_THROTTLED
                ? 'Please wait a moment before sending another email'
                : 'The set-password email could not be sent')
            ->send();

        return false;
    }

    public static function resendAction(): Action
    {
        return Action::make('resendSetPassword')
            ->label('Resend set-password email')
            ->icon(Heroicon::OutlinedEnvelope)
            ->requiresConfirmation()
            ->visible(fn () => self::isSuperAdmin())
            ->action(function (User $record): void {
                if (! self::isSuperAdmin()) {
                    abort(403);
                }

                self::sendSetPasswordLink($record);
            });
    }

    /** Policy check plus the rule that nobody (super-admin included) may use it on themselves. */
    public static function canSetTemporaryPassword(?User $record): bool
    {
        $actor = auth()->user();

        return $actor !== null
            && $record !== null
            && ! $actor->is($record)
            && Gate::forUser($actor)->allows('setTemporaryPassword', $record);
    }

    public static function temporaryPasswordAction(): Action
    {
        return Action::make('setTemporaryPassword')
            ->label('Set temporary password')
            ->icon(Heroicon::OutlinedKey)
            ->modalHeading('Set temporary password')
            ->modalDescription('The user must choose a new password at their next login. Their current sessions are signed out.')
            ->schema([
                TextInput::make('password')
                    ->label('Temporary password')
                    ->password()
                    ->revealable()
                    ->required()
                    ->rule(PasswordRule::defaults())
                    ->autocomplete('new-password'),
            ])
            ->visible(fn (?User $record) => self::canSetTemporaryPassword($record))
            ->action(function (User $record, array $data): void {
                if (! self::canSetTemporaryPassword($record)) {
                    abort(403);
                }

                $record->forceFill([
                    'password' => $data['password'],
                    'must_change_password' => true,
                    // The admin vouches for the address; otherwise /dashboard (verified) would trap the user.
                    'email_verified_at' => $record->email_verified_at ?? now(),
                    'remember_token' => Str::random(60),
                ])->save();

                if (config('session.driver') === 'database') {
                    DB::table(config('session.table', 'sessions'))->where('user_id', $record->id)->delete();
                }

                Notification::make()->success()->title('Temporary password set')->send();
            });
    }

    /**
     * Maps AssignRole's validation errors onto the Filament form fields.
     */
    public static function formErrors(ValidationException $e): ValidationException
    {
        return ValidationException::withMessages(
            collect($e->errors())->mapWithKeys(fn ($m, $k) => ["data.$k" => $m])->all()
        );
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')->required()->maxLength(255),
                TextInput::make('email')
                    ->email()
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true)
                    ->disabled(fn () => ! self::isSuperAdmin()),
                Select::make('branch_id')
                    ->label('Branch')
                    ->options(fn () => Branch::query()->orderBy('name')->pluck('name', 'id'))
                    ->searchable()
                    ->rules(['nullable', 'exists:branches,id'])
                    ->visible(fn () => self::isSuperAdmin()),
                Radio::make('password_mode')
                    ->label('Password')
                    ->options([
                        'email' => 'Send set-password email',
                        'temporary' => 'Set a temporary password',
                    ])
                    ->default('email')
                    ->live()
                    ->visible(fn (string $operation) => $operation === 'create' && self::isSuperAdmin()),
                TextInput::make('password')
                    ->label('Temporary password')
                    ->password()
                    ->revealable()
                    ->autocomplete('new-password')
                    ->required(fn (Get $get) => $get('password_mode') === 'temporary')
                    ->rule(PasswordRule::defaults())
                    ->visible(fn (Get $get, string $operation) => $operation === 'create'
                        && self::isSuperAdmin()
                        && $get('password_mode') === 'temporary'),
                Select::make('role')
                    ->options(array_combine(AssignRole::ROLES, AssignRole::ROLES))
                    ->required()
                    ->default('member')
                    ->afterStateHydrated(function (Select $component, ?User $record): void {
                        if ($record) {
                            $component->state($record->getRoleNames()->first() ?? 'member');
                        }
                    })
                    ->visible(fn () => self::isSuperAdmin()),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('email')->searchable()->sortable(),
                TextColumn::make('branch.name')->label('Branch')->placeholder('-'),
                TextColumn::make('roles.name')->label('Role')->badge()->placeholder('-'),
                TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->defaultSort('name')
            ->filters([
                SelectFilter::make('branch_id')
                    ->label('Branch')
                    ->options(fn () => Branch::query()->orderBy('name')->pluck('name', 'id'))
                    ->visible(fn () => self::isSuperAdmin()),
                SelectFilter::make('role')
                    ->options(array_combine(AssignRole::ROLES, AssignRole::ROLES))
                    ->query(fn (Builder $query, array $data) => filled($data['value'] ?? null)
                        ? $query->whereHas('roles', fn (Builder $q) => $q->where('name', $data['value']))
                        : $query),
            ])
            ->recordActions([
                EditAction::make(),
                self::temporaryPasswordAction(),
                self::resendAction(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->authorizeIndividualRecords('delete')
                        ->visible(fn () => self::isSuperAdmin())
                        ->using(function (Collection $records, DeleteBulkAction $action): void {
                            // Self and the last super-admin are skipped and reported as failures.
                            foreach ($records as $record) {
                                if (! self::deleteUser($record)) {
                                    $action->reportBulkProcessingFailure();
                                }
                            }
                        }),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUsers::route('/'),
            'create' => CreateUser::route('/create'),
            'edit' => EditUser::route('/{record}/edit'),
        ];
    }
}
