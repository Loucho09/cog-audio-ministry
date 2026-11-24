<?php

namespace App\Filament\Resources\Users;

use App\Actions\AssignRole;
use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Models\Branch;
use App\Models\User;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

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
                    ->unique(ignoreRecord: true),
                Select::make('branch_id')
                    ->label('Branch')
                    ->options(fn () => Branch::query()->orderBy('name')->pluck('name', 'id'))
                    ->searchable()
                    ->visible(fn () => self::isSuperAdmin()),
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
