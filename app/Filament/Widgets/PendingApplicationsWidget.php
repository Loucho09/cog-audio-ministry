<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\JoinApplications\JoinApplicationResource;
use App\Filament\Widgets\Concerns\HasSkeletonPlaceholder;
use App\Models\JoinApplication;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

/** The 5 newest pending join applications in the admin's scope. Read-only; review happens on the view page. */
class PendingApplicationsWidget extends TableWidget
{
    use HasSkeletonPlaceholder;

    protected string $skeletonVariant = 'table';

    protected static ?int $sort = 0;

    protected int|string|array $columnSpan = 'full';

    /** Policy check (Gate::before lets super-admin through); scoping is enforced in the query below, not here. */
    public static function canView(): bool
    {
        return auth()->user()?->can('viewAny', JoinApplication::class) ?? false;
    }

    /** One source of truth: the resource query (visibleTo + branch eager load), so widget and list cannot drift. */
    private function scoped(): Builder
    {
        return JoinApplicationResource::getEloquentQuery()->pending();
    }

    public function table(Table $table): Table
    {
        $count = $this->scoped()->count();

        return $table
            ->heading($count > 0 ? "Pending applications ({$count})" : 'Pending applications')
            ->query($this->scoped()->latest()->orderByDesc('id')->limit(5))
            ->columns([
                TextColumn::make('full_name')->label('Name'),
                TextColumn::make('branch.name')->label('Branch'),
                TextColumn::make('created_at')->label('Submitted')->since(),
            ])
            ->recordUrl(fn (JoinApplication $record) => JoinApplicationResource::getUrl('view', ['record' => $record], panel: 'admin'))
            ->headerActions([
                Action::make('viewAll')
                    ->label('View all')
                    ->icon(Heroicon::OutlinedArrowRight)
                    ->url(fn () => JoinApplicationResource::getUrl('index', panel: 'admin')),
            ])
            ->emptyStateIcon(Heroicon::OutlinedInboxArrowDown)
            ->emptyStateHeading('No pending applications')
            ->emptyStateDescription('New join applications will show up here.')
            ->paginated(false);
    }
}
