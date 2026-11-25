<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Users\UserResource;
use App\Filament\Widgets\Concerns\HasSkeletonPlaceholder;
use App\Models\User;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

/** The 5 newest accounts in the admin's scope. Read-only. */
class RecentAccountsWidget extends TableWidget
{
    use HasSkeletonPlaceholder;

    protected string $skeletonVariant = 'table';

    protected static ?int $sort = 5;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Recent accounts';

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
        return $table
            ->query(UserResource::getEloquentQuery()->latest()->orderByDesc('id')->limit(5))
            ->columns([
                TextColumn::make('name'),
                TextColumn::make('branch.name')->label('Branch')->placeholder('No branch'),
                TextColumn::make('roles')
                    ->label('Role')
                    ->state(fn (User $record) => $record->roles->pluck('name')->implode(', '))
                    ->badge(),
                TextColumn::make('created_at')->label('Created')->since(),
            ])
            ->paginated(false);
    }
}
