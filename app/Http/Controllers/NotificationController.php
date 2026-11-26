<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function index(Request $request): View
    {
        return view('notifications.index', [
            'notifications' => $request->user()->notifications()->paginate(20),
        ]);
    }

    public function read(Request $request, string $id): RedirectResponse
    {
        $notification = $request->user()->notifications()->findOrFail($id);
        $notification->markAsRead();

        $url = $notification->data['url'] ?? null;

        return redirect()->to($this->safeUrl($url) ?? route('dashboard'));
    }

    public function readAll(Request $request): RedirectResponse
    {
        $request->user()->unreadNotifications()->update(['read_at' => now()]);

        return back();
    }

    /** Only same-app URLs: a relative path (not protocol-relative) or one under APP_URL. */
    private function safeUrl(mixed $url): ?string
    {
        if (! is_string($url) || $url === '' || str_contains($url, '\\')) {
            return null;
        }

        if (Str::startsWith($url, '/') && ! Str::startsWith($url, '//')) {
            return $url;
        }

        $base = rtrim((string) config('app.url'), '/');

        if ($base !== '' && (Str::startsWith($url, $base.'/') || $url === $base)) {
            return $url;
        }

        return null;
    }
}
