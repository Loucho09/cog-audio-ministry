<?php

namespace App\Filament\Resources\Services;

use App\Filament\Resources\Services\Pages\CreateService;
use App\Filament\Resources\Services\Pages\EditService;
use App\Filament\Resources\Services\Pages\ListServices;
use App\Models\Branch;
use App\Models\Service;
use BackedEnum;
use Closure;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ServiceResource extends Resource
{
    protected static ?string $model = Service::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $navigationLabel = 'Services';

    protected static ?string $modelLabel = 'service';

    public const DAYS = [
        0 => 'Sunday', 1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday',
        4 => 'Thursday', 5 => 'Friday', 6 => 'Saturday',
    ];

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

    private static function isSuperAdmin(): bool
    {
        return (bool) auth()->user()?->hasRole('super-admin');
    }

    /** Rejects a second service on the same branch + day + time. */
    private static function slotRule(Get $get, ?Model $record): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) use ($get, $record): void {
            $branchId = self::isSuperAdmin() ? $get('branch_id') : auth()->user()?->branch_id;
            $day = $get('day_of_week');

            if ($branchId === null || $day === null || blank($value)) {
                return;
            }

            $exists = Service::query()
                ->where('branch_id', $branchId)
                ->where('day_of_week', $day)
                ->where('start_time', 'like', substr((string) $value, 0, 5).'%')
                ->when($record, fn (Builder $q) => $q->whereKeyNot($record->getKey()))
                ->exists();

            if ($exists) {
                $fail('This branch already has a service on that day and time.');
            }
        };
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('branch_id')
                    ->label('Branch')
                    ->options(fn () => Branch::query()->orderBy('name')->pluck('name', 'id'))
                    ->required()
                    ->searchable()
                    ->live()
                    ->visible(fn () => self::isSuperAdmin()),
                TextInput::make('name')->required()->maxLength(255),
                Select::make('day_of_week')
                    ->label('Day')
                    ->options(self::DAYS)
                    ->required()
                    ->live(),
                TimePicker::make('start_time')
                    ->label('Start time')
                    ->seconds(false)
                    ->required()
                    ->live()
                    ->rules([fn (Get $get, ?Model $record) => self::slotRule($get, $record)]),
                DatePicker::make('starts_on')->label('Starts on')->native(false),
                DatePicker::make('ends_on')
                    ->label('Ends on')
                    ->native(false)
                    ->afterOrEqual('starts_on'),
                TextInput::make('sort_order')->numeric()->minValue(0),
                Toggle::make('is_active')->label('Active')->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('branch.name')->label('Branch')->sortable(),
                TextColumn::make('day_of_week')
                    ->label('Day')
                    ->formatStateUsing(fn ($state) => self::DAYS[(int) $state] ?? $state)
                    ->sortable(),
                TextColumn::make('start_time')->label('Time')->time('g:i A')->sortable(),
                TextColumn::make('starts_on')->label('Starts')->date()->placeholder('-')->sortable(),
                TextColumn::make('ends_on')->label('Ends')->date()->placeholder('-')->sortable(),
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
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
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
            'index' => ListServices::route('/'),
            'create' => CreateService::route('/create'),
            'edit' => EditService::route('/{record}/edit'),
        ];
    }
}
