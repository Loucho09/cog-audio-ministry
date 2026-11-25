<?php

namespace App\Filament\Resources\Events\RelationManagers;

use App\Enums\DutyStatus;
use App\Exceptions\RosterRuleViolation;
use App\Models\DutyAssignment;
use App\Models\DutyRole;
use App\Models\Event;
use App\Models\User;
use App\Services\DutyRosterService;
use Closure;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class AssignmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'assignments';

    protected static ?string $title = 'Assignments';

    /** Also requires the Event policy, so another branch's event never opens this manager. */
    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return parent::canViewForRecord($ownerRecord, $pageClass)
            && (auth()->user()?->can('view', $ownerRecord) ?? false);
    }

    private function event(): Event
    {
        /** @var Event $event */
        $event = $this->getOwnerRecord();

        return $event;
    }

    /** "Sound: 1 of 2, Lights: 0 of 1" using active assignments only. */
    private function slotSummary(): string
    {
        $event = $this->event();
        $filled = $event->assignments()->active()->selectRaw('duty_role_id, count(*) as total')->groupBy('duty_role_id')->pluck('total', 'duty_role_id');

        $parts = $event->dutySlots()->with('dutyRole')->get()
            ->map(fn ($slot) => ($slot->dutyRole?->name ?? 'Role').': '.((int) ($filled[$slot->duty_role_id] ?? 0)).' of '.$slot->quantity);

        return $parts->isEmpty() ? 'This event has no duty slots yet. Add them on the event form.' : $parts->implode(', ');
    }

    private function roleOptions(): array
    {
        return DutyRole::query()
            ->whereIn('id', $this->event()->dutySlots()->pluck('duty_role_id'))
            ->ordered()
            ->pluck('name', 'id')
            ->all();
    }

    private function memberQuery(): Builder
    {
        return User::query()
            ->where('branch_id', $this->event()->branch_id)
            ->whereHas('roles', fn (Builder $q) => $q->whereIn('name', ['member', 'branch-admin']));
    }

    /** Assigning and removing duties is an event update, so the Event policy decides. */
    private function canManage(): bool
    {
        return auth()->user()?->can('update', $this->event()) ?? false;
    }

    private function started(): bool
    {
        return $this->event()->hasStarted();
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading('Assignments')
            ->description(fn () => $this->slotSummary())
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['user', 'dutyRole']))
            ->columns([
                TextColumn::make('displayName')->label('Member'),
                TextColumn::make('dutyRole.name')->label('Role'),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (DutyStatus $state, DutyAssignment $record) => $record->isGuest() && $state === DutyStatus::Accepted
                        ? 'Confirmed'
                        : $state->label())
                    ->color(fn (DutyStatus $state) => match ($state) {
                        DutyStatus::Accepted => 'success',
                        DutyStatus::Assigned => 'warning',
                        DutyStatus::Declined => 'danger',
                    }),
                TextColumn::make('responded_at')->label('Responded')->dateTime('M j, Y g:i A')->placeholder('-'),
            ])
            ->headerActions([
                Action::make('assign')
                    ->label('Assign')
                    ->icon('heroicon-o-user-plus')
                    ->hidden(fn () => $this->started() || ! $this->canManage())
                    ->authorize(fn () => $this->canManage())
                    ->schema([
                        Select::make('duty_role_id')
                            ->label('Role')
                            ->options(fn () => $this->roleOptions())
                            ->required()
                            ->in(fn () => array_keys($this->roleOptions())),
                        ToggleButtons::make('assignee_type')
                            ->label('Assign to')
                            ->options(['member' => 'Member', 'guest' => 'Guest (type a name)'])
                            ->default('member')
                            ->in(['member', 'guest'])
                            ->inline()
                            ->required()
                            ->live(),
                        Select::make('user_id')
                            ->label('Member')
                            ->options(fn () => $this->memberQuery()->orderBy('name')->pluck('name', 'users.id')->all())
                            ->searchable()
                            ->visible(fn (Get $get) => $get('assignee_type') !== 'guest')
                            ->required(fn (Get $get) => $get('assignee_type') !== 'guest')
                            ->rules([fn () => function (string $attribute, mixed $value, Closure $fail): void {
                                if (! $this->memberQuery()->whereKey($value)->exists()) {
                                    $fail('Pick a member from this event\'s branch.');
                                }
                            }]),
                        TextInput::make('guest_name')
                            ->label('Guest name')
                            ->helperText('For someone without an account.')
                            ->minLength(2)
                            ->maxLength(100)
                            ->visible(fn (Get $get) => $get('assignee_type') === 'guest')
                            ->required(fn (Get $get) => $get('assignee_type') === 'guest'),
                    ])
                    ->action(function (array $data): void {
                        $guest = ($data['assignee_type'] ?? 'member') === 'guest';

                        try {
                            $roster = app(DutyRosterService::class);
                            $role = DutyRole::findOrFail($data['duty_role_id']);

                            if ($guest) {
                                $roster->assignGuest($this->event(), $role, (string) ($data['guest_name'] ?? ''), auth()->user());
                            } else {
                                $roster->assign($this->event(), $role, User::findOrFail($data['user_id']), auth()->user());
                            }
                        } catch (RosterRuleViolation $e) {
                            Notification::make()->danger()->title($e->getMessage())->send();

                            return;
                        }

                        Notification::make()->success()->title($guest ? 'Guest assigned' : 'Member assigned')->send();
                    }),
            ])
            ->recordActions([
                Action::make('remove')
                    ->label('Remove')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->hidden(fn (DutyAssignment $record) => $this->started() || $record->status === DutyStatus::Declined || ! $this->canManage())
                    ->authorize(fn () => $this->canManage())
                    ->action(function (DutyAssignment $record): void {
                        try {
                            app(DutyRosterService::class)->remove($record);
                        } catch (RosterRuleViolation $e) {
                            Notification::make()->danger()->title($e->getMessage())->send();

                            return;
                        }

                        Notification::make()->success()->title('Assignment removed')->send();
                    }),
            ]);
    }
}
