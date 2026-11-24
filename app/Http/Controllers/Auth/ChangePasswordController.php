<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ChangePasswordRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ChangePasswordController extends Controller
{
    public function create(Request $request): View|RedirectResponse
    {
        if (! $request->user()->must_change_password) {
            return redirect()->route('profile.edit');
        }

        return view('auth.change-password');
    }

    public function store(ChangePasswordRequest $request): RedirectResponse
    {
        $password = $request->validated('password');

        $request->user()->forceFill([
            'password' => $password,
            'must_change_password' => false,
            'remember_token' => Str::random(60),
        ])->save();

        // Signs out every other device; also refreshes this session's password hash.
        Auth::logoutOtherDevices($password);

        $request->session()->regenerate();

        return redirect()->route('dashboard')->with('status', 'Your password has been changed.');
    }
}
