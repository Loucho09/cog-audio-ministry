<?php

namespace App\Filament\Resources\GalleryImages\Pages\Concerns;

use App\Filament\Resources\GalleryImages\GalleryImageResource;
use Livewire\Attributes\Locked;

/**
 * After saving, send the user back to the gallery list exactly as they left it
 * (page, tab, filters, search). Only a same-host gallery index URL is remembered,
 * so a crafted Referer can never become an open redirect.
 */
trait ReturnsToGalleryList
{
    #[Locked]
    public ?string $returnUrl = null;

    public function mountReturnsToGalleryList(): void
    {
        $this->returnUrl = static::safeListUrl(url()->previous());
    }

    protected function listUrl(): string
    {
        return static::safeListUrl($this->returnUrl) ?? GalleryImageResource::getUrl('index');
    }

    protected function getRedirectUrl(): string
    {
        return $this->listUrl();
    }

    /** Returns the URL (with its query string) only if it is this app's gallery index page. */
    protected static function safeListUrl(?string $candidate): ?string
    {
        if (blank($candidate) || strlen($candidate) > 2000 || preg_match('/[^!-\[\]-~]/', $candidate)) {
            return null;
        }

        $index = GalleryImageResource::getUrl('index');
        $want = parse_url($index);
        $got = parse_url($candidate);

        if ($got === false
            || ($got['scheme'] ?? null) !== ($want['scheme'] ?? null)
            || strtolower($got['host'] ?? '') !== strtolower($want['host'] ?? '')
            || ($got['port'] ?? null) !== ($want['port'] ?? null)
            || rtrim($got['path'] ?? '', '/') !== rtrim($want['path'] ?? '', '/')
            || isset($got['user']) || isset($got['pass'])) {
            return null;
        }

        return $index.(isset($got['query']) ? '?'.$got['query'] : '');
    }
}
