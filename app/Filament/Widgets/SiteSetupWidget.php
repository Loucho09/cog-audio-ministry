<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\HasSkeletonPlaceholder;
use App\Support\SiteSetupChecklist;
use Filament\Widgets\Widget;

/** Super-admin only: checklist of owner-provided content that is still missing. */
class SiteSetupWidget extends Widget
{
    use HasSkeletonPlaceholder;

    protected string $skeletonVariant = 'checklist';

    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    protected string $view = 'filament.widgets.site-setup';

    public static function canView(): bool
    {
        return (bool) auth()->user()?->hasRole('super-admin');
    }

    public function mount(): void
    {
        abort_unless(static::canView(), 403);
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
