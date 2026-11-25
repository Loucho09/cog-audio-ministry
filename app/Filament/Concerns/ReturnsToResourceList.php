<?php

namespace App\Filament\Concerns;

use Livewire\Attributes\Locked;

/**
 * After saving, send the user back to the resource list exactly as they left it
 * (page, tab, filters, search). Only a same-host index URL of this resource is
 * remembered, so a crafted Referer can never become an open redirect.
 */
trait ReturnsToResourceList
{
    #[Locked]
    public ?string $returnUrl = null;

    public function mountReturnsToResourceList(): void
    {
        $this->returnUrl = static::safeListUrl(url()->previous());
    }

    protected function listUrl(): string
    {
        return static::safeListUrl($this->returnUrl) ?? static::getResource()::getUrl('index');
    }

    protected function getRedirectUrl(): string
    {
        return $this->listUrl();
    }

    /** Returns the URL (with its query string) only if it is this resource's index page. */
    protected static function safeListUrl(?string $candidate): ?string
    {
        if (blank($candidate) || strlen($candidate) > 2000 || preg_match('/[^!-\[\]-~]/', $candidate)) {
            return null;
        }

        $index = static::getResource()::getUrl('index');
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
