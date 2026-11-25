<?php

use App\Filament\Widgets\AccountsAttentionWidget;
use App\Filament\Widgets\BranchOverviewWidget;
use App\Filament\Widgets\BranchStatsWidget;
use App\Filament\Widgets\RecentAccountsWidget;
use App\Filament\Widgets\SiteSetupWidget;

it('renders a static aria-busy skeleton placeholder', function (string $widget) {
    $html = app($widget)->placeholder()->render();

    expect($html)->toContain('aria-busy="true"')->toContain('cog-skel-bar')->toContain('Loading');
})->with([
    BranchStatsWidget::class,
    BranchOverviewWidget::class,
    SiteSetupWidget::class,
    AccountsAttentionWidget::class,
    RecentAccountsWidget::class,
]);
