<?php

namespace App\Filament\Resources\Services;

use App\Filament\Resources\Services\Pages\CreateService;
use App\Filament\Resources\Services\Pages\EditService;
use App\Filament\Resources\Services\Pages\ListServices;
use App\Models\Branch;
use App\Models\DutyRole;
use App\Models\Service;
use BackedEnum;
use Closure;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\TextSize;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

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
        // An emptied sort order field submits null; keep the column at 0 instead.
        $data['sort_order'] = $data['sort_order'] ?? 0;

        if (! self::isSuperAdmin()) {
            $data['branch_id'] = auth()->user()->branch_id;
        }

        return $data;
    }

    public const DELETE_NOTE = 'Events made from this service are kept but no longer linked to it. Their duty slots stay. The default duty slots set on this service are removed.';

    public static function duplicateSlotNotification(): Notification
    {
        return Notification::make()->danger()->title('A service already runs at this day and time.');
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

    /** The branch slot roles must belong to: the form branch for super-admins, else the user's own. */
    private static function slotBranchId(mixed $formBranchId): ?int
    {
        $id = self::isSuperAdmin() ? $formBranchId : auth()->user()?->branch_id;

        return blank($id) ? null : (int) $id;
    }

    /** The service being edited (inside the repeater, a plain ?Model would be the slot row). */
    private static function pageRecord(mixed $livewire): ?Model
    {
        return $livewire instanceof EditRecord ? $livewire->getRecord() : null;
    }

    /** Active roles of the branch, plus roles already on this service's slots (even if now inactive). */
    private static function roleQuery(mixed $formBranchId, ?Model $record): ?Builder
    {
        $branchId = self::slotBranchId($formBranchId);

        if ($branchId === null) {
            return null;
        }

        $existing = $record?->exists ? $record->dutySlots()->pluck('duty_role_id')->all() : [];

        return DutyRole::query()
            ->where('branch_id', $branchId)
            ->where(fn (Builder $q) => $q->where('is_active', true)->orWhereIn('id', $existing));
    }

    private static function roleOptions(mixed $formBranchId, ?Model $record): array
    {
        return self::roleQuery($formBranchId, $record)?->ordered()->pluck('name', 'id')->all() ?? [];
    }

    /** Server-side check that a chosen role is active and in the service's branch. */
    private static function roleRule(mixed $formBranchId, ?Model $record): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) use ($formBranchId, $record): void {
            $ok = self::roleQuery($formBranchId, $record)?->whereKey($value)->exists() ?? false;

            if (! $ok) {
                $fail('Pick an active role from this branch.');
            }
        };
    }

    /** "Sunday · 9:00 AM" */
    public static function scheduleLabel(Service $record): string
    {
        $day = self::DAYS[(int) $record->day_of_week] ?? '';
        $time = filled($record->start_time) ? Carbon::parse($record->start_time)->format('g:i A') : '';

        return trim($day.' · '.$time, ' ·');
    }

    /** Active, Inactive, Scheduled (starts later) or Ended (end date passed). */
    public static function statusOf(Service $record): string
    {
        $today = now()->startOfDay();

        return match (true) {
            ! $record->is_active => 'Inactive',
            $record->ends_on !== null && $record->ends_on->lt($today) => 'Ended',
            $record->starts_on !== null && $record->starts_on->gt($today) => 'Scheduled',
            default => 'Active',
        };
    }

    public static function statusColor(string $state): string
    {
        return match ($state) {
            'Active' => 'success',
            'Scheduled' => 'info',
            'Ended' => 'warning',
            default => 'gray',
        };
    }

    public static function statusIcon(string $state): Heroicon
    {
        return match ($state) {
            'Active' => Heroicon::OutlinedCheckCircle,
            'Scheduled' => Heroicon::OutlinedCalendarDays,
            'Ended' => Heroicon::OutlinedArchiveBox,
            default => Heroicon::OutlinedPauseCircle,
        };
    }

    public static function dateRangeLabel(Service $record): string
    {
        if ($record->starts_on === null && $record->ends_on === null) {
            return 'No date limits';
        }

        $from = $record->starts_on?->format('M j, Y') ?? 'Anytime';
        $to = $record->ends_on?->format('M j, Y') ?? 'no end date';

        return "{$from} to {$to}";
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Group::make([
                    Section::make('Details')
                        ->description('What the service is called and where it happens.')
                        ->icon(Heroicon::OutlinedRectangleStack)
                        ->columns(2)
                        ->schema([
                            TextInput::make('name')
                                ->required()
                                ->maxLength(255)
                                ->prefixIcon(Heroicon::OutlinedTag),
                            Select::make('branch_id')
                                ->label('Branch')
                                ->options(fn () => Branch::query()->orderBy('name')->pluck('name', 'id'))
                                ->required()
                                ->searchable()
                                ->live()
                                ->prefixIcon(Heroicon::OutlinedBuildingLibrary)
                                ->visible(fn () => self::isSuperAdmin()),
                        ]),
                    Section::make('Schedule')
                        ->description('When it happens each week, and the dates it runs between (both optional).')
                        ->icon(Heroicon::OutlinedCalendarDays)
                        ->columns(2)
                        ->schema([
                            Select::make('day_of_week')
                                ->label('Day')
                                ->options(self::DAYS)
                                ->required()
                                ->live()
                                ->prefixIcon(Heroicon::OutlinedCalendar),
                            TimePicker::make('start_time')
                                ->label('Start time')
                                ->seconds(false)
                                ->required()
                                ->live()
                                ->prefixIcon(Heroicon::OutlinedClock)
                                ->rules([fn (Get $get, ?Model $record) => self::slotRule($get, $record)]),
                            DatePicker::make('starts_on')->label('Starts on')->native(false)->prefixIcon(Heroicon::OutlinedPlay),
                            DatePicker::make('ends_on')
                                ->label('Ends on')
                                ->native(false)
                                ->prefixIcon(Heroicon::OutlinedStop)
                                ->afterOrEqual('starts_on'),
                        ]),
                    Section::make('Default duty slots')
                        ->description('How many people each role needs when events are made from this service.')
                        ->icon(Heroicon::OutlinedUserGroup)
                        ->schema([
                            Repeater::make('dutySlots')
                                ->hiddenLabel()
                                ->relationship('dutySlots')
                                ->addActionLabel('Add role')
                                ->defaultItems(0)
                                ->columns(2)
                                ->schema([
                                    Select::make('duty_role_id')
                                        ->label('Role')
                                        ->options(fn (Get $get, $livewire) => self::roleOptions($get('../../branch_id'), self::pageRecord($livewire)))
                                        ->required()
                                        ->distinct()
                                        ->searchable()
                                        ->rules([fn (Get $get, $livewire) => self::roleRule($get('../../branch_id'), self::pageRecord($livewire))]),
                                    TextInput::make('quantity')
                                        ->required()
                                        ->integer()
                                        ->minValue(1)
                                        ->maxValue(20)
                                        ->default(1),
                                ]),
                        ]),
                    Section::make('Settings')
                        ->description('Inactive services are hidden from the public schedule.')
                        ->icon(Heroicon::OutlinedCog6Tooth)
                        ->columns(2)
                        ->schema([
                            Toggle::make('is_active')->label('Active')->default(true),
                            TextInput::make('sort_order')
                                ->numeric()
                                ->minValue(0)
                                ->default(0)
                                ->prefixIcon(Heroicon::OutlinedArrowsUpDown),
                        ]),
                ])->columnSpan(fn (string $operation) => $operation === 'edit' ? ['default' => 3, 'lg' => 2] : 'full'),
                self::summary(),
            ]);
    }

    /** Read-only side card on the edit page. */
    protected static function summary(): Section
    {
        return Section::make('Summary')
            ->icon(Heroicon::OutlinedRectangleStack)
            ->visible(fn (string $operation) => $operation === 'edit')
            ->columnSpan(['default' => 3, 'lg' => 1])
            ->columns(2)
            ->schema([
                TextEntry::make('summary_name')
                    ->columnSpanFull()
                    ->hiddenLabel()
                    ->weight(FontWeight::Bold)
                    ->size(TextSize::Large)
                    ->state(fn (Service $record) => $record->name),
                TextEntry::make('summary_schedule')
                    ->columnSpanFull()
                    ->hiddenLabel()
                    ->color('gray')
                    ->icon(Heroicon::OutlinedClock)
                    ->state(fn (Service $record) => self::scheduleLabel($record)),
                TextEntry::make('summary_branch')
                    ->label('Branch')
                    ->columnSpanFull()
                    ->icon(Heroicon::OutlinedBuildingLibrary)
                    ->state(fn (Service $record) => $record->branch?->name ?? 'No branch'),
                TextEntry::make('summary_status')
                    ->label('Status')
                    ->columnSpanFull()
                    ->badge()
                    ->state(fn (Service $record) => self::statusOf($record))
                    ->color(fn (string $state) => self::statusColor($state))
                    ->icon(fn (string $state) => self::statusIcon($state)),
                TextEntry::make('summary_dates')
                    ->label('Runs')
                    ->columnSpanFull()
                    ->state(fn (Service $record) => self::dateRangeLabel($record)),
                TextEntry::make('summary_updated')
                    ->label('Last updated')
                    ->state(fn (Service $record) => $record->updated_at)
                    ->since(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            // A real table (not Split/Stack) so every column lines up under its header,
            // whatever actions a row has. Less important columns hide on small screens.
            ->columns([
                TextColumn::make('name')
                    ->label('Name')
                    ->weight(FontWeight::SemiBold)
                    ->searchable()
                    ->sortable(),
                TextColumn::make('schedule')
                    ->label('When')
                    ->color('gray')
                    ->state(fn (Service $record) => self::scheduleLabel($record))
                    ->sortable(['day_of_week', 'start_time']),
                TextColumn::make('branch.name')
                    ->label('Branch')
                    ->icon(Heroicon::OutlinedBuildingLibrary)
                    ->iconColor('gray')
                    ->sortable()
                    ->visibleFrom('md'),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->state(fn (Service $record) => self::statusOf($record))
                    ->color(fn (string $state) => self::statusColor($state))
                    ->icon(fn (string $state) => self::statusIcon($state))
                    ->visibleFrom('sm'),
                TextColumn::make('date_range')
                    ->label('Runs')
                    ->color('gray')
                    ->state(fn (Service $record) => self::dateRangeLabel($record))
                    ->visibleFrom('lg'),
            ])
            ->defaultSort('sort_order')
            ->striped()
            ->searchPlaceholder('Search services')
            ->paginated([10, 25, 50, 100])
            ->emptyStateIcon(Heroicon::OutlinedRectangleStack)
            ->emptyStateHeading('No services yet')
            ->emptyStateDescription('Add your weekly services and they will show in the public schedule.')
            ->filters([
                SelectFilter::make('branch_id')
                    ->label('Branch')
                    ->options(fn () => Branch::query()->orderBy('name')->pluck('name', 'id'))
                    ->visible(fn () => self::isSuperAdmin()),
                SelectFilter::make('day_of_week')
                    ->label('Day')
                    ->options(self::DAYS),
                TernaryFilter::make('is_active')->label('Active'),
            ])
            ->filtersTriggerAction(fn (Action $action) => $action->button()->label('Filters'))
            ->recordActions([
                EditAction::make()->iconButton()->tooltip('Edit'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->modalDescription(self::DELETE_NOTE)
                        ->authorizeIndividualRecords('delete'),
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
