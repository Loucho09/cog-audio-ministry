<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Branches\BranchResource;
use App\Filament\Widgets\Concerns\HasSkeletonPlaceholder;
use App\Models\Branch;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

/** Super-admin only: one row per branch with member and branch-admin counts. Read-only. */
class BranchOverviewWidget extends TableWidget
{
    use HasSkeletonPlaceholder;

    protected string $skeletonVariant = 'table';

    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Branch overview';

    public static function canView(): bool
    {
        return (bool) auth()->user()?->hasRole('super-admin');
    }

    public function mount(): void
    {
        abort_unless(static::canView(), 403);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Branch::query()
                    ->withCount('users')
                    ->with(['users' => fn ($q) => $q->role('branch-admin')])
                    ->orderByDesc('is_main')
                    ->orderBy('name')
            )
            ->columns([
                TextColumn::make('name')
                    ->label('Branch')
                    ->url(fn (Branch $record) => BranchResource::getUrl('edit', ['record' => $record]))
                    ->description(fn (Branch $record) => $record->is_main ? 'Main branch' : null),
                TextColumn::make('users_count')->label('Accounts'),
                TextColumn::make('admins')
                    ->label('Branch admins')
                    ->state(fn (Branch $record) => $record->users->isEmpty() ? 'No admin' : $record->users->pluck('name')->implode(', '))
                    ->badge()
                    ->color(fn (Branch $record) => $record->users->isEmpty() ? 'danger' : 'gray'),
            ])
            ->paginated(false);
    }
}
