<?php

namespace App\Filament\Resources\DutyRoles;

use App\Filament\Resources\DutyRoles\Pages\CreateDutyRole;
use App\Filament\Resources\DutyRoles\Pages\EditDutyRole;
use App\Filament\Resources\DutyRoles\Pages\ListDutyRoles;
use App\Models\Branch;
use App\Models\DutyRole;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rules\Unique;
use UnitEnum;

class DutyRoleResource extends Resource
{
    protected static ?string $model = DutyRole::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static string|UnitEnum|null $navigationGroup = 'Roster';

    protected static ?string $navigationLabel = 'Duty roles';

    protected static ?string $modelLabel = 'duty role';

    /** Rows are limited in the query so list, edit and URL access are all branch scoped. */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->visibleTo(auth()->user());
    }

    /** Non-super-admins always save into their own branch, whatever the request says. */
    public static function forceBranch(array $data): array
    {
        if (! self::isSuperAdmin()) {
            $data['branch_id'] = auth()->user()->branch_id;
        }

        return $data;
    }

    /** Returns a reason why the role cannot be deleted, or null when it can. */
    public static function deleteBlocker(DutyRole $role): ?string
    {
        return $role->isInUse() ? 'This role is in use. Deactivate it instead.' : null;
    }

    private static function isSuperAdmin(): bool
    {
        return (bool) auth()->user()?->hasRole('super-admin');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->required()
                ->maxLength(255)
                ->unique(
                    ignoreRecord: true,
                    modifyRuleUsing: fn (Unique $rule, Get $get) => $rule->where(
                        'branch_id',
                        self::isSuperAdmin() ? $get('branch_id') : auth()->user()?->branch_id,
                    ),
                ),
            Select::make('branch_id')
                ->label('Branch')
                ->options(fn () => Branch::query()->orderBy('name')->pluck('name', 'id'))
                ->required()
                ->searchable()
                ->live()
                ->disabled(fn (string $operation) => $operation === 'edit')
                ->dehydrated(fn (string $operation) => $operation !== 'edit')
                ->visible(fn () => self::isSuperAdmin()),
            TextInput::make('sort_order')
                ->numeric()
                ->integer()
                ->minValue(0)
                ->maxValue(65535)
                ->default(0),
            Toggle::make('is_active')->label('Active')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->withCount('eventSlots as used_on_count'))
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('branch.name')->label('Branch')->sortable()->visible(fn () => self::isSuperAdmin()),
                TextColumn::make('sort_order')->label('Order')->sortable(),
                TextColumn::make('used_on_count')->label('Used on')->suffix(' events')->sortable(),
                IconColumn::make('is_active')->label('Active')->boolean(),
            ])
            ->defaultSort('sort_order')
            ->filters([
                SelectFilter::make('branch_id')
                    ->label('Branch')
                    ->options(fn () => Branch::query()->orderBy('name')->pluck('name', 'id'))
                    ->visible(fn () => self::isSuperAdmin()),
            ])
            ->recordActions([
                EditAction::make()->iconButton()->tooltip('Edit'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->authorizeIndividualRecords('delete')
                        ->using(function (Collection $records, DeleteBulkAction $action): void {
                            // Roles still in use are kept and reported as failures.
                            $kept = 0;

                            foreach ($records as $record) {
                                if (self::deleteBlocker($record) !== null) {
                                    $action->reportBulkProcessingFailure();
                                    $kept++;

                                    continue;
                                }
                                $record->delete();
                            }

                            if ($kept > 0) {
                                Notification::make()->warning()
                                    ->title($kept.($kept === 1 ? ' role was' : ' roles were').' kept because '.($kept === 1 ? 'it is' : 'they are').' used on events.')
                                    ->body('Turn off Active instead.')
                                    ->send();
                            }
                        }),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDutyRoles::route('/'),
            'create' => CreateDutyRole::route('/create'),
            'edit' => EditDutyRole::route('/{record}/edit'),
        ];
    }
}
