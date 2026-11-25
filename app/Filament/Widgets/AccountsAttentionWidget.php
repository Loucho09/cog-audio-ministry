<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

/** Accounts still on a temporary password, plus (super-admin only) accounts with no branch. */
class AccountsAttentionWidget extends TableWidget
{
    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Accounts needing attention';

    public static function canView(): bool
    {
        return (bool) auth()->user()?->hasAdminAccess();
    }

    public function table(Table $table): Table
    {
        $isSuper = (bool) auth()->user()?->hasRole('super-admin');

        return $table
            ->query(
                UserResource::getEloquentQuery()->where(function (Builder $q) use ($isSuper) {
                    $q->where('must_change_password', true);

                    if ($isSuper) {
                        $q->orWhereNull('branch_id');
                    }
                })->latest()
            )
            ->columns([
                TextColumn::make('name'),
                TextColumn::make('email'),
                TextColumn::make('branch.name')->label('Branch')->placeholder('No branch'),
                TextColumn::make('reason')
                    ->label('Reason')
                    ->state(fn (User $record) => $record->must_change_password ? 'Still on temporary password' : 'No branch')
                    ->badge()
                    ->color(fn (string $state) => $state === 'No branch' ? 'danger' : 'warning'),
                TextColumn::make('created_at')->label('Created')->since(),
            ])
            ->recordUrl(fn (User $record) => auth()->user()?->can('update', $record)
                ? UserResource::getUrl('edit', ['record' => $record])
                : null)
            ->emptyStateHeading('All accounts are set up')
            ->emptyStateDescription('No one is on a temporary password.')
            ->paginated(false);
    }
}
