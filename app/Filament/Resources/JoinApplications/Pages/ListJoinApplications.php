<?php

namespace App\Filament\Resources\JoinApplications\Pages;

use App\Filament\Resources\JoinApplications\JoinApplicationResource;
use App\Models\JoinApplication;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;

class ListJoinApplications extends ListRecords
{
    protected static string $resource = JoinApplicationResource::class;

    /** Sentence case, to match the navigation label. */
    public function getTitle(): string
    {
        return 'Join applications';
    }

    public function getDefaultActiveTab(): string|int|null
    {
        return 'pending';
    }

    /** Quick views. Counts start from the resource query, so they stay branch scoped. */
    public function getTabs(): array
    {
        $status = fn (string $status) => fn (Builder $query) => $query->where('status', $status);

        // One grouped query instead of four counts; still starts from the scoped resource query.
        $counts = JoinApplicationResource::getEloquentQuery()
            ->reorder()
            ->toBase()
            ->selectRaw('status, count(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $count = fn (?string $s = null): int => $s === null
            ? (int) $counts->sum()
            : (int) ($counts[$s] ?? 0);

        $pending = $count(JoinApplication::STATUS_PENDING);

        return [
            'pending' => Tab::make('Pending')
                ->icon(Heroicon::OutlinedClock)
                ->badge($pending ?: null)
                ->badgeColor('warning')
                ->modifyQueryUsing($status(JoinApplication::STATUS_PENDING)),
            'approved' => Tab::make('Approved')
                ->icon(Heroicon::OutlinedCheckCircle)
                ->badge($count(JoinApplication::STATUS_APPROVED))
                ->modifyQueryUsing($status(JoinApplication::STATUS_APPROVED)),
            'rejected' => Tab::make('Rejected')
                ->icon(Heroicon::OutlinedXCircle)
                ->badge($count(JoinApplication::STATUS_REJECTED))
                ->modifyQueryUsing($status(JoinApplication::STATUS_REJECTED)),
            'all' => Tab::make('All')
                ->icon(Heroicon::OutlinedInboxArrowDown)
                ->badge($count()),
        ];
    }
}
