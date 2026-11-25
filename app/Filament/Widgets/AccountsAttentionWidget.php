<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Users\UserResource;
use App\Filament\Widgets\Concerns\HasSkeletonPlaceholder;
use App\Models\User;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

/** Accounts still on a temporary password, plus (super-admin only) accounts with no branch. */
class AccountsAttentionWidget extends TableWidget
{
    use HasSkeletonPlaceholder;

    protected string $skeletonVariant = 'table';

    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Accounts needing attention';

    public static function canView(): bool
    {
        return (bool) auth()->user()?->hasAdminAccess();
    }

    public function mount(): void
    {
        abort_unless(static::canView(), 403);
    }

    public function table(Table $table): Table
    {
        $isSuper = (bool) auth()->user()?->hasRole('super-admin');

        return $table
            ->query(UserResource::getEloquentQuery()->needsAttention($isSuper)->latest())
            ->columns([
                TextColumn::make('name'),
                TextColumn::make('email'),
                TextColumn::make('branch.name')->label('Branch')->placeholder('No branch'),
                TextColumn::make('reason')
                    ->label('Reason')
                    ->state(fn (User $record) => $record->attentionReason())
                    ->badge()
                    ->color(fn (string $state) => $state === 'No branch' ? 'danger' : 'warning'),
                TextColumn::make('created_at')->label('Created')->since(),
            ])
            ->recordUrl(fn (User $record) => auth()->user()?->can('update', $record)
                ? UserResource::getUrl('edit', ['record' => $record])
                : null)
            ->emptyStateHeading('No accounts need attention.')
            ->paginated(false);
    }
}
