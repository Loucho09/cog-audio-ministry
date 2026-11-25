<?php

namespace App\Filament\Resources\Events;

use App\Filament\Resources\Events\Pages\CreateEvent;
use App\Filament\Resources\Events\Pages\EditEvent;
use App\Filament\Resources\Events\Pages\ListEvents;
use App\Filament\Resources\Events\RelationManagers\AssignmentsRelationManager;
use App\Models\Branch;
use App\Models\DutyRole;
use App\Models\Event;
use App\Models\Service;
use BackedEnum;
use Carbon\Carbon;
use Closure;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use UnitEnum;

class EventResource extends Resource
{
    protected static ?string $model = Event::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static string|UnitEnum|null $navigationGroup = 'Roster';

    protected static ?string $navigationLabel = 'Events';

    protected static ?string $modelLabel = 'event';

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

    public static function isSuperAdmin(): bool
    {
        return (bool) auth()->user()?->hasRole('super-admin');
    }

    /** Started events are read-only for everyone, super-admin included (Gate::before would otherwise allow it). */
    public static function isLocked(?Event $record): bool
    {
        return $record !== null && $record->hasStarted();
    }

    /** Turns the repeater state into [dutyRoleId => quantity]. */
    public static function quantitiesFrom(array $slots): array
    {
        return collect($slots)
            ->filter(fn ($slot) => filled($slot['duty_role_id'] ?? null))
            ->mapWithKeys(fn ($slot) => [(int) $slot['duty_role_id'] => (int) $slot['quantity']])
            ->all();
    }

    /** The branch the event belongs to: the form branch for super-admins, else the user's own. */
    private static function formBranchId(mixed $formBranchId): ?int
    {
        $id = self::isSuperAdmin() ? $formBranchId : auth()->user()?->branch_id;

        return blank($id) ? null : (int) $id;
    }

    /** Active roles of the branch, plus roles already on this event's slots (even if now inactive). */
    private static function roleQuery(mixed $formBranchId, ?Event $record): ?Builder
    {
        $branchId = self::formBranchId($formBranchId);

        if ($branchId === null) {
            return null;
        }

        $existing = $record?->exists ? $record->dutySlots()->pluck('duty_role_id')->all() : [];

        return DutyRole::query()
            ->where('branch_id', $branchId)
            ->where(fn (Builder $q) => $q->where('is_active', true)->orWhereIn('id', $existing));
    }

    private static function roleOptions(mixed $formBranchId, ?Event $record): array
    {
        return self::roleQuery($formBranchId, $record)?->ordered()->pluck('name', 'id')->all() ?? [];
    }

    private static function roleRule(mixed $formBranchId, ?Event $record): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) use ($formBranchId, $record): void {
            $ok = self::roleQuery($formBranchId, $record)?->whereKey($value)->exists() ?? false;

            if (! $ok) {
                $fail('Pick an active role from this branch.');
            }
        };
    }

    private static function serviceOptions(mixed $formBranchId): array
    {
        $branchId = self::formBranchId($formBranchId);

        if ($branchId === null) {
            return [];
        }

        return Service::query()->where('branch_id', $branchId)->orderBy('name')->pluck('name', 'id')->all();
    }

    private static function serviceRule(mixed $formBranchId): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) use ($formBranchId): void {
            $branchId = self::formBranchId($formBranchId);

            $ok = $branchId !== null && Service::query()
                ->where('branch_id', $branchId)
                ->whereKey($value)
                ->exists();

            if (! $ok) {
                $fail('Pick a service from this branch.');
            }
        };
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('branch_id')
                ->label('Branch')
                ->options(fn () => Branch::query()->orderBy('name')->pluck('name', 'id'))
                ->required()
                ->searchable()
                ->live()
                ->disabled(fn (string $operation) => $operation === 'edit')
                ->visible(fn () => self::isSuperAdmin()),
            Select::make('service_id')
                ->label('Service (optional)')
                ->options(fn (Get $get) => self::serviceOptions($get('branch_id')))
                ->searchable()
                ->rules([fn (Get $get) => self::serviceRule($get('branch_id'))]),
            TextInput::make('name')->required()->maxLength(255),
            DateTimePicker::make('starts_at')->label('Starts at')->timezone(config('app.timezone'))->seconds(false)->required()->live()
                ->rules([fn (string $operation) => function (string $attribute, mixed $value, Closure $fail) use ($operation): void {
                    // New events must be in the future; edits are guarded by the roster service.
                    if ($operation === 'create' && filled($value) && Carbon::parse($value, config('app.timezone'))->lessThanOrEqualTo(now())) {
                        $fail('Pick a start time in the future.');
                    }
                }]),
            DateTimePicker::make('ends_at')->label('Ends at')->timezone(config('app.timezone'))->seconds(false)->required()->after('starts_at'),
            Textarea::make('notes')->rows(3)->maxLength(2000),
            Repeater::make('dutySlots')
                ->label('Duty slots')
                ->addActionLabel('Add role')
                ->defaultItems(0)
                ->columns(2)
                ->schema([
                    Select::make('duty_role_id')
                        ->label('Role')
                        ->options(fn (Get $get, ?Event $record) => self::roleOptions($get('../../branch_id'), $record))
                        ->required()
                        ->distinct()
                        ->searchable()
                        ->rules([fn (Get $get, ?Event $record) => self::roleRule($get('../../branch_id'), $record)]),
                    TextInput::make('quantity')
                        ->required()
                        ->integer()
                        ->minValue(1)
                        ->maxValue(20)
                        ->default(1),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query
                ->withCount(['assignments as filled_count' => fn ($q) => $q->active()])
                ->withSum('dutySlots as needed_sum', 'quantity'))
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('branch.name')->label('Branch')->visible(fn () => self::isSuperAdmin()),
                TextColumn::make('starts_at')->label('Starts')->dateTime('M j, Y g:i A')->sortable(),
                TextColumn::make('filled')
                    ->label('Filled / needed')
                    ->state(fn (Event $record) => ((int) $record->filled_count).' / '.((int) $record->needed_sum)),
            ])
            ->defaultSort('starts_at')
            ->filters([
                SelectFilter::make('branch_id')
                    ->label('Branch')
                    ->options(fn () => Branch::query()->orderBy('name')->pluck('name', 'id'))
                    ->visible(fn () => self::isSuperAdmin()),
                SelectFilter::make('timing')
                    ->label('When')
                    ->options(['upcoming' => 'Upcoming', 'past' => 'Past'])
                    ->default('upcoming')
                    ->selectablePlaceholder(false)
                    ->query(fn (Builder $query, array $data) => match ($data['value'] ?? null) {
                        'past' => $query->where('events.ends_at', '<', now()),
                        'upcoming' => $query->where('events.ends_at', '>=', now()),
                        default => $query,
                    }),
            ])
            ->recordActions([
                EditAction::make()->iconButton()->tooltip('Edit')->hidden(fn (Event $record) => self::isLocked($record)),
                DeleteAction::make()->iconButton()->modalDescription('This also removes every duty assigned for this event.')->hidden(fn (Event $record) => self::isLocked($record)),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            AssignmentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEvents::route('/'),
            'create' => CreateEvent::route('/create'),
            'edit' => EditEvent::route('/{record}/edit'),
        ];
    }

    /** Attributes the update service accepts, from validated form data. */
    public static function attributesFrom(array $data): array
    {
        return Arr::only($data, ['name', 'starts_at', 'ends_at', 'notes', 'service_id']);
    }
}
