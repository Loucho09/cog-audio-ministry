<?php

use App\Filament\Widgets\AccountsAttentionWidget;
use App\Filament\Widgets\BranchOverviewWidget;
use App\Filament\Widgets\BranchStatsWidget;
use App\Filament\Widgets\RecentAccountsWidget;
use App\Filament\Widgets\SiteSetupWidget;

it('renders a static aria-busy skeleton placeholder', function (string $widget) {
    $html = app($widget)->placeholder()->render();

    expect($html)->toContain('aria-busy="true"')->toContain('cog-skel-bar')->toContain('Loading');

    // Livewire attaches the lazy-load trigger to the first root element. If that is not the single
    // wrapper <div> (e.g. a <style> sibling), the widget never loads and stays a skeleton forever.
    $dom = new DOMDocument;
    @$dom->loadHTML('<?xml encoding="utf-8"?><body>'.$html.'</body>');
    $roots = array_values(array_filter(
        iterator_to_array($dom->getElementsByTagName('body')->item(0)->childNodes),
        fn ($node) => $node instanceof DOMElement,
    ));

    expect($roots)->toHaveCount(1)
        ->and($roots[0]->tagName)->toBe('div');
})->with([
    BranchStatsWidget::class,
    BranchOverviewWidget::class,
    SiteSetupWidget::class,
    AccountsAttentionWidget::class,
    RecentAccountsWidget::class,
]);
