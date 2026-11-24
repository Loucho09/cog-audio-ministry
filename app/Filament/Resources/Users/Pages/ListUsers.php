<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->icon(Heroicon::OutlinedUserPlus),
        ];
    }

    /** Quick views. Counts start from the resource query, so they stay branch scoped. */
    public function getTabs(): array
    {
        $admins = fn (Builder $query) => $query->whereHas('roles', fn (Builder $r) => $r->whereIn('name', ['super-admin', 'branch-head', 'branch-admin']));
        $members = fn (Builder $query) => $query->whereHas('roles', fn (Builder $r) => $r->where('name', 'member'));
        $attention = fn (Builder $query) => $query->where(fn (Builder $w) => $w->where('must_change_password', true)->orWhereNull('email_verified_at'));

        $count = function (?callable $scope = null): int {
            $query = UserResource::getEloquentQuery();

            if ($scope) {
                $scope($query);
            }

            return $query->count();
        };

        $needsAttention = $count($attention);

        return [
            'all' => Tab::make('All')
                ->icon(Heroicon::OutlinedUsers)
                ->badge($count()),
            'admins' => Tab::make('Admins')
                ->icon(Heroicon::OutlinedShieldCheck)
                ->badge($count($admins))
                ->modifyQueryUsing($admins),
            'members' => Tab::make('Members')
                ->icon(Heroicon::OutlinedUser)
                ->badge($count($members))
                ->modifyQueryUsing($members),
            'attention' => Tab::make('Needs attention')
                ->icon(Heroicon::OutlinedExclamationTriangle)
                ->badge($needsAttention ?: null)
                ->badgeColor('warning')
                ->modifyQueryUsing($attention),
        ];
    }
}
