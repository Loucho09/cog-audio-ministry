<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\JoinApplications\JoinApplicationResource;
use App\Filament\Widgets\Concerns\HasSkeletonPlaceholder;
use App\Models\JoinApplication;
use Filament\Actions\Action;
use Filament\Support\Enums\FontWeight;
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
            ->heading($count > 0 ? "Waiting for review ({$count})" : 'Waiting for review')
            ->query($this->scoped()->latest()->orderByDesc('id')->limit(5))
            ->columns([
                TextColumn::make('full_name')
                    ->label('Name')
                    ->weight(FontWeight::SemiBold)
                    ->description(fn (JoinApplication $record) => $record->email),
                TextColumn::make('branch.name')
                    ->label('Branch')
                    ->icon(Heroicon::OutlinedBuildingLibrary)
                    ->iconColor('gray')
                    ->visibleFrom('md'),
                TextColumn::make('created_at')
                    ->label('Submitted')
                    ->since()
                    ->dateTimeTooltip('M j, Y g:i A')
                    ->color('gray')
                    ->visibleFrom('md'),
            ])
            ->recordUrl(fn (JoinApplication $record) => JoinApplicationResource::getUrl('view', ['record' => $record], panel: 'admin'))
            ->headerActions([
                Action::make('viewAll')
                    ->label('View all')
                    ->icon(Heroicon::OutlinedArrowRight)
                    ->url(fn () => JoinApplicationResource::getUrl('index', ['tab' => 'pending'], panel: 'admin')),
            ])
            ->emptyStateIcon(Heroicon::OutlinedCheckCircle)
            ->emptyStateHeading('All caught up')
            ->emptyStateDescription('No applications are waiting for review.')
            ->paginated(false);
    }
}
