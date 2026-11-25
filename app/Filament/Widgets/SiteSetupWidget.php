<?php

namespace App\Filament\Widgets;

use App\Support\SiteSetupChecklist;
use Filament\Widgets\Widget;

/** Super-admin only: checklist of owner-provided content that is still missing. */
class SiteSetupWidget extends Widget
{
    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    protected string $view = 'filament.widgets.site-setup';

    public static function canView(): bool
    {
        return (bool) auth()->user()?->hasRole('super-admin');
    }

    protected function getViewData(): array
    {
        $items = SiteSetupChecklist::items();

        return [
            'items' => $items,
            'remaining' => count(array_filter($items, fn ($i) => ! $i['done'])),
        ];
    }
}
