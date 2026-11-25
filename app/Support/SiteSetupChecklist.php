<?php

namespace App\Support;

use App\Filament\Resources\Branches\BranchResource;
use App\Models\Branch;
use App\Models\GalleryImage;
use App\Models\TeamMember;

/** Builds the owner's "what is still missing" list from config, files and the database. */
class SiteSetupChecklist
{
    /** @return array<int, array{label: string, done: bool, hint: string, url?: ?string}> */
    public static function items(): array
    {
        $noAddress = Branch::query()
            ->where(fn ($q) => $q->whereNull('address')->orWhere('address', ''))
            ->orderBy('name')
            ->pluck('name');

        return [
            [
                'label' => 'Logo file',
                'done' => file_exists(public_path('images/logo.png')),
                'hint' => 'Add the official logo at public/images/logo.png.',
            ],
            [
                'label' => 'Church address',
                'done' => filled(config('ministry.address')),
                'hint' => 'Set MINISTRY_ADDRESS in .env (shows the map).',
            ],
            [
                'label' => 'Messenger link',
                'done' => filled(config('ministry.messenger_url')),
                'hint' => 'Set MINISTRY_MESSENGER_URL in .env (https only).',
            ],
            [
                'label' => 'Years serving',
                'done' => filled(config('ministry.years_serving')),
                'hint' => 'Set MINISTRY_YEARS_SERVING in .env.',
            ],
            [
                'label' => 'Branch addresses',
                'done' => $noAddress->isEmpty(),
                'hint' => $noAddress->isEmpty()
                    ? 'All branches have an address.'
                    : 'Missing for: '.$noAddress->implode(', ').'. Edit under Branches.',
                'url' => $noAddress->isEmpty() ? null : BranchResource::getUrl('index'),
            ],
            [
                'label' => 'Published gallery photo',
                'done' => GalleryImage::query()->where('is_published', true)->exists(),
                'hint' => 'Publish at least one photo under Gallery images.',
            ],
            [
                'label' => 'Featured team member',
                'done' => TeamMember::query()->where('is_featured', true)->where('is_published', true)->exists(),
                'hint' => 'Mark a published team member as featured under Team members.',
            ],
        ];
    }
}
