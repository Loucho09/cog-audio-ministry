<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Branches\BranchResource;
use App\Filament\Widgets\Concerns\HasSkeletonPlaceholder;
use App\Models\Branch;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

/** Super-admin only: one row per branch with member, head and admin counts. Read-only. */
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
                    ->withCount([
                        'users',
                        'users as admins_count' => fn (Builder $q) => $q->role('branch-admin'),
                    ])
                    ->with(['users' => fn ($q) => $q->role('branch-head')])
                    ->orderByDesc('is_main')
                    ->orderBy('name')
            )
            ->columns([
                TextColumn::make('name')
                    ->label('Branch')
                    ->url(fn (Branch $record) => BranchResource::getUrl('edit', ['record' => $record]))
                    ->description(fn (Branch $record) => $record->is_main ? 'Main branch' : null),
                TextColumn::make('users_count')->label('Accounts'),
                TextColumn::make('heads')
                    ->label('Branch head')
                    ->state(fn (Branch $record) => $record->users->isEmpty() ? 'No head' : $record->users->pluck('name')->implode(', '))
                    ->badge()
                    ->color(fn (Branch $record) => $record->users->isEmpty() ? 'danger' : 'gray'),
                TextColumn::make('admins_count')->label('Branch admins'),
            ])
            ->paginated(false);
    }
}
