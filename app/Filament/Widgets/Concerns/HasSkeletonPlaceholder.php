<?php

namespace App\Filament\Widgets\Concerns;

use Filament\Support\Components\Attributes\Unexposed;
use Illuminate\Contracts\View\View;

/** Lazy-load skeleton: static markup only, never touches user data. Set $skeletonVariant: stats | table | checklist. */
trait HasSkeletonPlaceholder
{
    #[Unexposed]
    public function placeholder(): View
    {
        return view('filament.widgets.placeholders.skeleton', [
            'variant' => $this->skeletonVariant ?? 'table',
            'columnSpan' => $this->getColumnSpan(),
            'columnStart' => $this->getColumnStart(),
        ]);
    }
}
