<?php

namespace App\Filament\Resources\DutySwaps;

use App\Enums\SwapStatus;
use App\Exceptions\RosterRuleViolation;
use App\Filament\Resources\DutySwaps\Pages\ListDutySwaps;
use App\Models\Branch;
use App\Models\DutySwap;
use App\Services\DutySwapService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class DutySwapResource extends Resource
{
    protected static ?string $model = DutySwap::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowsRightLeft;

    protected static string|UnitEnum|null $navigationGroup = 'Roster';

    protected static ?string $navigationLabel = 'Swaps';

    protected static ?string $modelLabel = 'swap';

    /** Rows are limited in the query so list and URL access are branch scoped. */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->visibleTo(auth()->user());
    }

    /** Claimed swaps still waiting for a decision. */
    public static function getNavigationBadge(): ?string
    {
        $count = DutySwap::query()
            ->visibleTo(auth()->user())
            ->live()
            ->where('status', SwapStatus::Claimed->value)
            ->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function isSuperAdmin(): bool
    {
        return (bool) auth()->user()?->hasRole('super-admin');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function isLive(DutySwap $swap): bool
    {
        return in_array($swap->status, [SwapStatus::Open, SwapStatus::Claimed], true)
            && ! $swap->assignment->event->hasStarted();
    }

    private static function decide(DutySwap $record, string $method, string $success): void
    {
        try {
            app(DutySwapService::class)->{$method}($record, auth()->user());
        } catch (RosterRuleViolation $e) {
            Notification::make()->danger()->title($e->getMessage())->send();

            return;
        }

        Notification::make()->success()->title($success)->send();
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['assignment.event', 'assignment.dutyRole', 'offeredBy', 'claimedBy', 'branch']))
            ->columns([
                TextColumn::make('event')
                    ->label('Event')
                    ->state(function (DutySwap $record) {
                        $event = $record->assignment->event;

                        return $event->name.' - '.$event->starts_at->timezone(config('app.timezone'))->format('M j, Y g:i A').' ('.$event->block()->label().')';
                    })
                    ->wrap(),
                TextColumn::make('assignment.dutyRole.name')->label('Role'),
                TextColumn::make('offeredBy.name')->label('Offered by'),
                TextColumn::make('claimedBy.name')->label('Claimed by')->placeholder('-'),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (SwapStatus $state) => $state->label())
                    ->color(fn (SwapStatus $state) => match ($state) {
                        SwapStatus::Open => 'info',
                        SwapStatus::Claimed => 'warning',
                        SwapStatus::Approved => 'success',
                        SwapStatus::Rejected => 'danger',
                        SwapStatus::Cancelled => 'gray',
                    }),
                TextColumn::make('claimed_at')->label('Claimed')->dateTime('M j, Y g:i A')->placeholder('-'),
                TextColumn::make('branch.name')->label('Branch')->visible(fn () => self::isSuperAdmin()),
            ])
            ->defaultSort(fn (Builder $query) => $query
                ->orderByRaw('status = ? desc', [SwapStatus::Claimed->value])
                ->orderByDesc('duty_swaps.id'))
            ->filters([
                SelectFilter::make('status')
                    ->options(collect(SwapStatus::cases())->mapWithKeys(fn (SwapStatus $s) => [$s->value => $s->label()])->all())
                    ->default(SwapStatus::Claimed->value),
                SelectFilter::make('branch_id')
                    ->label('Branch')
                    ->options(fn () => Branch::query()->orderBy('name')->pluck('name', 'id'))
                    ->visible(fn () => self::isSuperAdmin()),
            ])
            ->recordActions([
                Action::make('approve')
                    ->label('Approve')
                    ->icon(Heroicon::OutlinedCheck)
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (DutySwap $record) => $record->status === SwapStatus::Claimed
                        && self::isLive($record)
                        && app(DutySwapService::class)->canDecide(auth()->user(), $record))
                    ->action(fn (DutySwap $record) => self::decide($record, 'approve', 'Swap approved')),
                Action::make('reject')
                    ->label('Reject')
                    ->icon(Heroicon::OutlinedXMark)
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (DutySwap $record) => self::isLive($record)
                        && app(DutySwapService::class)->canDecide(auth()->user(), $record))
                    ->action(fn (DutySwap $record) => self::decide($record, 'reject', 'Swap rejected')),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDutySwaps::route('/'),
        ];
    }
}
